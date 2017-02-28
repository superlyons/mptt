<?php

$config = [
	'id' => 'app-test',
	'defaultRoute' => "default",
    'basePath' => dirname(dirname(__DIR__)),
    //'controllerNamespace' => 'superlyons\mptt\controllers',
    'bootstrap' => ['log'],
    //'language' => 'en-US',
    
    'aliases' => [
	    '@superlyons/mptt' => dirname(dirname(__DIR__)),
        "@superlyons/idGenerator" => dirname(dirname(__DIR__))."/vendor/superlyons/yii2-idGenerator"
	],

    'modules' => [
		'mptt' => [
		            'class' => 'superlyons\mptt\Module',
                    'languageSelfManage' => true,
		        ]
    ],

    'components' => [
        'user' => [
            /*'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,*/
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=abc',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
            'rules' => [],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '8-tW-9x5-5Q_ilYLi26YWMYMPMILzHLF',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
        	]
        ],
        'i18n' => [
            'translations' =>[
                '*'=>[
                    'class' => 'yii\i18n\PhpMessageSource',
                    'sourceLanguage' => 'en',
                    'basePath' => '@superlyons/mptt/nothing'
                ]
            ]
        ]
    ]
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;