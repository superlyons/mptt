<?php

use yii\helpers\Html;
use yii\helpers\Url;
use superlyons\mptt\models\MpttNode;
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\nodeSearch $searchModel
 */

$this->title = Yii::t('app', 'MPTT Tree');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="category-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Mptt Node'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php
        $node = new MpttNode;
        //返回所有根节点
        $roots=$node->roots()->all();
        //遍历根节点
        foreach ($roots as $key => $root) {
            //返回根节点的所有子节点
            $nodes = MpttNode::find()->where(['root' => $root->id])->orderBy('lft')->all();
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
                //输出当前节点
                echo Html::beginTag('li');
                echo Html::encode($node->name).'&nbsp;<span class="text-muted">(';
                echo Html::encode($node->id).')</span>&nbsp;&nbsp;';
                echo Html::a('<span class="glyphicon glyphicon-arrow-up"></span>', ['move', 'id' => $node->id, 'updown' => 'up']).'&nbsp;&nbsp;';
                echo Html::a('<span class="glyphicon glyphicon-arrow-down"></span>', ['move', 'id' => $node->id, 'updown' => 'down']).'&nbsp;&nbsp;';
                echo Html::a('<span class="glyphicon glyphicon-eye-open"></span>', ['view', 'id' => $node->id]).'&nbsp;&nbsp;';
                echo Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['update', 'id' => $node->id]).'&nbsp;&nbsp;';
                echo Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $node->id], [
                        'title' => Yii::t('app', 'Delete'),
                        'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                        'data-method' => 'post',
                        'data-pjax' => '0',
                    ]);
                $level = $node->level;
            }
            //回溯节点并封口
            for ($i = $level; $i; $i--) {
                echo Html::endTag('li') . "\n";
                echo Html::endTag('ul') . "\n";
            }
        }
    ?>
</div>
