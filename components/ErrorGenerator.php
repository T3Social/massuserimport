<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\components;

use Yii;

/**
 * ErrorGenerator generates the error messages for the massuserimport module displayed in the error summary.
 *
 * @package humhub.modules.massuserimport.components
 * @since 1.0
 * @author Sebastian Stumpf
 */
class ErrorGenerator
{

    const MAILER_ERROR = 0;

    const EMAIL_IN_USE = 1;

    const GENERAL = 2;

    const MODEL_ERROR = 3;
    
    const INFO = 4;

    /**
     * Generate the error message.
     *
     * @param string $additionalInfo            
     * @param string $lineNumber            
     * @param string $email            
     * @param integer $type
     *            See ErrorGenerator constants.
     * @return string The complete error message.
     */
    public static function generateErrorMessage($additionalInfo = '', $lineNumber = '', $email = '', $type = self::GENERAL)
    {
        $msg = $lineNumber == '' ? '' : Yii::t('MassuserimportModule.base', 'Line %lineNumber% - ', ['%lineNumber%' => $lineNumber]);
        switch ($type) {
            case self::GENERAL:
                $msg .= Yii::t('MassuserimportModule.base', 'A general error occurred.');
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
            case self::EMAIL_IN_USE:
                $msg .= Yii::t('MassuserimportModule.base', 'The email address %email% is already in use.', [
                    '%email%' => (empty($email) ? '_unknown_' : $email)
                ]);
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
            case self::MAILER_ERROR:
                $msg .= Yii::t('MassuserimportModule.base', 'The email to %email% could not be sent.', [
                    '%email%' => (empty($email) ? '_unknown_' : $email)
                ]);
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
            case self::MODEL_ERROR:
                $msg .= Yii::t('MassuserimportModule.base', 'The entry could not be saved.');
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
            case self::INFO:
                $msg .= Yii::t('MassuserimportModule.base', 'Info:');
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
            default:
                $msg .= Yii::t('MassuserimportModule.base', 'An error occurred.');
                $msg .= empty($additionalInfo) ? '' : " $additionalInfo";
                break;
        }
        return $msg;
    }
}