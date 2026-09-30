<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/** @var yii\web\View $this */
$this->title = 'Сведения о захоронениях г. Санкт-Петербурга';
$user = \app\models\User::findIdentity(\Yii::$app->user->id);
$role = $user->role ?? 0;
?>

<div class="site-index">
    <?php if ($role === 1 || Yii::$app->user->isGuest): ?>
        <div class="jumbotron text-center bg-transparent mt-5 mb-5">
            <h1 class="display-4">Здравствуйте!</h1>

            <p class="lead">Контрольно-поисковая система.</p>

            <?php if (!Yii::$app->user->isGuest): ?>
                <p><a class="btn btn-lg btn-success" href="<?= Url::to(['/cemetery']) ?>">Кладбища</a></p>
            <?php else: ?>
                <p><a class="btn btn-lg btn-success" href="<?= Url::to(['/site/login']) ?>">Войти</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h1><?= Html::encode($this->title) ?></h1>
        <?=
            DetailView::widget([
                'model' => $user,
                'attributes' => [
                    'username',
                    'firstname',
                    'lastname',
                    'middlename',
                ],
            ])
        ?>
    <?php endif; ?>

    <div class="body-content">
    </div>
</div>
