<?php

use \Symfony\Component\Yaml\Parser as YamlParser;

$databaseSetting = [];
$kollusSetting = [];
$kollusUploadProxySetting = [];
$configFilePath = __DIR__ . '/../config.yml';
$yamlParser = new YamlParser();
if (file_exists($configFilePath)) {
    $parser = $yamlParser->parse(file_get_contents($configFilePath));
    $databaseSetting  = $parser['database'];
    $kollusSetting = $parser['kollus'];
    $kollusUploadProxySetting = $parser['kollus-upload-proxy'];
}

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        'domain' => $kollusUploadProxySetting['domain'],

        // Twig View settings
        'view' => [
            'template_path' => __DIR__ . '/../templates/',
            'settings' => [
                'cache' => $kollusUploadProxySetting['use_twig_cache'] ? __DIR__ . '/../cache/twig/' : false,
            ]
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        // Doctrine settings
        'doctrine' => [
            'meta' => [
                'entity_paths' => [
                    'src/App/Entity'
                ],
                'is_dev_mode' => $kollusUploadProxySetting['is_dev_mode'],
                'proxy_dir' =>  __DIR__ . '/../cache/proxies',
                'cache' => null,
            ],
            'connection' => $databaseSetting
        ],

        // kollus settings
        'kollus' => $kollusSetting,
    ],
];
