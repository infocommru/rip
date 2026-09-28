document.addEventListener('DOMContentLoaded', function() {
    let images_files_urls = [];
    let images_files = [];
    let images_files_short = [];

    const container = document.getElementById('images-top-selector');
    const imagesApiUrl = container ? container.dataset.imagesPath : '';

    fetch(imagesApiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return response.json();
        })
        .then(data => {
            images_files_urls = data.map(item => item.url) || [];
            images_files = data.map(item => item.src2) || [];
            images_files_short = data.map(item => item.src3) || [];

            fillSelector();
            create_top_selector();
        })
        .catch(error => {
            console.error('Ошибка загрузки изображений:', error);
        });

    function fillSelector(){
        const select = document.getElementById('select_fname');
        const filename = document.getElementById('record-filename');
        const index = images_files.indexOf(filename.value);

        for (let i = 0; i < images_files.length; i++){
            const option = new Option(images_files_short[i], images_files[i]);

            if(i === index)
                option.selected = true;

            select.add(option);
        }
    }

    function create_top_selector(){
        if(images_files_urls.length > 0){
            const container = document.getElementById('images-top-selector');
            container.classList.remove('d-none');
            container.replaceChildren();

            const current_img_index = 2;

            let index = images_files.indexOf(jQuery('#record-filename').val());
            index = index === -1 ? 0 : index;
            
            const startIndex = Math.max(0, index - current_img_index);

            images_files_short.slice(startIndex, index + current_img_index * 2 + 1).forEach((item, i) => {
                const originalIndex = startIndex + i;
            
                const link = document.createElement('a');
                link.className = `img_gal img_gal${originalIndex} d-inline-block btn btn-link text-danger text-decoration-none p-0 me-1`;
                link.href = '#';
                
                // Безопасная обработка клика
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    click_image(originalIndex);
                });

                const p = document.createElement('p');
                p.className = 'm-0 d-inline';
                p.textContent = item; // textContent защищает от XSS

                link.appendChild(p);
                container.appendChild(link);
            });

            if(jQuery('#record-filename').val() === images_files[index]){
                jQuery(".img_gal").eq(index).addClass('current_gallery_elem');
                click_image(index);
            }
        }
    }

    function click_image(index) {
        jQuery('.img_gal').removeClass('current_gallery_elem');
        jQuery('.img_gal' + index).addClass('current_gallery_elem');
        jQuery("#record-filename").val(images_files[index]);
        jQuery("#image_shower").removeClass("d-none");
        jQuery('#select_fname').val(images_files[index]);

        document.dispatchEvent(new CustomEvent('changeRecordImage', {
            detail: { url: images_files_urls[index] }
        }));
    }

    document.getElementById("select_fname").addEventListener('change', (e) => {
        let val = jQuery('#select_fname').val();
        jQuery('#record-filename').val(val);
        create_top_selector();
    });
});