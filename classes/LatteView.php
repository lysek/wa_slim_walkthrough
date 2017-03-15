<?php

use \Psr\Http\Message\ResponseInterface as Response;

class LatteView {

	private $latte;
	private $pathToTemplates;
	private $additionalParam = [];

	function __construct(Latte\Engine $latte, $pathToTemplates) {
		$this->latte = $latte;
		$this->pathToTemplates = $pathToTemplates;
	}

	function addParams(array $params) {
		$this->additionalParam = array_merge($this->additionalParam, $params);
	}

	function render(Response $response, $name, array $params = []) {
		$name = $this->pathToTemplates . '/' . $name;
		$params = array_merge($this->additionalParam, $params);
		$output = $this->latte->renderToString($name, $params);
		$response->getBody()->write($output);
        return $response;
	}

}
