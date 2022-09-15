<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\models;

use humhub\models\Setting;
use humhub\modules\user\models\GroupUser;
use Yii;
use yii\base\Model;

/**
 * Container that holds all attributes to create a complete new user.
 * These are 'User', 'Password', 'Profile.'
 *
 * @package humhub.modules.massuserimport.models
 * @since 1.0
 * @author Sebastian Stumpf
 */
class UserImportContainer extends Model
{

    /**
     * @var MassuserimportUser
     */
    public $user;

    /**
     * @var MassuserimportPassword
     */
    public $password;

    /**
     * @var MassuserimportProfile
     */
    public $profile;

    /**
     * @var string This attribute contains the comma separated group ids the user should be added to
     */
    public $group_ids;

    /**
     * @var bool
     */
    public $autoConfirmPassword;

    // used for debugging -> models won't be saved
    /**
     * @var bool
     */
    public $ignorePasswordSave = false;
    /**
     * @var bool
     */
    public $ignoreUserSave = false;
    /**
     * @var bool
     */
    public $ignoreProfileSave = false;
    /**
     * @var bool
     */
    public $ignoreGroupsSave = false;

    const EXCEPTION_ERROR_KEY = 'exception_error_occurred';

    const MAILING_ERROR_KEY = 'mailing_error_occurred';
    
    const SCENARIO_CREATE = 'massuserimport_create';
    
    const SCENARIO_UPDATE = 'massuserimport_create';
    
    const ATTR_NAME_GROUP_IDS = 'group_ids';
    
    const SETTINGS_DEFAULT_SYS_EMAIL_ADDR = 'humhub-info@noreply.com';
    
    const SETTINGS_DEFAULT_SYS_EMAIL_NAME = 'Humhub Import Information';
    
    /**
     * Initialize container either with new instances or a given user.
     *
     * @see \yii\base\Object::init()
     */
    public function init(MassuserimportUser $user = null, $autoConfirmPassword = true, $imported = 1)
    {        
        if (empty($user)) {
            $this->autoConfirmPassword = $autoConfirmPassword;
            
            // init user model
            $this->user = new MassuserimportUser();
            $this->user->imported = $imported;
            $this->user->setScenario(self::SCENARIO_CREATE);
            
            // init password model
            $this->password = new MassuserimportPassword();
            $this->password->user_id = $this->user->id;
            $this->password->setScenario(self::SCENARIO_CREATE);
            
            // init profile model
            $this->profile = new MassuserimportProfile();
            $this->profile->user_id = $this->user->id;
            $this->profile->setScenario(self::SCENARIO_CREATE);
            $this->user->populateRelation('profile', $this->profile);
            
            // init user groups ids with default group id       
            $registrationGroups = \humhub\modules\user\models\Group::getRegistrationGroups();
            if (count($registrationGroups) == 1) {
                $this->group_ids = $registrationGroups[0]->id;
            }
        } else {
            //init user
            $this->user = $user;
            // init profile
            $this->profile->setScenario(self::SCENARIO_UPDATE);
            $this->profile = $this->user->profile;
            $this->profile->setScenario(self::SCENARIO_UPDATE);
            // init password
            $this->password = MassuserimportPassword::findOne([
                'user_id' => $this->user->id
            ]);
            $this->password->setScenario(self::SCENARIO_UPDATE);
            // group user ids will not be initialized in an scenario update
        }

        parent::init();
    }

    /**
     * Import the user by creating all necessary models.
     */
    public function save($sendMailAtSuccess = true)
    {
        if ($this->autoConfirmPassword) {
            $this->password->newPasswordConfirm = $this->password->newPassword;
        }
        $this->password->setPassword($this->password->newPassword);
        
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            if (! $this->ignoreUserSave && $this->user->validate()) {
                $this->user->save();
                $this->user->refresh();
            }
            if (! empty($this->user->id) && ! $this->ignorePasswordSave && $this->password->validate()) {
                $this->password->user_id = $this->user->id;
                $this->password->save();
            }
            if (! empty($this->user->id) && ! $this->ignoreProfileSave && $this->profile->validate()) {
                $this->profile->user_id = $this->user->id;
                $this->profile->save();
            }
            if (! empty($this->user->id) && ! $this->ignoreGroupsSave) {
                $currentGroupIds = [];
                foreach($this->user->groups as $group) {
                    $currentGroupIds[] = $group->id;
                }
                $newGroupIds = is_string($this->group_ids) && strlen($this->group_ids) > 0 ? explode(',', $this->group_ids) : [];

                // cleanup array a bit
                $newGroupIds = array_map("trim", $newGroupIds);
                $newGroupIds = array_unique($newGroupIds);
                $newGroupIds = array_filter($newGroupIds);

                // check which groups have to be added and removed
                $groupsToRemove = array_diff($currentGroupIds, $newGroupIds);
                $groupsToAdd = array_diff($newGroupIds, $currentGroupIds);

                $groupErrors = [];
                // remove the users groups not specified in groups array
                foreach($groupsToRemove as $groupId) {
                    $groupUser = GroupUser::findOne([
                        'user_id' => $this->user->id,
                        'group_id' => $groupId
                    ]);
                    if($groupUser) {
                        $groupUser->delete();
                    }
                }
                // add groups specified in groups array the user is not already in
                foreach($groupsToAdd as $groupId) {
                    $groupUser = new GroupUser();
                    $groupUser->group_id = $groupId;
                    $groupUser->user_id = $this->user->id;
                    $groupUser->save();
                    // gather occurring errors
                    $groupErrors = array_merge($groupErrors, $groupUser->errors);
                }
                // add errors to error pool
                $this->addErrors($groupErrors);
            }
        } catch (Exception $e) {
            $this->addError(self::EXCEPTION_ERROR_KEY, $e->getMessage());
        }
        
