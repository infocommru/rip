<?php

use app\models\RecordHistory;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\DetailView;

/** @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var \app\models\Record $model
 * @var array<\app\models\RecordHistory>|null $history
*/
$this->title = $model->book->name . ', запись №' . $model->numReg;

$user = \app\models\User::findIdentity(\Yii::$app->user->id);

if ($user->role == 1) {
    $this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['/record/update', 'id' => $model->id]];
    $this->params['breadcrumbs'][] = "История изменений";
} else {
    $this->params['breadcrumbs'][] = $this->title;
}

/**
 *
 * @param string $data
 * @param string $pole1
 * @param string $pole2
 * @return string
 */
function td_content($data, $pole1, $pole2) {
    if ($pole1 == $pole2) {
        return Html::encode($data);
    } else {
        return "<span class='ne_ravno'>" . Html::encode($data) . "</span>";
    }
}

function renderHistoryCell($field, $historyModel, $index, $history, $model) {
    $info = unserialize($historyModel->info);
    $historyLast = isset($history[$index + 1]) 
        ? unserialize($history[$index + 1]->info) 
        : $model->attributes;

    $val1 = isset($historyLast[$field]) ? $historyLast[$field] : null;
    $val2 = isset($info[$field]) ? $info[$field] : null;

    return td_content($val2, $val1, $val2);
}
?>
<div class="record-history-index">

    <h3><?= Html::encode($this->title) ?></h3>
    <hr />

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
        ],
    ])
    ?>
    <hr />
    <h4>Логи</h4>
    <?php if ($history): ?>
        <?= GridView::widget([
            'dataProvider' => new \yii\data\ArrayDataProvider([
                'allModels' => $history,
                'pagination' => false,
            ]),
            'summary' => false,
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'header' => '#',
                ],
                [
                    'label' => 'Юзер',
                    'value' => function ($historyModel) {
                        return $historyModel->user ? $historyModel->user->username : '-';
                    },
                ],
                [
                    'label' => 'Изменено',
                    'value' => function ($historyModel) {
                        return date("Y-m-d H:i", $historyModel->updated_at);
                    },
                ],
                [
                    'label' => 'Номер',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        $info = unserialize($historyModel->info);
                        $historyLast = isset($history[$index + 1]) 
                            ? unserialize($history[$index + 1]->info) 
                            : $model->attributes;

                        $regnum = $info['numReg'] ?: $info['numLiteral'];
                        return td_content($regnum, $historyLast['numReg'], $info['numReg']);
                    },
                ],
                [
                    'label' => 'ФИО',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('fio', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Возраст',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('age', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Дата смерти',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('death_date', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Дата захоронения',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('rip_date', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Рег. № кремации',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('num_crem_reg', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => '№ счета по кремации',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('num_crem_account', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'ЗАГС',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('zags', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Номер участка',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('area_num', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Номер ряда',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('row_num', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Номер могилы',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('rip_num', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Родственники',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('relative_fio', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Файл',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('filename', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Комментарий',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        return renderHistoryCell('comment', $historyModel, $index, $history, $model);
                    },
                ],
                [
                    'label' => 'Захоронение',
                    'format' => 'raw',
                    'value' => function ($historyModel, $key, $index) use ($history, $model) {
                        $info = unserialize($historyModel->info);
                        $historyLast = isset($history[$index + 1]) 
                            ? unserialize($history[$index + 1]->info) 
                            : $model->attributes;

                        $ripStyleTypes = \app\models\Record::ripStyleTypes();
                        $ripStyle = isset($ripStyleTypes[$info['rip_style']]) ? $ripStyleTypes[$info['rip_style']] : '';

                        return td_content($ripStyle, $historyLast['rip_style'], $info['rip_style']);
                    },
                ],
            ],
        ]);
    ?>
    <?php else: ?>
        <p>История изменений пуста</p>
    <?php endif; ?>
</div>
