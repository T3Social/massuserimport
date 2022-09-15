<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport;

use Yii;
use yii\helpers\Url;

/**
 * Mass User Import Module provides import and invite of users loaded from a csv.
 *
 * @package humhub.modules.massuserimport
 * @since 0.x
 * @author Sebastian Stumpf
 */
class Module extends \humhub\components\Module
{

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to([
            '/massuserimport/config'
        ]);
    }
}
