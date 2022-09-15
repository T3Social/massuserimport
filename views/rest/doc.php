<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use humhub\modules\massuserimport\Assets;

Assets::register($this);
?>
<div class="panel panel-default">
    <div class="massuserimport-header">
        <h1><?php echo Yii::t('MassuserimportModule.base', '<strong>Api</strong> documentation'); ?></h1>
    </div>

    <div class="panel-body">
        <?= \humhub\modules\admin\widgets\UserMenu::widget(); ?>
        <p />

        <div class="alert alert-warning">
            <?php echo Yii::t('MassuserimportModule.base', 'This Api offers methods to access the user database and create, delete and modify user data. The data format is json. The different methods are explained in the following section.'); ?>
        </div>
        
        <p />
        
        <div class="markdown-render">
            <?php echo \humhub\widgets\MarkdownView::widget(['markdown' => $markdown]); ?>
        </div>
    </div>
</div>