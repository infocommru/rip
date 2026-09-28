<?php

$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'timeZone' => getenv('TZ'),
    'language' => 'ru-RU',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
        '@images' => '@app/upload/rip2',
    ],
    'components' => [
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
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
