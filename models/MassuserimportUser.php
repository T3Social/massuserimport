<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\massuserimport\models;

use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;

/**
 * Extends user by massuserimport scenarios.
 *
 * @property-read Group[] $groups
 */
class MassuserimportUser extends User
{

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['massuserimport_create'] = ['username', 'email', 'auth_mode', 'status', 'tags', 'language', 'visibility', 'timezone'];
        $scenarios['massuserimport_update'] = ['username', 'email', 'auth_mode', 'status', 'tags', 'language', 'visibility', 'timezone'];
        return $scenarios;
    }

    /**
     * We want that relation filled up with a MassuserimportProfile model to have access to its scenarios.
     * @see \humhub\modules\user\models\User::getProfile()
     */
    public function getProfile()
    {
        return MassuserimportProfile::find()->where(['user_id' => $this->id]);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $ret = parent::afterSave($insert, $changedAttributes);

        // Fix contentcontainer table
        foreach (\humhub\modules\content\models\ContentContainer::findAll(['class' => MassuserimportUser::className()]) as $contentContainer) {
            $contentContainer->class = User::className();
            $contentContainer->save();
        }
        
        return $ret;
    }

}
