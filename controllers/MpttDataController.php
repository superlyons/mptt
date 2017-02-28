<?php

namespace superlyons\mptt\controllers;

use Yii;
use superlyons\mptt\models\MpttNode;
use superlyons\mptt\models\MpttSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use superlyons\mptt\components\SelectTreeAjaxAction;

/**
 * MpttDataController implements the CRUD actions for MpttNode model.
 */
class MpttDataController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actions(){
        return [
            'select-tree-ajax' => [
                'class' => SelectTreeAjaxAction::className(),
            ],
        ];
    }

    /**
     * Lists all MpttNode models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MpttSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single MpttNode model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new MpttNode model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new MpttNode;
        $model->openOptimisticLock = true; //乐观锁是否开启

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->parent == 0){
                $model->saveNode();
            } else if ($model->parent){
                $root = $this->findModel($model->parent);
                $model->appendTo($root);
            }
            //return $this->run('view', ['id' => $model->id]);
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing MpttNode model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id); //$id来自GET
        $model->openOptimisticLock = true; //乐观锁是否开启
        $parent = $model->parent()->one(); //更新之前获得parent

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try{
                $model->saveNode();
            } catch (yii\db\StaleObjectException $e) {
                $newOptimisticLock = MpttNode::find()->select(['optimistic_lock'])->andWhere(['id'=>$id])->asArray()->one();
                $model->optimistic_lock = $newOptimisticLock['optimistic_lock'];
                return $this->render('update', [
                        'model' => $model,
                        'stale' => true,
                    ]); 
            }
            if ($model->parent == 0 && !$model->isRoot()){
                $model->moveAsRoot();
            } elseif ($model->parent != 0 && $model->parent != $parent->id){
                $root = $this->findModel($model->parent);
                $model->moveAsLast($root);
            }
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing MpttNode model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->deleteNode();

        return $this->redirect(['index']);
    }

    public function actionDeleteBatch(){
        $delbatch = Yii::$app->request->post('delbatch');
        if(!empty($delbatch)){
            MpttNode::deleteAll( ['in', 'id', explode(',' , $delbatch) ]);
        }
        return $this->redirect(['index']);

    }

    public function actionTreeMode($id=''){

        $node = new MpttNode;
        $roots = $node->roots()->select(['id','name'])->asArray()->all();

        $markroots=[];
        foreach($roots as $key=>$val){
            $markroots[$val['id']] = $val['name'].'('.$val['id'].')';
        }

        if(count($roots)>0 && !empty($id)){
            $selid=$id;
            $node = $node->find()->select(['root'])->andWhere(['id'=>$id])->asArray()->one();
            $id = !$node ? 0 : $node['root'];
            $nodes = MpttNode::find()->where(['root' => $id])->orderBy('lft')->all();
        }else{
            $selid=$id;
            $id=0;
            $nodes=[];
        }

        return $this->render("treemode", ['id'=>$id, 'selid'=>$selid, 'nodes'=>$nodes, 'roots'=>$markroots]);
    }

    public function actionTreeAjax($id=''){
        $node = new MpttNode;
        $roots = $node->roots()->select(['id','name'])->asArray()->all();

        $markroots=[];
        foreach($roots as $key=>$val){
            $markroots[$val['id']] = $val['name'].'('.$val['id'].')';
        }

        if(count($roots)>0 && !empty($id)){
            $selid=$id;
            $node = $node->find()->select(['root'])->andWhere(['id'=>$id])->asArray()->one();
            $id = !$node ? 0 : $node['root'];
            $nodes = MpttNode::find()->where(['root' => $id])->orderBy('lft')->all();
        }else{
            $selid=$id;
            $id=0;
            $nodes=[];
        }

        return $this->getView()->renderAjax("treeajax", ['id'=>$id, 'selid'=>$selid, 'nodes'=>$nodes, 'roots'=>$markroots], $this);
    }

    public function actionTree($id='')
    {

        $id = empty($id) ? Yii::$app->getRequest()->post('id') : $id;

        $node = new MpttNode;
        $roots = $node->roots()->select(['id','name'])->asArray()->all();

        $markroots=[];
        foreach($roots as $key=>$val){
            $markroots[$val['id']] = $val['name']; //.'('.$val['id'].')';
        }

        if(count($roots)>0 && !empty($id)){
            $selid=$id;
            $node = $node->find()->select(['root'])->andWhere(['id'=>$id])->asArray()->one();
            $id = !$node ? 0 : $node['root'];
            $nodes = MpttNode::find()->where(['root' => $id])->orderBy('lft')->all();
        }else{
            $selid=$id;
            $id=0;
            $nodes=[];
        }

        return $this->render('tree', ['id'=>$id, 'selid'=>$selid, 'nodes'=>$nodes, 'roots'=>$markroots] );
    }


    public function actionMove($id,$updown)
    {
        $model=$this->findModel($id);

        if($updown=="down") {
            $sibling=$model->next()->one();
            if (isset($sibling)) {
                $model->moveAfter($sibling);
            }
        }
        if($updown=="up"){
            $sibling=$model->prev()->one();
            if (isset($sibling)) {
                $model->moveBefore($sibling);
            }
        }
        return $this->redirect(array('tree', 'id'=>$id ));
    }

    /**
     * Finds the MpttNode model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return MpttNode the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MpttNode::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
