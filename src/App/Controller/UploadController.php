<?php

namespace App\Controller;

use App\Repository\CallbackDataRepository;
use App\Entity\CallbackData;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UploadController
 * @package App\Controller
 */
class UploadController extends AbstractController
{
    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var array $settings
     */
    protected $settings;

    /**
     * @var RouterInterface $router
     */
    protected $router;

    /**
     * @var array $kollusSetting
     */
    protected $kollusSettings;

    /**
     * UploadController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->entityManager = $this->container->get('entityManager');
        $this->logger = $this->container->get('logger');
        $this->settings = $this->container->get('settings');
        $this->router = $this->container->get('router');
        $this->kollusSettings = $this->settings['kollus'];
    }

    /**
     * createUrlAction - POST
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function createUrlAction(Request $request, Response $response)
    {
        /**
         * @var CallbackDataRepository $repository
         */
        $repository = $this->entityManager->getRepository('App\Entity\CallbackData');

        $postParams = $request->getParsedBody();
        $serviceAccountKey = $request->getAttribute('serviceAccountKey');

        if (!isset($this->kollusSettings[$serviceAccountKey])) {
            return $response->withStatus(404)->write('Page not found');
        }

        $context = [
            'service_account_key' => $serviceAccountKey,
            'post_params' => $postParams,
        ];
        $this->logger->info('/upload/create_url - Start', $context);

        $oldUploadFileKey = '';
        if (isset($postParams['old_upload_file_key'])) {
            $oldUploadFileKey = trim($postParams['old_upload_file_key']);
            unset($postParams['old_upload_file_key']);
        }

        $baseUri = 'http://api.' . $this->kollusSettings[$serviceAccountKey]['domain'] . '/0/';
        $apiUri = 'media_auth/upload/create_url.json';

        $kollusApiHttpClient = new \GuzzleHttp\Client([
            'base_uri' => $baseUri,
            'timeout'  => 10.0
        ]);

        try {
            $kollusApiHttpResponse = $kollusApiHttpClient->post(
                $apiUri,
                [
                    'form_params' => $postParams,
                    'query' => ['access_token' => $this->kollusSettings[$serviceAccountKey]['api_access_token']],
                ]
            );
            $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
            $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

            $context = [
                'service_account_key' => $serviceAccountKey,
                'post_params' => $postParams,
                'url' => $baseUri . $apiUri,
                'status_code' => $kollusApiHttpStatusCode,
                'body' => $kollusApiHttpResponseBody,
                'old_upload_file_key' => $oldUploadFileKey
            ];

            if ($kollusApiHttpStatusCode === 200 &&
                !empty($kollusApiHttpResponseBody) &&
                ($kollusApiHttpResponseJSON = json_decode($kollusApiHttpResponseBody)) &&
                ((isset($kollusApiHttpResponseJSON->error) &&
                        (int)$kollusApiHttpResponseJSON->error === 0) ||
                    !isset($kollusApiHttpResponseJSON->error)) &&
                isset($kollusApiHttpResponseJSON->result->upload_file_key)
            ) {
                /**
                 * @var string $newUploadFileKey
                 */
                $newUploadFileKey = $kollusApiHttpResponseJSON->result->upload_file_key;

                $repository->registerBy($serviceAccountKey, $oldUploadFileKey, $newUploadFileKey);

                $context['new_upload_file_key'] = $newUploadFileKey;
                $this->logger->info('/upload/create_url - End', $context);
            } else {
                $this->logger->error('/upload/create_url - Kollus api result error', $context);
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\ClientException ||
                $e instanceof \GuzzleHttp\Exception\ServerException) {
                $kollusApiHttpResponse = $e->getResponse();

                $kollusApiHttpStatusCode = $kollusApiHttpResponse->getStatusCode();
                $kollusApiHttpResponseBody = $kollusApiHttpResponse->getBody()->getContents();

                $message = '/upload/create_url - Kollus Api Error';
                $context = [
                    'service_account_key' => $serviceAccountKey,
                    'post_params' => $postParams,
                    'url' => $baseUri.$apiUri,
                    'status_code' => $kollusApiHttpStatusCode,
                    'body' => $kollusApiHttpResponseBody,
                    'old_upload_file_key' => $oldUploadFileKey
                ];
                $this->logger->error($message, $context);
            } else {
                $kollusApiHttpStatusCode = 500;
                $kollusApiHttpResponseBody = $e->getMessage();

                $message = '/upload/create_url - Kollus Api Error';
                $context = [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                ];
                $this->logger->error($message, $context);
            }
        }

