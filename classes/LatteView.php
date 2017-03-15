<?php

use \Psr\Http\Message\ResponseInterface as Response;

class LatteView {

	private $latte;
	private $pathToTemplates;

	function __construct(Latte\Engine $latte, $pathToTemplates) {
		$this->latte = $latte;
		$this->pathToTemplates = $pathToTemplates;
	}

	function render(Response $response, $name, array $params = []) {
		$name = $this->pathToTemplates . '/' . $name;
		$output = $this->latte->renderToString($name, $params);
		$response->getBody()->write($output);
        return $response;
	}

}
