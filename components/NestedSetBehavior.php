<?php

namespace superlyons\mptt\components;

use Yii;
use yii\base\Behavior;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\Query;
use yii\db\ActiveRecord;
/*
查询:
	descendants : 子孙节点(可指定深度)
	children : 子节点
	ancestors : 祖先节点(可指定深度)
	roots : 根节点
	parent : 祖先节点
	prev : 前一个兄弟节点
	next : 下一个兄弟节点
	
持久化:
	save|saveNode
	delete|deleteNode

添加子操作:
	owner.prependTo(target): owner附加到target节点下并作为第一个节点
		owner.addNode(target, target.lft+1, 1, ......)
	owner.prepend(target): target附加到owner节点下并作为第一个节点
		target.addNode(owner, owner.left+1, 1, ......)
	owner.appendTo(target): owner附加到target节点下并作为最后一个节点
		owner.addNode(target, target->rgt, 1, ......)
	owner.append(target): target附加到owner节点下并作为最后一个节点
		target.addNode(owner, owner->rgt, 1, ......)
	
添加兄弟操作:
	owner.insertBefore(target): owner添加到target之前,兄弟节点
		owner.addNode(target, target->lft, 0, ......)
	owner.insertAfter(target): owner添加到target之后,兄弟节点
		owner.addNode(target, target->rgt+1, 0, ......)

兄弟间移动操作:
	owner.moveBefore(target): owner移动到target之前,兄弟节点
		owner.moveNode(target, target.lft, 0)
	owner.moveAfter(target): owner移动到target之后, 兄弟节点
		owner.moveNode(target, target.rgt+1, 0)
	owner.moveAsFirst(target): 移动owner作为target的第一个子节点
		owner.moveNode(target, target.lft+1, 1)
	owner.moveAsLast(target): 移动owner作为target的最后一个子节点
		owner.moveNode(target, target.rgt, 1)
	owner.moveAsRoot(): owner作为一个新的根节点

节点判断:
	owner.isDescendantOf(subj): owner是否是subj的子孙节点
	owner.isLeaf: owner是否是叶子节点
	owner.isRoot: owner是否是根节点
	owner.getIsDeletedRecord: owner是否已被删除
	owner.setIsDeletedRecord(bool): 标识owner是否已被删除
	
事件处理: self::$_cached变量操作
	owner.afterConstruct: EVENT_INIT事件处理函数, AR构造之后执行, 将owner存入$_cached中, 
		$_cached[get_class(owner)][this->_id = self::_c++] = owner
	afterFind: EVENT_AFTER_FIND, BaseActiveRecord::populateRecord()后应调用该方法
	beforeSave: EVENT_BEFORE_INSERT, EVENT_BEFORE_UPDATE
	beforeDelete: EVENT_BEFORE_DELETE
	$_cached[ARClass][inx]=owner
	
私有函数:
	owner.shiftLeftRight(key,delta): 变化左右值
	owner.addNode(target, key, levelUp, runValidation, attributes), 添加节点
		owner必须是新节点
		levelUp: 1-附加子, 0-添加兄弟
		target: owner添加到target
		key: owner.lft值
		
	owner.makeRoot
	owner.moveNode(target, key, levelUp), 移动节点
		levelUp: 0-兄弟间移动, 1-子节点兄弟间移动
	owner.correctCachedOnDelete 
	owner.correctCachedOnAddNode
	owner.correctCachedOnMoveNode
	owner.correctCachedOnMoveBetweenTrees

*/

class NestedSetBehavior extends Behavior
{
	public $hasManyRoots=false;
	public $rootAttribute='root';
	public $leftAttribute='lft';
	public $rightAttribute='rgt';
	public $levelAttribute='level';
	public $parentAttribute="parent";
	public $idAttribute="id";
	
	private $_ignoreEvent = false;
	private $_deleted = false;
	private $_id;
	
	private static $_cached;
	private static $_c = 0;
	
	public function events()
	{
		return [
			ActiveRecord::EVENT_INIT => 'afterConstruct',
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
		];
	}	

	/**
	 * Named scope. Gets descendants for node.
	 返回当前节点所有子节点, $depth:指定子节点的深度
	 公式: 
		node.lft > owner.lft and node.rgt < owner.rgt; 
		depth >= owner->depth + $depth;
	 * @param int $depth the depth.
	 * @return CActiveRecord the owner.
	 */
	public function descendants($depth = null)
	{
		/** @var \yii\db\ActiveRecord */
		$owner = $this->owner;
		$query = $owner->find()
			->andWhere("[[$this->leftAttribute]] > :left", [':left' => $owner->{$this->leftAttribute}])
			->andWhere("[[$this->rightAttribute]] < :right", [':right' => $owner->{$this->rightAttribute}])
			->orderBy([$this->leftAttribute => SORT_ASC]);
		
		if($depth !== null){
			$query->andWhere($this->levelAttribute . '<= :depth', [':depth' => $owner->{$this->levelAttribute} + $depth]);
		}

		if($this->hasManyRoots){
			$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
		}
		return $query;
	}

