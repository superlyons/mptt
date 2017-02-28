<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\web\JsExpression;
use yii\bootstrap\BootstrapPluginAsset;
use yii\helpers\Url;
use yii\web\Request;
/* @var $this yii\web\View */
/* @var $searchModel superlyons\mptt\models\MpttSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('mptt', 'Mptt Nodes');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mptt-node-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('mptt', 'Create Mptt Node'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

<div class="modal fade" id="typedialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?=Yii::t("mptt","Type Filter")?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="TypeName"><?=Yii::t("mptt","Type")?></label>
                    <input type="text" class="form-control" id="TypeName" placeholder="<?=Yii::t("mptt","Input Type Name...")?>"
                             value="<?=$searchModel->type?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=Yii::t("mptt","Close")?></button>
                <button type="button" class="btn btn-default btn-primary typebutton" ><?=Yii::t("mptt","Type Filter")?></button>
            </div>
        </div>
    </div>
</div>

    <?
    $that = $this;
    $wid = 'maingridview';

    $typebutton = Yii::t('mptt','Type');
    $typeColumnFilter = <<<HTML
        <button class="btn btn-default" data-toggle="modal" data-target="#typedialog" type="submit">$typebutton</button>
        <input type="hidden" name="MpttSearch[type]" value="$searchModel->type"/>
HTML;
    $js = <<<JSSTR
            \$(".typebutton").click(function(e){
                e.preventDefault();
                var target = \$("input[name='MpttSearch[type]']");

                target.val(\$("#TypeName").val());
                
                \$("#typedialog").modal('toggle');

                \$grid = \$('#{$wid}');
                \$grid.yiiGridView('applyFilter');
            });
JSSTR;
    $this->registerJs( new JsExpression($js) );
    ?>

    <div class="table-responsive">
    <?
    echo GridView::widget([
        'id' => $wid,
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            ['class' => 'yii\grid\CheckboxColumn'],
            
            'id',

            //'rootNode.name:text:root', //一种简单用法
            [
                'label' => Yii::t("mptt", "Root Name"),
                'format' => 'html',
                'value' => function($model, $key, $index, $dataColumn){
                    $a="<a href='{href}' title='{title}'>{rootname}</a>";
                    $rep['{rootname}'] = $model->rootNode->name;                    
                    $request = Yii::$app->getRequest();
                    $get = $request->getQueryParams();
                    if( isset($get['MpttSearch']['root']) ){
                        $rep['{title}'] = $model->root;
                    }else{
                        $params['MpttSearch']['root'] = $rep['{title}'] = $model->root;
                    }
                    $rep['{href}']=Url::toRoute($params);
                    $a=strtr($a, $rep);
                    return $a;
                },
                'attribute' => 'rootNodeName'
            ],

            [
                'attribute' => 'level', 
                'filterInputOptions' => ['class' => 'form-control', 'id' => null, 'prompt'=>Yii::t("mptt",'Level'), 'style'=>'width:90px' ],
                'filter' => [ ''=>'All', '1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10', '11'=>'11']
            ],

            'parentNode.name:text:'.Yii::t("mptt",'Parent'),
            'name',

            [
                'attribute' => 'value',
                'contentOptions' => ['style'=>'word-wrap: break-word;word-break:break-all;']
            ],

            [
                'attribute' => 'type', 
                'filter' => $typeColumnFilter
            ],
            
            'lft',
            'rgt',
            
            [
                'class' => 'yii\grid\ActionColumn',
                'options' => [
                    'width' => "110px",
                ],
                'template' => '{view} {update} {delete} {totree} {data} ',
                'visibleButtons' => [
                    'data' => function($model, $key, $index) use ($that){
                        if( empty( trim($model->data) ) ){
                            return false;
                        }else{
                            /*For performance reasons , popover and Tooltip must initialize them yourself.*/
                            BootstrapPluginAsset::register($that);
                            $that->registerJs( new JsExpression("$('[data-toggle=\"popover\"]').popover()") );
                            return true;
                        }
                    },
                ],
                'buttons' =>[
                    'data' => function($url,$model,$key){
                        $a='<span class="glyphicon glyphicon-text-size"></span>';
                        $options = [
                            'data-toggle' => 'popover',
                            'title' => 'Summary',
                            'data-content' => $model->data,
                            'data-placement' => "left",
                            'data-trigger' => "focus",
                        ];
                        $url="javascript:void(0);";
                        return Html::a($a, $url, $options);
                    },
                    'totree' => function($url,$model,$key){
                        $a='<span class="glyphicon glyphicon glyphicon-tree-conifer"></span>';
                        $options = [
                            'title' => Yii::t('yii', 'Tree View'),
                            'target' => '_blank',
                        ];
                        $url=Url::toRoute(["tree", 'id'=>$model->id]);
                        return Html::a($a, $url, $options);
                    }
                ]
            ],
        ],
        'beforeRow' => function($model, $key, $index, $grid){
            if( !empty($model->seo_title) || !empty($model->seo_keywords) ){
                Html::addCssClass($grid->rowOptions, 'info');
                /*For performance reasons , popover and Tooltip must initialize them yourself.*/
                BootstrapPluginAsset::register($grid->getView());
                $grid->getView()->registerJs( new JsExpression("$('[data-toggle=\"tooltip\"]').tooltip()") );
                
                $grid->rowOptions['data-toggle']="tooltip";
                $grid->rowOptions['data-placement']="top";
                $grid->rowOptions['title']=$model->seo_title." | ".$model->seo_keywords;
            }
        },
        'afterRow' => function($model, $key, $index, $grid){
            if( !empty($model->seo_title) || !empty($model->seo_keywords) ){
                Html::removeCssClass($grid->rowOptions, 'info');
                unset($grid->rowOptions['data-toggle']);
                unset($grid->rowOptions['data-placement']);
                unset($grid->rowOptions['title']);
            }
        }
    ]); ?>
    </div>
    <p>
        <?
         echo Html::a(Yii::t('mptt', 'Del Selected Node'), ['delete-batch'], ['class' => 'delbatch btn btn-danger']) ;
         //Heredoc : <<<JSSTR 其中的变量会被解析
         //Nowdoc : <<<'JSSTR' 其中的PHP变量不会被解析
         $msg = Yii::t("mptt","Batch deletes data will cause the entire MPTT errors, Are you sure you want to delete this item ?");
         $js = <<<JSSTR
            $(".delbatch").click(function(e){
                e.preventDefault();
                var iscon = confirm("$msg");
                if( iscon ){
                    \$this = \$(this);
                    \$grid = \$('#{$wid}');
                    var sel = \$grid.yiiGridView('getSelectedRows');
                    \$grid.find('form.gridview-filter-form').remove();
                    var \$form = \$('<form/>', {
                            action: \$this.attr("href"),
                            method: 'post',
                            class: 'gridview-filter-form',
                            style: 'display:none',
                            'data-pjax': ''
                        }).appendTo( \$grid );
                    \$form.append($('<input/>').attr({type: 'hidden', name: 'delbatch', value: sel}));
                    var csrf_p = \$('head meta[name=csrf-param]').attr('content');
                    var csrf_v = \$('head meta[name=csrf-token]').attr('content');
                    \$form.append($('<input/>').attr({type: 'hidden', name: csrf_p, value: csrf_v}));
                    \$form.submit();
                };
            });
JSSTR;
         $this->registerJs( new JsExpression($js) );
        ?>
    </p>
</div>
