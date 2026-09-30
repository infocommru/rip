(function ($) {
    function initFloatingScroll() {
        // Удаляем ранее созданные scrollbar
        $('.floating-scrollbar').remove();
        $('.grid-view:visible').each(function (index) {
            const $grid = $(this);

            // Ищем реальный горизонтально скроллируемый контейнер
            const $scrollTarget = $(this);
            const targetEl = $scrollTarget[0];

            if (!targetEl) {
                return;
            }

            const tableWidth = targetEl.scrollWidth;

            /*
             * Создаём scrollbar для конкретного GridView.
             * У каждого GridView будет свой floating scrollbar.
             */
            const $scrollContainer = $('<div>', {
                class: 'floating-scrollbar'
            });

            const $scrollInner = $('<div>', {
                class: 'floating-scrollbar-inner'
            }).width(tableWidth);

            $scrollContainer.append($scrollInner);
            $('body').append($scrollContainer);

            let isSyncing = false;

            function updatePosition() {
                // GridView или его контейнер скрыт
                if (!$grid.is(':visible') || !$scrollTarget.is(':visible')) {
                    $scrollContainer.hide();
                    return;
                }

                const rect = targetEl.getBoundingClientRect();
                const windowHeight = $(window).height();

                const currentWidth = $scrollTarget.outerWidth();
                const currentScrollWidth = targetEl.scrollWidth;

                // После изменения DOM горизонтальный scroll больше не нужен
                if (currentScrollWidth <= currentWidth + 1) {
                    $scrollContainer.hide();
                    return;
                }

                /*
                 * Показываем scrollbar только тогда,
                 * когда нижняя часть таблицы находится ниже viewport.
                 */
                if (
                    rect.top < windowHeight &&
                    rect.bottom > windowHeight
                ) {
                    $scrollContainer.css({
                        left: rect.left + 'px',
                        width: rect.width + 'px',
                        display: 'block'
                    });

                    $scrollContainer.scrollLeft(
                        $scrollTarget.scrollLeft()
                    );

                } else {
                    $scrollContainer.hide();
                }
            }

            // Floating scrollbar -> GridView
            $scrollContainer
                .off('scroll.float')
                .on('scroll.float', function () {

                    if (isSyncing) {
                        return;
                    }

                    isSyncing = true;

                    $scrollTarget.scrollLeft(
                        $(this).scrollLeft()
                    );

                    isSyncing = false;
                });

            // GridView -> Floating scrollbar
            $scrollTarget
                .off('scroll.float')
                .on('scroll.float', function () {

                    if (isSyncing) {
                        return;
                    }

                    isSyncing = true;

                    $scrollContainer.scrollLeft(
                        $(this).scrollLeft()
                    );

                    isSyncing = false;
                });

            // Первоначальная позиция
            updatePosition();

            /*
             * Сохраняем функцию на самом GridView,
             * чтобы при необходимости можно было обновить
             * только конкретный GridView.
             */
            $grid.data('floatingScrollUpdate', updatePosition);

        });
    }


    /*
     * Публичная функция.
     *
     * Можно вызвать:
     *
     * reinitFloatingScroll();
     */
    window.reinitFloatingScroll = function () {
        clearTimeout(window.floatingScrollTimer);

        window.floatingScrollTimer = setTimeout(
            initFloatingScroll,
            100
        );
    };

    // Первый запуск
    $(document).ready(function () {
        initFloatingScroll();
    });

    /*
     * Обычный scroll страницы.
     * Обновляем положение всех scrollbar.
     */
    $(window)
        .off('scroll.floatScrollGlobal')
        .on('scroll.floatScrollGlobal', function () {
            $('.grid-view:visible').each(function () {
                const update = $(this).data(
                    'floatingScrollUpdate'
                );

                if (typeof update === 'function') {
                    update();
                }
            });
        });


    /*
     * Resize.
     *
     * Полностью пересоздаём scrollbar,
     * потому что после resize меняются:
     *
     * - ширина GridView
     * - scrollWidth
     * - положение элемента
     */
    let resizeTimer;

    $(window)
        .off('resize.floatScrollGlobal')
        .on('resize.floatScrollGlobal', function () {

            clearTimeout(resizeTimer);

            resizeTimer = setTimeout(
                initFloatingScroll,
                150
            );

        });


    /*
     * Если используется jQuery UI Tabs.
     */
    $(document)
        .off('tabsactivate.floatScroll', '#tabs')
        .on('tabsactivate.floatScroll', '#tabs', function () {
            reinitFloatingScroll();
        });

    /*
     * AJAX.
     *
     * Подходит для Yii GridView с ajax-перезагрузкой.
     */
    $(document).ajaxComplete(function () {
        reinitFloatingScroll();
    });

    /*
     * PJAX.
     */
    $(document)
        .off('pjax:complete.floatScroll pjax:end.floatScroll')
        .on(
            'pjax:complete.floatScroll pjax:end.floatScroll',
            function () {
                reinitFloatingScroll();
            }
        );
})(jQuery);