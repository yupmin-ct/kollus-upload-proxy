<?php
// Commands

use Symfony\Component\Console\Output\OutputInterface;

$app->command('clear-callback-data [after_seconds]', function (OutputInterface $output, $after_seconds) use ($container) {
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

    /**
     * @var App\Entity\CallbackData[] $callbackDatas
     */
    $callbackDatas = $repository->findAllAfterSeconds($after_seconds);

    $logger->info(
        'clear-callback-data - Start',
        [
            'after_seconds' => $after_seconds,
        ]
    );

    foreach ($callbackDatas as $callbackData) {
        $oldUploadFileKey = $callbackData->getOldUploadFileKey();

        $kollusApiHttpClient = new GuzzleHttp\Client([
            'base_uri' => 'http://api.' . $kollusSettings['domain'] . '/0/',
            'timeout'  => 10.0
        ]);

        try {
            $kollusApiHttpResponse = $kollusApiHttpClient->post(
                'media/library/delete/'.$oldUploadFileKey.'.json',
                [
                    'query' => [
                        'access_token' => $kollusSettings['api_access_token'],
                    ],
                ]
            );

            /**
             * $var string $kollusApiHttpResponseBody
             */
            $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

            if ($kollusApiHttpResponse->getStatusCode() === 200 &&
                !empty($kollusApiHttpResponseBody) &&
                ($kollusApiHttpResponseJSON = json_decode($kollusApiHttpResponseBody)) &&
                ((isset($kollusApiHttpResponseJSON->error) && (int)$kollusApiHttpResponseJSON->error === 0)
                    || !isset($kollusApiHttpResponseJSON->error))
            ) {
                // remove
                $repository->removeBy($callbackData);
            } else {
                // set isError
                $repository->setIsErrorBy($callbackData);

                $logger->error(
                    'Kollus Api Error',
                    [
                        'status_code' => $kollusApiHttpResponse->getStatusCode(),
                        'body' => $kollusApiHttpResponseBody,
                        'old_upload_file_key' => $oldUploadFileKey
                    ]
                );
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            $kollusApiHttpResponse = $e->getResponse();
            $kollusApiHttpRequest = $e->getRequest();

            /**
             * $var string $kollusApiHttpResponseBody
             */
            $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

            $logger->error(
                'Kollus Api Response Error',
                [
                    'url' => $kollusApiHttpRequest->getUri(),
                    'status_code' => $kollusApiHttpResponse->getStatusCode(),
                    'body' => $kollusApiHttpResponseBody,
                    'old_upload_file_key' => $oldUploadFileKey
                ]
            );
        }
    }

    $logger->info(
        'clear-callback-data - END',
        [
            'callback_datas' => $callbackDatas,
        ]
    );

    $output->writeln('<info>finished.</info>');

    return 0;
})
    ->Defaults(['after_seconds' => 3600])
    ->Descriptions('Clear callback data', [
    'after_seconds' => 'Delete after seconds',
]);
