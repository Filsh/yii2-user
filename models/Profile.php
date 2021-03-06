<?php

namespace filsh\yii2\user\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "profile".
 *
 * @property integer $id
 * @property string $user_id
 * @property string $first_name
 * @property string $last_name
 * @property integer $birth_day
 * @property integer $birth_month
 * @property integer $birth_year
 * @property string $gender
 * @property integer $create_time
 * @property integer $update_time
 *
 * @property User $user
 */
class Profile extends ActiveRecord
{
    public static $genderMap = [
        0 => 'none',
        1 => 'male',
        2 => 'female',
        3 => 'other'
    ];
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%profile}}';
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
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
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
        return [
            [['user_id'], 'required', 'except' => [User::SCENARIO_REGISTER]],
            [['user_id', 'birth_day', 'birth_month', 'birth_year'], 'integer'],
            [['gender'], 'filter', 'filter' => function($value) {
                $none = self::$genderMap[0];
                if(empty($value)) {
                    return $none;
                } else if(is_int($value)) {
                    return isset(self::$genderMap[$value]) ? self::$genderMap[$value] : $none;
                } else if(!in_array($value, self::$genderMap)) {
                    return $none;
                }
                return $value;
            }],
            [['gender'], 'string'],
            [['gender'], 'in', 'range' => self::$genderMap],
            [['first_name', 'last_name'], 'string', 'max' => 255]
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return array_merge(parent::scenarios(), [
            User::SCENARIO_REGISTER => ['user_id', 'first_name', 'last_name', 'birth_day', 'birth_month', 'birth_year', 'gender']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'birth_day' => 'Birth Day',
            'birth_month' => 'Birth Month',
            'birth_year' => 'Birth Year',
            'gender' => 'Gender',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        $user = Yii::$app->getModule('user')->model('User');
        return $this->hasOne($user::className(), ['id' => 'user_id']);
    }

    /**
     * Register a new profile for user
     *
     * @param int $userId
     * @return static
     */
    public function register($userId)
    {
        $this->user_id = $userId;
        $this->save();
        return $this;
}

    /**
     * Set user id for profile
     *
     * @param int $userId
     * @return static
     */
    public function setUser($userId)
    {
        $this->user_id = $userId;
        return $this;
    }
}