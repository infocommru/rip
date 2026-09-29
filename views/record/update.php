<?php

use yii\helpers\Html;
use app\models\HelperImg;
use app\static\OpenSeadragonAsset;

/** @var yii\web\View $this
 * @var app\models\Record $model
 * @var app\models\Record|null $prev
 * @var app\models\Record|null $next
 * @var app\models\Record|null $first
 * @var app\models\User $user
*/
$this->title = $model->book->name . ", запись #" . $model->id;

if ($user->role == 1) {
    $this->params['breadcrumbs'][] = ['label' => $model->book->name, 'url' => "/record/index?book=" . $model->book_id];
    $this->params['breadcrumbs'][] = ['label' => '#' . $model->id, 'url' => ['view', 'id' => $model->id]];
    $this->params['breadcrumbs'][] = 'Обновить';
} else {
    $this->params['breadcrumbs'][] = $this->title;
}

$assetBundle = OpenSeadragonAsset::register($this);
$imagesUrl = $assetBundle->baseUrl . '/images/';

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let images_files_urls = [];
            let images_files = [];
            let images_files_short = [];

            const viewer = OpenSeadragon({
                id: "openseadragon-viewer",
                prefixUrl: "<?= $imagesUrl ?>",
                drawer: "html",
            });
        
            fetch('/book/get-images-path?book_id=<?= json_encode($model->book->id) ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    return response.json();
                })
                .then(data => {
                    images_files_urls = data.map(item => item.url) || [];
                    images_files = data.map(item => item.src2) || [];
                    images_files_short = data.map(item => item.src3) || [];

                    fillSelector();
                    create_top_selector();
                })
                .catch(error => {
                    console.error('Ошибка загрузки изображений:', error);
                });

            function fillSelector(){
                const select = document.getElementById('select_fname');
                const filename = document.getElementById('record-filename');
                const index = images_files.indexOf(filename.value);

                for (let i = 0; i < images_files.length; i++){
                    const option = new Option(images_files_short[i], images_files[i]);

                    if(i === index)
                        option.selected = true;

                    select.add(option);
                }
            }

            function create_top_selector(){
                if(images_files_urls.length > 0){
                    const container = document.getElementById('images-top-selector');
                    container.classList.remove('d-none');
                    container.replaceChildren();

                    const current_img_index = 2;

                    let index = images_files.indexOf(jQuery('#record-filename').val());
                    index = index === -1 ? 0 : index;
                    
                    const startIndex = Math.max(0, index - current_img_index);

                    images_files_short.slice(startIndex, index + current_img_index * 2 + 1).forEach((item, i) => {
                        const originalIndex = startIndex + i;
                    
                        const link = document.createElement('a');
                        link.className = `img_gal img_gal${originalIndex} d-inline-block btn btn-link text-danger text-decoration-none p-0 me-1`;
                        link.href = '#';
                        
                        // Безопасная обработка клика
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            click_image(originalIndex);
                        });

                        const p = document.createElement('p');
                        p.className = 'm-0 d-inline';
                        p.textContent = item; // textContent защищает от XSS

                        link.appendChild(p);
                        container.appendChild(link);
                    });

                    if(jQuery('#record-filename').val() === images_files[index]){
                        jQuery(".img_gal").eq(index).addClass('current_gallery_elem');
                        click_image(index);
                    }
                }
            }

            function click_image(index) {
                jQuery('.img_gal').removeClass('current_gallery_elem');
                jQuery('.img_gal' + index).addClass('current_gallery_elem');
                jQuery("#record-filename").val(images_files[index]);
                jQuery("#image_shower").removeClass("d-none");
                jQuery('#select_fname').val(images_files[index]);

                setTimeout(() => {
                    viewer.open({
                        type: 'image',
                        url: images_files_urls[index]
                    });
                }, 0);
            }

            document.getElementById("select_fname").addEventListener('change', (e) => {
                let val = jQuery('#select_fname').val();
                jQuery('#record-filename').val(val);
                create_top_selector();
            });
        });
    </script>

    <div id="images-top-selector" class="p-3 my-2 border rounded d-flex flex-wrap gap-2 align-items-center"></div>

    <div class="container">
        <div class="row">
            <?php if ($prev): ?>
                <div class="col-sm">
                    <a id="go_b" href="/record/update?id=<?= $prev->id ?>" class="btn btn-link">&#129044; Назад</a>
                </div>
            <?php endif; ?>
            <?php if ($next): ?>
                <div class="col-sm">
                    <a id="go_f" href="/record/update?id=<?= $next->id ?>" class="btn btn-link">Вперед &#10132;</a>
                </div>
            <?php endif; ?>
            <?php if ($model->updated_at): ?>
                <div class="col-sm">
                    <a  id="go_ff" href="/record-history/?record_id=<?= $model->id ?>" class="btn btn-info">История изменений</a>
                </div>
            <?php endif; ?>
            <div class="col-sm">
                <a target="_blank" id="go_new" href="/record/create?book_id=<?= $model->book_id ?>" class="btn btn-danger">Создать новую</a>
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
                <div id="openseadragon-viewer" style="width: 100%; height: 900px; background-color: #333;"></div>
            </div>
        </div>
    </div>
</div>
<style>
.current_gallery_elem {
    border:2px solid red;
}
</style>