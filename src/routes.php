<?php
// Routes

$app->get('/{serviceAccountKey}/upload/list[.{suffix:json}]', '\App\Controller\UploadController:listAction')
    ->setName('upload-list');

$app->group('/{serviceAccountKey}/upload/list/{id:[0-9]+}', function() {
    $this->delete('', '\App\Controller\UploadController:listDeleteAction')->setName('upload-list-delete');
    $this->put('/reset', '\App\Controller\UploadController:listResetAction')->setName('upload-list-reset');
});

$app->post('/{serviceAccountKey}/upload/create_url', '\App\Controller\UploadController:createUrlAction');

$app->post('/{serviceAccountKey}/upload/channel_callback', '\App\Controller\UploadController:channelCallbackAction');
