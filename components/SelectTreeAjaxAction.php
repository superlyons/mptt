<?php

namespace superlyons\mptt\components;

use Yii;
use yii\base\ViewContextInterface;
use superlyons\mptt\models\MpttNode;
use yii\base\Action;
use yii\helpers\Url;

class SelectTreeAjaxAction extends Action implements ViewContextInterface
{
	public $selid;
	public $dialogid;
	public $textid;
	public $valueid;
	public $inputwrapid;

	public $route="mptt-data/select-tree-ajax";

	public $customView = false;

	public $viewName = "SelectTreeAjax";
	
	public function run(){
		$request = Yii::$app->getRequest();

		$this->selid = $params['selid'] = $request->get("id");
		$this->dialogid = $params['dialogid'] = $request->get("dialogid");
		$this->textid = $params['textid'] = $request->get("textid");
		$this->valueid = $params['valueid'] = $request->get("valueid");
		$this->inputwrapid = $params['inputwrapid'] = $request->get("inputwrapid");

        $node = new MpttNode;
        $roots = $node->roots()->select(['id','name'])->asArray()->all();
        $markroots=[];
        foreach($roots as $key=>$val){
            $markroots[$val['id']] = $val['name'];//.'('.$val['id'].')';
        }

        if(count($roots)>0 && !empty($params['selid'])){
            $node = $node->find()->select(['root'])->andWhere(['id'=>$params['selid']])->asArray()->one();
            $params['rootid'] = !$node ? 0 : $node['root'];
            $nodes = MpttNode::find()->where(['root' => $params['rootid']])->orderBy('lft')->all();
        }else{
            $params['rootid'] = 0;
            $std = new \stdClass();
            $std->id = 0;
            $std->level = 1;
            $std->name = Yii::t("mptt",'As a root node');
            $nodes = [$std];
        }

        return $this->getView()->renderAjax($this->viewName, ['p'=>$params, 'nodes'=>$nodes, 'roots'=>$markroots], $this);
	}

	public function getRoute($route=false, $params=[]){
		$route = $route === false  ? $this->route : $route;
		$baseUrl=Url::toRoute(array_merge( 
					(array)$route , 
					['dialogid'=>$this->dialogid, 'textid'=>$this->textid, 'valueid'=>$this->valueid, 'inputwrapid'=>$this->inputwrapid],
					$params
				));
		return $baseUrl;
	}

	public function getDialogSelector(){
		return "#".$this->dialogid." .modal-body";
	}

	public function getInputSelector($text=false){
		return $text ? "#".$this->inputwrapid." #".$this->textid : "#".$this->inputwrapid." #".$this->valueid ;
	}

	public function getView(){
		return Yii::$app->getView();
	}

	public function getViewPath(){
		if($this->customView == true){
			return $this->controller->getViewPath();
		}elseif($this->customView === false){
			return Yii::getAlias("@superlyons/mptt/widgets/views");
		}else{
			return Yii::getAlias($this->customView);
		}
		
	}
}