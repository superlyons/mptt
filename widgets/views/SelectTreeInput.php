<?
use yii\helpers\Url;
use yii\web\JsExpression;

echo $widget->renderInputDefault();
?>

<?
	$baseUrl = $widget->getRouteUrl();
	$modalid = $widget->getDialogTag();
	$js = <<<JSSTR
		$("#$modalid").on("show.bs.modal",function(e){
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