	/**
	 * Named scope. Gets children for node (direct descendants only).
	 返回当前节点的孩子
	 * @return CActiveRecord the owner.
	 */
	public function children()
	{
		return $this->descendants(1);
	}

	/**
	 * Named scope. Gets ancestors for node.
	 返回当前节点的所有祖先节点, 由浅入深. $depth:指定祖先节点的深度
		公式:
			node.lft < owner.lft and node.rgt > owner.rgt; 
			depth >= owner->depth - $depth;
	 * @param int $depth the depth.
	 * @return CActiveRecord the owner.
	 */
	public function ancestors($depth=null)
	{
		$query = $this->owner->find()
			->andWhere("[[$this->leftAttribute]] < :left", [':left' => $this->owner->{$this->leftAttribute}])
			->andWhere("[[$this->rightAttribute]] > :right", [':right' => $this->owner->{$this->rightAttribute}])
			->orderBy([$this->leftAttribute => SORT_ASC]);

		if($depth !== null){
			$query->andWhere($this->levelAttribute . '>= :depth', [':depth' => $this->owner->{$this->levelAttribute} - $depth]);
		}

		if($this->hasManyRoots){
			$query->andWhere("$this->rootAttribute = :root", [':root' => $this->owner->{$this->rootAttribute}]);
		}
		return $query;
	}

	/**
	 * Named scope. Gets root node(s).
	 返回所有根节点
	 * @return CActiveRecord the owner.
	 */
	public function roots()
	{
		return $query = $this->owner->find()->andWhere("[[$this->leftAttribute]] = 1");
	}

	/**
	 * Named scope. Gets parent of node.
	 返回当前节点的所有祖先节点, 由深入浅.
	 * @return CActiveRecord the owner.
	 */
	public function parent()
	{
		$owner = $this->owner;
		$query = $owner->find()
			->andWhere("[[$this->leftAttribute]] < :left", [':left' => $owner->{$this->leftAttribute}])
			->andWhere("[[$this->rightAttribute]] > :right", [':right' => $owner->{$this->rightAttribute}])
			->orderBy([$this->rightAttribute => SORT_ASC]);

		if($this->hasManyRoots){
			$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
		}
		return $query;
	}

	/**
	 * Named scope. Gets previous sibling of node.
	 返回当前节点的前一个兄弟节点. node.rgt = owner.lft-1
	 * @return CActiveRecord the owner.
	 */
	public function prev()
	{
		$owner = $this->owner;
		$query = $owner->find()
			->andWhere("[[$this->rightAttribute]] = :right", [':right' => $owner->{$this->leftAttribute} - 1]);

		if($this->hasManyRoots){
			$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
		}
		return $query;
	}

