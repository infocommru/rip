<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Cemetery $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Кладбища', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="cemetery-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?=
        Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Точно удалить?',
                'method' => 'post',
            ],
        ])
        ?>
    </p>

    <?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name:ntext',
            'description:ntext',
        ],
    ])
    ?>
    <hr />

    <h3>Вгрузить архив книг (.zip)</h3>

    <?php
        ActiveForm::begin([
            'method' => 'post',
            'options' => ['enctype' => 'multipart/form-data']
        ]);

        echo Html::fileInput('zipfile', null, 
            ['class' => 'form-control', 'accept' => 'application/zip, application/x-zip-compressed']);
        echo Html::submitButton('Отправить', ['class' => 'btn btn-success mt-2']);

        ActiveForm::end();
    ?>

</div>
