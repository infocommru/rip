<?php

use app\models\Cemetery;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

use app\static\GridScrollBarAsset;

/** @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var Cemetery $model
*/
$this->title = 'Кладбища';
$this->params['breadcrumbs'][] = "Кладбища";

$this->registerAssetBundle(GridScrollBarAsset::class);

?>
<div class="cemetery-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить кладбище', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'name:ntext',
            'description:ntext',
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Cemetery $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]);
    ?>


</div>
