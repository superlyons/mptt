<?php

namespace superlyons\mptt\controllers;

use Yii;
use superlyons\idGenerator\Snowflake;
use superlyons\mptt\models\MpttNode;
use superlyons\mptt\components\MpttRbacRoutesHelper;
use yii\helpers\VarDumper;
/**
 * DefaultController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DefaultController extends \yii\web\Controller
{

    /**
     * Action index
     */
    public function actionIndex($page = 'README.md')
    {
        if (stripos($page, '.jpg') !== false) {
            $file = Yii::getAlias("@superlyons/mptt/{$page}");
            return Yii::$app->getResponse()->sendFile($file);
        }
        return $this->render('index', ['page' => $page]);
    }

    public function actionLanguage(){
        $file = file_get_contents(Yii::getAlias('@superlyons/mptt/language.txt'));
        if($file == "en"){
            $file = "zh-CN";
        }else{
            $file = "en";
        }
        file_put_contents(Yii::getAlias('@superlyons/mptt/language.txt'), $file);
        $url = Yii::$app->getRequest()->get('url');
        $this->redirect($url);
    }

    public function actionTest(){
        $node=new MpttNode();
        var_dump($node->safeAttributes());
    }

    public function actionId($page = 'README.md')
    {
        /*
    	$result="";
    	for($i=0; $i<1000; $i++){
    		$node = new MpttNode();
    		$node->name="test";
    		$node->saveNode();
    		$result .= $node->id." | ";
    		unset($node);
    	}
    	return $result.$i;
        */
        return "hello world!!!";
        
    }
    public function actionHelper(){
        $r=MpttRbacRoutesHelper::getAssignedRouteNodes(1);
        echo "<pre>".VarDumper::export($r)."<pre>";
        echo "<hr>";
        $r=MpttRbacRoutesHelper::getAssignedRouteNodes(1,['4641937160163824707','4641938999529709462']);
        echo "<pre>".VarDumper::export($r)."<pre>";
        echo "<hr>";

        $columns = ["id","lft","rgt","parent","name","value","data","root","level"];
        $roots = [ '4641937028538177630','4641938999529709462' ];
        $rootnodes = MpttNode::find()->select($columns)->where(['id'=>$roots])->all();
        foreach($rootnodes as $rootnode){
            if($rootnode['parent'] == 0){
                $parents = 0;
            }else{
                $parents = $rootnode['parent'];
            }
            $nodes = MpttNode::find()->select($columns)->andWhere(['>=', 'lft', $rootnode['lft']])
                ->andWhere(['<=', 'rgt', $rootnode['rgt']])->andWhere(['root'=>$rootnode['root']])->orderBy('lft')->asArray()->all();

            $arr = MpttRbacRoutesHelper::convertToArray($nodes, null, $parents);
            echo "<pre>".VarDumper::export($arr)."<pre>";
            echo "\n <br> --------------------------------------------------------------------------------------<br> \n";
        }


    }
}
