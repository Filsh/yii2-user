<?php

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
                'id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'name' => 'varchar(255) NOT NULL',
                'create_time' => 'timestamp NULL DEFAULT NULL',
                'update_time' => 'timestamp NULL DEFAULT NULL',
                'can_admin' => 'tinyint DEFAULT 0',
            ], $tableOptions);
            
            $this->createTable(User::tableName(), [
                'id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'role_id' => 'int UNSIGNED NOT NULL',
                'email' => 'varchar(255) NULL DEFAULT NULL',
                'new_email' => 'varchar(255) NULL DEFAULT NULL',
                'username' => 'varchar(255) NULL DEFAULT NULL',
                'password' => 'varchar(255) NULL DEFAULT NULL',
                'status' => 'tinyint NOT NULL',
                'auth_key' => 'varchar(255) NULL DEFAULT NULL',
                'api_key' => 'varchar(255) NULL DEFAULT NULL',
                'create_time' => 'timestamp NULL DEFAULT NULL',
                'update_time' => 'timestamp NULL DEFAULT NULL',
                'ban_time' => 'timestamp NULL DEFAULT NULL',
                'ban_reason' => 'varchar(255) NULL DEFAULT NULL',
                'registration_ip' => 'varchar(45) NULL DEFAULT NULL',
                'login_ip' => 'varchar(45) NULL DEFAULT NULL',
                'login_time' => 'timestamp NULL DEFAULT NULL',
                'UNIQUE KEY(`email`)',
                'UNIQUE KEY(`username`)',
                'FOREIGN KEY (`role_id`) REFERENCES ' . Role::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            $this->createTable(Userkey::tableName(), [
                'id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'int UNSIGNED NOT NULL',
                'type' => 'tinyint NOT NULL',
                'key' => 'varchar(255) NOT NULL',
                'create_time' => 'timestamp NULL DEFAULT NULL',
                'consume_time' => 'timestamp NULL DEFAULT NULL',
                'expire_time' => 'timestamp NULL DEFAULT NULL',
                'UNIQUE KEY(`key`)',
                'FOREIGN KEY (`user_id`) REFERENCES ' . User::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            $this->createTable(Profile::tableName(), [
                'id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'user_id' => 'int UNSIGNED NOT NULL',
                'create_time' => 'timestamp NULL DEFAULT NULL',
                'update_time' => 'timestamp NULL DEFAULT NULL',
                'full_name' => 'varchar(255) NULL DEFAULT NULL',
                'FOREIGN KEY (`user_id`) REFERENCES ' . User::tableName() . ' (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
            ], $tableOptions);
            
            // insert role data
            // note: i create a guest role because i like to give guest users the ability to use the site
            //       without registering. you can delete it if you want
            $this->batchInsert(Role::tableName(), ['name', 'can_admin', 'create_time'], [
                ['Admin', 1, date('Y-m-d H:i:s')],
                ['User', 0, date('Y-m-d H:i:s')],
                ['Guest', 0, date('Y-m-d H:i:s')],
            ]);

            // insert user data
            $this->batchInsert(User::tableName(), ['id', 'role_id', 'email', 'username', 'password', 'status', 'create_time'], [
                [1, Role::ROLE_ADMIN, 'neo@neo.com', 'neo', '$2y$10$WYB666j7MmxuW6b.kFTOde/eGCLijWa6BFSjAAiiRbSAqpC1HCmrC', User::STATUS_ACTIVE, date('Y-m-d H:i:s')],
            ]);

            // insert profile data
            $this->batchInsert(Profile::tableName(), ['id', 'user_id', 'full_name', 'create_time'], [
                [1, 1, 'the one', date('Y-m-d H:i:s')],
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
