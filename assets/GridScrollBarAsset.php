<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\bootstrap5\BootstrapAsset;

class GridScrollBarAsset extends AssetBundle
{
    public $css = [
        '/assets/css/gridScrollBar.css',
    ];

    public $js = [
        '/assets/js/gridScrollBar.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}