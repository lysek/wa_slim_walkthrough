<?php
// Routes

$app->get('/', function ($request, $response, $args) {
	$stmt = $this->db->query("SELECT * FROM persons ORDER BY last_name");
	$persons = $stmt->fetchAll();
    return $this->renderer->render($response, 'index.latte', [
		'persons' => $persons
	]);
});
