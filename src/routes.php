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
		$stmt = $this->db->query(	'SELECT persons.*, locations.city, locations.street_name, locations.street_number
									FROM persons
									LEFT JOIN locations ON locations.id = persons.id_location
									ORDER BY last_name');
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

$app->post('/smazat/{id}', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
	try {
		$stmt = $this->db->prepare('DELETE FROM persons WHERE id = :id');
		$stmt->bindValue(':id', $args['id']);
		$stmt->execute();
		return $response->withHeader('Location', '../vypis');
	} catch (PDOException $e) {
		die($e->getMessage());
	}
});

$app->get('/editace/{id}', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
	try {
		$stmt = $this->db->prepare('SELECT * FROM persons WHERE id = :id');
		$stmt->bindValue(':id', $args['id']);
		$stmt->execute();
		$osoba = $stmt->fetch();
		if($osoba) {
			return $this->renderer->render($response, 'edit.latte', [
				'form' => $osoba,
				'locations' => loadLocations($this->db)
			]);
		} else {
			return $response->withHeader('Location', '../vypis');
		}
	} catch (PDOException $e) {
		die($e->getMessage());
	}
});

$app->post('/aktualizace/{id}', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$hlaska = '';
	if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['nickname'])) {
		try {
			$stmt = $this->db->prepare('UPDATE persons SET first_name = :fn, last_name = :ln, nickname = :nn, id_location = :idl WHERE id = :id');
			$stmt->bindValue(':id', $args['id']);
			$stmt->bindValue(':fn', $data['first_name']);
			$stmt->bindValue(':ln', $data['last_name']);
			$stmt->bindValue(':nn', $data['nickname']);
			$stmt->bindValue(':idl', !empty($data['id_location']) ? $data['id_location'] : null);
			$stmt->execute();
			return $response->withHeader('Location', '../vypis');
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
	$data['id'] = $args['id'];
	return $this->renderer->render($response, 'edit.latte', [
		'hlaska' => $hlaska,
		'form' => $data,
		'locations' => loadLocations($this->db)
	]);
});

$app->get('/prihlasit', function(ServerRequestInterface $request, ResponseInterface $response) {
	return $this->renderer->render($response, 'login.latte');
});

$app->post('/prihlasit', function(ServerRequestInterface $request, ResponseInterface $response) {
	$data = $request->getParsedBody();
	if($data['login'] == $this->settings['auth']['user'] && sha1($data['pass']) == $this->settings['auth']['pass']) {
		$_SESSION['logged_in'] = true;
		return $response->withHeader('Location', 'user/profil');
	}
	return $response->withHeader('Location', 'prihlasit');
});

$app->group('/user', function () {

	$this->get('/profil', function(ServerRequestInterface $request, ResponseInterface $response) {
		return $this->renderer->render($response, 'profil.latte');
	});

	$this->get('/odhlasit', function(ServerRequestInterface $request, ResponseInterface $response) {
		$_SESSION['logged_in'] = false;
		return $response->withHeader('Location', '../vypis');
	});

})->add(function(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
	if(!empty($_SESSION['logged_in'])) {
		$this->renderer->addParams(['logged_in' => true]);
		return $next($request, $response);
	} else {
		return $response->withStatus(401)->withHeader('Location', '../vypis');
	}
});