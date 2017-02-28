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
            [['name', 'value', 'type', 'summary', 'seo_title', 'seo_keywords', 'seo_description', 'rootNode.name'], 'safe'],
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
                'rootNode.name'
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
        $model=new MpttNode();
        foreach ($model->attributes() as $attribute) {
            $attributes[$attribute] = [
                'asc' => [$attribute => SORT_ASC],
                'desc' => [$attribute => SORT_DESC],
                'label' => $model->getAttributeLabel($attribute),
            ];
        }
        $attributes['rootNode.name'] = [
            'asc' => ['rootNode.name' => SORT_ASC],
            'desc' => ['rootNode.name' => SORT_DESC],
            'label' => 'root',
        ];


        $query = MpttNode::find();

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

        $query->with('rootNode'); //提前加载关系数据, 避免多次查询

        //如果对关系数据进行排序
        if(isset($params[$dataProvider->getSort()->sortParam])){
            $query->joinWith('rootNode as rootNode');
        }

        //关系数据
        $m1='rootNode.name';
        if(!empty($this->$m1)){
            /*
            SELECT COUNT(*) FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%'
            SELECT `mmyclub_member`.* FROM `mmyclub_member` LEFT JOIN `mmyclub_card` ON `mmyclub_member`.`card_number` = `mmyclub_card`.`number` WHERE `mmyclub_card`.number LIKE '%123%' LIMIT 20
            */
            $query->joinWith('rootNode as rootNode')->andFilterWhere(['like', 'rootNode.name', $this->$m1]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'root' => $this->root,
            'lft' => $this->lft,
            'rgt' => $this->rgt,
            'level' => $this->level,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);


        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'value', $this->value])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'summary', $this->summary])
            ->andFilterWhere(['like', 'seo_title', $this->seo_title])
            ->andFilterWhere(['like', 'seo_keywords', $this->seo_keywords])
            ->andFilterWhere(['like', 'seo_description', $this->seo_description]);

        return $dataProvider;
    }
}
