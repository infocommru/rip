//Универсальная функция инициализации Autocomplete
const initAutocomplete = (selector, variableName) => {
    $(selector).autocomplete({
        source: (request, response) => {
            $.ajax({
                url: "/web/search/search-suggest",
                dataType: "json",
                data: {
                    q: request.term,
                    variable: variableName
                },
                success: data => response(data)
            });
        },
        minLength: 2
    });
};