<?php

use \Symfony\Component\Yaml\Parser as YamlParser;

$databaseSetting = [];
$kollusSetting = [];
$configFilePath = __DIR__ . '/../config.yml';
$yamlParser = new YamlParser();
if (file_exists($configFilePath)) {
    $databaseSetting  = $yamlParser->parse(file_get_contents($configFilePath))['database'];
    $kollusSetting = $yamlParser->parse(file_get_contents($configFilePath))['kollus'];
}
$is_dev_mode = true;

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Twig View settings
        'view' => [
            'template_path' => __DIR__ . '/../templates/',
            'settings' => [
                'cache' => $is_dev_mode ? false : __DIR__ . '/../cache/twig/',
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
                'is_dev_mode' => $is_dev_mode,
                'proxy_dir' =>  __DIR__.'/../cache/proxies',
                'cache' => null,
            ],
            'connection' => $databaseSetting
        ],

        // kollus settings
        'kollus' => $kollusSetting,
    ],
];
