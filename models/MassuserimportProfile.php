<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\massuserimport\models;

use humhub\modules\user\models\Profile;
use humhub\modules\user\models\ProfileField;
/**
 * Extends profile by massuserimport scenarios.
 */
class MassuserimportProfile extends Profile
{
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        // bd hide year is not defined as a profile field, thus has to be added manually
        $scenarios['massuserimport_create'] = ['birthday_hide_year'];
        $scenarios['massuserimport_update'] = ['birthday_hide_year'];
        foreach (ProfileField::find()->all() as $profileField) {
            $scenarios['massuserimport_create'][] = $profileField->internal_name;
            $scenarios['massuserimport_update'][] = $profileField->internal_name;
        }
        return $scenarios;
    }       
}