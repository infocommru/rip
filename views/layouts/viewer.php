<?php

use yii\helpers\Html;

/** @var yii\web\View $this
 * @var string $content 
 */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <?php $this->head() ?>
    </head>

    <body style="margin: 0; padding: 0; overflow: hidden;">
        <?php $this->beginBody() ?>

        <?= $content ?>

        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>