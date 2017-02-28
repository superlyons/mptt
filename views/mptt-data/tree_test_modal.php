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

$this->title = Yii::t('app', 'MPTT Tree Mode');
$this->params['breadcrumbs'][] = $this->title;

?>

<div>
<h1>Tree Mode</h1>

<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#myModal">
  Launch demo modal
</button>

<div id="select">
	no select
</div>

<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">MPTT Tree Select</h4>
			</div>
			<div class="modal-body">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
	<?
	$baseUrl=Url::toRoute(['tree-ajax', 'id'=>$selid]);
	$js = <<<JSSTR
		$("#myModal").on("show.bs.modal",function(e){
			var \$body = $(e.target).find(".modal-body");
			var loaded = \$body.data('loaded');
			if(!loaded){
				\$body.load('{$baseUrl}', function(){
					$(this).data('loaded', true);
				});
			}
		});
JSSTR;

	$this->registerJs( new JsExpression($js) );
	?>

</div>