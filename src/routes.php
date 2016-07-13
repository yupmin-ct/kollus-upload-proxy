<?php
// Routes

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * create_url proxy api
 */
$app->post('/upload/create_url', function (Request $request, Response $response) {
    /**
     * @var Monolog\Logger $logger
     */
    $logger = $this->get('logger');

    /**
     * @var array $kollusSettings
     */
    $kollusSettings = $this->get('settings')['kollus'];

    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    $entityManager = $this->get('entityManager');

    /**
     * @var \App\Repository\CallbackDataRepository $repository
     */
    $repository = $entityManager->getRepository('App\Entity\CallbackData');

    $postParams = $request->getParsedBody();

    $logger->info(
        '/upload/create_url - Start',
        [
            'post_params' => $postParams,
        ]
    );

    $oldUploadFileKey = null;
    if (isset($postParams['old_upload_file_key'])) {
        $oldUploadFileKey = $postParams['old_upload_file_key'];
        unset($postParams['old_upload_file_key']);
    }

    $kollusApiHttpClient = new \GuzzleHttp\Client([
        'base_uri' => 'http://api.' . $kollusSettings['domain'] . '/0/',
        'timeout'  => 10.0
    ]);

    try {
        $kollusApiHttpResponse = $kollusApiHttpClient->post(
            'media_auth/upload/create_url.json',
            ['form_params' => $postParams]
        );

        $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
        $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

        if ($kollusApiHttpStatusCode === 200 &&
            !empty($kollusApiHttpResponseBody) &&
            ($kollusApiHttpResponseJSON = json_decode($kollusApiHttpResponseBody)) &&
            ((isset($kollusApiHttpResponseJSON->error) &&
                    (int)$kollusApiHttpResponseJSON->error === 0) ||
                !isset($kollusApiHttpResponseJSON->error)) &&
            !empty($oldUploadFileKey) &&
            !empty($kollusApiHttpResponseJSON->result->upload_file_key)
        ) {
            /**
             * @var string $newUploadFileKey
             */
            $newUploadFileKey = $kollusApiHttpResponseJSON->result->upload_file_key;

            $repository->registerBy($oldUploadFileKey, $newUploadFileKey);

            $logger->info(
                '/upload/create_url - End',
                [
                    'status_code' => $kollusApiHttpStatusCode,
                    'body' => $kollusApiHttpResponseBody,
                    'post_params' => $postParams,
                    'new_upload_file_key' => $newUploadFileKey,
                    'old_upload_file_key' => $oldUploadFileKey,
                ]
            );
        } else {
            $logger->error(
                '/upload/create_url - Kollus api error',
                [
                    'status_code' => $kollusApiHttpStatusCode,
                    'body' => $kollusApiHttpResponseBody,
                    'post_params' => $postParams,
                    'old_upload_file_key' => $oldUploadFileKey,
                ]
            );
        }

    } catch (GuzzleHttp\Exception\ClientException $e) {
        $kollusApiHttpResponse = $e->getResponse();
        $kollusApiHttpRequest = $e->getRequest();

        $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
        $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

        $logger->error(
            '/upload/create_url - Kollus api error',
            [
                'url' => $kollusApiHttpRequest->getUri(),
                'status_code' => $kollusApiHttpStatusCode,
                'body' => $kollusApiHttpResponseBody,
                'post_params' => $postParams,
                'old_upload_file_key' => $oldUploadFileKey,
            ]
        );
    }

    return $response->withJson(json_decode($kollusApiHttpResponseBody), $kollusApiHttpStatusCode);
});

/**
 * channel_callback proxy api
 */
$app->post('/upload/channel_callback', function (Request $request, Response $response) {
    /**
     * @var Monolog\Logger $logger
     */
    $logger = $this->get('logger');

    /**
     * @var array $kollusSettings
     */
    $kollusSettings = $this->get('settings')['kollus'];

    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    $entityManager = $this->get('entityManager');

    /**
     * @var \App\Repository\CallbackDataRepository $repository
     */
    $repository = $entityManager->getRepository('App\Entity\CallbackData');

    $postParams = $request->getParsedBody();

    $logger->info(
        '/upload/channel_callback - Start',
        [
            'channel_callback_url' => $kollusSettings['channel_callback_url'],
            'post_params' => $postParams,
        ]
    );

    $newUploadFileKey = null;
    if (isset($postParams['upload_file_key'])) {
        $newUploadFileKey = $postParams['upload_file_key'];

        $proxyHttpClient = new \GuzzleHttp\Client([
            'timeout'  => 10.0
        ]);

        try {
            /**
             * @var \App\Entity\CallbackData $callbackData
             */
            $callbackData = $repository->findOneBy(['newUploadFileKey' => $newUploadFileKey]);

            if (!empty($callbackData)) {
                $postParams['old_upload_file_key'] = $callbackData->getOldUploadFileKey();
            }

            $proxyHttpResponse = $proxyHttpClient->post(
                $kollusSettings['channel_callback_url'],
                ['form_params' => $postParams]
            );

            $proxyHttpStatusCode = $proxyHttpResponse->getStatusCode();
            $proxyHttpResponseBody = $proxyHttpResponse->getBody()->getContents();

            /**
             * if new_upload_file_key exists and proxy is success.
             */
            if ($proxyHttpStatusCode === 200 &&
                !empty($newUploadFileKey)) {
                $repository->setIsDeletedByNewUploadFileKey($newUploadFileKey);
            }

            $logger->info(
                '/upload/channel_callback - End',
                [
                    'channel_callback_url' => $kollusSettings['channel_callback_url'],
                    'status_code' => $proxyHttpStatusCode,
                    'body' => $proxyHttpResponseBody,
                    'post_params' => $postParams,
                    'new_upload_file_key' => $newUploadFileKey,
                ]
            );
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $proxyHttpResponse = $e->getResponse();
            $proxyHttpRequest = $e->getRequest();

            $proxyHttpStatusCode = $proxyHttpResponse->getStatusCode();
            $proxyHttpResponseBody = $proxyHttpResponse->getBody()->getContents();

            $logger->error(
                '/upload/channel_callback - Proxy http request error',
                [
                    'url' => $proxyHttpRequest->getUri(),
                    'status_code' => $proxyHttpStatusCode,
                    'body' => $proxyHttpResponseBody,
                    'post_params' => $postParams,
                    'new_upload_file_key' => $newUploadFileKey,
                ]
            );
        }
    } else {
        $proxyHttpStatusCode = 404;
        $proxyHttpResponseBody = 'Not found.';

        $logger->error(
            '/upload/channel_callback - New upload file key is not exists',
            [
                'post_params' => $postParams,
            ]
        );
    }
    return $response->withStatus($proxyHttpStatusCode)->write($proxyHttpResponseBody);
});
