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

/**
 * Search logic for model 'ExtendedUserInvite'.
 *
 * @see ExtendedUserInvite
 *
 * @package humhub.modules.massuserimport.models
 * @since 1.0
 * @author Sebastian Stumpf, Thomas Rabl
 */
class ExtendedUserInviteSearch extends ExtendedUserInvite
{

    public $filterSource = null;

    public function rules()
    {
        return [
            [
                [
                    'email',
                    'firstname',
                    'lastname'
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
     * Creates data provider instance with search query applied.
     *
     * @param array $params            
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ExtendedUserInvite::find();
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50
            ]
        ]);
        
        $dataProvider->setSort([
            'attributes' => [
                'firstname',
                'lastname'
            ]
        ]);
        
        $this->load($params);
        
        if (! $this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }
        
        if (! empty($this->filterSource)) {
            $query->andFilterWhere([
                '=',
                'source',
                $this->filterSource
            ]);
        }
        $query->andFilterWhere([
            'like',
            'email',
            $this->email
        ]);
        $query->andFilterWhere([
            'like',
            'firstname',
            $this->firstname
        ]);
        $query->andFilterWhere([
            'like',
            'lastname',
            $this->lastname
        ]);
        
        return $dataProvider;
    }
}
