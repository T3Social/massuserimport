<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use humhub\modules\user\models\User;
use yii\base\InvalidParamException;

/**
 * Search logic for model 'User' with specializations for the mass user import module.
 *
 * @see User
 *
 * @package humhub.modules.massuserimport.models
 * @since 1.0
 * @author Sebastian Stumpf, Thomas Rabl
 */
class UserImportSearch extends User
{

    public function attributes()
    {
        // add related fields to searchable attributes
        return array_merge(parent::attributes(), [
            'profile.firstname',
            'profile.lastname'
        ]);
    }

    public function rules()
    {
        return [
            [
                [
                    'id'
                ],
                'integer'
            ],
            [
                [
                    'username',
                    'email',
                    'created_at',
                    'profile.firstname',
                    'profile.lastname'
                ],
                'safe'
            ]
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

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params            
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = User::find()->joinWith('profile');
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ]
        ]);
        
        $dataProvider->setSort([
            'attributes' => [
                'id',
                'username',
                'email',
                'profile.firstname',
                'profile.lastname',
                'created_at'
            ]
        ]);
        
        $this->load($params);
        
        if (! $this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }
        
        $query->andFilterWhere([
            'id' => $this->id
        ]);
        $query->andFilterWhere([
            'like',
            'id',
            $this->id
        ]);
        $query->andFilterWhere([
            'like',
            'username',
            $this->username
        ]);
        $query->andFilterWhere([
            'like',
            'email',
            $this->email
        ]);
        $query->andFilterWhere([
            'like',
            'profile.firstname',
            $this->getAttribute('profile.firstname')
        ]);
        $query->andFilterWhere([
            'like',
            'profile.lastname',
            $this->getAttribute('profile.lastname')
        ]);
        
        if ($this->getAttribute('created_at') !== null) {
            try {
                $created_at = Yii::$app->formatter->asDate($this->getAttribute('created_at'), 'php:Y-m-d');
                $query->andWhere([
                    '=',
                    new \yii\db\Expression("DATE(created_at)"),
                    new \yii\db\Expression("DATE('$created_at')")
                ]);
            } catch (InvalidParamException $e) {
                // do not change the query if the date is wrong formatted
            }
        }
        
        // filter out not imported users
        $query->andFilterWhere([
            '=',
            'imported',
            1
        ]);
        
        return $dataProvider;
    }
}
