<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model superlyons\mptt\models\MpttNode */

$this->title = Yii::t('mptt', 'Update Mptt Node: ') . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('mptt', 'Mptt Nodes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('mptt', 'Update');
?>
<div class="mptt-node-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'stale' => $stale
    ]) ?>

</div>
