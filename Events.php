<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport;

use Yii;
use yii\helpers\Url;
use humhub\models\Setting;

/**
 * Description of mass user import events
 *
 * @author Sebastian Stumpf
 */
class Events
{

    /**
     * On admin menu init, this callback will be called
     * to add the extra menu item.
     *
     * @param type $event            
     */
    public static function onAdminMenuInit($event)
    {
        if (Yii::$app->hasModule('massuserimport')) {
            if (Yii::$app->controller->module->id == 'massuserimport') {
                $event->sender->markAsActive(Url::toRoute('/admin/user'));
            }
        }
    }

    public static function onAdminUserMenuInit($event)
    {
        if (Yii::$app->hasModule('massuserimport')) {
            $event->sender->addItem(array(
                'label' => Yii::t('MassuserimportModule.base', 'Import users'),
                'sortOrder' => 300,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'massuserimport' && Yii::$app->controller->id == 'import' && Yii::$app->controller->action->id == 'index'),
                'url' => Url::to([
                    '/massuserimport/import/index'
                ]),
                'isVisible' => Yii::$app->user->isAdmin()
            ));
            $event->sender->addItem(array(
                'label' => Yii::t('MassuserimportModule.base', 'Invite users'),
                'sortOrder' => 310,
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'massuserimport' && Yii::$app->controller->id == 'invite' && Yii::$app->controller->action->id == 'index'),
                'url' => Url::to([
                    '/massuserimport/invite/index'
                ]),
                'isVisible' => Yii::$app->user->isAdmin()
            ));
            // enable menu entry only if api is activated
            if (Setting::Get('activateJsonRestApi', 'massuserimport')) {
                $event->sender->addItem(array(
                    'label' => Yii::t('MassuserimportModule.base', 'Rest Api documentation'),
                    'sortOrder' => 320,
                    'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'massuserimport' && Yii::$app->controller->id == 'rest' && Yii::$app->controller->action->id == 'api-documentation'),
                    'url' => Url::to([
                        '/massuserimport/rest/api-documentation'
                    ]),
                    'isVisible' => Yii::$app->user->isAdmin()
                ));
            }
        }
    }
}
