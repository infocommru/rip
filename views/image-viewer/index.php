<?php

use app\static\OpenSeadragonAsset;
use yii\helpers\Url;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $path */

$assetBundle = OpenSeadragonAsset::register($this);
$imagesUrl = $assetBundle->baseUrl . '/images/';
?>

<div id="openseadragon-viewer" 
    data-base-url="<?= Url::to(['/']) ?>"
    data-images-url="<?= Html::encode($imagesUrl) ?>"
    data-image-path="<?= Html::encode($path) ?>">
</div>
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