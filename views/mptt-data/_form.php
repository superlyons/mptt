<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use superlyons\mptt\models\MpttNode;
use superlyons\mptt\widgets\SelectTreeInput;

/* @var $this yii\web\View */
/* @var $model superlyons\mptt\models\MpttNode */
/* @var $form yii\widgets\ActiveForm */
?>

<div>
<?
if($stale){
?>
    <div class="alert alert-warning alert-dismissible" role="alert">
      <strong><?=Yii::t('mptt','Warning!')?></strong> <?=Yii::t('mptt','The object being updated is outdated. Continue to update will use outdated data!')?>
      <br><strong> <?= Html::a( Yii::t('mptt',"View New Version") , ['mptt-data/view', 'id' => $model->id], ['target' => '_blank'] ) ?> </strong>
    </div>
<?
}
?>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"> <a href="#home" data-toggle="tab"><?=Yii::t("mptt","Base Info")?></a> </li>
        <li> <a href="#seo" data-toggle="tab"><?=Yii::t("mptt","SEO Info")?></a> </li>
    </ul>
    <!-- Nav tabs End-->

    <?php $form = ActiveForm::begin(); ?>
    <!-- Tab panes -->
    <div class="tab-content" style="padding-top: 10px;">
        

        <div class="tab-pane active" id="home">
            <?=$model->renderOptimisticLockHiddenInput()?>
            
            <?php       
                echo $form->field($model, 'parent')->widget( SelectTreeInput::className(), ['renderListBox'=>false]);
            ?>

            <?php 
            /*
                if (!$model->isNewRecord && isset($parent)){
                    $parent = null;

                    if (!$model->isNewRecord) {
                        $parent = $model->getParent();
                    }
                    $model->parent=$parent->id;
                    echo $form->field($model, 'parent')->dropDownList($items, $itemOptions);
                }else{
                    echo $form->field($model, 'parent')->dropDownList($items, $itemOptions);
                }
            */
            ?>
            <?= $form->field($model, 'type')->textInput(['maxlength' => 255])?>
            <?= $form->field($model, 'name')->textInput(['maxlength' => 255])?>
            <?= $form->field($model, 'value')->textInput(['maxlength' => 255])?>
            <?= $form->field($model, 'data')->textarea(['rows'=>'10'])?>
            <?= $form->field($model, 'summary')->textArea(['rows' => 6])?>

        </div>

        <div class="tab-pane" id="seo">
            <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 255]) ?>
            <?= $form->field($model, 'seo_keywords')->textInput(['maxlength' => 255]) ?>
            <?= $form->field($model, 'seo_description')->textArea(['rows' => 5]) ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('mptt', 'Create') : Yii::t('mptt', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
        
    </div>
    <!-- Tab panes End-->
    <?php ActiveForm::end(); ?>

    <?php
        if (isset(Yii::$app->params['SelectTreeInput'])){
            $widget = Yii::$app->params['SelectTreeInput'];
            echo $widget->runDialog();
        }
    ?>

</div>

