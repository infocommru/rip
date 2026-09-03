<?php

use app\models\Helper;
use app\models\User;
use app\models\Record;
use yii\helpers\Url;
use yii\web\View;

use yii\grid\GridView;
use yii\widgets\Pjax;

/**
* @var yii\web\View $this
* @var int $count_result
* @var \yii\data\ActiveDataProvider $dataProvider
*/

$this->registerCssFile('assets/css/search_result.css', [
    'depends' => [\yii\bootstrap5\BootstrapAsset::class], // или BootstrapPluginAsset
]);

$this->registerJsFile('assets/js/search_result.js', [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

$search['cemetery'] = $search['cemetery'] ?? Yii::$app->request->get('id', 'default');
?>

<h5>Всего записей: <?= $count_result ?>. 
    Выгрузить <a href="<?= \yii\helpers\Url::to(['search/export', 'search' => $search]) ?>">excel</a></h5>

<?php Pjax::begin([
    'id' => 'pjax-tab-' . $search['cemetery'],
    'enablePushState' => false, // Чтобы клик по странице не переписывал URL браузера
    'timeout' => 5000,
]); ?>


<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'options' => ['class' => 'grid-view sticky-table-wrapper'],
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

        'regnum:text:Номер',
        'fio_display:text:ФИО',
        'age:text:Возраст',
        [
            'attribute' => 'dead_date',
            'label' => 'Дата смерти',
            'value' => function ($model) {
                return Helper::formatDate((string)$model->dead_date);
            },
        ],
        [
            'attribute' => 'rip_date',
            'label' => 'Дата захоронения',
            'value' => function ($model) {
                return Helper::formatDate((string)$model->rip_date);
            },
        ],
        'num_crem_reg:text:Рег. № кремации',
        'num_crem_account:text:№ счета по кремации',
        'docnum:text:Номер документа',
        'zags:text:ЗАГС',
        [
            'attribute' => 'rip_style',
            'label' => 'Захоронение',
            'value' => function ($model) {
                $types = \app\models\Record::ripStyleTypes();
                
                // Приоритет отдаем book_rip_style, если он заполнен
                $styleKey = !empty($model->book_rip_style) ? $model->book_rip_style : $model->rip_style;

                // Защита от несуществующего ключа в массиве
                return $types[$styleKey] ?? '';
            },
        ],
        'areanum:text:Номер участка',
        'rownum:text:Номер ряда',
        'ripnum:text:Номер могилы',
        'relative:text:Родственники',
        [
            'label' => 'Доп. инфо',
            'format' => 'raw', // Обязательно для работы HTML-тегов (br, a)
            'value' => function ($model) {
                // Экранируем данные из БД для безопасности
                $svazka = \yii\helpers\Html::encode($model->svazka_num);
                $book   = \yii\helpers\Html::encode($model->book_num);
                $page   = \yii\helpers\Html::encode($model->page_num);
                $punkt  = \yii\helpers\Html::encode($model->page_punkt);

                $dopInfo = "св. {$svazka}, кн. {$book}, стр. {$page}, строка: {$punkt}";

                // Собираем комментарии
                $comment = trim($model->comment ?? '');
                if (!empty($model->comment_book)) {
                    $comment .= ($comment !== '' ? ' ' : '') . $model->comment_book;
                }

                if ($comment !== '') {
                    $encodedComment = \yii\helpers\Html::encode($comment);
                    $dopInfo .= ', ' . \yii\helpers\Html::tag('span', $encodedComment, ['class' => 'text-danger']);
                }

                // Генерируем ссылку через хелпер Yii
                if (!empty($model->book_id)) {
                    $link = \yii\helpers\Html::a(
                        'обложка',
                        ['/search/book-cover', 'book_id' => $model->book_id],
                        [
                            'class' => 'link-primary', 
                            'target' => '_blank',
                            'data-pjax' => '0',
                        ]
                    );
                    $dopInfo .= "<br />" . $link;
                }

                return $dopInfo;
            },
        ],
        [
            'label' => '',
            'format' => 'raw',
            'value' => function ($model) {
                // Доступ к текущему пользователю в Yii2
                $user = app\models\User::findIdentity(Yii::$app->user->id);
                $links = [];

                // 1. Просмотр файла/изображения
                if (!empty($model->filename)) {
                    $path = str_replace('\\', '/', $model->filename);
                    $links[] = \yii\helpers\Html::a(
                        \yii\helpers\Html::img('/assets/img/view.png', ['width' => '24px', 'alt' => 'просмотр']),
                        ['image-viewer/index', 'path' => $path],
                        [
                            'target' => '_blank',
                            'title' => 'Просмотр',
                            'data-pjax' => '0',
                        ]
                    );
                }

                // 2. Требуется уточнить данные
                if (empty($model->vopros)) {
                    $links[] = \yii\helpers\Html::a(
                        \yii\helpers\Html::img('/assets/img/vopros.png', ['width' => '24px', 'alt' => 'уточнить']),
                        '#', // Избавление от javascript:
                        [
                            'class' => 'btn-vopros', // По классу будет навешиваться событие
                            'data-id' => $model->record_id, // Передаем ID через data-атрибут
                            'title' => 'Требуется уточнить данные',
                        ]
                    );
                }

                // 3. История изменений
                if (!empty($model->updated_at)) {
                    $links[] = \yii\helpers\Html::a(
                        \yii\helpers\Html::img('/assets/img/history.png', ['width' => '24px', 'alt' => 'история']),
                        ['/record-history/index', 'record_id' => $model->record_id],
                        [
                            'target' => '_blank',
                            'id' => 'history-' . $model->record_id,
                            'title' => 'История изменения',
                            'data-pjax' => '0',
                        ]
                    );
                }

                // 4. Печать
                $links[] = \yii\helpers\Html::a(
                    \yii\helpers\Html::img('/assets/img/print.png', ['width' => '24px', 'alt' => 'печать']),
                    ['/print/index', 'record_id' => $model->record_id],
                    [
                        'target' => '_blank',
                        'id' => 'print-' . $model->record_id,
                        'title' => 'Печать',
                        'data-pjax' => '0',
                    ]
                );

                // 5. Редактирование (для пользователей с ролью != 2)
                if ($user && $user->role != 2) {
                    $links[] = \yii\helpers\Html::a(
                        \yii\helpers\Html::img('/assets/img/edit.png', ['width' => '24px', 'alt' => 'редактировать']),
                        ['/record/update', 'id' => $model->record_id],
                        [
                            'target' => '_blank',
                            'id' => 'edit-' . $model->record_id,
                            'title' => 'Редактировать',
                            'data-pjax' => '0',
                        ]
                    );
                }

                // Объединяем все ссылки через небольшой отступ
                return implode(' ', $links);
            },
        ],
    ],
    'pager' => [
        'class' => \yii\widgets\LinkPager::class,
    ],
]); ?>

<?php Pjax::end(); ?>