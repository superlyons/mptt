<?php

namespace superlyons\mptt\models;

use Yii;
use superlyons\idGenerator\ActiveRecord;
use superlyons\mptt\components\NestedSetBehavior;
use yii\helpers\Html;

/**
 * This is the model class for table "mptt".
 *
 * @property string $id
 * @property string $root
 * @property string $lft
 * @property string $rgt
 * @property integer $level
 * @property string $name
 * @property string $value
 * @property string $type
 * @property string $summary
 * @property string $seo_title
 * @property string $seo_keywords
 * @property string $seo_description
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 */
class MpttNode extends ActiveRecord
{
    //是否开启乐观锁, 默认不开启
    //load()|Model::createValidators()方法后本属性控制的验证规则无效
    public $openOptimisticLock = false; 

    //public $parent;

    public static function tableName()
    {
        return 'mptt';
    }

    public function behaviors()
    {
        $c = [        
                'NestedSet' => [
                    'class' => NestedSetBehavior::className(),
                    'hasManyRoots' => true
                ],
                'timestamp' => [
                    'class' => 'yii\behaviors\TimestampBehavior',
                ],
            ];
        $AppComponentsConfig = Yii::$app->getComponents();
        if( isset($AppComponentsConfig['user']['identityClass']) ){
            $c['blameable'] = [
                'class' => 'yii\behaviors\BlameableBehavior'
            ];
        }
        return $c; 
    }

    public function saveNode_notUse(){
        /*
        $this->data = $this->data === null || $this->data === '' ? null : Json::decode($this->data);
        $NestedSet = $this->getBehavior('NestedSet');
        $NestedSet->saveNode();
        */
    }

    public function rules()
    {
        $rules = [
            [['name',], 'required'],
            [['parent'], 'integer'],
            [['name', 'value', 'summary', 'seo_title', 'seo_keywords', 'seo_description'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 10],
            [['data'], 'default'],
        ];
        //乐观锁
        if($this->openOptimisticLock){
            $rules[] = [ ['optimistic_lock'] , 'required' ];
        }
        return $rules;
    }

    public function renderOptimisticLockHiddenInput(){
        if($this->openOptimisticLock){
            $value = $this->optimistic_lock;
            if(empty($value)){
                $value = 0;
            }
            return Html::activeHiddenInput($this, 'optimistic_lock', [ 'value' => $value ] );
        }
        return '';
    }

    public function optimisticLock(){
        return $this->openOptimisticLock ? 'optimistic_lock' : null;
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('mptt', 'ID'),
            'root' => Yii::t('mptt', 'Root'),
            'lft' => Yii::t('mptt', 'Lft'),
            'rgt' => Yii::t('mptt', 'Rgt'),
            'level' => Yii::t('mptt', 'Level'),
            'parent' => Yii::t('mptt', 'Parent'),
            'name' => Yii::t('mptt', 'Name'),
            'value' => Yii::t('mptt', 'Value'),
            'type' => Yii::t('mptt', 'Type'),
            'summary' => Yii::t('mptt', 'Summary'),
            'data'  => Yii::t('mptt', 'Data'),
            'seo_title' => Yii::t('mptt', 'Seo Title'),
            'seo_keywords' => Yii::t('mptt', 'Seo Keywords'),
            'seo_description' => Yii::t('mptt', 'Seo Description'),
            'created_by' => Yii::t('mptt', 'Created By'),
            'updated_by' => Yii::t('mptt', 'Updated By'),
            'created_at' => Yii::t('mptt', 'Created At'),
            'updated_at' => Yii::t('mptt', 'Updated At'),
            'optimistic_lock' => Yii::t('mptt', 'Optimistic Lock')
        ];
    }

    public function getRootNode(){
        return $this->hasOne(self::className(),['id'=>'root']);
    }

    public function getParentNode(){
        return $this->hasOne(self::className(),['id'=>'parent']);   
    }
}
