(function ($) {
    function initFloatingScroll() {
        $('#floating-scrollbar').remove();

        const $activePanel = $('.ui-tabs-panel:visible');
        const $grid = $activePanel.length ? $activePanel.find('.grid-view') : $('.grid-view:visible');

        if (!$grid.length) return;

        const $scrollTarget = $grid.find('.table-responsive').length ? $grid.find('.table-responsive') : $grid;
        const targetEl = $scrollTarget[0];

        if (!targetEl) return;

        const tableWidth = targetEl.scrollWidth;
        const containerWidth = $scrollTarget.outerWidth();

        if (tableWidth <= containerWidth) return;

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

            if(targetEl.scrollWidth < $scrollTarget.outerWidth() + 1){
                $scrollContainer.hide();
                return;
            }

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

        // Теперь здесь вешаем ТОЛЬКО обновление позиции при скролле окна —
        // resize убираем отсюда, он ниже, снаружи функции
        $(window).off('scroll.floatScroll').on('scroll.floatScroll', updatePosition);

        updatePosition();
    }

    window.reinitFloatingScroll = function() {
        setTimeout(initFloatingScroll, 100);
    };

    $(document).ready(initFloatingScroll);

    $(document).on('tabsactivate', '#tabs', function () {
        window.reinitFloatingScroll();
    });

    $(document).ajaxComplete(function () {
        window.reinitFloatingScroll();
    });

    $(document).on('pjax:complete pjax:end', function () {
        window.reinitFloatingScroll();
    });

    let resizeTimer;

    $(window).off('resize.floatScrollGlobal').on('resize.floatScrollGlobal', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initFloatingScroll, 150); // debounce, чтобы не дёргать пересчёт на каждый пиксель
    });

})(jQuery);