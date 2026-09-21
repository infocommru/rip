<?php

use app\assets\OpenSeadragonAsset;

/** @var yii\web\View $this */
/** @var string $path */

$assetBundle = OpenSeadragonAsset::register($this);
$imagesUrl = $assetBundle->baseUrl . '/images/';
?>

<div id="openseadragon-viewer"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewer = OpenSeadragon({
        id: "openseadragon-viewer",
        prefixUrl: "<?= $imagesUrl ?>",
        drawer: "html",
        tileSources: {
            type: 'image',
            url: "<?= $path ?>"
        }
    });

    const printButton = new OpenSeadragon.Button({
        tooltip: '',
        srcRest: '/assets/img/printer.png',
        srcHover: '/assets/img/printer_hover.png',
        srcDown: '/assets/img/printer_hover.png',

        onClick: function () {
            printImage("<?= $path ?>");
        }
    });

    viewer.addControl(printButton.element, {
        anchor: OpenSeadragon.ControlAnchor.ABSOLUTE,
        top: 3,
        left: 145,
    });

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
                            /* Сбрасываем поля страницы, чтобы контролировать отступы через CSS */
                            margin: 0; 
                        }
                        html, body {
                            width: 100%;
                            height: 100%;
                            margin: 0;
                            /* Добавляем безопасный padding (3mm) под физические поля принтера */
                            padding: 3mm; 
                            box-sizing: border-box;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            background: #fff;
                        }
                        img {
                            /* Ограничиваем картинку размерами области с учетом padding */
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
});
</script>

<style>
html,
body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

#openseadragon-viewer {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    background: #333;
}
</style>