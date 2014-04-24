<?php

use yii\helpers\Url;

/**
 * @var string $subject
 * @var \filsh\yii2\user\models\User $user
 * @var \filsh\yii2\user\models\Profile $profile
 * @var \filsh\yii2\user\models\Userkey $userkey
 */
?>

<h3><?= $subject ?></h3>

<p>Please confirm your email address by clicking the link below:</p>

<p><?= Url::toRoute(["/user/confirm", "key" => $userkey->key], true); ?></p>