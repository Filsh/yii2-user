<?php

namespace filsh\yii2\user\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\swiftmailer\Mailer;
use yii\helpers\Inflector;
use yii\helpers\Security;
use ReflectionClass;

/**
 * User model
 *
 * @property int $id
 * @property int $role_id
 * @property string $email
 * @property string $new_email
 * @property string $username
 * @property string $password
 * @property int $status
 * @property string $auth_key
 * @property string $api_key
 * @property string $create_time
 * @property string $update_time
 * @property string $ban_time
 * @property string $ban_reason
 * @property string $registration_ip
 * @property string $login_ip
 * @property string $login_time
 *
 * @property Profile $profile
 * @property Role $role
 * @property Userkey[] $userkeys
 */
class User extends ActiveRecord implements IdentityInterface
{
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_ACCOUNT = 'account';
    const SCENARIO_ADMIN = 'admin';
    
    /**
     * @var int Inactive status
     */
    const STATUS_INACTIVE = 0;

    /**
     * @var int Active status
     */
    const STATUS_ACTIVE = 1;

    /**
     * @var int Unconfirmed email status
     */
    const STATUS_UNCONFIRMED_EMAIL = 2;
    
    /**
     * @var string Current password - for account page updates
     */
    public $currentPassword;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'create_time',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'update_time',
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        // set initial rules
        $rules = [
            // general email and username rules
            [['email', 'username'], 'string', 'max' => 255],
            [['email', 'username'], 'unique'],
            [['email', 'username'], 'filter', 'filter' => 'trim'],
            [['email'], 'email'],
            [['username'], 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => '{attribute} can contain only letters, numbers, and \'_\'.'],
            // password rules
            [['password'], 'string', 'min' => 3],
            [['password'], 'filter', 'filter' => 'trim'],
            [['password'], 'required', 'on' => [self::SCENARIO_REGISTER]],
            // account page
            [['currentPassword'], 'required', 'on' => [self::SCENARIO_ACCOUNT]],
            [['currentPassword'], 'validateCurrentPassword', 'on' => [self::SCENARIO_ACCOUNT]],
            // admin crud rules
            [['role_id', 'status'], 'required', 'on' => [self::SCENARIO_ADMIN]],
            [['role_id', 'status'], 'integer', 'on' => [self::SCENARIO_ADMIN]],
            [['ban_time'], 'integer', 'on' => [self::SCENARIO_ADMIN]],
            [['ban_reason'], 'string', 'max' => 255, 'on' => self::SCENARIO_ADMIN],
        ];

        // add required rules for email/username depending on module properties
        $requireFields = ['requireEmail', 'requireUsername'];
        foreach ($requireFields as $requireField) {
            if (Yii::$app->getModule('user')->$requireField) {
                $attribute = strtolower(substr($requireField, 7)); // 'email' or 'username'
                $rules[] = [$attribute, 'required'];
            }
        }

