<?php

namespace humhub\modules\massuserimport\models;

use Yii;

/**
 * MassuserimportModule ConfigureForm defines the configurable fields.
 *
 * @package humhub\modules\massuserimport\models
 * @author Sebastian Stumpf
 */
class ConfigureForm extends \yii\base\Model
{

    public $activateJsonRestApi;
    public $jsonRestApiPassword;
    public $noNotificationMail;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['activateJsonRestApi', 'boolean'],
            ['noNotificationMail', 'boolean'],
            ['jsonRestApiPassword', 'string', 'length' => [6, 24]],
            ['jsonRestApiPassword', 'required'],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'activateJsonRestApi' => 'Activate Json REST API',
            'jsonRestApiPassword' => 'Json REST API password',
            'noNotificationMail' => 'Disable notification mails'
        );
    }

}
