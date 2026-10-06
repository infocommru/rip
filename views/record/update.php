<?php

use yii\helpers\Html;
use app\models\HelperImg;
use app\static\OpenSeadragonAsset;
use yii\helpers\Url;

/** @var yii\web\View $this
 * @var app\models\Record $model
 * @var app\models\Record|null $prev
 * @var app\models\Record|null $next
 * @var app\models\Record|null $first
 * @var app\models\User $user
*/
$this->title = $model->book->name . ", запись #" . $model->id;

if ($user->role == 1) {
    $this->params['breadcrumbs'][] = ['label' => $model->book->name, 'url' => ['/record/index', 'book' => $model->book_id]];
    $this->params['breadcrumbs'][] = ['label' => '#' . $model->id, 'url' => ['view', 'id' => $model->id]];
    $this->params['breadcrumbs'][] = 'Обновить';
} else {
    $this->params['breadcrumbs'][] = $this->title;
}

$assetBundle = OpenSeadragonAsset::register($this);
$imagesUrl = $assetBundle->baseUrl . '/images/';

$this->registerJsFile(
    '@web/js/updateRecordImageSelector.js', 
    [
        'depends' => [
            \app\static\OpenSeadragonAsset::class, // Загрузится ПОСЛЕ OpenSeadragon
            \yii\web\JqueryAsset::class,          // Загрузится ПОСЛЕ jQuery
        ],
        'position' => \yii\web\View::POS_END,     // В конце <body>
    ]
);

?>
<div class="record-update">
    <?php if ($_POST): ?>
        <div class="alert alert-success" role="alert">
            Данные были успешно обновлены
        </div>
    <?php endif; ?>
    <h3><?= Html::encode($this->title) ?>
        <?php if ($model->updated_at): ?>
            (Обновлено <?= date("Y-m-d H:i", $model->updated_at) ?>)
        <?php endif; ?>
    </h3>
    <div id="images-top-selector" 
        class="p-3 my-2 border rounded d-flex flex-wrap gap-2 align-items-center"
        data-images-path="<?= Url::to(['/book/get-images-path', 'book_id' => $model->book->id]) ?>">
    </div>

    <div class="container">
        <div class="row">
            <?php if ($prev): ?>
                <div class="col-sm">
                    <a id="go_b" href=<?= Url::to(['/record/update', 'id' => $prev->id]) ?> class="btn btn-link">&#129044; Назад</a>
                </div>
            <?php endif; ?>
            <?php if ($next): ?>
                <div class="col-sm">
                    <a id="go_f" href=<?= Url::to(['/record/update', 'id' => $next->id]) ?> class="btn btn-link">Вперед &#10132;</a>
                </div>
            <?php endif; ?>
            <?php if ($model->updated_at): ?>
                <div class="col-sm">
                    <a  id="go_ff" href=<?= Url::to(['/record-history', 'record_id' => $model->id]) ?> class="btn btn-info">История изменений</a>
                </div>
            <?php endif; ?>
            <div class="col-sm">
                <a target="_blank" id="go_new" href=<?= Url::to(['/record/create', 'book_id' => $model->book_id]) ?> class="btn btn-danger">Создать новую</a>
            </div>
        </div>
    </div>
    <hr />
    <?=
        $this->render('_form', [
            'model' => $model,
            'is_create' => false,
        ])
    ?>
    <hr />

    <div id="image_shower" class="container d-none">
        <div class="row">
            <div class="col-sm-12">
                <div id="openseadragon-viewer"
                    data-base-url="<?= Url::to(['/']) ?>"
                    data-images-url="<?= Html::encode($imagesUrl) ?>"
                    style="width: 100%; height: 900px; background-color: #333;">
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .current_gallery_elem {
        border:2px solid red;
    }
</style>