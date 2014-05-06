<?php

use yii\db\Schema;
use filsh\yii2\user\models\Profile;
use filsh\yii2\user\models\Role;
use filsh\yii2\user\models\User;
use filsh\yii2\user\models\Userkey;

class m131114_141544_add_user extends \yii\db\Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        $transaction = $this->db->beginTransaction();
        try {
            $this->createTable(Role::tableName(), [
                'id' => Schema::TYPE_PK,
                'name' => Schema::TYPE_STRING . ' NOT NULL',
                'can_admin' => Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 0',
                'create_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'update_time' => Schema::TYPE_INTEGER . ' NOT NULL'
            ], $tableOptions);
            
            $this->createTable(User::tableName(), [
                'id' => Schema::TYPE_PK,
                'role_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'email' => Schema::TYPE_STRING . ' NOT NULL',
                'new_email' => Schema::TYPE_STRING . ' DEFAULT NULL',
                'username' => Schema::TYPE_STRING . ' DEFAULT NULL',
                'password' => Schema::TYPE_STRING . ' NOT NULL',
                'status' => Schema::TYPE_SMALLINT . ' NOT NULL',
                'auth_key' => Schema::TYPE_STRING . ' NOT NULL',
                'api_key' => Schema::TYPE_STRING . ' NOT NULL',
                'ban_time' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'ban_reason' => Schema::TYPE_STRING . ' DEFAULT NULL',
                'registration_ip' => Schema::TYPE_STRING . '(45) DEFAULT NULL',
                'login_ip' => Schema::TYPE_STRING . '(45) DEFAULT NULL',
                'login_time' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'create_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'update_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'UNIQUE KEY(`email`)',
                'UNIQUE KEY(`username`)',
                'FOREIGN KEY (`role_id`) REFERENCES ' . Role::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            $this->createTable(Userkey::tableName(), [
                'id' => Schema::TYPE_PK,
                'user_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'type' => Schema::TYPE_SMALLINT . ' NOT NULL',
                'key' => Schema::TYPE_STRING . ' NOT NULL',
                'consume_time' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'expire_time' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'create_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'update_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'UNIQUE KEY(`key`)',
                'FOREIGN KEY (`user_id`) REFERENCES ' . User::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            $this->createTable(Profile::tableName(), [
                'id' => Schema::TYPE_PK,
                'user_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'first_name' => Schema::TYPE_STRING . ' NOT NULL DEFAULT \'\'',
                'last_name' => Schema::TYPE_STRING . ' NOT NULL DEFAULT \'\'',
                'birth_day' => Schema::TYPE_SMALLINT . ' DEFAULT NULL',
                'birth_month' => Schema::TYPE_SMALLINT . ' DEFAULT NULL',
                'birth_year' => Schema::TYPE_SMALLINT . ' DEFAULT NULL',
                'gender' => 'ENUM(\'none\', \'male\', \'female\') NOT NULL',
                'create_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'update_time' => Schema::TYPE_INTEGER . ' NOT NULL',
                'FOREIGN KEY (`user_id`) REFERENCES ' . User::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            // insert role data
            // note: i create a guest role because i like to give guest users the ability to use the site
            //       without registering. you can delete it if you want
            $this->batchInsert(filsh\yii2\user\models\Role::tableName(), ['name', 'can_admin', 'create_time', 'update_time'], [
                ['Admin', 1, 'NOW()', 'NOW()'],
                ['User', 0, 'NOW()', 'NOW()'],
                ['Guest', 0, 'NOW()', 'NOW()'],
            ]);
            
            $transaction->commit();
        } catch (Exception $e) {
            echo 'Exception: ' . $e->getMessage() . '\n';
            $transaction->rollback();

            return false;
        }

        return true;
    }

    public function down()
    {
        $transaction = $this->db->beginTransaction();
        try {
            $this->dropTable(Profile::tableName());
            $this->dropTable(Userkey::tableName());
            $this->dropTable(User::tableName());
            $this->dropTable(Role::tableName());
            
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            echo $e->getMessage();
            echo "\n";
            echo get_called_class() . ' cannot be reverted.';
            echo "\n";

            return false;
        }

        return true;
    }
}
