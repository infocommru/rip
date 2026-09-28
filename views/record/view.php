<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Record $model */
$this->title = $model->book->name . ",запись №" . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Записи', 'url' => ['index', 'book' => $model->book_id]];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="record-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?=
        Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Удаляем?',
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
            [
                'label' => 'Обновлено',
                'value' => function ($model) {
                    if (!$model->updated_at)
                        return '-';
                    return date("Y-m-d H:i", $model->updated_at);
                }
            ],
            [
                'label' => 'Книга',
                'value' => function ($model) {
                    return $model->book->name;
                }
            ],
            'numReg',
            'numLiteral',
            'fio',
            'age',
            'death_date',
            'rip_date',
            'docnum',
            'zags',
            'area_num',
            'row_num',
            'rip_num',
            'relative_fio',
            'filename',
            'comment:ntext',
            //'rip_style',
            [
                'label' => 'Захоронение',
                'value' => function ($model) {
                    return \app\models\Record::ripStyleTypes()[$model->rip_style];
                }
            ],
        ],
    ])
    ?>

</div>
