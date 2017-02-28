<?php

use yii\helpers\Html;
use yii\helpers\Url;
use superlyons\mptt\models\MpttNode;
use yii\web\JsExpression;
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\nodeSearch $searchModel
 */

$this->title = Yii::t('mptt', 'MPTT Tree View');
$this->params['breadcrumbs'][] = $this->title;

?>

<style>
    .li-responsive{
        overflow-x: auto;

    }
    @media screen and (max-width: 767px) {
        .li-responsive{
            width: 100%;
            margin-bottom: 15px;
            overflow-y: hidden;
            -ms-overflow-style: -ms-autohiding-scrollbar;
            border: 1px solid #ddd;
        }

        .li-responsive li {
            white-space: nowrap;
        }
        .li-responsive li .btn-group a{
            float: none;
        }
    }
</style>
<div class="mptt-tree-wapper">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="clearfix">
        <div style="float:left; padding-right:10px; padding-bottom:10px;" >
           <?= Html::a(Yii::t('mptt', 'Create Mptt Node'), ['create'], ['class' => 'btn btn-success']) ?>
        </div>
        <div style="float:left;">
            <?
                echo Html::dropDownList("id", $id, $roots, ['prompt'=>Yii::t("mptt","Select Root Node"), 'id'=>"rootlist", "class"=>"form-control"]);
            ?>
        </div>
    </div>
    <p></p>   

    <?php
        $route = urldecode(Url::toRoute([]));
        $js = <<<JSSTR
            \$("#rootlist").change(function(e){
                e.preventDefault();
                \$wapper = \$(".mptt-tree-wapper");
                \$wapper.find('form.rootlist-filter-form').remove();
                var \$form = \$('<form/>', {
                        action: '{$route}',
                        //不能使用get, 因为如果GET请求的表单action属性中已经包含参数，浏览器会直接将其过滤掉，再附加form表单数据。
                        method: 'post',
                        class: 'rootlist-filter-form',
                        style: 'display:none',
                        'data-pjax': ''
                    }).appendTo( \$wapper );
                \$form.append($('<input/>').attr({type: 'hidden', name: 'id', value: \$(this).val()}));
                var csrf_p = \$('head meta[name=csrf-param]').attr('content');
                var csrf_v = \$('head meta[name=csrf-token]').attr('content');
                \$form.append($('<input/>').attr({type: 'hidden', name: csrf_p, value: csrf_v}));
                \$form.submit();
            });
JSSTR;
        $this->registerJs( new JsExpression($js) );
    ?>
    <div class="li-responsive">
    <?php
        $level = 0;
        //遍历树，即当前根节点和所有子节点
        foreach ($nodes as $n => $node)
        {
            //多个叶子节点
            if ($node->level == $level) {
                echo Html::endTag('li') . "\n";
            } elseif ($node->level > $level) { //下级节点
                echo Html::beginTag('ul') . "\n";
            } else { //回溯节点
                echo Html::endTag('li') . "\n"; //关闭前一个节点

                //回溯几级节点
                for ($i = $level - $node->level; $i; $i--) {
                    echo Html::endTag('ul') . "\n";
                    echo Html::endTag('li') . "\n";
                }
            }

            //输出当前节点<
            echo Html::beginTag('li');
            echo ($node->id == $selid) ? "<strong class='text-primary'>" : "";
            echo Html::encode($node->name).'<span class="text-muted hidden-xs">&nbsp;(';
            echo Html::encode($node->id).')</span>&nbsp;&nbsp;';
            echo ($node->id == $selid) ? "</strong>" : "";
            echo "<div class='btn-group' role='group' style='margin-top:5px; margin-bottom:5px;'>";
            echo Html::a('<span class="glyphicon glyphicon-arrow-up"></span>', ['move', 'id' => $node->id, 'updown' => 'up'], ['class'=>'btn btn-default']);
            echo Html::a('<span class="glyphicon glyphicon-arrow-down"></span>', ['move', 'id' => $node->id, 'updown' => 'down'], ['class'=>'btn btn-default']);
            echo Html::a('<span class="glyphicon glyphicon-eye-open"></span>', ['view', 'id' => $node->id], ['class'=>'btn btn-default']);
            echo Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $node->id], ['class'=>'btn btn-default']);
            echo Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $node->id], [
                                    'title' => Yii::t('app', 'Delete'),
                                    'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                    'data-method' => 'post',
                                    'data-pjax' => '0',
                                    'class' => 'btn btn-default'
                                ]);
            echo "</div>";
            $level = $node->level;
        }
        //回溯节点并封口
        for ($i = $level; $i; $i--) {
            echo Html::endTag('li') . "\n";
            echo Html::endTag('ul') . "\n";
        }
    ?>
    </div>
</div>