<?php

namespace filsh\yii2\user\models\forms;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    /**
     * @var string Username and/or email
     */
    public $username;

    /**
     * @var string Password
     */
    public $password;

    /**
     * @var bool If true, users will be logged in for $loginDuration
     */
    public $rememberMe = true;

    /**
     * @var \filsh\yii2\user\models\User
     */
    protected $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['username', 'validateUser'],
            ['username', 'validateUserStatus'],
            ['password', 'validatePassword'],
            ['rememberMe', 'boolean'],
        ];
    }

    /**
     * Validate user
     */
    public function validateUser()
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addError('username', Yii::t('app', '{username} not found', ['username' => $this->getAttributeLabel('username')]));
        }
    }

    /**
     * Validate user status
     */
    public function validateUserStatus()
    {
        // define variables
        $user = $this->getUser();

        // check for ban status
        if ($user->ban_time) {
            $this->addError('username', 'User is banned - ' . $user->ban_reason);
        }
        // check for inactive status and resend email
        if ($user->status == $user::STATUS_INACTIVE) {
            /** @var \filsh\yii2\user\models\Userkey $userkey */
            $userkey = Yii::$app->getModule('user')->model('Userkey');
            $userkey = $userkey::generate($user->id, $userkey::TYPE_EMAIL_ACTIVATE);
            $user->sendEmailConfirmation($userkey);
            $this->addError('username', 'Email confirmation resent');
        }
    }

    /**
     * Validate password
     */
    public function validatePassword()
    {
        // skip if there are already errors
        if ($this->hasErrors()) {
            return;
        }

        // check password
        /** @var \filsh\yii2\user\models\User $user */
        $user = $this->getUser();
        if (!$user->verifyPassword($this->password)) {
            $this->addError('password', 'Incorrect password');
        }
    }

    /**
     * Get user based on email and/or username
     *
     * @return \filsh\yii2\user\models\User|null
     */
    public function getUser()
    {
        // check if we need to get user
        if ($this->_user === false) {

            // build query based on email and/or username login properties
            $user = Yii::$app->getModule('user')->model('User');
            $user = $user::find();
            if (Yii::$app->getModule('user')->loginEmail) {
                $user->orWhere(['email' => $this->username]);
            }
            if (Yii::$app->getModule('user')->loginUsername) {
                $user->orWhere(['username' => $this->username]);
            }

            // get and store user
            $this->_user = $user->one();
        }

        // return stored user
        return $this->_user;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        // calculate attribute label for 'username'
        // calculate error message
        if (Yii::$app->getModule('user')->loginEmail && Yii::$app->getModule('user')->loginUsername) {
            $username = 'Email/username';
        } elseif (Yii::$app->getModule('user')->loginEmail) {
            $username = 'Email';
        } else {
            $username = 'Username';
        }
        
        return [
            'username' => $username,
            'password' => 'Password'
        ];
    }

    /**
     * Validate and log user in
     *
     * @param int $duration
     * @return bool
     */
    public function login($duration = 0)
    {
        if ($this->validate()) {
            $duration = $this->rememberMe ? $duration : 0;
            return Yii::$app->user->login($this->getUser(), $duration);
        }

        return false;
    }
}