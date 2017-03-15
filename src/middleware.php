<?php
// Application middleware

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$app->add(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
	$currentPath = dirname($_SERVER['PHP_SELF']);
	$this->renderer->addParams([
		'base_path' => $currentPath == '/' ? $currentPath : $currentPath . '/'
	]);
    return $next($request, $response);
});