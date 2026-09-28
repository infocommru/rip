<?php

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

use app\static\GridScrollBarAsset;

/** @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var \app\models\User $model
*/
$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;

$this->registerAssetBundle(GridScrollBarAsset::class);

?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'username',
            'password',
            'firstname',
            'lastname',
            'middlename',
            'position',
            [
                'label' => 'Роль',
                'value' => function ($model) {
                    return User::roleList()[$model->role];
                }
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, User $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]);
    ?>


</div>
