<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\massuserimport\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\massuserimport\models\ExtendedUserInvite;
use humhub\modules\massuserimport\models\Csv;
use yii\web\UploadedFile;
use humhub\modules\massuserimport\models\ExtendedUserInviteSearch;
use yii\helpers\Url;
use humhub\modules\massuserimport\components\CsvParser;
use humhub\modules\user\models\User;
use humhub\modules\massuserimport\components\ErrorGenerator;

/**
 * InviteController handles the mass user invite requests.
 *
 * @package humhub.modules.massuserimport.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class InviteController extends Controller
{

    /**
     * Returns a List of Users
     */
    public function actionIndex()
    {
        $model = new Csv();

        if (Yii::$app->request->isPost && UploadedFile::getInstance($model, 'csvFile') != null) {
            $model->csvFile = UploadedFile::getInstance($model, 'csvFile');
            $csvData = file_get_contents($model->csvFile->tempName, "r");
            $parserResult = CsvParser::parse(';', '.', ExtendedUserInvite::className(), $csvData, [
                        'user_originator_id' => Yii::$app->user->id,
                        'space_invite_id' => 1,
                        'source' => ExtendedUserInvite::SOURCE_MASS_INVITE,
                        'language' => Yii::$app->language
            ]);
            $entries = $parserResult['result'];
            $model->errors = $parserResult['errors'];

            $counter = 0;
            foreach ($entries as $entry) {
                // check if mail is in use
                $counter ++;
                $checkMsg = $this->checkExistingEntries($entry);
                if (!empty($checkMsg)) {
                    $model->errors[] = ErrorGenerator::generateErrorMessage($checkMsg, $counter, $entry->email, ErrorGenerator::EMAIL_IN_USE);
                    continue;
                }
                
                // use of transaction here as save has to be done before sendInviteMail AND has to be rolled back if sendInviteMail fails
                $transaction = Yii::$app->getDb()->beginTransaction();
                try {
                    $entry->save();
                    $transaction->commit();
                    if(!$entry->sendInviteMail()) {
                        $model->errors[] = ErrorGenerator::generateErrorMessage(Yii::t('MassuserimportModule.base', 'Invitation email could not be sent!'), $counter, $entry->email, ErrorGenerator::MAILER_ERROR);
                        $transaction->rollBack();                       
                    }
                } catch (\Exception $e) {
                    $model->errors[] = ErrorGenerator::generateErrorMessage($e->getMessage(), $counter, $entry->email, ErrorGenerator::MAILER_ERROR);
                    $transaction->rollBack();
                }
            }
        }

        $searchModel = new ExtendedUserInviteSearch();
        $searchModel->filterSource = ExtendedUserInvite::SOURCE_MASS_INVITE;
        $dataProviderMassInvite = $searchModel->search(Yii::$app->request->queryParams);
        $searchModel->filterSource = ExtendedUserInvite::SOURCE_INVITE;
        $dataProviderInvite = $searchModel->search(Yii::$app->request->queryParams);
        $searchModel->filterSource = ExtendedUserInvite::SOURCE_SELF;
        $dataProviderSelf = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                    'dataProviderMassInvite' => $dataProviderMassInvite,
                    'dataProviderInvite' => $dataProviderInvite,
                    'dataProviderSelf' => $dataProviderSelf
        ]);
    }

    /**
     * Delete invite
     */
    public function actionDelete()
    {
        $id = (int) Yii::$app->request->get('id');
        $doit = (int) Yii::$app->request->get('doit');

        $invite = ExtendedUserInvite::findOne([
                    'id' => $id
        ]);

        if ($invite == null) {
            throw new HttpException(404, Yii::t('AdminModule.controllers_UserController', 'Entry not found!'));
        }
        if ($doit == 2) {
            $this->forcePostRequest();
            $invite->delete();
        }
        return $this->redirect(Url::to([
                            '/massuserimport/invite'
        ]));
    }

    /**
     * Resend invitation email.
     */
    public function actionResend()
    {
        $id = (int) Yii::$app->request->get('id');
        $doit = (int) Yii::$app->request->get('doit');

        $invite = ExtendedUserInvite::findOne([
                    'id' => $id
        ]);

        if ($invite == null) {
            throw new HttpException(404, Yii::t('AdminModule.controllers_UserController', 'Entry not found!'));
        }
        if ($doit == 2) {
            $this->forcePostRequest();
            $invite->sendInviteMail();
        }
        return $this->redirect(Url::to([
                            '/massuserimport/invite'
        ]));
    }

    /**
     * Check if a user with the given, unique attributes already exists.
     *
     * @param ExtendedUserInvite $invite            
     * @return string error message if conflict occurred, empty string if not.
     */
    private function checkExistingEntries($invite)
    {
        $record = ExtendedUserInvite::findOne([
                    'email' => $invite->email
        ]);
        if ($record != null) {
            return Yii::t('MassuserimportModule.base', 'An invitation has already been sent. If you want to resend it use the manual resend button.');
        }
        $record = User::findOne([
                    'email' => $invite->email
        ]);
        if ($record != null) {
            return Yii::t('MassuserimportModule.base', 'An user with this email adress already exists.');
        }
        return "";
    }

    /**
     * Trigger the download of the description file.
     */
    public function actionDownload()
    {
        $path = Yii::$app->getModule('massuserimport')->getBasePath() . '/resources';

        $file = $path . '/invite_details.txt';

        if (file_exists($file)) {

            Yii::$app->response->sendFile($file);
        }
    }

}
