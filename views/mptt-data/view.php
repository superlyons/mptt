<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model superlyons\mptt\models\MpttNode */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('mptt', 'Mptt Nodes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mptt-node-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('mptt', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('mptt', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'root',
            'lft',
            'rgt',
            'level',
            'name',
            'value',
            'type',
            'data',
            'summary',
            'seo_title',
            'seo_keywords',
            'seo_description',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'optimistic_lock'
        ],
    ]) ?>

</div>