        $this->addErrors(array_merge($this->user->errors, $this->password->errors, $this->profile->errors));
        
        // rollback transaction if something went wrong
        if (!empty($this->errors)) {
            $transaction->rollBack();
            return;
        }
        if (!Setting::Get('noNotificationMail', 'massuserimport')) {
            if (!$this->sendImportMail()) {
                $this->addError(self::MAILING_ERROR_KEY, null);
                $transaction->rollBack();
                return;
            }
        }
        $transaction->commit();
    }

    public function attributes()
    {
        $userAttributes = $this->user->attributes();
        $passwordAttributes = $this->password->attributes();
        $profileAttributes = $this->profile->attributes();
        $additionalAttributes = [];
        
        foreach ($userAttributes as $key => $value) {
            $userAttributes[$key] = "user.$value";
        }
        foreach ($passwordAttributes as $key => $value) {
            $passwordAttributes[$key] = "password.$value";
        }
        foreach ($profileAttributes as $key => $value) {
            $profileAttributes[$key] = "profile.$value";
        }
        
        // add additional attribute used to store the group ids
        $additionalAttributes[] = self::ATTR_NAME_GROUP_IDS;
        
        return array_merge($userAttributes, $passwordAttributes, $profileAttributes, $additionalAttributes);
    }

    public function safeAttributes()
    {
        $userAttributes = $this->user->safeAttributes();
        $passwordAttributes = $this->password->safeAttributes();
        $profileAttributes = $this->profile->safeAttributes();
        $additionalAttributes = [];
        
        foreach ($userAttributes as $key => $value) {
            $userAttributes[$key] = "user.$value";
        }
        foreach ($passwordAttributes as $key => $value) {
            $passwordAttributes[$key] = "password.$value";
        }
        foreach ($profileAttributes as $key => $value) {
            $profileAttributes[$key] = "profile.$value";
        }
        
        // add additional attribute used to store the group ids
        $additionalAttributes[] = self::ATTR_NAME_GROUP_IDS;
        
        return array_merge($userAttributes, $passwordAttributes, $profileAttributes, $additionalAttributes);
    }

    public function sendImportMail()
    {
        $mail = Yii::$app->mailer->compose([
            'html' => '@humhub/modules/massuserimport/views/mails/ImportUser'
        ], [
            'model' => $this
        ]);
        $settingsManager = Yii::$app->settings;
        $systemEmailAddress = $settingsManager->get('mailer.systemEmailAddress');
        $systemEmailAddress = empty($systemEmailAddress) ? self::SETTINGS_DEFAULT_SYS_EMAIL_ADDR : $systemEmailAddress;
        $systemEmailName = $settingsManager->get('mailer.systemEmailName');
        $systemEmailName = empty($systemEmailName) ? self::SETTINGS_DEFAULT_SYS_EMAIL_NAME : $systemEmailName;
        $mail->setFrom([
            $systemEmailAddress => $systemEmailName
        ]);
        $mail->setTo($this->user->email);
        $mail->setSubject(Yii::t('MassuserimportModule.base', 'Welcome to %appName%', array(
            '%appName%' => Yii::$app->name
        )));
        
        return $mail->send();
    }

    public function generatePassword($override = false)
    {
        if (empty($this->password->newPassword) || $override) {
            $this->password->newPassword = base64_encode(openssl_random_pseudo_bytes(12));
            $this->password->newPasswordConfirm = $this->password->newPassword;
        }
    }

    public function generateUsername()
    {
        $username = '';
        $number = 1;
        if (! empty($this->user->username)) {
            $username = $this->user->username;
            // search a unique name on base of the given username
            while (MassuserimportUser::findOne([
                'username' => $username
            ]) !== null) {
                $username = $this->user->username . ++ $number;
            }
        } else 
            if (! empty($this->profile->firstname) && ! empty($this->profile->lastname)) {
                $affixCounter = 1;
                $username = substr($this->profile->firstname, 0, 1) . $this->profile->lastname;
                // search a unique name on base of firstname and lastname
                while (MassuserimportUser::findOne([
                    'username' => $username
                ]) !== null) {
                    if ($affixCounter >= strlen($this->profile->firstname)) {
                        $number ++;
                    } else {
                        $affixCounter ++;
                    }
                    $username = substr($this->profile->firstname, 0, $affixCounter) . $this->profile->lastname . ($number == 1 ? '' : $number);
                }
            }
        $this->user->username = $username;
    }

    /**
     * Check if a user with the given, unique attributes already exists.
     *
     * @return false if the unique attributes were invalid, else true.
     */
    public function validateUniqueAttr()
    {
        $record = MassuserimportUser::findOne([
            'email' => $this->user->email
        ]);
        if ($record != null) {
            return false;
        }
        return true;
    }
}