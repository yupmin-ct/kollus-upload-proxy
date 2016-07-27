<?php
// Commands

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @var \Slim\Container $container
 */
$app->command('clear-callback-data serviceAccountKey [afterSeconds]',function (
    OutputInterface $output,
    $serviceAccountKey, $afterSeconds
) use ($container) {
    $kollusSettings = $container->get('settings')['kollus'];

    /**
     * @var Doctrine\ORM\EntityManager $entityManager
     */
    $entityManager = $container->get('entityManager');

    /**
     * @var Monolog\Logger $logger
     */
    $logger = $container->get('logger');

    /**
     * @var \App\Repository\CallbackDataRepository $repository
     */
    $repository = $entityManager->getRepository('App\Entity\CallbackData');

    if (!isset($kollusSettings[$serviceAccountKey])) {
        $output->writeln('<error>Service account key is not exists.</error>');
        return 1;
    }

    $context = [
        'service_account_key' => $serviceAccountKey,
        'afterSeconds' => $afterSeconds,
    ];
    $logger->info('clear-callback-data - Start', $context);

    /**
     * @var App\Entity\CallbackData[] $callbackDatas
     */
    $callbackDatas = $repository->findAllAfterSeconds($serviceAccountKey, $afterSeconds);

    $baseUri = 'http://api.' . $kollusSettings[$serviceAccountKey]['domain'] . '/0/';

    foreach ($callbackDatas as $callbackData) {
        $oldUploadFileKey = $callbackData->getOldUploadFileKey();

        if (!empty($oldUploadFileKey)) {
            $apiUri = 'media/library/delete/'.$oldUploadFileKey.'.json';

            $kollusApiHttpClient = new GuzzleHttp\Client(
                [
                    'base_uri' => $baseUri,
                    'timeout' => 10.0
                ]
            );

            try {
                $kollusApiHttpResponse = $kollusApiHttpClient->post(
                    $apiUri,
                    [
                        'query' => ['access_token' => $kollusSettings[$serviceAccountKey]['api_access_token']],
                    ]
                );

                $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
                $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

                if ($kollusApiHttpStatusCode === 200 &&
                    !empty($kollusApiHttpResponseBody) &&
                    ($kollusApiHttpResponseJSON = json_decode($kollusApiHttpResponseBody)) &&
                    ((isset($kollusApiHttpResponseJSON->error) && (int)$kollusApiHttpResponseJSON->error === 0)
                        || !isset($kollusApiHttpResponseJSON->error))
                ) {
                    // success
                    $repository->removeBy($callbackData);
                } else {
                    // fail
                    $message = 'clear-callback-data - Kollus api result error';
                    $context = [
                        'service_account_key' => $serviceAccountKey,
                        'afterSeconds' => $afterSeconds,
                        'url' => $baseUri.$apiUri,
                        'status_code' => $kollusApiHttpStatusCode,
                        'body' => $kollusApiHttpResponseBody,
                        'old_upload_file_key' => $oldUploadFileKey
                    ];

                    $repository->setIsErrorBy($callbackData, $message, $context);

                    $logger->error($message, $context);
                }
            } catch (\Exception $e) {
                if ($e instanceof \GuzzleHttp\Exception\ClientException OR
                    $e instanceof \GuzzleHttp\Exception\ServerException) {

                    $kollusApiHttpResponse = $e->getResponse();
                    $kollusApiHttpRequest = $e->getRequest();

                    $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
                    $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

                    $message = 'clear-callback-data - Kollus api request error';
                    $context = [
                        'service_account_key' => $serviceAccountKey,
                        'afterSeconds' => $afterSeconds,
                        'url' => $kollusApiHttpRequest->getUri(),
                        'status_code' => $kollusApiHttpStatusCode,
                        'body' => $kollusApiHttpResponseBody,
                        'old_upload_file_key' => $oldUploadFileKey
                    ];

                    $repository->setIsErrorBy($callbackData, $message, $context);

                    $logger->error($message, $context);
                } else {
                    $message = 'clear-callback-data - Kollus api request error';
                    $context = [
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ];
                    $logger->error($message, $context);
                }
            } // try catch
        } else {
            // success
            $repository->removeBy($callbackData);
        }
    } // foreach

    $context = [
        'callback_datas' => $callbackDatas,
    ];
    $logger->info('clear-callback-data - END', $context);

    $output->writeln('<info>Finished.</info>');

    return 0;
})
->Defaults(['afterSeconds' => 3600])
->Descriptions(
    'Clear callback data',
    [
        'serviceAccountKey' => 'Service account key',
        'afterSeconds' => 'Delete after seconds',
    ]
);
