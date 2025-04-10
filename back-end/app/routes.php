<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use DI\Container;
use Slim\Factory\AppFactory;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

	$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
		$name = $args['name'];
		$response->getBody()->write("Hello, $name");
		return $response;
	});


	// Endpoint per ottenere l'elenco dei laboratori
	$app->get('/api/laboratori', function (Request $request, Response $response, array $args) {
		$stmt = $this->get("db")->query("SELECT * FROM laboratori");
		$laboratori = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
		$response->getBody()->write(json_encode($laboratori));
	
		return $response->withHeader('Content-Type', 'application/json');
	});
	
	// Endpoint per ottenere i dettagli di un laboratorio tramite ID
	$app->get('/api/laboratori/{id}', function (Request $request, Response $response, array $args) {
		$id = $args['id'];
		$stmt = $this->get("db")->prepare("SELECT * FROM laboratori WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$laboratorio = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$laboratorio) {
			$response->getBody()->write(json_encode(['error' => 'Laboratorio non trovato']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}
		$response->getBody()->write(json_encode($laboratorio));
		return $response->withHeader('Content-Type', 'application/json');
	});
	
	// Endpoint per creare una nuova prenotazione
	$app->post('/api/prenotazioni', function (Request $request, Response $response, array $args) {
		$data = $request->getParsedBody();
		
		// Validazione dei dati in ingresso
		if (!isset($data['utente_id']) || !isset($data['laboratorio_id']) || !isset($data['data_prenotazione'])) {
			$response->getBody()->write(json_encode(['error' => 'Dati mancanti']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		
		$stmt = $this->get("db")->prepare("INSERT INTO prenotazioni (utente_id, laboratorio_id, data_prenotazione, ora_inizio, ora_fine, stato) 
									VALUES (:utente_id, :laboratorio_id, :data_prenotazione, :ora_inizio, :ora_fine, 'in attesa')");
		
		$stmt->bindParam(':utente_id', $data['utente_id'], PDO::PARAM_INT);
		$stmt->bindParam(':laboratorio_id', $data['laboratorio_id'], PDO::PARAM_INT);
		$stmt->bindParam(':data_prenotazione', $data['data_prenotazione']);
		
		// Imposta ora_inizio e ora_fine se presenti
		$ora_inizio = isset($data['ora_inizio']) ? $data['ora_inizio'] : null;
		$ora_fine = isset($data['ora_fine']) ? $data['ora_fine'] : null;
		$stmt->bindParam(':ora_inizio', $ora_inizio);
		$stmt->bindParam(':ora_fine', $ora_fine);
		
		$stmt->execute();
		$prenotazione_id = $this->db->lastInsertId();
		
		$response->getBody()->write(json_encode(['success' => true, 'prenotazione_id' => $prenotazione_id]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
	});

};
