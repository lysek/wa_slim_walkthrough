<?php
// DIC configuration

$container = $app->getContainer();

$container['renderer'] = function($c) {
    $settings = $c->get('settings')['renderer'];
	$engine = new Latte\Engine();
	$engine->setTempDirectory(__DIR__ . '/../cache');
	$latteView = new LatteView($engine, $settings['template_path']);
    return $latteView;
};

$container['db'] = function ($c) {
    $settings = $c->get('settings')['database'];
    $pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['name'], $settings['user'], $settings['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$pdo->query("SET NAMES 'utf8'");
    return $pdo;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};