        return $response->withJson(json_decode($kollusApiHttpResponseBody), $kollusApiHttpStatusCode);
    }

    /**
     * channelCallbackAction - POST
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function channelCallbackAction(Request $request, Response $response)
    {
        /**
         * @var CallbackDataRepository $repository
         */
        $repository = $this->entityManager->getRepository('App\Entity\CallbackData');

        $postParams = $request->getParsedBody();
        $serviceAccountKey = $request->getAttribute('serviceAccountKey');

        if (!isset($this->kollusSettings[$serviceAccountKey])) {
            return $response->withStatus(404)->write('Page not found');
        }

        $context = [
            'service_account_key' => $serviceAccountKey,
            'post_params' => $postParams,
            'channel_callback_url' => $this->kollusSettings[$serviceAccountKey]['channel_callback_url'],
        ];
        $this->logger->info('/upload/channel_callback - Start', $context);

        $newUploadFileKey = null;
        if (!isset($postParams['upload_file_key'])) {
            $this->logger->error('/upload/channel_callback - New upload file key is not exists.', $context);
            $data = ['error' => 1, 'message' => 'New upload file key is not exists.'];
            return $response->withJson($data, 404);
        }
        $newUploadFileKey = $postParams['upload_file_key'];

        /**
         * @var CallbackData $callbackData
         */
        $callbackData = $repository->findOneBy(['newUploadFileKey' => $newUploadFileKey]);
        if (!empty($callbackData)) {
            $postParams['old_upload_file_key'] = $callbackData->getOldUploadFileKey();
        }

        $proxyHttpClient = new \GuzzleHttp\Client(['timeout'  => 10.0]);
        try {
            $proxyHttpResponse = $proxyHttpClient->post(
                $this->kollusSettings[$serviceAccountKey]['channel_callback_url'],
                ['form_params' => $postParams]
            );
            $proxyHttpStatusCode = $proxyHttpResponse->getStatusCode();
            $proxyHttpResponseBody = $proxyHttpResponse->getBody()->getContents();

            /**
             * if new_upload_file_key exists and proxy is success.
             */
            if ($proxyHttpStatusCode === 200 && !empty($callbackData)) {
                $repository->setWillDeletedBy($callbackData);
            }

            $context = [
                'service_account_key' => $serviceAccountKey,
                'post_params' => $postParams,
                'channel_callback_url' => $this->kollusSettings[$serviceAccountKey]['channel_callback_url'],
                'status_code' => $proxyHttpStatusCode,
                'body' => $proxyHttpResponseBody,
                'new_upload_file_key' => $newUploadFileKey,
            ];

            $this->logger->info('/upload/channel_callback - End', $context);
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\ClientException ||
                $e instanceof \GuzzleHttp\Exception\ServerException) {
                $proxyHttpResponse = $e->getResponse();

                $proxyHttpStatusCode = $proxyHttpResponse->getStatusCode();
                $proxyHttpResponseBody = $proxyHttpResponse->getBody()->getContents();

                $message = '/upload/channel_callback - Proxy http request error';
                $context = [
                    'service_account_key' => $serviceAccountKey,
                    'post_params' => $postParams,
                    'channel_callback_url' => $this->kollusSettings[$serviceAccountKey]['channel_callback_url'],
                    'status_code' => $proxyHttpStatusCode,
                    'body' => $proxyHttpResponseBody,
                    'new_upload_file_key' => $newUploadFileKey,
                ];
                $this->logger->error($message, $context);
                if ($callbackData instanceof CallbackData) {
                    $repository->setIsErrorBy($callbackData, $message, $context);
                }
            } else {
                $proxyHttpStatusCode = 500;
                $proxyHttpResponseBody = $e->getMessage();

                $message = '/upload/channel_callback - Kollus Api Error';
                $context = [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                ];
                $this->logger->error($message, $context);
            }
        }

        return $response->withStatus($proxyHttpStatusCode)->write($proxyHttpResponseBody);
    }

    /**
     * listAction - GET
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listAction(Request $request, Response $response)
    {
        /**
         * @var CallbackDataRepository $repository
         */
        $repository = $this->entityManager->getRepository('App\Entity\CallbackData');

        /**
         * @var Twig $view
         */
        $view = $this->container->get('view');

        $getParams = $request->getQueryParams();
        $serviceAccountKey = $request->getAttribute('serviceAccountKey');
        $suffix = $request->getAttribute('suffix');

        if (!isset($this->kollusSettings[$serviceAccountKey])) {
            return $response->withStatus(404)->write('Page not found');
        }

        $perPage = 10;
        $page = isset($getParams['page']) ? (int)$getParams['page'] : 1;
        $paginator = $repository->findPaginatorByPage($serviceAccountKey, $page, $perPage);

        $pageCount = ceil($paginator->count() / $perPage);
        $pageItems = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $queryParams = [];
            if ($i !== 1) {
                $queryParams['page'] = $i;
            }
            $pageUrl = $this->router->pathFor('upload-list', ['serviceAccountKey' => $serviceAccountKey], $queryParams);
            $pageItem = [
                'thisPage' => $page === $i,
                'pageNumber' => $i,
                'pageUrl' => $pageUrl,
            ];
            $pageItems[] = $pageItem;
        }

        $data = [
            'serviceAccountKey' => $serviceAccountKey,
            'paginator' => $paginator,
            'pageCount' => $pageCount,
            'pageItems' => $pageItems,
            'kollusSettings' => $this->kollusSettings[$serviceAccountKey],
        ];

        if (in_array($suffix, ['json']) && $request->isXhr()) {
            return $response->withJson(
                [
                    'data' => [
                        'partials' => [
                            'list-page' => $view->fetch('upload/list-page.html.twig', $data)
                        ]
                    ]
                ],
                200
            );
        }

        return $view->render($response, 'upload/list.html.twig', $data);
    }

    /**
     * listResetAction - PUT
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listResetAction(Request $request, Response $response)
    {
        /**
         * @var CallbackDataRepository $repository
         */
        $repository = $this->entityManager->getRepository('App\Entity\CallbackData');

        $getParams = $request->getQueryParams();
        $serviceAccountKey = $request->getAttribute('serviceAccountKey');

        if (!isset($this->kollusSettings[$serviceAccountKey])) {
            return $response->withStatus(404)->write('Page not found');
        }

        $responseStatusCode = 200;
        $responseJSON = ['data' => []];

        if (!$request->isXhr()) {
            $responseJSON['message'] = 'Invalid request';
            return $response->withJson($responseJSON, 404);
        }

        $id = (int)$request->getAttribute('id');
        /**
         * @var CallbackData $callbackData
         */
        $callbackData = $repository->find($id);
        if (empty($callbackData)) {
            $responseJSON['message'] = 'Item is not exist.';
            return $response->withJson($responseJSON, 404);
        }

        $repository->resetBy($callbackData);
        $responseJSON['message'] = 'Successfully reset.';
        $responseJSON['data']['partial_url'] = $this->router->pathFor(
            'upload-list',
            ['serviceAccountKey' => $serviceAccountKey, 'suffix' => 'json'],
            $getParams
        );

        return $response->withJson($responseJSON, $responseStatusCode);
    }

    /**
     * listDeleteAction - DELETE
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listDeleteAction(Request $request, Response $response)
    {
        /**
         * @var CallbackDataRepository $repository
         */
        $repository = $this->entityManager->getRepository('App\Entity\CallbackData');

        $getParams = $request->getQueryParams();
        $serviceAccountKey = $request->getAttribute('serviceAccountKey');

        if (!isset($this->kollusSettings[$serviceAccountKey])) {
            return $response->withStatus(404)->write('Page not found');
        }

        $responseStatusCode = 200;
        $responseJSON = ['data' => []];

        if (!$request->isXhr()) {
            $responseJSON['message'] = 'Invalid request';
            return $response->withJson($responseJSON, 404);
        }

        $id = (int)$request->getAttribute('id');
        /**
         * @var CallbackData $callbackData
         */
        $callbackData = $repository->find($id);
        if (empty($callbackData)) {
            $responseJSON['message'] = 'Item is not exist.';
            return $response->withJson($responseJSON, 404);
        }

        $repository->deleteBy($callbackData);
        $responseJSON['message'] = 'Successfully deleted.';
        $responseJSON['data']['partial_url'] = $this->router->pathFor(
            'upload-list',
            ['serviceAccountKey' => $serviceAccountKey, 'suffix' => 'json'],
            $getParams
        );

        return $response->withJson($responseJSON, $responseStatusCode);
    }
}
