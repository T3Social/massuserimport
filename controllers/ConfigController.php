<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\controllers;

use Yii;
use humhub\modules\massuserimport\models\ConfigureForm;
use humhub\modules\content\components\ContentContainerController;
use humhub\models\Setting;

/**
 * ConfigController handles the configuration requests.
 *
 * @package humhub.modules.massuserimport.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class ConfigController extends \humhub\modules\admin\components\Controller
{

    /**
     * Configuration action for super admins.
     */
    public function actionIndex()
    {
        $form = new ConfigureForm();
        $form->activateJsonRestApi = Setting::Get('activateJsonRestApi', 'massuserimport');
        $form->jsonRestApiPassword = Setting::Get('jsonRestApiPassword', 'massuserimport');
        $form->noNotificationMail = Setting::Get('noNotificationMail', 'massuserimport');
        
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            Setting::Set('activateJsonRestApi', $form->activateJsonRestApi, 'massuserimport');
            Setting::Set('jsonRestApiPassword', $form->jsonRestApiPassword, 'massuserimport');
            Setting::Set('noNotificationMail', $form->noNotificationMail, 'massuserimport');
        }
        
        return $this->render('index', array('model' => $form));
    }
}

?>
