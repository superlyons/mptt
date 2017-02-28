<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model superlyons\mptt\models\MpttNode */

$this->title = Yii::t('mptt', 'Create Mptt Node');
$this->params['breadcrumbs'][] = ['label' => Yii::t('mptt', 'Mptt Nodes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mptt-node-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
