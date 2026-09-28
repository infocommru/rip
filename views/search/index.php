<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Cemetery;
use app\models\Helper;

use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/** @var yii\web\View $this
 * @var app\models\Record $model
 * @var yii\widgets\ActiveForm $form
 * @var yii\web\View $this
 * @var false|array<string, mixed> $search_data
*/

$this->registerCssFile(Url::to(['/css/searchResults.css']), ['depends' => [\yii\jui\JuiAsset::class]]);
$this->registerCssFile(Url::to(['/css/jquery_styles.css']), ['depends' => [\yii\jui\JuiAsset::class]]);

$this->registerJsFile(Url::to(['/js/autocomplete.js']), [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

$this->registerJsFile(Url::to(['/js/searchResults.js']), [
    'depends' => [\yii\web\JqueryAsset::class], // Обязательно подгружать ПОСЛЕ jQuery
    'position' => View::POS_END, // Вставка перед закрывающим тегом </body>
]);

$user = app\models\User::findIdentity(Yii::$app->user->id);
$this->title = 'Поиск по захоронениям г. Санкт-Петербурга';

/**
 * @param string $name
 * @return string
 */
function echo_select_fuzziness($name) {
    $r = "<label for = \"$name\">Вхождение</label>";
    $r .= "<select id = \"$name\" name='$name' class=\"form-control\">";

    $values = [
        1 => 'точная фраза',
        2 => 'искать с опечатками',
        3 => 'начинается с',
        4 => 'заканчивается на'
    ];

    foreach ($values as $k => $v)
        $r .= "<option value='$k'>" . $v . "</option>";

    $r .= "</select>";
    return $r;
}

$cemeteries = Cemetery::find()->orderBy('name')->all();
$cemeteriesFormated = ArrayHelper::toArray($cemeteries, [
    'app\models\Cemetery' => [
        'id',
        'name',
    ],
]);

?>
    <div class="search-form" id="filter-container">
        <h2>Поиск по захоронениям г. Санкт-Петербурга</h2>
        <hr />
        <h4>Основные параметры</h4>
        <div class="container">
            <div class="row">
                <div class="col-sm-2">
                    <label for='fam'>Фамилия</label>
                    <input type="text" class='form-control' name='fam' id='fam' />
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('fam_cont') ?>
                </div>
                <div class="col-sm-2">
                    <label for='nam'>Имя</label>
                    <input type="text" class='form-control' name='nam' id='nam' />
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('nam_cont') ?>
                </div>
                <div class="col-sm-2">
                    <label for='ot'>Отчество</label>
                    <input type="text" class='form-control' name='ot' id='ot' />
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('ot_cont') ?>
                </div>
                <div class="col-sm-2">
                    <label for='regnum'>Номер записи</label>
                    <input type="text" class='form-control' name='regnum' id='regnum' />
                </div>
            </div>
            <div class='row'>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="cemetery">Кладбище</label>
                        <select id="cemetery" name='cemetery' class="form-control">
                            <option value='0'>-</option>
                            <?php
                                foreach ($cemeteries as $cemetery)
                                    echo "<option value='" . $cemetery->id . "'>" . $cemetery->name . "</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="rip_style">Захоронение</label>
                        <select id="rip_style" name='rip_style' class="form-control">
                            <?php
                                $riplist = \app\models\Record::ripStyleTypes();
                                foreach ($riplist as $rip_id => $rip_val)
                                    echo "<option value='" . $rip_id . "'>" . $rip_val . "</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <br />
                    <div class="form-group">
                        <input type='checkbox' name='unknown' id='unknown' />
                        <label style='color:#cc0000' for="unknown">Неизвестный</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <label for="unknown_number">Номер неизвестного</label>
                    <input type=text class='form-control' id='unknown_number' name='unknown_number' />
                </div>
                <div class="col-sm-4">
                    <br />
                    <div class="form-group">
                        <input type='checkbox' id='ext_search' name='ext_search'/>
                        <label for="ext_search">Дополнительные параметры</label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-1">
                    <label for="age">Возраст</label>
                    <input type=text class='form-control' id='age' name='age' />
                </div>
                <div class="col-sm-1">
                    <label for="age_cmp">Сравнение</label>

                    <select id="age_cmp" name='age_cmp' class="form-control">
                        <option value='1'>Равно</option>
                        <option value='2'>Меньше</option>
                        <option value='3'>Больше</option>
                    </select>
                </div>

                <div class="col-sm-3">
                    <label for="dead_y">Дата смерти</label>
                    <div class="input-group mb-2">
                        <input id="dead_y" name="dead_y" type="text" class="form-control" placeholder="Год" maxlength="4">
                        <span class="input-group-text">.</span>
                        <input id="dead_m" name="dead_m" type="text" class="form-control" placeholder="Месяц" maxlength="2">
                        <span class="input-group-text">.</span>
                        <input id="dead_d" name="dead_d" type="text" class="form-control" placeholder="День" maxlength="2">
                    </div>
                </div>

                <div class="col-sm-1">
                    <label for="dead_year_cmp">Сравнение</label>
                    <select id="dead_year_cmp" name='dead_year_cmp' class="form-control">
                        <option value='1'>Равно</option>
                        <option value='2'>Меньше</option>
                        <option value='3'>Больше</option>
                    </select>
                </div>

                <div class="col-sm-3">
                    <label for="rip_y">Дата захоронения</label>
                    <div class="input-group mb-2">
                        <input id="rip_y" name="rip_y" type="text" class="form-control only-digits" placeholder="Год" maxlength="4">
                        <span class="input-group-text">.</span>
                        <input id="rip_m" name="rip_m" type="text" class="form-control" placeholder="Месяц" maxlength="2">
                        <span class="input-group-text">.</span>
                        <input id="rip_d" name="rip_d" type="text" class="form-control" placeholder="День" maxlength="2">
                    </div>
                </div>  

                <div class="col-sm-1">
                    <label for="rip_year_cmp">Сравнение</label>
                    <select id="rip_year_cmp" name='rip_year_cmp' class="form-control">
                        <option value='1'>Равно</option>
                        <option value='2'>Меньше</option>
                        <option value='3'>Больше</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="zags">ЗАГС</label>
                        <input name="zags" id="zags" class="form-control">
                    </div>
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('zags_cont') ?>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="docnum">Номер документа</label>
                        <input type=text class='form-control' id='docnum' name='docnum' />
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="form-group">
                        <label for="comment">Комментарий</label>
                        <input type=text class='form-control' id='comment' name='comment' />
                    </div>
                </div>           
                <div class="col-sm-2">
                    <button type="submit" id="find_results" class="btn btn-primary btn-lg btn-block search_btn">Найти</button>
                </div>
                <div class="col-sm-2">
                    <a id="create_form_btn" class="btn btn-success btn-lg btn-block search_btn" target="_blank">Создать форму</a>
                </div>
            </div>
            <div id='additional_search_params' class='d-none'>
                <hr />
                <h4>Дополнительные параметры</h4>
                <div class="row">
                    <div class="col-sm-2">
                        <label for="areanum">Номер участка</label>
                        <input type=text class='form-control' id='areanum' name='areanum' />
                    </div>
                    <div class="col-sm-2">
                        <?= echo_select_fuzziness('area_cont') ?>
                    </div>
                    <div class="col-sm-2">
                        <label for="rownum">Номер ряда</label>
                        <input type=text class='form-control' id='rownum' name='rownum' />
                    </div>
                    <div class="col-sm-2">
                        <?= echo_select_fuzziness('row_cont') ?>
                    </div>
                    <div class="col-sm-2">
                        <label for="ripnum">Номер могилы</label>
                        <input type=text class='form-control' id='ripnum' name='ripnum' />
                    </div>
                    <div class="col-sm-2">
                        <?= echo_select_fuzziness('rip_cont') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <label for="rel">Родственники</label>
                        <input type=text class='form-control' id='rel' name='rel' />
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="num_crem_reg">Регистрационный № кремации</label>
                            <input name="num_crem_reg" id="num_crem_reg" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <?= echo_select_fuzziness('num_crem_reg_cont') ?>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="num_crem_account">№ счета по кремации</label>
                            <input name="num_crem_account" id="num_crem_account" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <?= echo_select_fuzziness('num_crem_account_cont') ?>
                    </div>
                    <div class="col-sm-2">
                        <br/>
                        <div class="form-group">
                            <input class="form-check-input" type="checkbox" role="switch" id="gos" name="gos" value="1">
                            <label for="gos">Госсчет</label>
                        </div>
                    </div>
                </div>
            </div>

            <hr />
        </div>
    </div>
    <div class="row d-none" id="search_results" data-base-url="<?= Url::to(['/']) ?>">
        <div class="col-sm-12">
            <ul id="tabs"></ul>
        </div>
    </div>