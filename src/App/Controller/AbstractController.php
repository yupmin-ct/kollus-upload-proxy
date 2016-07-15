<?php

namespace App\Controller;

use Interop\Container\ContainerInterface;
use Slim\Container;

/**
 * Class AbstractController
 * @package App\Controller
 */
abstract class AbstractController
{
    /**
     * @var Container $container
     */
    protected $container;

    /**
     * AbstractController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
