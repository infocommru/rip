document.addEventListener('DOMContentLoaded', function() {
    let timer;

    document.querySelectorAll(".parent-speller").forEach(el => {
        setSpellValue(el.querySelector('input'), el.querySelector('.speller'));
    });

    function setSpellValue(recordDom, spellerDom)
    {
        if(recordDom.value){
            speller(recordDom.value).then(result => {
                if(result && result !== recordDom.value)
                    spellerDom.textContent = result;
                else
                    spellerDom.textContent = '';
            });
        }
        else
            spellerDom.textContent = '';

    }

    async function speller(text) {
        let text_array = text.trim().split(/\s+/);
        const positions = [...text.matchAll(/\S+/g)].map(m => m.index);

        try {
            const response = await fetch(
                "https://speller.yandex.net/services/spellservice.json/checkText",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        text: text,
                        lang: "ru",
                        options: 0
                    })
                }
            );

            if (!response.ok) {
                throw new Error(`Speller HTTP ${response.status}`);
            }

            const result = await response.json();

            result.forEach(index => {
                if (index.s && index.s.length > 0) {
                    text_array[positions.indexOf(index.pos)] = index.s[0];
                }
            });

            return text_array.join(" ");

        } catch (error) {
            console.error(error);
            return false;
        }
    }

    document.querySelectorAll(".speller").forEach(el => {
        el.addEventListener('click', (e) => {
            const inputDom = e.target.parentElement
                .querySelector('input');

            inputDom.value = el.textContent;
            el.textContent = '';
        });
    });

    document.querySelectorAll(".parent-speller").forEach(el => {
        el.querySelector('input').addEventListener('input', (e) => {
            clearTimeout(timer);

            timer = setTimeout(async () => {
                    const spellerDom = e.target
                        .closest('.parent-speller')
                        .querySelector('.speller');

                    setSpellValue(e.target, spellerDom);
            }, 500);
        });
    });
});