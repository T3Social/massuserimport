<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\massuserimport\models;

use humhub\modules\user\models\Password;
/**
 * Extends password by massuserimport scenarios.
 */
class MassuserimportPassword extends Password
{
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['massuserimport_create'] = ['newPassword', 'newPasswordConfirm'];
        $scenarios['massuserimport_update'] = ['newPassword', 'newPasswordConfirm', 'currentPassword'];
        return $scenarios;
    }      
}