<?php

use app\models\Record;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this
 *  @var yii\data\ActiveDataProvider $dataProvider
 *  @var app\models\Book $book
 *  @var app\models\Record $model
 *  @var int $book_id
*/
$this->title = $book->name;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="record-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить запись', ['create', 'book_id' => $book_id], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Экспорт Excel', "/web/record/export-excel?id=" . $book_id, ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Сбросить фильтры', "/web/record/index?book=" . $book_id, ['class' => 'btn btn-info']) ?>
    </p>


    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            //'book_id',
            [
                'label' => 'Обновлено',
                'value' => function ($model) {
                    if (!$model->updated_at)
                        return '-';
                    return date("Y-m-d H:i", $model->updated_at);
                }
            ],
            'numReg',
            'numLiteral',
            'fio',
            'age',
            'death_date',
            'rip_date',
            'num_crem_reg',
            'num_crem_account',
            'docnum',
            'zags',
            'area_num',
            'row_num',
            'rip_num',
            'relative_fio',
            'filename',
            'comment:ntext',
            [
                'label' => 'Захоронение',
                'value' => function ($model) {
                    return \app\models\Record::ripStyleTypes()[$model->rip_style];
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '',
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'delete' => function ($model) {
                        $user = Yii::$app->user->identity;
                        return true;
                    },
                    'view' => function ($model) {
                        return true;
                    },
                    'update' => function ($model) {
                        $user = Yii::$app->user->identity;
                        return true;
                    },
                ],
            ],
        ],
    ]);
    ?>


</div>
