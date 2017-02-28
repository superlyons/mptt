<?
use yii\helpers\Url;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\bootstrap\BootstrapPluginAsset;

$action = $this->context;
?>
<style>
    .li-responsive{
        overflow-x: auto;
    }
    .li-responsive li span.active{
        font-weight: bold;
        color: #337ab7;
    }
    .li-responsive li span.treeitem{
        cursor: pointer;
    }
    .li-responsive li span.treeitem:hover{
        font-weight:bold;
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
    }
</style>
			    <div class="clearfix">
		            <?
		                echo Html::dropDownList("id", $p['rootid'], $roots, ['prompt'=>Yii::t("mptt","Select Root Node"), 'id'=>"rootlist", "class"=>"form-control"]);
		            ?>
			    </div>
			    <p></p>   


				<div class="mptt-tree-wapper li-responsive">
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
            $tmp = "<span class='treeitem {active}' data-selid=\"{selid}\" data-selname=\"{selname}\">";
            $rep['{active}'] = ($node->id == $p['selid']) ? "active" : "";
            $rep['{selid}'] = $node->id;
            $rep['{selname}'] = Html::encode($node->name);
            echo strtr($tmp, $rep);
            echo $rep['{selname}'].'<span class="text-muted hidden-xs">&nbsp;('.Html::encode($node->id).')</span>';
            echo "</span>";
            $level = $node->level;
        }
        //回溯节点并封口
        for ($i = $level; $i; $i--) {
            echo Html::endTag('li') . "\n";
            echo Html::endTag('ul') . "\n";
        }
?>
				</div>

                <?php
                    $baseUrl=$action->getRoute();
                    $dialog = $action->getDialogSelector();
                    $Textid = $action->getInputSelector(true);
                    $Valueid = $action->getInputSelector(false);
                    $js = <<<JSSTR
                        \$("#rootlist").change(function(){
                            \$("{$dialog}").load( '{$baseUrl}'+'&id='+$(this).val() );
                        });
JSSTR;
                    $js2 = <<<JSSTR
                        \$(".mptt-tree-wapper li span.treeitem").click(function(e){
                            e.preventDefault();
                            var \$inputText = \$("{$Textid}"),
                            	\$inputValue = \$("{$Valueid}");
                            \$(".mptt-tree-wapper li span.treeitem").removeClass("active");
                            \$(this).addClass("active");
                            var text = \$(this).data('selname'),
                                value = \$(this).data('selid');
                            \$inputText.val(text);
                            \$inputValue.val(value);
                        });
JSSTR;

                    $this->registerJs( new JsExpression($js) , $this::POS_END);
                    $this->registerJs( new JsExpression($js2) , $this::POS_END);
                ?>