<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\static;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        '/static/css/site.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\jui\JuiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];

    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        $view->registerLinkTag([
            'rel' => 'icon',
            'type' => 'image/png',
            'href' => \Yii::getAlias('@web/favicon.png'),
        ]);
    }
}
