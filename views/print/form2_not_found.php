<?php

use yii\helpers\Html;
use yii\web\View;

use yii\widgets\DetailView;
use app\models\Book;
use app\models\Record;
use app\models\Cemetery;
use app\models\Helper;

/**
 * @var yii\web\View $this
 * @var array<string, string|null|false> $res
 * @var string $user_fio
 */

$this->registerJsFile('assets/js/autocomplete.js', [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

$this->title = "Форма Ф2";
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

?>
<div class="print-view">
    <h5><?= Html::encode($this->title) ?></h5>
    
    <form method="get" action="/web/print/forma">
        <div class="container">
            <div class="row">
                <div class="col-sm-4">
                    <label for="nn">Номер</label>
                    <input class="form-control" type="text" name="nn" id="nn" value="" />
                </div>
                <div class="col-sm-4">
                    <label for="date">Дата выдачи</label>
                    <input class="form-control" type="date" name="date" id="date" value="<?= date('Y-m-d') ?>" />
                </div>
                <div class="col-sm-4">
                    <label for="dead_year">Год захоронения</label>
                    <input class="form-control" type="text" name="dead_year" id="dead_year" value="<?= htmlspecialchars($res['rip_y']) ?>" />
                    <input type="hidden" name="spravka" value="2">
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <label for="vidano">Справка выдана (ФИО)</label>
                    <input class="form-control" type="text" name="vidano" id="vidano" value="" />
                </div>
                <div class="col-sm-6">
                    <label for="fio">ФИО умершего</label>
                    <input class="form-control" type="text" name="fio" id="fio" value="<?= htmlspecialchars($res['fio']) ?>" />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3"> 
                    <label for="zahr">Кладбище</label>
                    <select class="form-select" type="text" name="cemetery" id="cemetery">
                        <?php
                            $names = Cemetery::find()->select('name')->orderBy(['name' => SORT_ASC])->column();

                            foreach ($names as $name){
                                $selected = '';

                                if($res['cemetery'] === $name)
                                    $selected = 'selected';

                                echo "<option $selected value=\"$name\">$name</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="col-sm-9"> 
                    <label for="author">Специалист по работе с архивом</label>
                    <input class="form-control" type="text" name="author" id="author" value="<?= htmlspecialchars($user_fio) ?>" />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12"> 
                    <label for="comment">Комментарий</label>
                    <input class="form-control" type="text" name="comment" id="comment" value="<?= htmlspecialchars($res['comment']) ?>" />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12"> 
                    <div class="form-check form-check-inline">
                        <input checked="checked" class="form-check-input" type="radio" name="saveas" id="inlinRadio1" value="1">
                        <label class="form-check-label" for="inlinRadio1">pdf</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio2" value="2">
                        <label class="form-check-label" for="inlinRadio2">jpg</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input checked="checked" class="form-check-input" type="radio" name="saveas" id="inlinRadio3" value="3">
                        <label class="form-check-label" for="inlinRadio3">Сохранить в pdf</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="saveas" id="inlinRadio4" value="4">
                        <label class="form-check-label" for="inlinRadio4">Сохранить в jpg</label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 mt-2"> 
                    <input type="submit" value="печать" class="btn btn-primary btn-lg btn-block ">
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const autocompleteFields = [          
            { selector: '#comment', name: 'comment' },

            { selector: '#vidano', name: ['fam', 'nam', 'ot'] },
            { selector: '#fio', name: ['fam', 'nam', 'ot'] },
        ];

        autocompleteFields.forEach(({ selector, name }) => initAutocomplete(selector, name));
    });
</script>