	/**
	 * Named scope. Gets next sibling of node.
	 返回当前节点的后一个兄弟节点.node.lft = owner.rgt+1
	 * @return CActiveRecord the owner.
	 */
	public function next()
	{
		$owner = $this->owner;
		$query = $owner->find()
			->andWhere("[[$this->leftAttribute]] = :left", [':left' => $owner->{$this->rightAttribute} + 1]);

		if($this->hasManyRoots){
			$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
		}
		return $query;
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new.
	 保存当前节点, 如果是新节点则创建一个根节点(makeRoot()), 否则更新当前节点
	 * @param boolean $runValidation whether to perform validation.
	 * @param boolean $attributes list of attributes.
	 * @return boolean whether the saving succeeds.
	 注意此处的save不是覆盖ActiveRecord的save()方法, 也覆盖不了, 使用saveNode()
	 */
	public function save($runValidation = true,$attributes = null)
	{
		$owner = $this->owner;

		if($runValidation && !$owner->validate($attributes)){
			return false;
		}

		if($owner->getIsNewRecord()){
			return $this->makeRoot($attributes);
		}

		$this->_ignoreEvent = true;
		$result = $owner->update();
		$this->_ignoreEvent = false;

		return $result;
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new.
	 * @param boolean $runValidation whether to perform validation.
	 * @param boolean $attributes list of attributes.
	 * @return boolean whether the saving succeeds.
	 */
	public function saveNode($runValidation = true, $attributes = null)
	{
		return $this->save($runValidation, $attributes);
	}

	/**
	 * Deletes node and it's descendants.
		删除当前节点以及其所有子节点. 叶子节点直接删除. 最后更新缓存
			公式-删除节点筛选: node.lft >= owner->lft and node.rgt <= owner->rgt
	 * @return boolean whether the deletion is successful.
	 */
	public function delete()
	{
		$owner = $this->owner;

		if($owner->getIsNewRecord()){
			throw new Exception('The node cannot be deleted because it is new.');
		}

		if($this->getIsDeletedRecord()){
			throw new Exception('The node cannot be deleted because it is already deleted.');
		}

		$db = $owner->getDb();
		$extTransFlag = $db->getTransaction();

		if($extTransFlag === null){
			$transaction = $db->beginTransaction();
		}

		try{
			//叶子节点
			if($owner->isLeaf()){ //this->isLeaf();
				$this->_ignoreEvent = true;
				$result = $owner->delete();
				$this->_ignoreEvent = false;
			}else{ //非叶子节点
				$query = new Query();
				$query->andWhere("[[$this->leftAttribute]] >= :left", [':left' => $owner->{$this->leftAttribute}])
					->andWhere("[[$this->rightAttribute]] <= :right", [':right' => $owner->{$this->rightAttribute}]);

				if($this->hasManyRoots){
					$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
				}
				$result = $owner->deleteAll($query->where, $query->params) > 0;
			}

			if(!$result){
				if($extTransFlag === null){
					$transaction->rollback();
				}
				return false;
			}
			$this->shiftLeftRight($owner->{$this->rightAttribute} + 1, $owner->{$this->leftAttribute} - $owner->{$this->rightAttribute} - 1);

			if($extTransFlag===null){
				$transaction->commit();
			}
			$this->correctCachedOnDelete();
		}catch(Exception $e){
			if($extTransFlag === null){
				$transaction->rollback();
			}
			throw $e;
		}
		return true;
	}

	/**
	 * Deletes node and it's descendants.
	 * @return boolean whether the deletion is successful.
	 */
	public function deleteNode()
	{
		return $this->delete();
	}

	/**
	 * Prepends node to target as first child.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the prepending succeeds.
	 将owner节点附加到target节点之下并作为第一个节点, owner.lft=target.lft+1, 后续节点的左右值+2
	 */
	public function prependTo($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode($target, $target->{$this->leftAttribute} + 1, 1, $runValidation, $attributes);
	}

	/**
	 * Prepends target to node as first child.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the prepending succeeds.
	 将target节点附加到owner节点之下并作为第一个节点, target.lft=owner.lft+1, 后续节点的左右值+2
	 */
	public function prepend($target, $runValidation = true, $attributes = null)
	{
		return $target->prependTo($this->owner, $runValidation, $attributes);
	}

	/**
	 * Appends node to target as last child.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the appending succeeds.
	 将owner节点附加到target节点之下并作为最后一个节点, owner.lft=target.rgt, 后续节点左右值+2
	 */
	public function appendTo($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode($target, $target->{$this->rightAttribute}, 1, $runValidation, $attributes);
	}

	/**
	 * Appends target to node as last child.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the appending succeeds.
	 将target节点附加到owner节点之下并作为最后一个节点, target.lft=owner.rgt, 后续节点左右值+2
	 */
	public function append($target,$runValidation = true,$attributes = null)
	{
		return $target->appendTo($this->owner, $runValidation, $attributes);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the inserting succeeds.
	 将owner节点添加到target节点之前,即作为兄弟节点, owner.lft=target.lft, 后续节点左右值+2
	 */
	public function insertBefore($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode($target, $target->{$this->leftAttribute}, 0, $runValidation, $attributes);
	}

	/**
	 * Inserts node as next sibling of target.
	 * @param CActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the inserting succeeds.
	 将owner节点添加到target节点之后,即作为兄弟节点, owner.lft=target.lft, 后续节点左右值+2
	 */
	public function insertAfter($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode($target, $target->{$this->rightAttribute} + 1, 0, $runValidation, $attributes);
	}

	/**
	 * Move node as previous sibling of target.
	 * @param CActiveRecord $target the target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveBefore($target)
	{
		return $this->moveNode($target, $target->{$this->leftAttribute}, 0);
	}

	/**
	 * Move node as next sibling of target.
	 * @param CActiveRecord $target the target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAfter($target)
	{
		return $this->moveNode($target, $target->{$this->rightAttribute} + 1, 0);
	}

	/**
	 * Move node as first child of target.
	 * @param CActiveRecord $target the target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAsFirst($target)
	{
		return $this->moveNode($target, $target->{$this->leftAttribute} + 1, 1);
	}

	/**
	 * Move node as last child of target.
	 * @param CActiveRecord $target the target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAsLast($target)
	{
		return $this->moveNode($target, $target->{$this->rightAttribute}, 1);
	}

	/**
	 * Move node as new root.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAsRoot()
	{
		$owner = $this->owner;

		if(!$this->hasManyRoots){
			throw new Exception('Many roots mode is off.');
		}

		if($owner->getIsNewRecord()){
			throw new Exception('The node should not be new record.');
		}

		if($this->getIsDeletedRecord()){
			throw new Exception('The node should not be deleted.');
		}

		if($owner->isRoot()){
			throw new Exception('The node already is root node.');
		}

		$db = $owner->getDb();
		$extTransFlag = $db->getTransaction();

		if($extTransFlag === null){
			$transaction = $db->beginTransaction();
		}

		try{
			$left = $owner->{$this->leftAttribute};
			$right = $owner->{$this->rightAttribute};
			$levelDelta = 1 - $owner->{$this->levelAttribute};
			$delta = 1 - $left;

			$owner->updateAll(
				array(
					$this->leftAttribute => new Expression($db->quoteColumnName($this->leftAttribute) . sprintf('%+d', $delta)),
					$this->rightAttribute => new Expression($db->quoteColumnName($this->rightAttribute) . sprintf('%+d', $delta)),
					$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute) . sprintf('%+d', $levelDelta)),
					$this->rootAttribute => $owner->getPrimaryKey(),
				),
				[
					"and",
					"[[$this->leftAttribute]] >= :left",
					"[[$this->rightAttribute]] <= :right",
					"[[$this->rootAttribute]] = :root"
				],
				[
					':left' => $left,
					':right' => $right,
					':root' => $owner->{$this->rootAttribute}
				]
			);
			/*
			update set lft=lft+($delta), rgt=rgt+($delta), level=level+($levelDelta), root=owner->getPrimaryKey() WHERE
				lft >= $left AND rgt <= $right, root=owner->root
			*/
			$this->shiftLeftRight($right + 1, $left - $right - 1);

			$owner->updateAll(
				[$this->parentAttribute => 0 ],
				[ $this->idAttribute => $owner->{$this->idAttribute} ]
			);

			if($extTransFlag === null){
				$transaction->commit();
			}
			$this->correctCachedOnMoveBetweenTrees(1, $levelDelta, $owner->getPrimaryKey(), 0);
		}catch(Exception $e){
			if($extTransFlag === null){
				$transaction->rollback();
			}
			throw $e;
		}
		return true;
	}

	/**
	 * Determines if node is descendant of subject node.
	 * @param CActiveRecord $subj the subject node.
	 * @return boolean whether the node is descendant of subject node.
	 owner是否是subj的子孙节点
	 */
	public function isDescendantOf($subj)
	{
		$owner = $this->owner;
		$result = ($owner->{$this->leftAttribute} > $subj->{$this->leftAttribute})
		&& ($owner->{$this->rightAttribute} < $subj->{$this->rightAttribute});

		if($this->hasManyRoots){
			$result = $result && ($owner->{$this->rootAttribute} === $subj->{$this->rootAttribute});
		}
		return $result;
	}

	/**
	 * Determines if node is leaf.
	 * @return boolean whether the node is leaf.
	 */
	public function isLeaf()
	{
		$owner = $this->owner;
		return $owner->{$this->rightAttribute} - $owner->{$this->leftAttribute} === 1;
	}

	/**
	 * Determines if node is root.
	 * @return boolean whether the node is root.
	 */
	public function isRoot()
	{
		return $this->owner->{$this->leftAttribute} == 1;
	}

	/**
	 * Returns if the current node is deleted.
	 * @return boolean whether the node is deleted.
	 */
	public function getIsDeletedRecord()
	{
		return $this->_deleted;
	}

	/**
	 * Sets if the current node is deleted.
	 * @param boolean $value whether the node is deleted.
	 */
	public function setIsDeletedRecord($value)
	{
		$this->_deleted = $value;
	}

	/**
	 * Handle 'afterConstruct' event of the owner.
	 * @param CEvent $event event parameter.
	 */
	public function afterConstruct($event)
	{
		$owner = $this->owner;
		self::$_cached[get_class($owner)][$this->_id = self::$_c++] = $owner;
	}

	/**
	 * Handle 'afterFind' event of the owner.
	 * @param CEvent $event event parameter.
	 */
	public function afterfind($event)
	{
		$owner = $this->owner;
		self::$_cached[get_class($owner)][$this->_id = self::$_c++] = $owner;
	}

	/**
	 * Handle 'beforeSave' event of the owner.
	 * @param CEvent $event event parameter.
	 * @return boolean.
	 */
	public function beforeSave($event)
	{
		if($this->_ignoreEvent){
			return true;
		}else{
			throw new Exception('You should not use CActiveRecord::save() method when NestedSetBehavior attached.');
		}
	}

	/**
	 * Handle 'beforeDelete' event of the owner.
	 * @param CEvent $event event parameter.
	 * @return boolean.
	 */
	public function beforeDelete($event)
	{
		if($this->_ignoreEvent){
			return true;
		}else{
			throw new Exception('You should not use CActiveRecord::delete() method when NestedSetBehavior attached.');
		}
	}

	/**
	改变左右值, 用于在移动,添加,删除节点后更新后续节点.
	 * @param int $key. 移动,添加节点时key=已添加或移动的节点的左值的起始位置, 删除节点时key=已删除节点的右值+1
	 * @param int $delta. 删除/移动节点的 左值-右值-1(2-11-1=-10)
	 */
	private function shiftLeftRight($key, $delta)
	{
		$owner = $this->owner;
		$db = $owner->getDb();

		foreach(array($this->leftAttribute, $this->rightAttribute) as $attribute){
			$condition = ['and', "[[$attribute]] >= :key"];
			$params = [':key' => $key];

			if($this->hasManyRoots){
				$condition[] = "[[$this->rootAttribute]] = :$this->rootAttribute";
				$params[":$this->rootAttribute"] = $owner->{$this->rootAttribute};
			}
			
			$owner->updateAll(
				[$attribute => new Expression($db->quoteColumnName($attribute) . sprintf('%+d', $delta))],
				$condition,
				$params
			);
			/*
				update lft=lft+($delta) WHERE lft >= $key AND root = owner.root 
				update rgt=rgt+($delta) WHERE rgt >= $key AND root = owner.root
			*/

		}
	}

	/**
	 * @param CActiveRecord $target.
	 * @param int $key. 添加节点的左序值
	 * @param int $levelUp. 向$target附加子节点时=1, 向$target平级插入节点时=0
	 * @param boolean $runValidation.
	 * @param array $attributes.
	 * @return boolean.
	 */
	private function addNode($target, $key, $levelUp, $runValidation, $attributes)
	{
		$owner = $this->owner; //要添加的节点

		if(!$owner->getIsNewRecord()){ //添加节点不是新节点
			throw new Exception('The node cannot be inserted because it is not new.');
		}

		if($this->getIsDeletedRecord()){ //添加节点是否已被删除
			throw new Exception('The node cannot be inserted because it is deleted.');
		}

		if($target->getIsDeletedRecord()){ //目标节点是否是以删除节点
			throw new Exception('The node cannot be inserted because target node is deleted.');
		}

		if($owner->equals($target)){ //添加节点与目标节点时同一节点
			throw new Exception('The target node should not be self.');
		}

		if(!$levelUp && $target->isRoot()){ //平级插入 并且 目标节点是跟节点
			throw new Exception('The target node should not be root.');
		}

		if($runValidation && !$owner->validate()){
			return false;
		}

		if($this->hasManyRoots){
			$owner->{$this->rootAttribute} = $target->{$this->rootAttribute};
		}

		$db = $owner->getDb();
		$extTransFlag = $db->getTransaction();

		if($extTransFlag === null){
			$transaction = $db->beginTransaction();
		}

		if($levelUp === 0){
			$parentid = $target->{$this->parentAttribute};
		}else{
			$parentid = $target->{$this->idAttribute};
		}

		try{
			$this->shiftLeftRight($key, 2);
			$owner->{$this->leftAttribute} = $key;
			$owner->{$this->rightAttribute} = $key + 1;
			$owner->{$this->levelAttribute} = $target->{$this->levelAttribute} + $levelUp;
			$owner->{$this->parentAttribute} = $parentid;
			$this->_ignoreEvent = true;
			$result = $owner->insert($attributes);
			$this->_ignoreEvent = false;

			if(!$result){
				if($extTransFlag === null){
					$transaction->rollback();
				}
				return false;
			}

			if($extTransFlag === null){
				$transaction->commit();
			}
			$this->correctCachedOnAddNode($key);
		}catch(Exception $e){
			if($extTransFlag === null){
				$transaction->rollback();
			}
			throw $e;
		}
		return true;
	}

	/**
	 * @param array $attributes.
	 * @return boolean.
	 */
	private function makeRoot($attributes)
	{
		$owner = $this->owner;
		$owner->{$this->leftAttribute} = 1;
		$owner->{$this->rightAttribute} = 2;
		$owner->{$this->levelAttribute} = 1;
		$owner->{$this->parentAttribute} = 0;

		if($this->hasManyRoots){
			$db = $owner->getDb();
			$extTransFlag = $db->getTransaction();

			if($extTransFlag === null){
				$transaction = $db->beginTransaction();
			}

			try{
				$this->_ignoreEvent = true;
				$result = $owner->insert($attributes);
				$this->_ignoreEvent = false;

				if(!$result){
					if($extTransFlag === null){
						$transaction->rollback();
					}
					return false;
				}
				//$owner->getPrimaryKey() = 返回主键的值
				$pk = $owner->{$this->rootAttribute} = $owner->getPrimaryKey();
				//$owner->primaryKey()[0] = 返回主键名
				$owner->updateAll([$this->rootAttribute => $pk], [$owner->primaryKey()[0] => $pk]);

				if($extTransFlag === null){
					$transaction->commit();
				}
			}catch(Exception $e){
				if($extTransFlag === null){
					$transaction->rollback();
				}
				throw $e;
			}
		}else{
			if($owner->roots()->exists()){
				throw new Exception('Cannot create more than one root in single root mode.');
			}

			$this->_ignoreEvent = true;
			$result = $owner->insert($attributes);
			$this->_ignoreEvent = false;

			if(!$result){
				return false;
			}
		}
		return true;
	}

	/**
	 * @param CActiveRecord $target.
	 * @param int $key. 节点移动到位置后的左值
	 * @param int $levelUp. 
	 * @return boolean.

	 Delta: 对冲值, 集合A中的数A1移动到集合B中的位置B1则这个A1的值=B1的值
	 例如 B={1,2,3,4,5,6,7}, A={18,19,20,21,22}, 如果A20移动到B4的位置, 则20+(4-20)=20-16=4
	 B4插入到A20的位置, 则4+(20-4)=4+16=20
	 B6移动到B3, 6+(3-6)=3, B3移动到B6 3+(6-3)=6
	 */
	private function moveNode($target, $key, $levelUp)
	{
		$owner = $this->owner; //要移动的节点

		if($owner->getIsNewRecord()){ //移动的节点是新节点
			throw new Exception('The node should not be new record.');
		}

		if($this->getIsDeletedRecord()){ //移动的节点是已删除的节点
			throw new Exception('The node should not be deleted.');
		}

		if($target->getIsDeletedRecord()){ //目标节点是已删除的节点
			throw new Exception('The target node should not be deleted.');
		}

		if($owner->equals($target)){ //节点相等
			throw new Exception('The target node should not be self.');
		}

		if($target->isDescendantOf($owner)){ //目标节点是移动的节点的子孙
			throw new Exception('The target node should not be descendant.');
		}

		if(!$levelUp && $target->isRoot()){ //平级移动 并且 目标节点是根节点
			throw new Exception('The target node should not be root.');
		}


		if($levelUp === 0){
			$parentid = $target->{$this->parentAttribute};
		}else{
			$parentid = $target->{$this->idAttribute};
		}

		$db = $owner->getDb();
		$extTransFlag = $db->getTransaction();

		if($extTransFlag === null){
			$transaction = $db->beginTransaction();
		}

		try{
			$left = $owner->{$this->leftAttribute};
			$right = $owner->{$this->rightAttribute};
			//移动节点到目标节点的层级差值.
			$levelDelta = $target->{$this->levelAttribute} - $owner->{$this->levelAttribute} + $levelUp;

			//delta = right - left + 1 ;  在key位置插入节点时, key位置的后续节点应该增加的左右值
			//delta = left - right - 1 ;  在key位置移除节点时, key位置的后续节点应该减少的左右值

			//允许多根节点 并且 移动的节点和目标节点不属于同一根节点
			if($this->hasManyRoots && $owner->{$this->rootAttribute} !== $target->{$this->rootAttribute}){
				//1. 更新目标树: 为将要移动过来的节点腾出空间
				foreach([$this->leftAttribute, $this->rightAttribute] as $attribute){
					$owner->updateAll(
						[$attribute => new Expression($db->quoteColumnName($attribute) . sprintf('%+d', $right - $left + 1))],
						['and', "[[$attribute]] >= :key", $this->rootAttribute . '= :root'],
						[':key' => $key, ':root' => $target->{$this->rootAttribute}]
					);
					/* 
						update TreeTable Set lft=lft + owner.rgt - owner.lft + 1 WHERE lft >= target.lft(平级之前)|target.rgt+1(平级之后)|target.lft+1(子集之首)|target.rgt(子集最后) AND root = target.root
						update TreeTable Set rgt=lft + owner.rgt - owner.lft + 1 WHERE rgt >= target.lft(平级之前)|target.rgt+1(平级之后)|target.lft+1(子集之首)|target.rgt(子集最后) AND root = target.root
					*/
				}

				//2. 更新移动的节点为移动到目标树中的左右值
				//target.lft(平级之前)|target.rgt+1(平级之后)|target.lft+1(子集之首)|target.rgt(子集最后) - owner.lft
				$delta = $key - $left;

				$owner->updateAll(
					[
						$this->leftAttribute => new Expression($db->quoteColumnName($this->leftAttribute).sprintf('%+d',$delta)),
						$this->rightAttribute => new Expression($db->quoteColumnName($this->rightAttribute).sprintf('%+d',$delta)),
						$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute).sprintf('%+d',$levelDelta)),
						$this->rootAttribute => $target->{$this->rootAttribute},
					],
					[
						"and",
						"[[$this->leftAttribute]] >= :left",
						"[[$this->rightAttribute]] <= :right",
						"[[$this->rootAttribute]] = :root"
					],
					[
						':left' => $left,
						':right' => $right,
						':root' => $owner->{$this->rootAttribute}
					]
				);
				/*
				update C SET lft=lft+(delta), rgt=rgt+(delta), level=level+(levelDelta) WHERE
					lft >= owner->lft AND
					rgt <= owner->rgt AND
					root = owner->root 
				*/

				//3. 更新移动的节点所属的树移除移动节点后正确的左右值
				$this->shiftLeftRight($right + 1, $left - $right - 1);

				//4. 更新移动节点的parent值
				$owner->updateAll(
					[ $this->parentAttribute => $parentid ],
					[ $this->idAttribute => $owner->{$this->idAttribute} ]
				);

				if($extTransFlag === null){
					$transaction->commit();
				}
				$this->correctCachedOnMoveBetweenTrees($key, $levelDelta, $target->{$this->rootAttribute}, $parentid);
			}else{ //移动节点 与 目标节点 同属同一根节点 并且 可能允许多根节点

				//1. 更新目标树: 为将要移动过来的节点腾出空间, 因为在同一个树中移动因此移动的节点的左右值也发生了变化
				$delta = $right - $left + 1;
				$this->shiftLeftRight($key, $delta);

				//2. 移动的节点的原始位置 在 移动到位置 的 右侧 则 更新移动节点的左右值
				// 如果在左侧则不需要,因为1步中没有影响到移动节点的左右值
				if($left >= $key){
					$left += $delta;
					$right += $delta;
				}

				//3. 更新移动节点的层级
				$query = new Query();
				$query->andWhere("[[$this->leftAttribute]] >= :left", [':left' => $left])
					->andWhere("[[$this->rightAttribute]] <= :right", [':right' => $right]);
				
				if($this->hasManyRoots){
					$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
				}

				$owner->updateAll(
					[$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute) . sprintf('%+d', $levelDelta))],
					$query->where,
					$query->params
				);
				/*
				update set level=level+($levelDelta) WHERE lft >= $left AND rgt <= $right
				*/

