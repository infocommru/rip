<?php

use app\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

use app\static\GridScrollBarAsset;

/** @var yii\web\View $this
 *  @var yii\data\ActiveDataProvider $dataProvider
 *  @var Book $model
 */
$user = \app\models\User::findIdentity(\Yii::$app->user->id);
$is_admin = $user->role == 1;

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;

$this->registerAssetBundle(GridScrollBarAsset::class);

?>
<div class="book-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?php if ($is_admin) echo Html::a('Добавить книгу', ['create'], ['class' => 'btn btn-success']); ?>
        <?= Html::a('Сбросить фильтры', ['index'], ['class' => 'btn btn-info']) ?>
    </p>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
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
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => '',
                'template' => '{view} {update} {delete}',
                'visibleButtons' => [
                    'delete' => function ($model) {
                        /** @var \app\models\User $user */
                        $user = Yii::$app->user->identity;
                        return $user->role == 1;
                    },
                    'view' => function ($model) {
                        return true;
                    },
                    'update' => function ($model) {
                        /** @var \app\models\User $user */
                        $user = Yii::$app->user->identity;
                        return $user->role == 1;
                    },
                ],
            ],
        ],
    ]);
    ?>
</div>
