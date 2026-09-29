<?php

namespace app\static;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\bootstrap5\BootstrapAsset;

class GridScrollBarAsset extends AssetBundle
{
    public $css = [
        '/static/css/gridScrollBar.css',
    ];

    public $js = [
        '/static/js/gridScrollBar.js',
    ];

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}