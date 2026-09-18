const initAutocomplete = (selector, variableNames) => {
    const variables = Array.isArray(variableNames)
        ? variableNames
        : [variableNames];

    $(selector).autocomplete({
        source: (request, response) => {
            // Для нескольких переменных ищем только последнее слово.
            const term = variables.length > 1
                ? request.term.split(/\s+/).pop() || ''
                : request.term;

            if (term.length < 2) {
                response([]);
                return;
            }

            $.ajax({
                url: "/web/search/search-suggest",
                dataType: "json",
                data: {
                    q: term,
                    variables: variables
                },
                success: data => {
                    // Backend уже возвращает единый отсортированный массив.
                    const items = Array.isArray(data)
                        ? [...new Set(data.filter(Boolean))].slice(0, 10)
                        : [];

                    response(items);
                },
                error: xhr => {
                    console.error(
                        'Autocomplete error:',
                        xhr.status,
                        xhr.responseText
                    );

                    response([]);
                }
            });
        },

        minLength: 2,

        // Не даём ↑/↓ перезаписывать всё значение input.
        focus: (event, ui) => {
            if (variables.length > 1) {
                return false;
            }
        },

        // Для нескольких переменных заменяем только последнее слово.
        select: (event, ui) => {
            if (variables.length <= 1) {
                return;
            }

            const $input = $(event.target);
            const value = $input.val();

            // Сохраняем всё до последнего слова.
            const prefix = value.replace(/\S+\s*$/, '');

            $input.val(prefix + ui.item.value + ' ');

            return false;
        }
    });
};
