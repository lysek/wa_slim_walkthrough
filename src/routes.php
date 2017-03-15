<?php

// Routes

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

function loadLocations(PDO $db) {
	try {
		$stmt = $db->query('SELECT * FROM locations ORDER BY city');
		return $stmt->fetchAll();
	} catch (PDOException $e) {
		die($e->getMessage());
	}
}

$app->get('/[vypis]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	try {
		$stmt = $this->db->query('SELECT * FROM persons LEFT JOIN locations ON locations.id = persons.id_location ORDER BY last_name');
		$persons = $stmt->fetchAll();
		return $this->renderer->render($response, 'index.latte', [
			'persons' => $persons
		]);
	} catch (PDOException $e) {
		die($e->getMessage());
	}
});

$app->get('/pridat', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
	return $this->renderer->render($response, 'create.latte', [
		'form' => [
			'first_name' => '',
			'last_name' => '',
			'nickname' => '',
			'id_location' => ''
		],
		'locations' => loadLocations($this->db)
	]);
});

$app->post('/ulozit', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$hlaska = '';
	if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['nickname'])) {
		try {
			$stmt = $this->db->prepare('INSERT INTO persons (first_name, last_name, nickname, id_location) VALUES (:fn, :ln, :nn, :idl)');
			$stmt->bindValue(':fn', $data['first_name']);
			$stmt->bindValue(':ln', $data['last_name']);
			$stmt->bindValue(':nn', $data['nickname']);
			$stmt->bindValue(':idl', !empty($data['id_location']) ? $data['id_location'] : null);
			$stmt->execute();
			return $response->withHeader('Location', 'vypis');
		} catch (PDOException $e) {
			if ($e->getCode() == 23000) {
				$hlaska = 'Takovato osoba jiz existuje';
			} else {
				die($e->getMessage());
			}
		}
	} else {
		$hlaska = 'Nebyly vyplneny vsechny povinne informace';
	}
	return $this->renderer->render($response, 'create.latte', [
		'hlaska' => $hlaska,
		'form' => $data,
		'locations' => loadLocations($this->db)
	]);
});
