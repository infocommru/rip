<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Book;
use app\models\Record;
use app\models\HelperImg;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Book $model */
$user = \app\models\User::findIdentity(\Yii::$app->user->id);
$is_admin = $user->role == 1;

$this->title = "Книга #" . $model->name . " (" . ($model->cemetery->name) . ")";
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$obloshka = \app\models\HelperImg::getTitleImage($model);
?>
<div class="book-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if ($is_admin) echo Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']); ?>
        <?php echo Html::a('Смотреть записи', "/web/record?book=" . $model->id, ['class' => 'btn btn-primary']); ?>
        <?php if ($obloshka): ?>
            <a target="_blank" class='btn btn-primary' href='<?= $obloshka ?>'>Обложка</a>
        <?php endif; ?>
        <?php
        if ($is_admin) {
            echo Html::a('Загрузить сканы', ['upload-images', 'id' => $model->id], ['class' => 'btn btn-primary me-1']);
            echo Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Точно удалить?',
                    'method' => 'post',
                ],
            ]);
        }
        ?>
    </p>

    <?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'label' => 'Кладбише',
                'value' => function ($model) {
                    return $model->cemetery->name;
                }
            ],
            'name',
            'number',
            'svazka',
            'year1',
            'year2',
            'records',
            'per_page',
            [
                'label' => 'Статус',
                'value' => function ($model) {
                    return Book::getStatuses()[$model->status];
                }
            ],
            'comment',
            [
                'label' => 'Захоронение',
                'value' => function ($model) {
                    return \app\models\Book::ripStyleTypes()[$model->rip_style];
                }
            ],
            [
                'attribute' => 'gos',
                'format' => 'boolean',
            ],
        ],
    ])
    ?>
    <?php if ($is_admin): ?>
        <div>
            <h4>Вгрузить записи</h4>
            <?php
                 ActiveForm::begin([
                    'method' => 'post',
                    'options' => ['enctype' => 'multipart/form-data']
                ]);

                echo Html::fileInput('excel', null, 
                    ['class' => 'form-control', 'accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
                echo Html::submitButton('Отправить', ['class' => 'btn btn-primary mt-2']);

                ActiveForm::end();
            ?>
        </div>
    <?php endif; ?>
</div>
