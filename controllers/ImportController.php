<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\massuserimport\models\Csv;
use yii\web\UploadedFile;
use yii\helpers\Url;
use humhub\modules\massuserimport\components\CsvParser;
use humhub\modules\massuserimport\components\ErrorGenerator;
use humhub\modules\massuserimport\models\UserImportContainer;
use humhub\modules\user\models\forms\AccountRecoverPassword;
use humhub\modules\massuserimport\models\UserImportSearch;

/**
 * ImportController handles the mass user import requests.
 *
 * @package humhub.modules.massuserimport.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class ImportController extends Controller
{

    /**
     * Render the index page.
     * Accepts submitted csv files and generates users for each entry.
     *
     * @return string the rendered page.
     */
    public function actionIndex()
    {
        $csvModel = new Csv();
        
        // Generate users for each entry of a submitted csv file
        if (Yii::$app->request->isPost && UploadedFile::getInstance($csvModel, 'csvFile') != null) {
            
            $csvModel->csvFile = UploadedFile::getInstance($csvModel, 'csvFile');
            $csvData = file_get_contents($csvModel->csvFile->tempName, "r");
            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData); // BOM in UTF-8
            $parserResult = CsvParser::parse(';', '.', UserImportContainer::className(), $csvData, []);
            $entries = $parserResult['result'];
            $csvModel->errors = $parserResult['errors'];
            
            $counter = 1;
            foreach ($entries as $entry) {
                // check if mail is in use
                $counter ++;
                $checkMsg = $this->checkExistingEntries($entry);
                if (! empty($checkMsg)) {
                    $csvModel->errors[] = ErrorGenerator::generateErrorMessage($checkMsg, $counter, $entry->user->email, ErrorGenerator::EMAIL_IN_USE);
                    continue;
                }
                try {
                    // fill username and password if they were not submitted
                    $entry->generateUsername();
                    $entry->generatePassword();
                    // will automatically send an email if the save was successfull
                    $entry->save();
                    if (! empty($entry->errors)) {
                        foreach ($entry->errors as $key => $value) {
                            switch ($key) {
                                case UserImportContainer::EXCEPTION_ERROR_KEY:
                                    $csvModel->errors[] = ErrorGenerator::generateErrorMessage("An Exception occurred - " . $value[0], $counter, $entry->user->email, ErrorGenerator::GENERAL);
                                    break;
                                case UserImportContainer::MAILING_ERROR_KEY:
                                    $csvModel->errors[] = ErrorGenerator::generateErrorMessage(null, $counter, $entry->user->email, ErrorGenerator::MAILER_ERROR);
                                    break;
                                default:
                                    $csvModel->errors[] = ErrorGenerator::generateErrorMessage("Property $key - $value[0]", $counter, $entry->user->email, ErrorGenerator::MODEL_ERROR);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $csvModel->errors[] = ErrorGenerator::generateErrorMessage($e->getMessage(), $counter, $entry->user->email, ErrorGenerator::GENERAL);
                }
            }
        }
        
        $searchModel = new UserImportSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'csvModel' => $csvModel,
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel
        ]);
    }

    /**
     * Check if a user with the given, unique attributes already exists.
     *
     * @param UserImportContainer $importContainer            
     * @return string error message if conflict occurred, empty string if not.
     */
    private function checkExistingEntries($importContainer)
    {
        if (! $importContainer->validateUniqueAttr()) {
            return Yii::t('MassuserimportModule.base', 'An user with this email adress already exists.');
        }
        return '';
    }

    /**
     * Trigger the users password recovery.
     * In the process the user will get an email with a recovery link.
     */
    public function actionRecoverPassword()
    {
        $model = new AccountRecoverPassword();
        $model->email = Yii::$app->request->get('email');
        
        if ($model->recover()) {
            return $this->redirect(Url::toRoute('/massuserimport/import/index'));
        }
        return $this->render('error', [
            'details' => Yii::t('MassuserimportModule.base', 'Password recovery failed.')
        ]);
    }

    /**
     * Trigger the download of the description file.
     */
    public function actionDownload()
    {
        $path = Yii::$app->getModule('massuserimport')->getBasePath() . '/resources';
        
        $file = $path . '/import_details.txt';
        
        if (file_exists($file)) {
            
            Yii::$app->response->sendFile($file);
        }
    }
}
