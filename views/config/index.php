<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>

<div class="panel panel-default">

    <div class="panel-heading"><?php echo Yii::t('MassuserimportModule.base', '<strong>Mass User Import</strong> Module Configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>
        <div class="form-group">
            <?= $form->field($model, 'noNotificationMail')->checkbox(null, false)->label(Yii::t('MassuserimportModule.base', 'Disable sending of a notification mail with username/password on user import via this module.')); ?>
            <?= $form->field($model, 'activateJsonRestApi')->checkbox(null, false)->label(Yii::t('MassuserimportModule.base', 'Activate/deactivate json rest API.')); ?>
            <?= $form->field($model, 'jsonRestApiPassword')->textInput()->label(Yii::t('MassuserimportModule.base', 'Password to access the json API.'));?>
        </div>
        <div class="form-group">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
