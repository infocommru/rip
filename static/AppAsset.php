<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\static;

use yii\web\AssetBundle;
use yii\helpers\Url;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\jui\JuiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];

    public function init()
    {
        parent::init();

        $this->css = [
            Url::to(['/css/site.css'])
        ];
    }

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
