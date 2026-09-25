<?php

use yii\helpers\Html;
use app\models\Book;
use app\models\Record;
use app\models\Cemetery;
use yii\web\View;

/**
 * @var yii\web\View $this
 * @var array<string, string|null|false> $res
 * @var string $title
 * @var string $zah_suffix
 * @var string $grob
 * @var string $user_fio
 */

$this->title = $title;
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="print-view">
    <h5><?= Html::encode($this->title) ?></h5>
    <form method="get" action="/web/print/forma">
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <label for="nn">Номер</label>
                    <input class="form-control" type="text" name="nn" id="nn" />
                </div>
                <div class="col-sm-6">
                    <label for="date" class="form-label mb-0">Дата выдачи</label>
                    <input class="form-control" type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" />
                </div>
                <div class="col-sm-6">
                    <label for="vidano">Справка выдана (ФИО)</label>
                    <input class="form-control" type="text" name="vidano" id="vidano" />
                </div>
                <div class="col-sm-6">
                    <label for="fio">ФИО умершего</label>
                    <input class="form-control" type="text" name="fio" id="fio" value="<?= htmlspecialchars($res['fio']) ?>" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3">
                    <label for="docnum">Номер документа</label>
                    <input class="form-control" type="text" name="docnum" id="docnum" value="<?= htmlspecialchars($res['docnum']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="rip_date">Дата захоронения</label>
                    <input class="form-control" type="text" name="rip_date" id="rip_date" value="<?= htmlspecialchars($res['rip_date']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="death_date">Дата смерти</label>
                    <input class="form-control" type="text" name="death_date" id="death_date" value="<?= htmlspecialchars($res['death_date']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="age">Возраст</label>
                    <input class="form-control" type="text" name="age" id="age" value="<?= htmlspecialchars($res['age']) ?>" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3">
                    <label for="svazka">Номер связки</label>
                    <input class="form-control" type="text" name="svazka" id="svazka" value="<?= htmlspecialchars($res['svazka']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="book_num">Номер книги</label>
                    <input class="form-control" type="text" name="book_num" id="book_num" value="<?= htmlspecialchars($res['number']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="page_num">Страница</label>
                    <input class="form-control" type="text" name="page_num" id="page_num" value="<?= htmlspecialchars($res['page_num']) ?>" />
                </div>
                <div class="col-sm-3">
                    <label for="pp">п/п</label>
                    <input class="form-control" type="text" name="pp" id="pp" value="<?= htmlspecialchars($res['regnum']) ?>" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3"> 
                    <label for="cemetery" class="form-label mb-0">Кладбище</label>
                    <input
                        type="text"
                        class="form-control"
                        name="cemetery"
                        id="cemetery"
                        value="<?= htmlspecialchars($res['cemetery'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
                <div class="col-sm-2"> 
                    <label for="zahr" class="form-label mb-0">Захоронение</label>
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
                <div class="col-sm-7">
                    <label for="place" class="form-label mb-0">Номер участка, ряда и места</label>
                    <input class="form-control" type="text" name="place" id="place" value="<?= htmlspecialchars($zah_suffix) ?>" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4"> 
                    <label for="author">Специалист по работе с архивом</label>
                    <input class="form-control" type="text" name="author" id="author" value="<?= htmlspecialchars($user_fio) ?>" />
                </div>
                <div class="col-sm-4"> 
                    <label for="author2">Ответственное лицо</label>
                    <input class="form-control" type="text" name="author2" id="author2" value="<?= htmlspecialchars($res['relative_fio']) ?>" />
                </div>
                <div class="col-sm-4"> 
                    <label for="zags">ЗАГС</label>
                    <input class="form-control" type="text" name="zags" id="zags" value="<?= htmlspecialchars($res['zags']) ?>" />
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12"> 
                    <label for="comment">Комментарий</label>
                    <input class="form-control" type="text" name="comment" id="comment" value="<?= htmlspecialchars($res['comment']) ?>"/>
                </div>
            </div>

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
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio1" value="1">
                        <label class="form-check-label" for="inlinRadio1">pdf</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio2" value="2">
                        <label class="form-check-label" for="inlinRadio2">jpg</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input checked class="form-check-input" type="radio" name="saveas" id="inlinRadio3" value="3">
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
    });
</script>
