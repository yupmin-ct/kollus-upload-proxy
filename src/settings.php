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

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
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
                'is_dev_mode' => true,
                'proxy_dir' =>  __DIR__.'/../cache/proxies',
                'cache' => null,
            ],
            'connection' => $databaseSetting
        ],

        // kollus settings
        'kollus' => $kollusSetting,
    ],
];
