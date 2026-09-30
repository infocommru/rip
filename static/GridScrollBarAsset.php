<?php

namespace app\static;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\helpers\Url;

class GridScrollBarAsset extends AssetBundle
{
    public function init()
    {
        parent::init();

        $this->css = [
            Url::to(['/css/gridScrollBar.css'])
        ];

         $this->js = [
            Url::to(['/js/gridScrollBar.js'])
        ];
    }

    public $depends = [
        BootstrapAsset::class,
        JqueryAsset::class,
    ];
}