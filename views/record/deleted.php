<?php

use app\models\Record;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this
 *  @var yii\data\ActiveDataProvider $dataProvider
 *  @var string $flash
 *  @var int $book_id
 *  @var app\models\Record $model
*/
$this->title = "Записи, которые были удалены";
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="record-index">
    <?php if ($flash): ?>
        <div class="alert alert-success" role="alert">
            <?= $flash ?>
        </div>
    <?php endif; ?>

    <h1><?= Html::encode($this->title) ?></h1>
    
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
            ],
            'id',
            [
                'label' => 'Книга',
                'value' => function ($model) {
                    return $model->book->name;
                }
            ],
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
                'class' => ActionColumn::className(),
                'header' => ' ',
                'template' => '{update} {restore} {delete}', // Набор кнопок
                'buttons' => [
                    // Изменяем стандартную кнопку update, чтобы добавить картинку
                    'update' => function ($url, $model, $key) {
                        return Html::a(
                            Html::img('/assets/img/edit.png', ['width' => '24px']), 
                            ['/record/update', 'id' => $model->id], 
                            ['target' => '_blank', 'title' => 'редактировать']
                        );
                    },
                    // Кастомная кнопка для восстановления
                    'restore' => function ($url, $model, $key) {
                        return Html::a(
                            Html::img('/assets/img/restore.png', ['width' => '24px']), 
                            ['/record/deleted', 'record_id' => $model->id, 'a' => 'restore'], 
                            ['title' => 'восстановить']
                        );
                    },
                    // Кастомная кнопка для полного удаления
                    'delete' => function ($url, $model, $key) {
                        return Html::a(
                            Html::img('/assets/img/del.png', ['width' => '24px']), 
                            ['/record/deleted', 'record_id' => $model->id, 'a' => 'del'], 
                            ['title' => 'удалить']
                        );
                    },
                ],
            ],
        ],
    ]);
    ?>
</div>
