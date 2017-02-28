<?php
/**
 * Yii console bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$config = [
    'id' => 'app-console',
    'basePath' => __DIR__,
    'controllerNamespace' => 'superlyons\\mptt',
    'aliases' => [
        '@superlyons/mptt' => __DIR__,
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=abc',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
    ],
];

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
