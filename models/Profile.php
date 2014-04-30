<?php

namespace filsh\yii2\user\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Profile model
 *
 * @property int $id
 * @property int $user_id
 * @property string $create_time
 * @property string $update_time
 * @property string $full_name
 *
 * @property User $user
 */
class Profile extends ActiveRecord
{
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
    public function rules()
    {
        return [
            [['user_id'], 'required', 'except' => [User::SCENARIO_REGISTER]],
            [['user_id'], 'integer'],
            [['full_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'full_name' => 'Full Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getUser()
    {
        $user = Yii::$app->getModule('user')->model('User');
        return $this->hasOne($user::className(), ['id' => 'user_id']);
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
                ],
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
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
