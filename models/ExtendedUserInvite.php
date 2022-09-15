<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\models;

use humhub\modules\user\models\Invite;
use Yii;
use yii\helpers\Url;

/**
 * Extension of model 'Invite' with additional mass user invite logic.
 *
 * @see Invite
 *
 * @package humhub.modules.massuserimport.models
 * @since 1.0
 * @author Sebastian Stumpf, Thomas Rabl
 */
class ExtendedUserInvite extends Invite
{

    const SOURCE_MASS_INVITE = 'massinvite';

    /**
     * Adds option to send the mass user invite mail.
     */
    public function sendInviteMail()
    {
        if ($this->source == self::SOURCE_MASS_INVITE) {
            $mail = Yii::$app->mailer->compose([
                'html' => '@humhub/modules/massuserimport/views/mails/InviteUser'
            ], [
                'token' => $this->token
            ]);
            $mail->setFrom([
                Yii::$app->settings->get('mailer.systemEmailAddress') => Yii::$app->settings->get('mailer.systemEmailName')
            ]);
            $mail->setTo($this->email);
            $mail->setSubject(Yii::t('UserModule.views_mails_UserInviteSelf', 'Accept invitation link'));
            return $mail->send();
        } else {
            return parent::sendInviteMail();
        }
    }
}