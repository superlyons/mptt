<?php

namespace superlyons\mptt\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use superlyons\mptt\models\MpttNode;

/**
 * MpttSearch represents the model behind the search form about `superlyons\mptt\models\MpttNode`.
 */
class MpttSearch extends MpttNode
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'root', 'lft', 'rgt', 'level', 'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['name', 'value', 'data', 'type', 'summary', 'seo_title', 'seo_keywords', 'seo_description', 'rootNodeName', 'parentNode.name'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function attributes(){
        $p=parent::attributes();
        $r=array_merge($p,[
                'rootNodeName',
                'parentNode.name'
            ]);
        return $r;
    }
    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        /* 关系数据排序支持 */
        $model = new MpttNode();
        foreach ($model->attributes() as $attribute) {
            $attributes[$attribute] = [
                'asc' => [$attribute => SORT_ASC],
                'desc' => [$attribute => SORT_DESC],
                'label' => $model->getAttributeLabel($attribute),
            ];
        }
        $attributes['rootNodeName'] = [
            'asc' => ['rootNode.name' => SORT_ASC],
            'desc' => ['rootNode.name' => SORT_DESC],
            'label' => 'root',
        ];

        $attributes['parentNode.name'] = [
            'asc' => ['parentNode.name' => SORT_ASC],
            'desc' => ['parentNode.name' => SORT_DESC],
            'label' => 'root',
        ];

        $query = MpttNode::find();
        $query->alias("mpttMaster");

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => $attributes
            ]
        ]);

        $this->load($params); //填充查询对象

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->with(['rootNode','parentNode']); //提前加载关系数据, 避免多次查询

        //关系数据支持, 查询,排序
        $sortParam = $params[$dataProvider->getSort()->sortParam]; //排序
        $searchParam = []; //查询
        $relationParam = ['rootNodeName','parentNode.name'];
        foreach($relationParam as $value){
            if(!empty($params[$this->formName()][$value])){
                $searchParam[$value] = $params[$this->formName()][$value];
                break;
            }
        }

        if(!empty($sortParam) || count($searchParam)>0){
            $query->joinWith('rootNode as rootNode');
            $query->joinWith('parentNode as parentNode');
        }

        //关系数据
        $m1='rootNodeName';
        if(!empty($this->$m1)){
            /*
            SELECT COUNT(*) FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%'
            SELECT `mmyclub_member`.* FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%' LIMIT 20
            */
            $query->andFilterWhere(['like', 'rootNode.name', $this->$m1]);
        }
        $m1='parentNode.name';
        if(!empty($this->$m1)){
            /*
            SELECT COUNT(*) FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%'
            SELECT `mmyclub_member`.* FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%' LIMIT 20
            */
            $query->andFilterWhere(['like', 'parentNode.name', $this->$m1]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'mpttMaster.root' => $this->root,
            'mpttMaster.lft' => $this->lft,
            'mpttMaster.rgt' => $this->rgt,
            'mpttMaster.level' => $this->level,
            'mpttMaster.created_by' => $this->created_by,
            'mpttMaster.updated_by' => $this->updated_by,
            'mpttMaster.created_at' => $this->created_at,
            'mpttMaster.updated_at' => $this->updated_at,
        ]);


        $query->andFilterWhere(['like', 'mpttMaster.name', $this->name])
            ->andFilterWhere(['like', 'mpttMaster.id', $this->id])
            ->andFilterWhere(['like', 'mpttMaster.value', $this->value])
            ->andFilterWhere(['like', 'mpttMaster.type', $this->type])
            ->andFilterWhere(['like', 'mpttMaster.summary', $this->summary])
            ->andFilterWhere(['like', 'mpttMaster.seo_title', $this->seo_title])
            ->andFilterWhere(['like', 'mpttMaster.seo_keywords', $this->seo_keywords])
            ->andFilterWhere(['like', 'mpttMaster.seo_description', $this->seo_description]);

        return $dataProvider;
    }
}
