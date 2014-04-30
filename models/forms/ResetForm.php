<?php

namespace filsh\yii2\user\models\forms;

use Yii;
use yii\base\Model;

/**
 * Reset password form
 */
class ResetForm extends Model
{
    /**
     * @var \filsh\yii2\user\models\Userkey
     */
    public $userkey;

    /**
     * @var string
     * @deprecated
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $passwordConfirm;

    /**
     * @var \filsh\yii2\user\models\User
     */
    protected $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        // set initial rules
        $rules = [
            // uncomment these lines if you want users to confirm their email address
            /*
              [['email'], 'required'],
              [['email'], 'email'],
              [['email'], 'validateUserkeyEmail'],
              [['email'], 'filter', 'filter' => 'trim'],
             */
            [['password', 'passwordConfirm'], 'required'],
            [['passwordConfirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match']
        ];

        // add and return user rules
        return $this->copyPasswordRules($rules);
    }

    /**
     * Copy password rules (min length, max length, etc) from user class
     *
     * @param $rules
     * @return array
     */
    protected function copyPasswordRules($rules)
    {
        // go through user rules
        $user = Yii::$app->getModule('user')->model('User');
        $userRules = $user->rules();
        foreach ($userRules as $rule) {

            // get first and second elements
            $attribute = $rule[0];
            $validator = trim(strtolower($rule[1]));

            // convert string to array if needed
            if (is_string($attribute)) {
                $attribute = [$attribute];
            }

            // check for password attribute and that it's not required
            if (in_array('password', $attribute) and $validator != 'required') {

                // overwrite the attribute
                $rule[0] = ['password'];

                // add to rules
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Validate proper email
     *
     * @deprecated
     */
    public function validateUserkeyEmail()
    {
        // compare user's email
        $user = $this->getUser();
        if (!$user or ($user->email !== $this->email)) {
            $this->addError('email', 'Incorrect email');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'password' => 'Password',
            'passwordConfirm' => 'Confirm Password',
        ];
    }

    /**
     * Get user based on userkey.user_id
     *
     * @return \filsh\yii2\user\models\User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $user = Yii::$app->getModule('user')->model('User');
            $this->_user = $user::findOne($this->userkey->user_id);
        }
        return $this->_user;
    }

    /**
     * Reset user's password
     *
     * @return bool
     */
    public function resetPassword()
    {
        // validate
        if ($this->validate()) {

            // update password
            $user = $this->getUser();
            $user->password = $this->password;
            $user->save(false);

            // consume userkey
            $userkey = $this->userkey;
            $userkey->consume();

            return true;
        }

        return false;
    }
}