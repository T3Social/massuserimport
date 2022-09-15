<?php
use humhub\modules\admin\widgets\UserMenu;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\admin\controllers\UserController;

return [
    'id' => 'massuserimport',
    'class' => 'humhub\modules\massuserimport\Module',
    'namespace' => 'humhub\modules\massuserimport',
    'events' => [
        array(
            'class' => AdminMenu::className(),
            'event' => AdminMenu::EVENT_INIT,
            'callback' => array(
                'humhub\modules\massuserimport\Events',
                'onAdminMenuInit'
            )
        ),
        array(
            'class' => UserMenu::className(),
            'event' => UserMenu::EVENT_INIT,
            'callback' => array(
                'humhub\modules\massuserimport\Events',
                'onAdminUserMenuInit'
            )
        )
    ]
];
?>