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
        <h1><?php echo Yii::t('MassuserimportModule.base', '<strong>Import</strong> users'); ?></h1>
    </div>

    <div class="panel-body">
		<?= \humhub\modules\admin\widgets\UserMenu::widget(); ?>
		<p />
        <div class="alert alert-danger">
        <?php echo Yii::t('MassuserimportModule.base', 'An error occurred.'); ?>
    	</div>
        <div class="alert alert-danger">
    	<?php echo empty($details) ? Yii::t('MassuserimportModule.base', 'No details available.')  : $details; ?><br />
        </div>
    </div>
</div>