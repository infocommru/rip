<?php

$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' =>"Сведения о захоронении",
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'language' => 'ru-RU',
    'timeZone' => getenv('TZ'),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@images' => '@app/upload/rip2',
    ],
     'container' => [
        'definitions' => [
            \yii\widgets\LinkPager::class => \yii\bootstrap5\LinkPager::class,
        ],
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY'),
        ],
        'assetManager' => [
            'baseUrl' => getenv('BASE_URL') . "/assets",
        ],
        'redis' => [
		    'class' => 'yii\redis\Connection',
		    'hostname' => 'redis',
		    'port' => 6379,
		    'database' => 0,
    	],
    	'elasticsearch' => [
		    'class' => 'yii\elasticsearch\Connection',
		    'nodes' => [
		        ['http_address' => 'opensearch-node:9200'], // Адрес вашего OpenSearch
		    ],
		    'auth' => ['username' => 'admin', 'password' => getenv('OPENSEARCH_INITIAL_ADMIN_PASSWORD')],
		    'dslVersion' => 7,
    	],
        'cache' => [
           //'class' => 'yii\caching\FileCache',
           'class' => 'yii\redis\Cache',
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis',
            'channel' => 'queue',
            'as log' => \yii\queue\LogBehavior::class,
            'ttr' => 86400,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
         
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('BASE_URL'),
            'rules' => [],
        ],
        
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
