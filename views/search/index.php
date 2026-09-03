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

$this->registerJsFile('assets/js/autocomplete.js', [
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
    <h2>Поиск по захоронениям г. Санкт-Петербурга</h2>
    <hr />
    <h4>Основные параметры</h4>
    <div class="search-form" id="filter-container">
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
                    <label for="dead_year">Дата смерти</label>
                    <div class="input-group mb-2">
                        <input id="dead_y" name="dead_y" type="text" class="form-control" placeholder="Год" >
                        <span class="input-group-text">.</span>
                        <input id="dead_m" name="dead_m" type="text" class="form-control" placeholder="Месяц" >
                        <span class="input-group-text">.</span>
                        <input id="dead_d" name="dead_d" type="text" class="form-control" placeholder="День" >
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
                    <label for="dead_year">Дата захоронения</label>
                    <div class="input-group mb-2">
                        <input id="rip_y" name="rip_y" type="text" class="form-control" placeholder="Год" >
                        <span class="input-group-text">.</span>
                        <input id="rip_m" name="rip_m" type="text" class="form-control" placeholder="Месяц" >
                        <span class="input-group-text">.</span>
                        <input id="rip_d" name="rip_d" type="text" class="form-control" placeholder="День" >
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
                        <label for="num_crem_reg">Регистрационный № кремации</label>
                        <input name="num_crem_reg" id="num_crem_reg" class="form-control">
                    </div>
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('num_crem_reg_cont') ?>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="num_crem_account">№ счета по кремации</label>
                        <input name="num_crem_account" id="num_crem_account" class="form-control">
                    </div>
                </div>
                <div class="col-sm-1">
                    <?= echo_select_fuzziness('num_crem_account_cont') ?>
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
                    <a href="<?= \yii\helpers\Url::to(['print/index']) ?>" class="btn btn-success btn-lg btn-block search_btn" target="_blank">Создать форму</a>
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
            </div>

            <hr />

            <div class="row d-none" id="search_results">
                <div class="col-sm-12">
                    <ul id="tabs" style="padding-left: 0px;">
                    </ul>
                </div>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', () => { 
        // Массив полей для подключения автодополнения
        const autocompleteFields = [
            { selector: '#regnum', name: 'regnum' },
            { selector: '#fam', name: 'fam' },
            { selector: '#nam', name: 'nam' },
            { selector: '#ot', name: 'ot' },
            { selector: '#zags', name: 'zags' },
            { selector: '#comment', name: 'comment' },
            { selector: '#rel', name: 'relative' },
            { selector: '#unknown_number', name: 'unknown_number' },
            { selector: '#docnum', name: 'docnum' },
            { selector: '#areanum', name: 'areanum' },
            { selector: '#rownum', name: 'rownum' },
            { selector: '#ripnum', name: 'ripnum' },
            { selector: '#num_crem_reg', name: 'num_crem_reg' },
            { selector: '#num_crem_account', name: 'num_crem_account' },
        ];

        autocompleteFields.forEach(({ selector, name }) => initAutocomplete(selector, name));

        // 2. Переключение расширенного поиска
        document.getElementById('ext_search').addEventListener('click', (event) => {
            const $additionalParams = $('#additional_search_params');
            const $extSearchCheckbox = $('#ext_search');
            
            const isVisible = $additionalParams.is(':visible') && !$additionalParams.hasClass('d-none');

            if (isVisible) {
                $additionalParams.addClass('d-none');
                $extSearchCheckbox.prop('checked', false);
            } else {
                if (!validateForm(true)){
                    $extSearchCheckbox.prop('checked', false);
                    return;
                }

                $extSearchCheckbox.prop('checked', true);
                $additionalParams.removeClass('d-none');
            }
        });

        // Глобальная переменная для хранения текущих фильтров поиска
        let currentFilterObject = {};

        document.addEventListener('click', async (e) => {
            // Находим ближайшую кнопку с классом .btn-vopros
            const btn = e.target.closest('.btn-vopros');
            if (!btn) return;

            e.preventDefault();

            const recordId = btn.dataset.id; // Получаем data-id

            try {
                // Формируем URL с GET-параметром
                const url = new URL("<?= \yii\helpers\Url::to(['search/vopros']) ?>", window.location.origin);
                url.searchParams.append('record_id', recordId);

                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Флаг AJAX-запроса для Yii2
                    }
                });

                if (!response.ok) {
                    throw new Error(`Ошибка сервера: ${response.status}`);
                }

                alert('Данные отправлены на уточнение');

                // Анимация плавного исчезновения (аналог fadeOut)
                btn.style.transition = 'opacity 0.4s ease';
                btn.style.opacity = '0';

                setTimeout(() => {
                    btn.style.display = 'none';
                }, 400);

            } catch (error) {
                alert('Ошибка при отправке запроса');
                console.error(error);
            }
        });

        // 3. Выполнение поиска и построение вкладок
        document.getElementById('find_results').addEventListener('click', (event) => {
            if (!validateForm(true)) return;

            currentFilterObject = {};

            document.querySelectorAll('#filter-container input, #filter-container select').forEach(input => {
                if (!input.name) return;

                if (input.type === 'checkbox') {
                    currentFilterObject[input.name] = input.checked ? 1 : 0;
                } else {
                    currentFilterObject[input.name] = input.value;
                }
            });

            $('#tabs').html(`
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>
            `);

            const $tabsContainer = $('#tabs');

            $.ajax({
                url: '<?= \yii\helpers\Url::to(['search/found-result-exists']) ?>',
                type: 'GET',
                data: { search: currentFilterObject },
                success: function(response) {
                    // Уничтожаем старый экземпляр jQuery UI Tabs
                    if ($tabsContainer.data('ui-tabs')) {
                        $tabsContainer.tabs('destroy');
                    }

                    $tabsContainer.empty(); // Очищаем контейнер

                    const activeItems = response.filter(item => item.exists);
                    if (activeItems.length === 0) {
                        $tabsContainer.html('<div class="alert alert-danger p-3">Ничего не найдено</div>');
                        document.querySelector('#search_results').classList.remove('d-none');
                        return;
                    }

                    // Создаем базовую структуру <ul>
                    const $ul = $('<ul></ul>');

                    // Заполняем <ul> ссылками на табы и сразу генерируем контейнеры под них
                    activeItems.forEach(item => {
                        $ul.append(`<li><a href="#tabs-${item.id}" data-id="${item.id}">${item.name}</a></li>`);
                        
                        $tabsContainer.append(`
                            <div id="tabs-${item.id}" data-id="${item.id}" data-loaded="false">
                                <div class="text-center p-4 spinner-container">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Загрузка...</span>
                                    </div>
                                </div>
                            </div>
                        `);
                    });

                    $tabsContainer.prepend($ul);

                    // Инициализируем jQuery UI Tabs с обработчиком переключения (beforeActivate)
                    $tabsContainer.tabs({
                        beforeActivate: function(event, ui) {
                            const $newTabPanel = ui.newPanel;
                            loadTabContent($newTabPanel);
                        }
                    });

                    // Показываем блок результатов
                    document.querySelector('#search_results').classList.remove('d-none');

                    // Ручной вызов загрузки первой активной вкладки
                    const $firstPanel = $tabsContainer.find('.ui-tabs-panel').first();
                    if ($firstPanel.length) {
                        loadTabContent($firstPanel);
                    }
                },
                error: function() {
                    $tabsContainer.html('<div class="p-3 border border-danger-subtle alert alert-danger border-danger rounded text-danger-emphasis">Ошибка при загрузке данных</div>');
                }
            });
        });

        // 4. Функция ленивой загрузки содержимого вкладки
        function loadTabContent($tabPanel) {
            const isLoaded = $tabPanel.attr('data-loaded') === 'true';
            const itemId = $tabPanel.attr('data-id');

            currentFilterObject.cemetery = itemId;

            if (!isLoaded) {
                $.ajax({
                    url: '<?= \yii\helpers\Url::to(['search/show-search-result']) ?>',
                    type: 'GET',
                    data: {
                        page: 1,
                        search: currentFilterObject // Передаем условия поиска в контроллер
                    },
                    success: function(html) {
                        $tabPanel.html(html);
                        $tabPanel.attr('data-loaded', 'true');
                    },
                    error: function() {
                        $tabPanel.html('<div class="p-3 text-danger">Ошибка при загрузке данных.</div>');
                    }
                });
            }
            else {
                // Если таб уже был загружен ранее, просто пересчитываем позицию скролла
                if (typeof window.reinitFloatingScroll === 'function')
                    window.reinitFloatingScroll();
            }
        }

        // 5. Валидация формы
        function validateForm(showAlert = false) {
            const fields = [
                '#nam', '#fam', '#ot', '#age', 
                '#dead_y', '#dead_m', '#dead_d', 
                '#rip_y', '#rip_m', '#rip_d', 
                '#regnum', '#zags', '#docnum', '#comment',
                '#num_crem_reg', '#num_crem_account',
            ];

            const hasValue = fields.some(selector => Boolean($(selector).val()?.trim()));
            const hasCemetery = $('#cemetery').val() !== '0';
            const isUnknownChecked = $('#unknown').is(':checked');

            const isValid = hasValue || hasCemetery || isUnknownChecked;

            if (!isValid && showAlert) {
                alert("Требуются данные основного поиска");
            }

            return isValid;
        }
    });
</script>