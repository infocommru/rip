(function ($) {
    function initFloatingScroll() {
        // Удаляем старый плавающий скроллбар перед перерасчетом
        $('#floating-scrollbar').remove();

        // Ищем активную открытую панель таба jQuery UI или контейнер результатов
        const $activePanel = $('.ui-tabs-panel:visible');
        const $grid = $activePanel.length ? $activePanel.find('.grid-view') : $('.grid-view:visible');

        if (!$grid.length) return;

        // Определяем контейнер таблицы, который имеет горизонтальную прокрутку
        const $scrollTarget = $grid.find('.table-responsive').length ? $grid.find('.table-responsive') : $grid;
        const targetEl = $scrollTarget[0];

        if (!targetEl) return;

        const tableWidth = targetEl.scrollWidth;
        const containerWidth = $scrollTarget.outerWidth();

        // Если ширина содержимого меньше или равна ширине контейнера — скроллбар не нужен
        if (tableWidth <= containerWidth) return;

        // Создаем плавающий скроллбар
        const $scrollContainer = $('<div id="floating-scrollbar" class="floating-scrollbar"><div class="floating-scrollbar-inner"></div></div>');
        $scrollContainer.find('.floating-scrollbar-inner').width(tableWidth);
        $('body').append($scrollContainer);

        function updatePosition() {
            if (!$scrollTarget.is(':visible')) {
                $scrollContainer.hide();
                return;
            }

            const rect = targetEl.getBoundingClientRect();
            const windowHeight = $(window).height();

            // Отображаем скроллбар, если нижняя граница таблицы выходит за пределы экрана
            if (rect.top < windowHeight && rect.bottom > windowHeight) {
                $scrollContainer.css({
                    left: rect.left + 'px',
                    width: rect.width + 'px',
                    display: 'block'
                });
                $scrollContainer.scrollLeft($scrollTarget.scrollLeft());
            } else {
                $scrollContainer.hide();
            }
        }

        // Синхронизация прокрутки с защитой от зацикливания
        let isSyncing = false;

        $scrollContainer.off('scroll.float').on('scroll.float', function () {
            if (!isSyncing) {
                isSyncing = true;
                $scrollTarget.scrollLeft($(this).scrollLeft());
                isSyncing = false;
            }
        });

        $scrollTarget.off('scroll.float').on('scroll.float', function () {
            if (!isSyncing) {
                isSyncing = true;
                $scrollContainer.scrollLeft($(this).scrollLeft());
                isSyncing = false;
            }
        });

        $(window).off('scroll.floatScroll resize.floatScroll').on('scroll.floatScroll resize.floatScroll', updatePosition);

        updatePosition();
    }

    // Экспортируем функцию в глобальную область, чтобы её можно было вызывать вручную после AJAX
    window.reinitFloatingScroll = function() {
        setTimeout(initFloatingScroll, 100);
    };

    $(document).ready(initFloatingScroll);

    // События jQuery UI Tabs (активация таба)
    $(document).on('tabsactivate', '#tabs', function () {
        window.reinitFloatingScroll();
    });

    // Обработка глобальных AJAX-запросов и PJAX
    $(document).ajaxComplete(function () {
        window.reinitFloatingScroll();
    });

    $(document).on('pjax:complete pjax:end', function () {
        window.reinitFloatingScroll();
    });
})(jQuery);