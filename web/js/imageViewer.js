document.addEventListener('DOMContentLoaded', function () {
    const viewerElement = document.getElementById('openseadragon-viewer');
    if (!viewerElement) return;

    // Считываем конфигурацию из data-атрибутов
    const config = viewerElement.dataset;
    const baseUrl = config.baseUrl;
    const printerIcon = `${baseUrl}img/printer.png`;
    const printerRest = `${baseUrl}img/printer_rest.png`;
    const printerIconHover = `${baseUrl}img/printer_hover.png`;
    const printerIconPressed = `${baseUrl}img/printer_pressed.png`;

    let imagePath = config.imagePath;

    const viewer = OpenSeadragon({
        id: "openseadragon-viewer",
        prefixUrl: config.imagesUrl,
        drawer: "html",
    });

    const printButton = new OpenSeadragon.Button({
        tooltip: 'Печать',
        srcRest: printerRest,
        srcHover: printerIconHover,
        srcDown: printerIconPressed,
        srcGroup: printerIcon,

        onRelease: function () {
            printImage(imagePath);
        }
    });

    // Добавляем экземпляр кнопки в стандартную группу кнопок
    viewer.buttonGroup.buttons.push(printButton);

    // Вставляем DOM-элемент кнопки в блок навигации
    viewer.buttonGroup.element.appendChild(printButton.element);

    const printImage = (imageUrl) => {
        const tempImg = new Image();
        tempImg.src = imageUrl;

        tempImg.onload = () => {
            const isLandscape = tempImg.naturalWidth > tempImg.naturalHeight;
            const orientation = isLandscape ? 'landscape' : 'portrait';

            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';

            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;

            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        @page {
                            size: A4 ${orientation};
                            margin: 0; 
                        }
                        html, body {
                            width: 100%;
                            height: 100%;
                            margin: 0;
                            padding: 3mm; 
                            box-sizing: border-box;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            background: #fff;
                        }
                        img {
                            max-width: 100%;
                            max-height: 100%;
                            object-fit: contain;
                        }
                    </style>
                </head>
                <body>
                    <img id="printImage" src="${imageUrl}">
                </body>
                </html>
            `);
            doc.close();

            const img = doc.getElementById('printImage');

            img.onload = () => {
                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();

                    setTimeout(() => {
                        iframe.remove();
                    }, 1000);
                }, 100);
            };
        };
    };

    if(imagePath?.trim()) showImage(imagePath);

    function showImage(imageURL) {
        setTimeout(() => {
            viewer.open({
                type: 'image',
                url: imageURL
            });

            imagePath = imageURL;
        }, 0);
    }

    document.addEventListener('changeRecordImage', function (e) {
        if (e.detail?.url) {
            showImage(e.detail.url);
        }
    });
});