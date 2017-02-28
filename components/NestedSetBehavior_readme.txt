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
	owner.afterConstruct: EVENT_INIT事件处理函数, AR构造之后执行, 将owner存入$_cached中, $_cached[get_class(owner)][this->_id = self::_c++] = owner
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