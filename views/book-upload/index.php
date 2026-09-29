<?php

use app\models\BookUpload;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

use app\static\GridScrollBarAsset;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Логи загрузки';
$this->params['breadcrumbs'][] = $this->title;

$this->registerAssetBundle(GridScrollBarAsset::class);

?>
<div class="book-upload-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать заново поисковый кэш', ['start-search-cache'], ['class' => 'btn btn-danger']) ?>
        <?= Html::a('Загрузить в базу записи', ['start-upload-books'], ['class' => 'btn btn-success']) ?>
    </p>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'label' => 'Кладбише',
                'value' => function ($model) {
                    return $model->cemetery->name;
                }
            ],
            [
                'label' => 'Добавлено',
                'value' => function ($model) {
                    return date("Y-m-d H:i", $model->add_at);
                }
            ],
            'filename',
            [
                'label' => 'Статус',
                'value' => function ($model) {
                    switch ($model->status) {
                        case 0:
                            return "не обработано";
                        case 1:
                            return "в обработке";
                        case 2:
                            return "обработано";
                        case 3:
                            return "кладбище удалено, обработка невозможна";
                    }
                }
            ],
        ],
    ]);
    ?>
</div>