				//4. 更新移动节点的左右值为目标节点的左右值
				foreach([$this->leftAttribute, $this->rightAttribute] as $attribute){
					$query = new Query();
					$query->andWhere("[[$attribute]] >= :left", [':left' => $left])
						->andWhere("[[$attribute]] <= :right", [':right' => $right]);

					if($this->hasManyRoots){
						$query->andWhere("$this->rootAttribute = :root", [':root' => $owner->{$this->rootAttribute}]);
					}
					$owner->updateAll(
						[$attribute => new Expression($db->quoteColumnName($attribute) . sprintf('%+d', $key - $left))],
						$query->where,
						$query->params
					);
					/*
					update SET lft=lft+($key-$left) WHERE lft >= $left AND lft <= $right
					update SET rgt=rgt+($key-$left) WHERE rgt >= $left AND rgt <= $right
					*/
				}

				//5. 更新原移动节点右侧的左右值
				$this->shiftLeftRight($right + 1, -$delta);

				//6. 更新移动节点的parent值
				$owner->updateAll(
					[$this->parentAttribute => $parentid ],
					[ $this->idAttribute => $owner->{$this->idAttribute} ]
				);

				if($extTransFlag === null){
					$transaction->commit();
				}
				$this->correctCachedOnMoveNode($key, $levelDelta, $parentid);
			}
		}catch(Exception $e){
			if($extTransFlag === null){
				$transaction->rollBack();
			}
			throw $e;
		}
		return true;
	}

	/**
	 * Correct cache for {@link NestedSetBehavior::delete()} and {@link NestedSetBehavior::deleteNode()}.
	 */
	private function correctCachedOnDelete()
	{
		$owner = $this->owner;
		$left = $owner->{$this->leftAttribute};
		$right = $owner->{$this->rightAttribute};
		$key = $right + 1;
		$delta = $left - $right - 1;

		foreach(self::$_cached[get_class($owner)] as $node){
			//新节点或已被删除的节点
			if($node->getIsNewRecord() || $node->getIsDeletedRecord()){
				continue;
			}
			//不是同一个树
			if($this->hasManyRoots && $owner->{$this->rootAttribute} !== $node->{$this->rootAttribute}){
				continue;
			}
			//遍历节点.left >= 当前节点.left 并且 遍历节点.right <= 当前节点.right
			//即遍历节点是当前节点或当前节点的子节点
			if($node->{$this->leftAttribute} >= $left && $node->{$this->rightAttribute} <= $right){
				$node->setIsDeletedRecord(true);
			}else{
				if($node->{$this->leftAttribute} >= $key){ //如果遍历节点是当前节点的后续节点, 则代表需要更新
					$node->{$this->leftAttribute} += $delta;
				}
				if($node->{$this->rightAttribute} >= $key){ //如果遍历节点是当前节点的后续节点, 则代表需要更新
					$node->{$this->rightAttribute} += $delta;
				}
			}
		}
	}

	/**
	 * Correct cache for {@link NestedSetBehavior::addNode()}.
	 * @param int $key.
	 */
	private function correctCachedOnAddNode($key)
	{
		$owner = $this->owner;
		foreach(self::$_cached[get_class($owner)] as $node){
			if($node->getIsNewRecord() || $node->getIsDeletedRecord()){
				continue;
			}
			if($this->hasManyRoots && $owner->{$this->rootAttribute} !== $node->{$this->rootAttribute}){
				continue;
			}
			if($owner === $node){
				continue;
			}
			if($node->{$this->leftAttribute} >= $key){
				$node->{$this->leftAttribute} += 2;
			}
			if($node->{$this->rightAttribute} >= $key){
				$node->{$this->rightAttribute} += 2;
			}
		}
	}

	/**
	 * Correct cache for {@link NestedSetBehavior::moveNode()}.
	 * @param int $key.
	 * @param int $levelDelta.
	 */
	private function correctCachedOnMoveNode($key, $levelDelta, $parent)
	{
		$owner = $this->owner;
		$left = $owner->{$this->leftAttribute};
		$right = $owner->{$this->rightAttribute};
		$delta = $right - $left + 1;

		if($left >= $key){
			$left += $delta;
			$right += $delta;
		}

		$delta2 = $key - $left;

		foreach(self::$_cached[get_class($owner)] as $node){
			if($node->getIsNewRecord() || $node->getIsDeletedRecord()){
				continue;
			}
			if($this->hasManyRoots && $owner->{$this->rootAttribute} !== $node->{$this->rootAttribute}){
				continue;
			}

			if($node->{$this->leftAttribute} >= $key){
				$node->{$this->leftAttribute} += $delta;
			}
			if($node->{$this->rightAttribute} >= $key){
				$node->{$this->rightAttribute} += $delta;
			}
			if($node->{$this->leftAttribute} >= $left && $node->{$this->rightAttribute} <= $right){
				$node->{$this->levelAttribute} += $levelDelta;
			}
			if($node->{$this->leftAttribute} >= $left && $node->{$this->leftAttribute} <= $right){
				$node->{$this->leftAttribute} += $delta2;
			}
			if($node->{$this->rightAttribute} >= $left && $node->{$this->rightAttribute} <= $right){
				$node->{$this->rightAttribute} += $delta2;
			}
			if($node->{$this->leftAttribute} >= $right + 1){
				$node->{$this->leftAttribute} -= $delta;
			}
			if($node->{$this->rightAttribute} >= $right + 1){
				$node->{$this->rightAttribute} -= $delta;
			}

			if($node->{$this->idAttribute} == $owner->{$this->idAttribute}){
				$node->{$this->parentAttribute} = $parent;
			}
			
		}
	}

	/**
	 * Correct cache for {@link NestedSetBehavior::moveNode()}.
	 * @param int $key.
	 * @param int $levelDelta.
	 * @param int $root.
	 修正树间移动以缓存的对象的值
	 */
	private function correctCachedOnMoveBetweenTrees($key, $levelDelta, $root, $parent)
	{
		$owner = $this->owner;
		$left = $owner->{$this->leftAttribute};
		$right = $owner->{$this->rightAttribute};
		$delta = $right - $left + 1;
		$delta2 = $key - $left;
		$delta3 = $left - $right - 1;

		foreach(self::$_cached[get_class($owner)] as $node)
		{
			if($node->getIsNewRecord() || $node->getIsDeletedRecord()){
				continue;
			}

			//当前节点属于目标树
			if($node->{$this->rootAttribute} === $root){
				//当前节点在移动节点的右侧
				if($node->{$this->leftAttribute} >= $key){
					$node->{$this->leftAttribute} += $delta;
				}
				if($node->{$this->rightAttribute} >= $key){
					$node->{$this->rightAttribute} += $delta;
				}
			//当前节点属于移动树; moveAsRoot会执行此处
			}else if($node->{$this->rootAttribute} === $owner->{$this->rootAttribute}){
				if($node->{$this->idAttribute} == $owner->{$this->idAttribute}){
					$node->{$this->parentAttribute} = $parent;
				}
				//当前节点属于移动节点或其子节点
				if($node->{$this->leftAttribute} >= $left && $node->{$this->rightAttribute} <= $right){
					$node->{$this->leftAttribute} += $delta2;
					$node->{$this->rightAttribute} += $delta2;
					$node->{$this->levelAttribute} += $levelDelta;
					$node->{$this->rootAttribute} = $root;
				//当前节点不属于移动节点
				}else{
					//当前节点在移动节点的右侧
					if($node->{$this->leftAttribute} >= $right + 1){
						$node->{$this->leftAttribute} += $delta3;
					}
					if($node->{$this->rightAttribute} >= $right + 1){
						$node->{$this->rightAttribute} += $delta3;
					}
				}

			}
		}
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		unset(self::$_cached[get_class($this->owner)][$this->_id]);
	}
}