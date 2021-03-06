<?php
use yii\helpers\Html;

/**
 * @var array $actions
 */
$this->title = "User";
?>

<div class="user-default-index">

    <h1>Yii 2 User Module - <?= Yii::$app->getModule("user")->getVersion(); ?></h1>
    <h3>Actions in this module</h3>
    <p>
        <em><strong>Note:</strong> some actions may be unavailable depending on if you are logged in/out, or as an
            admin/regular user</em>
    </p>
    <table class="table table-bordered">
        <tr>
            <th>Link</th>
            <th>Description</th>
        </tr>

        <?php foreach ($actions as $text => $info): ?>

            <?php
                $url = isset($info["url"]) ? $info["url"] : $info;
                $description = isset($info["description"]) ? $info["description"] : "";
            ?>

            <tr>
                <td><?= $text ?></td>
                <td>
                    URL: <strong><?= Html::a($url, [$url]) ?></strong>
                    <?= $description ? "<br/><br/>$description" : "" ?>
                </td>
            </tr>

        <?php endforeach; ?>

    </table>

</div>
