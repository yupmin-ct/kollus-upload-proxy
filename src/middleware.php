<?php
// Application middleware

if ($settings['settings']['kollus']) {
    foreach ($settings['settings']['kollus'] as $serviceAccountKey => $settingItem) {
        $app->add(new \Slim\Middleware\HttpBasicAuthentication([
            'path' => '/' . $serviceAccountKey . '/upload/list',
            'realm' => $settingItem['service_account_name'].'\'s upload file list',
            'users' => $settingItem['http_auth_users'],
        ]));
    }
}
