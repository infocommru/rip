<?php
use yii\helpers\Html;
use \app\models\Record;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use \app\models\HelperImg;
use yii\widgets\ActiveForm;
use app\models\Book;
use yii\grid\ActionColumn;
use yii\helpers\FileHelper;

/**
 * @var yii\web\View $this
 * @var Book $book
 */

$user = \app\models\User::findIdentity(\Yii::$app->user->id);
$is_admin = $user->role == 1;

$this->title = "Книга #" . $book->name . " (" . ($book->cemetery->name) . ")";
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['view', 'id' => $book->id]];
$this->params['breadcrumbs'][] = 'Загрузить сканы';

$titlePath = HelperImg::getImagesFilepath($book, true);

if(!$titlePath['path'])
    $titlePath['path'] = $book->cemetery->name;
?>

<h1><?= Html::encode($this->title) ?></h1>

<? if(Record::find()->where(['book_id' => $book->id])->exists()): ?>
    <div class="form-group">
        <div>
            <?php
                ActiveForm::begin([
                    'action' => ['rename-folder'],
                    'method' => 'post',
                    'id' => 'folder-form'
                ]);

                echo Html::hiddenInput('id', (string) $book->id);
                echo Html::hiddenInput('final-imagefolder', $titlePath['path']);
            ?>
            <div id="path-builder">
                <label class="mb-2">Путь к папке со сканами</label>
            </div>

            <div class="hide-render d-none">
                <button type="button" class="btn btn-primary" id="btn-add">+ Добавить папку</button>

                <?php
                    if ($titlePath['existed'])
                        echo Html::submitButton('Переименовать папку', ['class' => 'btn btn-danger', 'id' => "btn-submit"]);
                    else
                        echo Html::submitButton('Создать папку', ['class' => 'btn btn-success', 'id' => "btn-submit"]);
                ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        
        <div class="hide-render d-none">
            <?php
                if($titlePath['existed']){
                    echo '<hr class="my-4 text-muted">';
                    echo '<label class="mb-2">Максимальный размер отдельного скана 2МБ, количество сканов до 200</label>';

                    ActiveForm::begin([
                        'action' => ['download-images'],
                        'method' => 'post',
                        'options' => ['enctype' => 'multipart/form-data']
                    ]);

                    echo Html::hiddenInput('id', (string) $book->id);

                    echo Html::fileInput('images[]', null, 
                        ['class' => 'form-control', 'multiple' => true, 'accept' => 'image/jpeg']);
                    echo Html::submitButton('Загрузить новые сканы', ['class' => 'btn btn-success mt-2']);
                    ActiveForm::end();
                }
            ?>
        </div>
    </div>
<? else: ?>
    <div class="alert alert-danger" role="alert">
        Книга пустая. Для изменения директории или загрузки сканов сначала заполните книгу
    </div>
<? endif; ?>

<hr class="my-4 text-muted hide-render d-none">

<div class="hide-render d-none">
    <label class="mb-2">Записи сканов в базе и их наличие в хранилище</label>
    <?php
        $dataProvider = new ActiveDataProvider([
            'query' => Record::find()->select(['filename', 'book_id'])->
                    andWhere(['book_id' => $book->id ])->distinct(), 
            
            'pagination' => [
                'pageSize' => 50, // Количество элементов на страницу
            ],
            'sort' => [
                'defaultOrder' => ['filename' => SORT_ASC], // Сортировка по умолчанию
            ],
        ]);

        echo GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'], // Порядковый номер строки
                'filename',
                [
                    'label' => 'Скан в хранилище',
                    'format' => 'boolean',
                    'value' => function ($record) {
                        return is_file(FileHelper::normalizePath(Yii::getAlias("@images/$record->filename")));
                    }
                ],
                [
                    'class' => ActionColumn::className(),
                    'template' => '{upload} {showscan}',
                    'buttons' => [
                        'upload' => function ($url, $record) use ($titlePath) {
                            if(!$titlePath['existed'] || is_dir(FileHelper::normalizePath(Yii::getAlias("@images/$record->filename"))))
                                return '';

                            $inputId = 'upload_image_' . uniqid();

                            return Html::beginForm(
                                ['reload-image'],
                                'post',
                                [
                                    'enctype' => 'multipart/form-data',
                                    'style' => 'display:inline',
                                ]
                            )
                            . Html::fileInput(
                                'image',
                                null,
                                [
                                    'id' => $inputId,
                                    'accept' => 'image/jpeg',
                                    'style' => 'display:none',
                                    'onchange' => "if (confirm('Заменить скан?')) {
                                        this.form.submit();
                                    }"
                                ]
                            )
                            . Html::hiddenInput('id', $record->book->id)
                            . Html::hiddenInput('filename', $record->filename)
                            . Html::button(
                                Html::img('/assets/img/edit.png', ['width' => '24px']),
                                [
                                    'type' => 'button',
                                    'class' => 'btn btn-sm p-0',
                                    'title' => 'Заменить скан',
                                    'onclick' => "document.getElementById('$inputId').click();",
                                ]
                            )
                            . Html::endForm();
                        },
                        'showscan' => function ($url, $record) use ($titlePath) {
                            if(!$titlePath['existed']
                                || !is_file(FileHelper::normalizePath(Yii::getAlias("@images/$record->filename")))
                                || is_dir(FileHelper::normalizePath(Yii::getAlias("@images/$record->filename"))))
                                return '';

                            return Html::a(
                                Html::img('/assets/img/view.png', ['width' => '24px']),
                                ['image-viewer/index', 'path' => str_replace('\\', '/', $record->filename) ],
                                [
                                    'class' => 'btn btn-sm p-0',
                                    'title' => 'Показать скан',
                                    'target' => '_blank',
                                ]
                            );
                        },
                    ]
                ],
            ],
        ]);
    ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('path-builder');
        
        if(container) {
            let dirs = document.getElementsByName('final-imagefolder')[0].value.split(/[\/\\]+/);
            dirs = (dirs && dirs.length > 0) ? dirs : [''];

            dirs.forEach((dir) => {
                createFolder(dir);
            });
        }

        document.querySelectorAll('.hide-render').forEach((element) => {
            element.classList.remove('d-none');
        });

        function createFolder(value = ''){
            const row = document.createElement('div');

            row.className = 'folder-row d-flex align-items-center gap-2 mb-2';
            row.innerHTML = `
                <input type="text" class="form-control w-25" value="${value}" placeholder="Уровень папки">
                <button type="button" class="remove-button btn-close"></button>
            `;

            container.appendChild(row);
        }

        // Добавление нового поля
        document.getElementById('btn-add').addEventListener('click', () => createFolder());

        // Удаление поля (оставляем минимум одно)
        container.addEventListener('click', function(e) {
            // Проверяем, есть ли у кликнутого элемента нужный класс
            if (e.target.classList.contains('remove-button')) {
                const rows = container.querySelectorAll('.folder-row');

                if (rows.length > 1) {
                    const row = e.target.closest('.folder-row');
                    row.remove();
                } else {
                    rows[0].querySelector('input').value = ''; // Очищаем, если осталось последнее
                }
            }
        });

        // Сборка пути без использования '\'
        document.getElementById('folder-form').addEventListener('submit', function(e) {
            const inputs = container.querySelectorAll('.form-control');
            
            const pathSegments = Array.from(inputs)
                .map(input => input.value.trim())
                .filter(val => val !== ''); // Исключаем пустые поля

            // Собираем через прямой слэш /
            const resultPath = pathSegments.join('\\');
            
            document.getElementsByName('final-imagefolder')[0].value = resultPath;
        });
    });
</script>