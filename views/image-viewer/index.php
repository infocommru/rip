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
    OpenSeadragon({
        id: "openseadragon-viewer",
        prefixUrl: "<?= $imagesUrl ?>",
        drawer: "html",
        tileSources: {
            type: 'image',
            url: "<?= $path ?>"
        }
    });
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