        return $rules;
    }
    
    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return array_merge(parent::scenarios(), [
            self::SCENARIO_REGISTER => ['email', 'username', 'password']
        ]);
    }

    /**
     * Validate password
     */
    public function validateCurrentPassword()
    {
        // check password
        if (!$this->verifyPassword($this->currentPassword)) {
            $this->addError('currentPassword', 'Current password incorrect');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_id' => 'Role ID',
            'email' => 'Email',
            'new_email' => 'New Email',
            'username' => 'Username',
            'password' => 'Password',
            'status' => 'Status',
            'auth_key' => 'Auth Key',
            'ban_time' => 'Ban Time',
            'ban_reason' => 'Ban Reason',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time'
        ];
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getUserkeys()
    {
        $userkey = Yii::$app->getModule('user')->model('Userkey');
        return $this->hasMany($userkey::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    /*
      public function getProfiles() {
      $profile = Yii::$app->getModule('user')->model('Profile');
      return $this->hasMany($profile::className(), ['user_id' => 'id']);
      }
     */

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getProfile()
    {
        $profile = Yii::$app->getModule('user')->model('Profile');
        return $this->hasOne($profile::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getRole()
    {
        $role = Yii::$app->getModule('user')->model('Role');
        return $this->hasOne($role::className(), ['id' => 'role_id']);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token)
    {
        return static::findOne(['api_key' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * Get a clean display name for the user
     *
     * @var string $default
     * @return string|int
     */
    public function getDisplayName($default = '')
    {
        // define possible names
        $possibleNames = [
            'username',
            'email',
        ];

        // go through each and return if valid
        foreach ($possibleNames as $possibleName) {
            if (!empty($this->$possibleName)) {
                return $this->$possibleName;
            }
        }

        return $default;
    }

    /**
     * Send email confirmation to user
     *
     * @param Userkey $userkey
     * @return int
     */
    public function sendEmailConfirmation($userkey)
    {
        // modify view path to module views
        /** @var Mailer $mailer */
        $mailer = Yii::$app->mail;
        $mailer->viewPath = Yii::$app->getModule('user')->emailViewPath;

        // send email
        $user = $this;
        $profile = $user->profile;
        $email = $user->new_email !== null ? $user->new_email : $user->email;
        $subject = Yii::$app->id . ' - Email confirmation';
        
        $numSent = $mailer->compose('confirmEmail', compact('subject', 'user', 'profile', 'userkey'))
            ->setTo($email)
            ->setSubject($subject)
            ->send();
        
        if($numSent === false) {
            Yii::error(sprintf('Failed to send email \'%s\' with subject \'%s\'', $email, $subject), __CLASS__);
            return false;
        }
        return true;
    }
    
    /**
     * Calculate whether we need to send confirmation email or log user in based on user's status
     */
    public function sendEmailOrLogin()
    {
        // determine userkey type to see if we need to send email
        /** @var \filsh\yii2\user\models\User $user */
        /** @var \filsh\yii2\user\models\Userkey $userkey */
        $userkeyType = null;
        $userkey = Yii::$app->getModule('user')->model('Userkey');
        if ($this->status == $this::STATUS_INACTIVE) {
            $userkeyType = $userkey::TYPE_EMAIL_ACTIVATE;
        } elseif ($this->status == $this::STATUS_UNCONFIRMED_EMAIL) {
            $userkeyType = $userkey::TYPE_EMAIL_CHANGE;
        }

        // check if we have a userkey type to process
        if ($userkeyType !== null) {
            $userkey = $userkey::generate($this->id, $userkeyType);
            $this->sendEmailConfirmation($userkey);
        }
        // log user in automatically
        else {
            Yii::$app->user->login($this, Yii::$app->getModule('user')->loginDuration);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if(parent::beforeSave($insert) === false) {
            return false;
        }
        
        // hash new password if set
        if ($this->password) {
            $this->encryptPassword();
        }

        // generate auth_key and api_key if needed
        if (!$this->auth_key) {
            $this->auth_key = Security::generateRandomKey();
        }
        if (!$this->api_key) {
            $this->api_key = Security::generateRandomKey();
        }


        // convert ban_time checkbox to date
        if ($this->ban_time) {
            $this->ban_time = date('Y-m-d H:i:s');
        }

        // ensure fields are null so they won't get set as empty string
        $nullAttributes = ['email', 'username', 'ban_time', 'ban_reason'];
        foreach ($nullAttributes as $nullAttribute) {
            $this->$nullAttribute = $this->$nullAttribute ? $this->$nullAttribute : null;
        }
        
        return true;
    }

    /**
     * Encrypt password into password
     *
     * @return static
     */
    public function encryptPassword()
    {
        $this->password = Security::generatePasswordHash($this->password);
        return $this;
    }

    /**
     * Validate password
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return Security::validatePassword($password, $this->password);
    }

    /**
     * Register a new user
     *
     * @param int $roleId
     * @param string $userIp
     * @return static
     */
    public function register($roleId, $userIp = null)
    {
        if($userIp === null) {
            $userIp = Yii::$app->request->userIP;
        }
        
        // set default attributes for registration
        $attributes = [
            'role_id' => $roleId,
            'registration_ip' => $userIp,
        ];

        // determine if we need to change status based on module properties
        $emailConfirmation = Yii::$app->getModule('user')->emailConfirmation;

        // set status inactive if email is required
        if ($emailConfirmation && Yii::$app->getModule('user')->requireEmail) {
            $attributes['status'] = static::STATUS_INACTIVE;
        }
        // set unconfirmed if email is set required
        else if(Yii::$app->getModule('user')->requireEmail) {
            $attributes['status'] = static::STATUS_UNCONFIRMED_EMAIL;
        }
        // set unconfirmed if email is set but NOT required
        elseif ($emailConfirmation && Yii::$app->getModule('user')->useEmail && $this->email) {
            $attributes['status'] = static::STATUS_UNCONFIRMED_EMAIL;
        }
        // set active otherwise
        else {
            $attributes['status'] = static::STATUS_ACTIVE;
        }

        // set attributes
        $this->setAttributes($attributes, false);

        // save and return
        // note: we assume that we have already validated (both $user and $profile)
        $this->save(false);
        return $this;
    }

    /**
     * Set login ip and time
     *
     * @param bool $save Save record
     * @return static
     */
    public function setLoginIpAndTime($save = true)
    {
        // set data
        $this->login_ip = Yii::$app->getRequest()->getUserIP();
        $this->login_time = date('Y-m-d H:i:s');

        // save and return
        // auth key is added here in case user doesn't have one set from registration
        // it will be calculated in [[before_save]]
        if ($save) {
            $this->save(false, ['login_ip', 'login_time', 'auth_key']);
        }
        return $this;
    }

    /**
     * Check and prepare for email change
     *
     * @return bool
     */
    public function checkAndPrepareEmailChange()
    {
        // check if user is removing email address
        // this only happens if $requireEmail = false
        if (trim($this->email) === '') {
            return false;
        }

        // check for change in email
        if ($this->email != $this->getOldAttribute('email')) {

            // change status
            $this->status = static::STATUS_UNCONFIRMED_EMAIL;

            // set new_email attribute and restore old one
            $this->new_email = $this->email;
            $this->email = $this->getOldAttribute('email');

            return true;
        }

        return false;
    }

    /**
     * Confirm user email
     *
     * @return static
     */
    public function confirm()
    {
        // update status
        $this->status = static::STATUS_ACTIVE;

        // update new_email if set
        if ($this->new_email) {
            $this->email = $this->new_email;
            $this->new_email = null;
        }

        // save and return
        $this->save(true, ['email', 'new_email', 'status']);
        return $this;
    }

    /**
     * Check if user can do specified $permission
     *
     * @param string $permission
     * @return bool
     */
    public function can($permission)
    {
        return $this->role->checkPermission($permission);
    }

    /**
     * Get list of statuses for creating dropdowns
     *
     * @return array
     */
    public static function statusDropdown()
    {
        // get data if needed
        static $dropdown;
        if ($dropdown === null) {

            // create a reflection class to get constants
            $refl = new ReflectionClass(get_called_class());
            $constants = $refl->getConstants();

            // check for status constants (e.g., STATUS_ACTIVE)
            foreach ($constants as $constantName => $constantValue) {

                // add prettified name to dropdown
                if (strpos($constantName, 'STATUS_') === 0) {
                    $prettyName = str_replace('STATUS_', '', $constantName);
                    $prettyName = Inflector::humanize(strtolower($prettyName));
                    $dropdown[$constantValue] = $prettyName;
                }
            }
        }

        return $dropdown;
    }
}