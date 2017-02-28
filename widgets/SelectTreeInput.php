<?php

namespace superlyons\mptt\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;
use yii\bootstrap\InputWidget;
use yii\bootstrap\ActiveField;
use yii\helpers\Url;
use superlyons\mptt\models\MpttNode;


class SelectTreeInput extends InputWidget{
	public $model;
    public $attribute;

    public $paramId;

	public $inForm = true;

    public $action = 'select-tree-ajax';
    public $renderContext;
	public $inputView = "SelectTreeInput";
	public $dialogView = "SelectTreeDialog";

    public $renderListBox = false;
	private $_node;

	public function init()
    {
    	parent::init();

        if ($this->action === null) {
            throw new InvalidConfigException(" SelectTreeInput::action properties must be specified.");
        }

        $this->renderContext = $this->renderContext ? : $this;

        if ( !$this->model->isNewRecord) {
		    if($this->model instanceof MpttNode){
                $this->_node = $this->model->parent()->select(['id','name'])->asArray()->one();
                if ( empty($this->model->{$this->attribute}) && !empty($this->_node) ){
                    $this->model->{$this->attribute} = $this->_node['id'];
                }
            }else{
                $this->_node = MpttNode::find()->select(['id','name'])
                    ->andWhere(['id'=>$this->model->{$this->attribute}])->asArray()->one();;
            }
		}

		$this->clientOptions = false;
        $this->registerPlugin('');

        $this->paramId = $this->paramId ? : "SelectTreeInput";
        Yii::$app->params[$this->paramId] = $this;
    }

    public function render($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this->renderContext);
    }

    public function run(){
        if ($this->renderListBox) {
            return $this->runListBox();
        }else{
		      return $this->render($this->inputView, [ 'widget' => $this ]);
        }
    }

    public function runDialog(){
        if (!$this->renderListBox) {
            return $modal = $this->render($this->dialogView, [ 'widget' => $this ] );
        };
        return "";
    }

/*
<div class="form-group field-mpttnode-parent">
    <label class="control-label" for="mpttnode-parent">Parent</label>
 -------------------------------------------------------------------------------------------------renderInputDefault()--
    <div id="w1-wrap" class="input-group">
        <input type="text" id="w1-mpttnode-parent-text-id" class="form-control" name="w1-MpttNode[parent]-text-name" value="MySQL">
        <input type="hidden" id="mpttnode-parent" class="form-control" name="MpttNode[parent]" value="4639755323056330585">
        <span class="input-group-btn">
            <button type="button" id="w1-mpttnode-parent-btn-id" class="btn btn-default" data-toggle="modal" data-target="#w1-mpttnode-parent-dialog-id">
                Select Node
            </button>
        </span>
    </div>
 -------------------------------------------------------------------------------------------------renderInputDefault() End--
    <p class="help-block help-block-error"></p>
</div>
*/
    public function renderInputDefault(){
    	$result = Html::input('text', $this->getTextTag(false), $this->getTextValue(), [ 'class' => 'form-control', 'id' => $this->getTextTag()]);
    	$result .= Html::activeHiddenInput($this->model, $this->attribute, ['class' => 'form-control']);
    	$resultbtn = Html::buttonInput(Yii::t('mptt', Yii::t('mptt', 'Select Node')), 
    						[ 'class'=>'btn btn-default', 'data-toggle'=>"modal", "data-target"=>"#".$this->getDialogTag(), 'id'=>$this->getBtnTag() ]);
    	$result .= Html::tag('span', $resultbtn, ['class'=>'input-group-btn']);
    	$result = Html::tag('div', $result, ['class'=>"input-group ", 'id'=>$this->getInputWrapId()]);
    	if(!$this->inForm){
    		$result = Html::tag("div", $result, ['class'=>'form-group', 'id'=>$this->getFieldId()] );
    	}
    	return $result;
    }

    public function getTextValue(){
    	return $this->_node ? $this->_node['name'] : Yii::t('mptt', 'Please select the parent node');
    }
    public function getRouteUrl(){
    	return Url::toRoute([$this->action, 
    			'id'=>$this->model->{$this->attribute},
    			'dialogid' => $this->getDialogTag(),
    			'textid' => $this->getTextTag(),
    			'valueid' => $this->getHiddenTag(),
                'inputwrapid' => $this->getInputWrapId(),
    		]);
    }

    public function getHiddenTag($id=true){
    	return $id ? Html::getInputId($this->model, $this->attribute) : Html::getInputName($this->model, $this->attribute);
    }
    public function getTextTag($id=true){
    	$tag = $id ? "-text-id" : "-text-name";
    	return $this->getId()."-".$this->getHiddenTag($id).$tag;
    }
    public function getBtnTag($id=true){
    	$tag = $id ? "-btn-id" : "-btn-name";
    	return $this->getId()."-".$this->getHiddenTag($id).$tag;
    }
    public function getDialogTag($id=true){
    	$tag = $id ? "-dialog-id" : "-dialog-name";
    	return $this->getId()."-".$this->getHiddenTag($id).$tag;
    }
    public function getFieldId(){
    	return 'field-'.$this->getHiddenTag();
    }
    public function getInputWrapId(){
        return $this->getId().'-wrap';
    }
    
    public function runListBox(){
        $nodes = new MpttNode;
        $nodes = $nodes->roots()->all();
        $level = 0;

        $separator="";
        $depth_separator="    ";

        $items[0] = Yii::t('app', 'Please select the parent node');
        foreach ($nodes as $key => $value){            
            $items[$value->attributes['id']]=$value->attributes['name'];
            $children = $value->descendants()->all();
            foreach ($children as $child){
                $string = $separator;
                $string .= str_repeat($depth_separator, $child->level - $level - 1);
                $string .= $child->name;
                $items[$child->id]=$string;
            }
        }

        $itemOptions = [
            'encodeSpaces'=>true,
            'class' => 'form-control'
        ];

        return Html::activeDropDownList($this->model, $this->attribute, $items, $itemOptions);
    }

}