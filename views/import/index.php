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
    <div class="panel-heading">
        <?php echo Yii::t('MassuserimportModule.base', '<strong>Import</strong> users'); ?>
    </div>
    
    <?= \humhub\modules\admin\widgets\UserMenu::widget(); ?>
    <div class="panel-body">
        <div class="alert alert-warning">
            <?php echo Yii::t('MassuserimportModule.base', 'Use the following form to upload a csv file containing the users you want to import. Submit the form to import all users to your database and send an email notification containing the login credentials to each of them.'); ?>
        </div>
        <p>
            <?php echo Yii::t('MassuserimportModule.base', 'Download an example with additional information here:'); ?>
            <a
                class="btn btn-primary btn-xs tt"
                data-pjax-prevent="1"
                title="<?php echo Yii::t('MassuserimportModule.base', 'Download example'); ?>"
                href="<?php echo Url::toRoute(['/massuserimport/import/download']) ?>"><i
                    class="fa fa-download"></i></a>
        </p>

        <?php
        $form = ActiveForm::begin([
            'enableAjaxValidation' => false,
            'options' => [
                'enctype' => 'multipart/form-data'
            ]
        ]);
        $form->setId('csv-form');
        ?>

        <div>
            <?= $form->field($csvModel, 'csvFile', ['options' => ['class' => empty($csvModel->errors) ? "" : $form->errorCssClass]])->fileInput() ?>
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="error-summary" style="display:<?php echo empty($csvModel->errors) ? "none" : "block" ?>">
            <hr/>
            <div class="alert alert-danger"><?php echo Yii::t('MassuserimportModule.base', 'Errors occurred.'); ?>
                <ul>
                    <?php
                    foreach ($csvModel->errors as $key => $error)
                        echo '<li>' . $error . '</li>';
                    ?>
                </ul>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <?php if ($dataProvider->totalCount > 0) { ?>
            <hr/>
            <p>
                <strong><?php echo Yii::t('MassuserimportModule.base', 'Imported users'); ?></strong>
            </p>
            <p>
                <?php echo Yii::t('MassuserimportModule.base', 'Below you can see all imported users. In case an user did not get the email notification, you can trigger a password recovery for that user from here.'); ?>
            </p>

            <!-- Grid -->
            <?php
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => [
                    'class' => 'table table-hover',
                ],
                'filterModel' => $searchModel,
                'columns' => [
                    [
                        'attribute' => 'id',
                        'options' => ['style' => 'width:40px;'],
                        'format' => 'raw',
                        'value' => function ($data) {
                            return $data->id;
                        }
                    ],
                    'username',
                    'email',
                    'profile.firstname',
                    'profile.lastname',
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created',
                        'filter' => \yii\jui\DatePicker::widget([
                            'model' => $searchModel,
                            'attribute' => 'created_at',
                            'options' => ['style' => 'width:86px'],
                        ]),
                        'value' => function ($data) {
                            return ($data->created_at == NULL) ? Yii::t('yii', '(not set)') : Yii::$app->formatter->asDate($data->created_at);
                        }
                    ],
                    [
                        'header' => 'Actions',
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {sendmail} ',
                        'options' => ['style' => 'width:56px; min-width:56px;'],
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fa fa-eye"></i>', $model->getUrl(), [
                                    'class' => 'btn btn-primary btn-xs tt',
                                    'title' => Yii::t('MassuserimportModule.base', 'View user')
                                ]);
                            },
                            'sendmail' => function ($url, $model) {
                                $confirm_link = \humhub\widgets\ModalConfirm::widget(array(
                                    'uniqueID' => 'modal_recoverpassword' . $model->id,
                                    'linkOutput' => 'a',
                                    'cssClass' => 'btn btn-primary btn-xs tt" title="' . Yii::t('MassuserimportModule.base', 'Send recover password email'),
                                    'title' => Yii::t('MassuserimportModule.base', "<strong>Confirm</strong> recover password action."),
                                    'message' => Yii::t('MassuserimportModule.base', 'This will create a 24-hour recover password token and send a recover link for %email%.', ['%email%' => $model->email]),
                                    'buttonTrue' => Yii::t('MassuserimportModule.base', 'Ok'),
                                    'buttonFalse' => Yii::t('MassuserimportModule.base', 'Cancel'),
                                    'linkContent' => '<i class="fa fa-envelope"></i>',
                                    'linkHref' => Url::toRoute([
                                        'recover-password',
                                        'email' => $model->email,
                                        'doit' => 2
                                    ])
                                ));
                                return $confirm_link;
                            }
                        ]
                    ]
                ]
            ]);
        } else { ?>
            <hr/>
            <p>
                <?php echo Yii::t('MassuserimportModule.base', 'There are currently no imported users.'); ?>
            </p>
        <?php } ?>
    </div>
</div>