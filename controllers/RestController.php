<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\massuserimport\controllers;

use Yii;
use humhub\modules\massuserimport\models\ExtendedUserInvite;
use humhub\modules\massuserimport\models\Csv;
use yii\web\UploadedFile;
use yii\web\Response;
use humhub\modules\massuserimport\models\ExtendedUserInviteSearch;
use yii\helpers\Url;
use humhub\modules\massuserimport\components\CsvParser;
use humhub\modules\massuserimport\models\User;
use humhub\modules\massuserimport\components\ErrorGenerator;
use humhub\modules\massuserimport\models\UserImportContainer;
use humhub\modules\user\models\forms\AccountRecoverPassword;
use humhub\modules\admin\models\UserImportSearch;
use humhub\modules\massuserimport\models\MassuserimportPassword;
use humhub\modules\user\models\Profile;
use yii\web\HttpException;
use Zend\Validator\InArray;
use humhub\models\Setting;
use humhub\modules\massuserimport\models\MassuserimportUser;
use humhub\modules\massuserimport\models\MassuserimportProfile;
use humhub\components\behaviors\AccessControl;

/**
 * RestController offers a rest json API to create delete and update users and their profiles.
 *
 * @package humhub.modules.massuserimport.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class RestController extends \humhub\modules\admin\components\Controller
{

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    public function init()
    {
        Yii::$app->request->setBodyParams(NULL);
        Yii::$app->request->parsers = [
            '*' => 'yii\web\JsonParser'
        ];
        Yii::$app->response->format = Response::FORMAT_JSON;

        return parent::init();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \yii\web\Controller::beforeAction()
     */
    public function beforeAction($action)
    {
        // use the parent controllers access behaviors for all not rest actions
        if ($action->id === 'api-documentation') {
            return parent::beforeAction($action);
        }
        // use the api key check for all rest actions
        if (!$this->checkAccess()) {
            throw new HttpException(403, Yii::t('MassuserimportModule.base', 'Wrong password!'));
        }
        return true;
    }

    /**
     * Check if the incoming request has access to the database.
     */
    public function checkAccess()
    {
        if (!Setting::Get('activateJsonRestApi', 'massuserimport')) {
            throw new HttpException(404, Yii::t('MassuserimportModule.base', 'JSON REST API is not activated!'));
        }

        $givenPassword = $this->getParamFromGetPostBody('apipassword');

        if ($givenPassword === NULL) {
            return false;
        } else {
            return $givenPassword === Setting::Get('jsonRestApiPassword', 'massuserimport');
        }
    }

    /**
     * Render the markdown API documentation.
     * 
     * @throws HttpException MassuserimportApiDocumentation.md could not be found.
     * @return string the rendered view.
     */
    public function actionApiDocumentation()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $path = Yii::$app->getModule('massuserimport')->getBasePath() . '/resources';

        $file = $path . '/MassuserimportApiDocumentation.md';

        if (file_exists($file)) {
            return $this->render('doc', array(
                        'markdown' => file_get_contents($file)
            ));
        } else {
            throw new HttpException(404, Yii::t('MassuserimportModule.base', 'Documentation could not be found!'));
        }
    }

    /**
     * Render a list of available rest actions and options.
     *
     * @return json: a list of all users.
     */
    public function actionIndex()
    {
        $safeAttributes = [];
        $safeAttributes['profile'] = [];
        $safeAttributes['user'] = [];
        $safeAttributes['password'] = [];

        $dummy = new MassuserimportUser();
        $dummy->setScenario('massuserimport_create');
        $safeAttributes['user']['massuserimport_create'] = $dummy->safeAttributes();
        $dummy->setScenario('massuserimport_update');
        $safeAttributes['user']['massuserimport_update'] = $dummy->safeAttributes();

        $dummy = new MassuserimportProfile();
        $dummy->setScenario('massuserimport_create');
        $safeAttributes['profile']['massuserimport_create'] = $dummy->safeAttributes();
        $dummy->setScenario('massuserimport_update');
        $safeAttributes['profile']['massuserimport_update'] = $dummy->safeAttributes();

        $dummy = new MassuserimportPassword();
        $dummy->setScenario('massuserimport_create');
        $safeAttributes['password']['massuserimport_create'] = $dummy->safeAttributes();
        $dummy->setScenario('massuserimport_update');
        $safeAttributes['password']['massuserimport_update'] = $dummy->safeAttributes();

        return [
            'list' => [
                'required_fields' => [
                    'apipassword' => ""
                ],
                'response' => 'array of all user data'
            ],
            'view' => [
                'required_fields' => [
                    'email',
                    'id',
                    'guid',
                    'username',
                    'apipassword' => ""
                ],
                'information' => 'Apipassword is always needed, only one of the other required fields is needed.'
            ],
            'create' => [
                'required_fields' => [
                    'user' => [
                        'email'
                    ],
                    'profile' => [
                        'firstname',
                        'lastname'
                    ],
                    'apipassword' => ""
                ],
                'all_editable_fields' => [
                    'user' => $safeAttributes['user']['massuserimport_create'],
                    'profile' => $safeAttributes['profile']['massuserimport_create'],
                    'password' => $safeAttributes['password']['massuserimport_create'],
                    'group_ids' => 'comma separated list of assigned groups'
                ],
                'information' => 'Please note: If you provide no password a safe one will be generated. If you provide no username, it will be generated from the firstname and lastname. If the given username is not unique, it will be slightly changed to a unique one. The only required parameters are user.email | profile.firstname | profile.lastname. The created user will be informed via email about his new account.'
            ],
            'update' => [
                'required_fields' => [
                    'user' => [
                        'id',
                        'guid'
                    ],
                    'apipassword' => ""
                ],
                'all_editable_fields' => [
                    'user' => $safeAttributes['user']['massuserimport_update'],
                    'profile' => $safeAttributes['profile']['massuserimport_update'],
                    'password' => $safeAttributes['password']['massuserimport_update'],
                    'group_ids' => 'comma separated list of assigned groups'
                ],
                'information' => 'Please note: Only one of the user identifiers (guid, id) is needed. You can only change the password if you provide the old one.'
            ],
            'delete' => [
                'required_fields' => [
                    'user' => [
                        'id',
                        'guid'
                    ],
                    'apipassword' => ""
                ],
                'information' => 'Please note: Only one of the user identifiers (guid, id) is needed. You can only change the password if you provide the old one.'
            ]
        ];
    }

    /**
     * Render a list of all users.
     *
     * @return json: a list of all users.
     */
    public function actionList()
    {
        $users = MassuserimportUser::find()->all();
        $entries = [];
        foreach ($users as $user) {
            $group_ids = "";
            foreach ($user->groups as $group) {
                $group_ids .= "$group->id,";
            }
            $group_ids = rtrim($group_ids, ',');
            $entries['users'][] = [
                'user' => $user,
                'profile' => $user->profile,
                'group_ids' => $group_ids
            ];
        }
        return $entries;
    }

    /**
     * Accepts a json with a id|email|username|guid and renders the user data as json if the id is valid.
     *
     * @return json: a list of all users.
     */
    public function actionView()
    {
        $user = $this->loadUser();

        if ($user == NULL) {
            return [
                'name' => 'Error.',
                'message' => 'User could not be found in the database.',
                'details' => [
                    'url-params' => Yii::$app->request->get(),
                    'body-params' => Yii::$app->request->getBodyParams()
                ],
                'status' => 404,
                'code' => 0
            ];
        }

        $group_ids = "";
        foreach ($user->groups as $group) {
            $group_ids .= "$group->id,";
        }
        $group_ids = rtrim($group_ids, ',');
        return [
            'user' => $user,
            'profile' => empty($user) ? null : $user->profile,
            'group_ids' => $group_ids
        ];
    }

    /**
     * Accepts a json with combined user / profile / password data and creates a user if the data is valid.
     *
     *
     * @return json: status of the operation.
     */
    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $container = new UserImportContainer();
        $container->init(null, false);

        if (is_array($params)) {
            if (array_key_exists('user', $params)) {
                $container->user->load($params['user'], '');
            }
            if (array_key_exists('profile', $params)) {
                $container->profile->load($params['profile'], '');
            }
            if (array_key_exists('group_ids', $params)) {
                $container->group_ids = $params['group_ids'];
            }
            // a password must be provided or generated if an user user created
            if (array_key_exists('password', $params)) {
                if (array_key_exists('newPassword', $params['password'])) {
                    $container->password->load($params['password'], '');
                } else {
                    $container->generatePassword();
                }
            } else {
                $container->generatePassword();
            }
            // generate username if it was empty or not unique
            $container->generateUsername();
        }

        // var_dump($params);

        $container->save();
        if (empty($container->errors)) {
            return [
                'success' => true,
                'message' => Yii::t('MassuserimportModule.base', 'The user was successfully created with id %id%.', [
                    '%id%' => $container->user->id
                ])
            ];
        } else {
            return [
                'name' => 'Errors occurred.',
                'message' => 'Errors occurred. The user could not be created.',
                'details' => $container->errors,
                'status' => 400,
                'code' => 0
            ];
        }
    }

    /**
     * Accepts a json with combined user / profile / password data and updates a user if the data is valid.
     *
     * @return json: status of the operation.
     */
    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $container = new UserImportContainer();
        $container->init($this->loadUserFromUserJson(), false, 0);

        $container->user->load($params['user'], '');
        if (array_key_exists('profile', $params)) {
            $container->profile->load($params['profile'], '');
        }
        if (array_key_exists('group_ids', $params)) {
            $container->group_ids = $params['group_ids'];
        }
        if (array_key_exists('password', $params)) {
            if (array_key_exists('newPassword', $params['password'])) {
                $container->password->load($params['password'], '');
            }
        } else {
            $container->ignorePasswordSave = true;
        }

        $container->save();
        if (empty($container->errors)) {
            return [
                'success' => true,
                'message' => Yii::t('MassuserimportModule.base', 'The user was successfully updated.')
            ];
        } else {
            return [
                'name' => 'Errors occurred.',
                'message' => 'Errors occurred. The user could not be updated.',
                'details' => $container->errors,
                'status' => 400,
                'code' => 0
            ];
        }
    }

    /**
     * Accepts a json with a user id and deletes the user if the id is valid.
     *
     * @return json: status of the operation.
     */
    public function actionDelete()
    {
        $user = $this->loadUser();
        if (empty($user)) {
            throw new HttpException(400, Yii::t('MassuserimportModule.base', 'User not found!'));
        }

        $message = Yii::t('MassuserimportModule.base', 'The user was successfully deleted.');
        $ownerSpaces = [];
        foreach (\humhub\modules\space\models\Membership::GetUserSpaces($user->id) as $space) {
            if ($space->isSpaceOwner($user->id)) {
                $ownerSpaces[] = $space;
            }
        }
        // if user is spaceowner of at least one space
        if (sizeof($ownerSpaces) > 0) {
            $newSpaceOwnerEmail = $this->getParamFromGetPostBody('newspaceowneremail');
            if ($newSpaceOwnerEmail === NULL) {
                throw new HttpException(400, Yii::t('MassuserimportModule.base', 'The user you want to delete is the owner of some spaces. Provide the email address of a valid new owner for them!'));
            }
            if ($newSpaceOwnerEmail == $user->email) {
                throw new HttpException(400, Yii::t('MassuserimportModule.base', 'The email for the new space owner must not be the one of the user to delete.'));
            }
            $newSpaceOwner = \humhub\modules\user\models\User::findOne([
                        'email' => $newSpaceOwnerEmail
            ]);
            if (empty($newSpaceOwner)) {
                throw new HttpException(400, Yii::t('MassuserimportModule.base', 'The user owing the email for the new space owner does not exist.'));
            }
            foreach ($ownerSpaces as $space) {
                $space->addMember($newSpaceOwner->id);
                $space->setSpaceOwner($newSpaceOwner->id);
            }
            $message .= ' ' . Yii::t('MassuserimportModule.base', 'The user with email %newspaceowner% overtakes the ownership of the user\'s spaces.', ['%newspaceowner%' => $newSpaceOwnerEmail]);
        }

        $user->delete();

        return [
            'success' => true,
            'message' => $message
        ];
    }

    private function getParamFromGetPostBody($name, $defaultValue = NULL)
    {
        if (Yii::$app->request->get($name)) {
            $value = Yii::$app->request->get($name);
        } else if (Yii::$app->request->post($name)) {
            $value = Yii::$app->request->post($name);
        } else if (Yii::$app->request->getBodyParam($name)) {
            $value = Yii::$app->request->getBodyParam($name);
        } else {
            return $defaultValue;
        }
        return $value;
    }

    private function loadUser()
    {
        $user = $this->loadUserFromSimpleParams();
        if ($user === NULL) {
            $user = $this->loadUserFromUserJson();
        }
        return $user;
    }

    /**
     * Loads a user from given get or post parameters.
     *
     * @throws HttpException if the user wasnt found.
     * @return the user
     */
    private function loadUserFromSimpleParams()
    {
        $id = $this->getParamFromGetPostBody('id');
        $guid = !empty($id) ? null : $this->getParamFromGetPostBody('guid');
        $email = !empty($guid) ? null : $this->getParamFromGetPostBody('email');
        $username = !empty($email) ? null : $this->getParamFromGetPostBody('username');

        $query = MassuserimportUser::find();
        if ($id) {
            $query = $query->where([
                'id' => $id
            ]);
        } elseif ($guid) {
            $query = $query->orWhere([
                'guid' => $guid
            ]);
        } elseif ($email) {
            $query = $query->orWhere([
                'email' => $email
            ]);
        } elseif ($username) {
            $query = $query->orWhere([
                'username' => $username
            ]);
        } else {
            return NULL;
        }
        $user = $query->one();
        return $user;
    }

    /**
     * Loads a user from a given user json.
     *
     * @throws HttpException if the user wasnt found.
     * @return the user
     */
    private function loadUserFromUserJson()
    {
        $params = Yii::$app->request->getBodyParams();
        $user = NULL;
        $guid = NULL;
        $id = NULL;
        $email = NULL;
        $username = NULL;
        if (is_array($params)) {
            if (array_key_exists('user', $params)) {
                if (array_key_exists('id', $params['user']) && !empty($params['user']['id'])) {
                    $id = $params['user']['id'];
                } elseif (array_key_exists('guid', $params['user']) && !empty($params['user']['guid'])) {
                    $guid = $params['user']['guid'];
                } elseif (array_key_exists('email', $params['user']) && !empty($params['user']['email'])) {
                    $email = $params['user']['email'];
                } elseif (array_key_exists('username', $params['user']) && !empty($params['user']['username'])) {
                    $username = $params['user']['username'];
                }
            }
            $user = MassuserimportUser::find()->where([
                        'id' => $id
                    ])
                    ->orWhere([
                        'guid' => $guid
                    ])
                    ->orWhere([
                        'email' => $email
                    ])
                    ->orWhere([
                        'username' => $username
                    ])
                    ->one();
        }
        return $user;
    }

}
