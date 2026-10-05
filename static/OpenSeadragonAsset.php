<?php
namespace app\static;
use yii\web\AssetBundle;
use yii\helpers\Url;

class OpenSeadragonAsset extends AssetBundle
{
    // Путь к установленным исходникам библиотеки в vendor
    public $sourcePath = '@npm/openseadragon/build/openseadragon';

    public $js = [
        'openseadragon.min.js',
    ];

    public $publishOptions = [
        'only' => [
            'openseadragon.min.js',
            'images/*', // Важно: публикуем иконки кнопок (плюс, минус, полный экран)
        ],
    ];

    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        $view->registerLinkTag([
            'rel' => 'icon',
            'type' => 'image/x-icon',
            'href' => Url::to(['/favicon.ico']),
        ]);
    }
}