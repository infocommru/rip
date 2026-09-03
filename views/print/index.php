<?php

use yii\helpers\Html;
use app\models\Book;
use app\models\Record;
use app\models\Helper;
use app\models\HelperCache;
use app\models\Cemetery;
use yii\web\View;

/**
 * @var \app\models\Record | null  $record
 * @var \app\models\CacheRecords | null $sdata
 * @var yii\web\View $this
 * @var \app\models\User $user
 */

$this->registerJsFile('assets/js/autocomplete.js', [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

$book = $record->book ?? null;
$cemetery = $book->cemetery ?? null;

$this->title = "Печать" . ($record ? ": {$cemetery->name}, {$record->fio}" : '');
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

// Определение способа захоронения (приоритет у книги)
$grob = '';

if (!empty($book->rip_style)) {
    $grob = Book::ripStyleTypes()[$book->rip_style] ?? '';
} elseif ($record) {
    $grob = Record::ripStyleTypes()[$record->rip_style] ?? '';
}

// Формирование базового шаблона места захоронения
$zah_suffix = $record ? "уч. {$record->area_num}, ряд {$record->row_num}, место {$record->rip_num}" : '';

// Формирование ФИО оператора
$user_fio = $user->middlename 
    ? $user->lastname . ' ' . mb_substr($user->firstname, 0, 1, 'utf8') . '. ' . mb_substr($user->middlename, 0, 1, 'utf8') . '.'
    : "$user->lastname $user->firstname $user->middlename";

// Собираем данные записи
$res = [
    'cemetery'     => $cemetery->name ?? '',
    'docnum'       => $record->docnum ?? '',
    'age'          => $record->age ?? '',
    'relative_fio' => $record->relative_fio ?? '',
    'zags'         => $record->zags ?? '',
    'comment'      => $record->comment ?? '',
    'number'       => $book->number ?? '',
    'svazka'       => $book->svazka ?? '',
    'page_num'     => $sdata->page_num ?? '',
    'regnum'       => $sdata->regnum ?? '',
    'rip_date'     => Helper::formatDate($record->rip_date ?? ''),
    'death_date'   => Helper::formatDate($record->death_date ?? ''),
    'num-crem-reg' => $record->num_crem_reg ?? '',
    'num-crem-account' => $record->num_crem_account ?? '',
];

$res = array_merge($res, HelperCache::splitFIO($record->fio ?? ''));
?>

<div class="print-view">
    <h5><?= Html::encode($this->title) ?></h5>
    <form method="get" action="/web/print/forma">
        <div class="container">
            <fieldset class="border rounded-3 px-3 pb-3 pt-0 my-2">
                <legend class="float-none w-auto px-2 fs-6 text-muted text-uppercase mb-1">
                    Данные справки
                </legend>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="nn" class="form-label">Номер</label>
                        <input class="form-control" type="text" name="nn" id="nn" />
                    </div>
                    
                    <div class="col-sm-6">
                        <label for="date" class="form-label">Дата выдачи</label>
                        <input class="form-control" type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" />
                    </div>

                    <div class="col-12">
                    <fieldset class="border rounded-3 px-3 pb-3 pt-0 my-2">
                        <legend class="float-none w-auto px-2 fs-6 text-muted text-uppercase mb-1">
                            Справка выдана (ФИО)
                        </legend>
                        
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label for="vidano-fam" class="form-label">Фамилия</label>
                                <input class="form-control" type="text" name="vidano-fam" id="vidano-fam" />
                            </div>
                            
                            <div class="col-sm-4">
                                <label for="vidano-nam" class="form-label">Имя</label>
                                <input class="form-control" type="text" name="vidano-nam" id="vidano-nam" />
                            </div>
                            
                            <div class="col-sm-4">
                                <label for="vidano-ot" class="form-label">Отчество</label>
                                <input class="form-control" type="text" name="vidano-ot" id="vidano-ot" />
                            </div>
                        </div>
                    </fieldset>
                    </div>
                </div>
                </fieldset>

            <fieldset class="border rounded-3 px-3 pb-3 pt-0 my-2">
                <legend class="float-none w-auto px-2 fs-6 text-muted text-uppercase mb-1">
                    Данные умершего
                </legend>

                <fieldset class="border rounded-3 px-3 pb-3 pt-0 my-2">
                    <legend class="float-none w-auto px-2 fs-6 text-muted text-uppercase mb-1">
                        ФИО умершего
                    </legend>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label for="fam">Фамилия</label>
                            <input class="form-control" type="text" name="fam" id="fam" value="<?= $res['fam'] ?>" />
                        </div>
                        <div class="col-sm-4">
                            <label for="nam">Имя</label>
                            <input class="form-control" type="text" name="nam" id="nam" value="<?= $res['nam'] ?>" />
                        </div>
                        <div class="col-sm-4">
                            <label for="ot">Отчество</label>
                            <input class="form-control" type="text" name="ot" id="ot" value="<?= $res['ot'] ?>" />
                        </div>
                    </div>
                </fieldset>

                <div class="row g-3">
                    <!-- Ряд 1 -->
                    <div class="col-sm-3">
                        <label for="docnum" class="form-label">Номер документа</label>
                        <input class="form-control" type="text" name="docnum" id="docnum" value="<?= $res['docnum'] ?>" />
                    </div>
                    <div class="col-sm-3">
                        <label for="rip_date" class="form-label">Дата захоронения</label>
                        <input class="form-control" type="text" name="rip_date" id="rip_date" value="<?= $res['rip_date'] ?>" />
                    </div>
                    <div class="col-sm-3">
                        <label for="death_date" class="form-label">Дата смерти</label>
                        <input class="form-control" type="text" name="death_date" id="death_date" value="<?= $res['death_date'] ?>" />
                    </div>
                    <div class="col-sm-3">
                        <label for="age" class="form-label">Возраст</label>
                        <input class="form-control" type="text" name="age" id="age" value="<?= $res['age'] ?>" />
                    </div>

                    <!-- Ряд 2 -->
                    <div class="col-sm-3">
                        <label for="svazka" class="form-label">Номер связки</label>
                        <input class="form-control" type="text" name="svazka" id="svazka" value="<?= $res['svazka'] ?>" />
                        </div>
                    <div class="col-sm-3">
                        <label for="book_num" class="form-label">Номер книги</label>
                        <input class="form-control" type="text" name="book_num" id="book_num" value="<?= $res['number'] ?>" />
                    </div>
                    <div class="col-sm-3">
                        <label for="page_num" class="form-label">Страница</label>
                        <input class="form-control" type="text" name="page_num" id="page_num" value="<?= $res['page_num'] ?>" />
                    </div>
                    <div class="col-sm-3">
                        <label for="pp" class="form-label">п/п</label>
                        <input class="form-control" type="text" name="pp" id="pp" value="<?= $res['regnum'] ?>" />
                        </div>

                    <!-- Ряд 3 -->
                    <div class="col-sm-3">
                        <label for="cemetery" class="form-label">Кладбище</label>
                        <select class="form-select" name="cemetery" id="cemetery">
                            <?php
                                $names = Cemetery::find()->select('name')->column();

                                foreach ($names as $name){
                                    $selected = '';

                                    if($res['cemetery'] === $name)
                                        $selected = 'selected';

                                    echo "<option $selected value=\"$name\">$name</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="zahr" class="form-label">Захоронение</label>
                        <select class="form-select" name="zahr" id="zahr">
                            <?php
                                foreach (Book::ripStyleTypes() as $name){
                                    $selected = '';

                                    if($grob === $name)
                                        $selected = 'selected';

                                    echo "<option $selected value=\"$name\">$name</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="place" class="form-label">Номер участка/Ряда/Места</label>
                        <input class="form-control" type="text" name="place" id="place" value="<?= $zah_suffix ?>" />
                    </div>

                    <!-- Ряд 4 -->
                    <div class="col-sm-4">
                        <label for="author" class="form-label">Специалист по работе с архивом</label>
                        <input class="form-control" type="text" name="author" id="author" value="<?= $user_fio ?>" />
                    </div>
                    <div class="col-sm-4">
                        <label for="author2" class="form-label">Ответственное лицо</label>
                        <input class="form-control" type="text" name="author2" id="author2" value="<?= $res['relative_fio'] ?>" />
                    </div>
                    <div class="col-sm-4">
                        <label for="zags" class="form-label">ЗАГС</label>
                        <input class="form-control" type="text" name="zags" id="zags" value="<?= $res['zags'] ?>" />
                    </div>

                    <!-- Ряд 5 -->
                     <div class="col-sm-6">
                        <label for="num-crem-reg" class="form-label">Регистрационный № кремации</label>
                        <input class="form-control" type="text" name="num-crem-reg" id="num-crem-reg" value="<?= $res['num-crem-reg'] ?>" />
                    </div>
                    <div class="col-sm-6">
                        <label for="num-crem-account" class="form-label">№ счета по кремации</label>
                        <input class="form-control" type="text" name="num-crem-account" id="num-crem-account" value="<?= $res['num-crem-account'] ?>" />
                    </div>

                    <!-- Ряд 6 -->
                    <div class="col-12">
                        <label for="comment" class="form-label">Комментарий</label>
                        <input class="form-control" type="text" name="comment" id="comment" value="<?= $res['comment'] ?>" />
                    </div>
                </div>
            </fieldset>

            <!-- Чекбоксы опций -->
            <div class="row">
                <div class="col-sm-12"> 
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="checkbox" name="print_date" id="print_date" value="1">
                        <label class="form-check-label" for="print_date">Печатать дату смерти</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="checkbox" name="print_grob" id="print_grob" value="2">
                        <label class="form-check-label" for="print_grob">Печатать способ захоронения (гроб/урна)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="checkbox" name="print_addr" id="print_addr" value="2">
                        <label class="form-check-label" for="print_addr">Печатать адрес ответственного лица</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="checkbox" name="print_comment" id="print_comment" value="2">
                        <label class="form-check-label" for="print_comment">Печатать комментарий</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="print_crem" id="print_crem" value="2">
                        <label class="form-check-label" for="print_crem">Печатать номера кремации</label>
                    </div>
                </div>
            </div>

            <!-- Переключатели типа справки -->
            <div class="row">
                <div class="col-sm-6"> 
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="radio" name="spravka" id="inlineRadio1" value="1">
                        <label class="form-check-label" for="inlineRadio1">СПРАВКА (Ф-1)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="spravka" id="inlineRadio2" value="2">
                        <label class="form-check-label" for="inlineRadio2">СПРАВКА (Ф-2)</label>
                    </div>
                </div>
            </div>

            <!-- Форматы сохранения -->
            <div class="row">
                <div class="col-sm-12"> 
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="radio" name="saveas" id="inlinRadio1" value="1">
                        <label class="form-check-label" for="inlinRadio1">pdf</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio2" value="2">
                        <label class="form-check-label" for="inlinRadio2">jpg</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio3" value="3">
                        <label class="form-check-label" for="inlinRadio3">Сохранить в pdf</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio4" value="4">
                        <label class="form-check-label" for="inlinRadio4">Сохранить в jpg</label>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-6"> 
                    <input type="submit" value="печать" class="btn btn-primary btn-lg btn-block">
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const relativeFio = <?= json_encode($res['relative_fio']) ?>;
        const printAddr = document.getElementById('print_addr');
        const author2Input = document.getElementById('author2');

        // Обновление поля "Ответственное лицо"
        const updateAuthor = () => {
            author2Input.value = printAddr.checked 
                ? relativeFio 
                : relativeFio.split(',')[0];
        };

        printAddr.addEventListener('change', updateAuthor);

        // Первоначальная инициализация значений
        updateAuthor();

        const autocompleteFields = [
            { selector: '#fam', name: 'fam' },
            { selector: '#nam', name: 'nam' },
            { selector: '#ot', name: 'ot' },
            { selector: '#vidano-fam', name: 'fam' },
            { selector: '#vidano-nam', name: 'nam' },
            { selector: '#vidano-ot', name: 'ot' },

            { selector: '#docnum', name: 'docnum' },
            { selector: '#pp', name: 'regnum' },
            
            { selector: '#author2', name: 'relative' },
            { selector: '#zags', name: 'zags' },
            { selector: '#comment', name: 'comment' },
        ];

        autocompleteFields.forEach(({ selector, name }) => initAutocomplete(selector, name));
    });
</script>