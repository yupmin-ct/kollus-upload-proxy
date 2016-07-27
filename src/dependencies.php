<?php
// DIC configuration

$container = $app->getContainer();

// twig view
$container['view'] = function ($c) {
    $settings = $c->get('settings')['view'];

    $view = new \Slim\Views\Twig($settings['template_path'], $settings['settings']);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $c['router'],
        $c['request']->getUri()
    ));

    return $view;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG, true, 0666));
    return $logger;
};

// doctirine
$container['entityManager'] = function ($c) {
    $settings = $c->get('settings')['doctrine'];
    $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        $settings['meta']['entity_paths'],
        $settings['meta']['is_dev_mode'],
        $settings['meta']['proxy_dir'],
        $settings['meta']['cache'],
        false
    );

    if ($settings['meta']['is_dev_mode']) {
        $config->setSQLLogger(new \Cobaia\Doctrine\MonologSQLLogger(
            null,
            new Monolog\Handler\StreamHandler(__DIR__ . '/../logs/doctrine.log', Monolog\Logger::DEBUG, true, 0666)
        ));
    }

    return \Doctrine\ORM\EntityManager::create($settings['connection'], $config);
};
