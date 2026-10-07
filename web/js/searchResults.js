document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('search_results');
    const config = container.dataset;
    const baseUrl = config.baseUrl;

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

    function returnGET(baseUrl, f2notFound = false){
        // 1. Проверяем валидность формы

        if (!validateForm(true)){
            event.preventDefault();
            return;
        }

        // 2. Собираем актуальные значения всех заполненных полей
        const printParams = new URLSearchParams();

        document.querySelectorAll('#filter-container input, #filter-container select').forEach(input => {
            if (!input.name) return;

            let value;
            if (input.type === 'checkbox') {
                value = input.checked ? '1' : '0';
            } else {
                value = input.value.trim();
            }

            // Добавляем параметр в URL, если значение заполнено
            if (value !== '' && value !== '0') {
                printParams.append(input.name, value);
            }
        });

        if (f2notFound)
            printParams.append('f2notFound', '1');

        // 3. Формируем итоговый URL с параметрами и обновляем href у ссылки
        const finalUrl = baseUrl + (baseUrl.includes('?') ? '&' : '?') + printParams.toString();

        event.currentTarget.href = finalUrl;
    }

    // Передача параметров формы при клике на "Создать форму"
    document.getElementById('create_form_btn').addEventListener('click', (event) => {
        returnGET("<?= \yii\helpers\Url::to(['print/index']) ?>");
    });

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
            url: `${baseUrl}search/found-result-exists`,
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
                    $tabsContainer.html(
                        '<div class="alert alert-danger p-3">По вашему запросу ничего не найдено, попробуйте уточнить критерии поиска <a id="A2-not-found" target="_blank" href="#">Форма Ф2</a></div>');

                    const btnA2 = document.getElementById('A2-not-found');

                    if (btnA2) {
                        btnA2.addEventListener('click', (event) => {
                            returnGET("<?= \yii\helpers\Url::to(['print/index']) ?>", true);
                        });
                    }

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
                url: `${baseUrl}search/show-search-result`,
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

    // Обработка нажатия Enter в полях ввода для выполнения поиска
    document.querySelectorAll('#filter-container input[type="text"]').forEach(input => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault(); // Предотвращаем отправку формы (если она есть)
                document.getElementById('find_results').click();
            }
        });
    });
});