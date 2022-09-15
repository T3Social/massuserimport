<?php

namespace humhub\modules\massuserimport;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{

    public $css = [
        'massuserimport.css',
    ];

    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . '/assets';
        parent::init();
    }

}
