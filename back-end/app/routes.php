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

	$app->post('/api/login', function (Request $request, Response $response, array $args) {
		$data = $request->getParsedBody();

		$validationTable = [
			isset($data["username"]),
			isset($data["password"])
		];

		// Validazione dei dati in ingresso
		if (in_array(false, $validationTable)) {
			$response->getBody()->write(json_encode(['error' => 'Dati mancanti']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$username = $data['username'];
		$password = $data['password'];
		$stmt = $this->get("db")->prepare("SELECT * FROM utenti WHERE username = :username and password = :password");
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$hashed_password = sha1($password);
		$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
		$stmt->execute();
		$utente = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$utente) {
			$response->getBody()->write(json_encode(['error' => 'Utente non trovato']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$stmt = $this->get("db")->prepare("INSERT INTO sessioni (sessione_id, username, last_seen) VALUES (:sessione_id, :username, :last_seen)");
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$session_id = uniqid("", true);
		$current_time = date("Y-m-d H:i:s");
		$stmt->bindParam(':sessione_id', $session_id, PDO::PARAM_STR);
		$stmt->bindParam(':last_seen', $current_time, PDO::PARAM_STR);
		$stmt->execute();
		$id = $this->get("db")->lastInsertId();

		$stmt = $this->get("db")->prepare("SELECT * FROM sessioni WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_STR);
		$stmt->execute();
		$sessione = $stmt->fetch(PDO::FETCH_ASSOC);

		$response->getBody()->write(json_encode([ "sessione_id" => $sessione["sessione_id"] ]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	});

	$app->get('/api/logout/{sessione_id}', function (Request $request, Response $response, array $args) {
		$sessione_id = $args["sessione_id"];

		$stmt = $this->get("db")->prepare("SELECT * FROM sessioni WHERE sessione_id = :sessione_id");
		$stmt->bindParam(':sessione_id', $sessione_id, PDO::PARAM_STR);
		$stmt->execute();
		$sessione = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($sessione) {
			$stmt = $this->get("db")->prepare("DELETE FROM sessioni WHERE sessione_id = :sessione_id");
			$stmt->bindParam(':sessione_id', $sessione_id, PDO::PARAM_STR);
			$stmt->execute();
		}

		$response->getBody()->write(json_encode([ 'success' => true ]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
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

	$app->post('/api/laboratori', function (Request $request, Response $response, array $args) {
		$data = $request->getParsedBody();

		$validationTable = [
			isset($data["nome"]),
			isset($data["descrizione"]),
			isset($data["capacita"])
		];

		// Validazione dei dati in ingresso
		if (in_array(false, $validationTable)) {
			$response->getBody()->write(json_encode(['error' => 'Dati mancanti']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$stmt = $this->get("db")->prepare("INSERT INTO laboratori (nome, descrizione, capacita, orario_apertura, orario_chiusura, data_inizio, data_fine)
			VALUES (:nome, :descrizione, :capacita, :orario_apertura, :orario_chiusura, :data_inizio, :data_fine)");

		$stmt->bindParam(':nome', $data["nome"], PDO::PARAM_STR);
		$stmt->bindParam(':descrizione', $data["descrizione"], PDO::PARAM_STR);
		$stmt->bindParam(':capacita', $data["capacita"], PDO::PARAM_INT);
		$orario_apertura = isset($data["orario_apertura"]) && $data["orario_apertura"] !== '' ? $data["orario_apertura"] : null;
		$orario_chiusura = isset($data["orario_chiusura"]) && $data["orario_chiusura"] !== '' ? $data["orario_chiusura"] : null;
		$data_inizio = isset($data["data_inizio"]) && $data["data_inizio"] !== '' ? $data["data_inizio"] : null;
		$data_fine = isset($data["data_fine"]) && $data["data_fine"] !== '' ? $data["data_fine"] : null;
		
		$stmt->bindParam(':orario_apertura', $orario_apertura, PDO::PARAM_STR);
		$stmt->bindParam(':orario_chiusura', $orario_chiusura, PDO::PARAM_STR);
		$stmt->bindParam(':data_inizio', $data_inizio, PDO::PARAM_STR);
		$stmt->bindParam(':data_fine', $data_fine, PDO::PARAM_STR);

		$stmt->execute();
		$laboratorio_id = $this->get("db")->lastInsertId();
		
		$response->getBody()->write(json_encode(['success' => true, 'id' => $laboratorio_id]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
	});

	$app->put('/api/laboratori/{id}', function (Request $request, Response $response, array $args) {
		$id = $args['id'];
		$data = $request->getParsedBody();

		$validationTable = [
			isset($data["nome"]),
			isset($data["descrizione"]),
			isset($data["capacita"])
		];

		// Validazione dei dati in ingresso
		if (in_array(false, $validationTable)) {
			$response->getBody()->write(json_encode(['error' => 'Dati mancanti']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$stmt = $this->get("db")->prepare("
			UPDATE laboratori SET
				nome = :nome, 
				descrizione = :descrizione, 
				capacita = :capacita, 
				orario_apertura = :orario_apertura, 
				orario_chiusura = :orario_chiusura, 
				data_inizio = :data_inizio, 
				data_fine = :data_fine
			WHERE
				id = :id
		");

		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->bindParam(':nome', $data["nome"], PDO::PARAM_STR);
		$stmt->bindParam(':descrizione', $data["descrizione"], PDO::PARAM_STR);
		$stmt->bindParam(':capacita', $data["capacita"], PDO::PARAM_INT);
		$orario_apertura = isset($data["orario_apertura"]) && $data["orario_apertura"] !== '' ? $data["orario_apertura"] : null;
		$orario_chiusura = isset($data["orario_chiusura"]) && $data["orario_chiusura"] !== '' ? $data["orario_chiusura"] : null;
		$data_inizio = isset($data["data_inizio"]) && $data["data_inizio"] !== '' ? $data["data_inizio"] : null;
		$data_fine = isset($data["data_fine"]) && $data["data_fine"] !== '' ? $data["data_fine"] : null;
		
		$stmt->bindParam(':orario_apertura', $orario_apertura, PDO::PARAM_STR);
		$stmt->bindParam(':orario_chiusura', $orario_chiusura, PDO::PARAM_STR);
		$stmt->bindParam(':data_inizio', $data_inizio, PDO::PARAM_STR);
		$stmt->bindParam(':data_fine', $data_fine, PDO::PARAM_STR);
		$stmt->execute();
		
		$response->getBody()->write(json_encode(['success' => true, 'id' => $id]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	});
	
	$app->get('/api/prenotazioni/{id}', function (Request $request, Response $response, array $args) {
		$id = $args['id'];
		$stmt = $this->get("db")->prepare("SELECT * FROM prenotazioni WHERE laboratorio_id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$prenotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
		$response->getBody()->write(json_encode($prenotazioni));
	
		return $response->withHeader('Content-Type', 'application/json');
	});
	
	// Endpoint per ottenere l'elenco degli utenti attivi
	$app->get('/api/utenti', function (Request $request, Response $response, array $args) {
		$stmt = $this->get("db")->query("SELECT id, username, nome, cognome FROM utenti WHERE attivo = 1 ORDER BY cognome, nome");
		$utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$response->getBody()->write(json_encode($utenti));
		
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
		
		// Recupera informazioni sul laboratorio
		$lab_stmt = $this->get("db")->prepare("SELECT * FROM laboratori WHERE id = :id");
		$lab_stmt->bindParam(':id', $data['laboratorio_id'], PDO::PARAM_INT);
		$lab_stmt->execute();
		$laboratorio = $lab_stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$laboratorio) {
			$response->getBody()->write(json_encode(['error' => 'Laboratorio non trovato']));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}
		
		// 1. Verifica che la data di prenotazione sia all'interno del periodo di validità del laboratorio
		$data_prenotazione = $data['data_prenotazione'];
		if ($laboratorio['data_inizio'] && $laboratorio['data_fine']) {
			if ($data_prenotazione < $laboratorio['data_inizio'] || $data_prenotazione > $laboratorio['data_fine']) {
				$response->getBody()->write(json_encode([
					'error' => 'La prenotazione deve essere all\'interno del periodo di validità del laboratorio',
					'periodo_validita' => [
						'inizio' => $laboratorio['data_inizio'],
						'fine' => $laboratorio['data_fine']
					]
				]));
				return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
			}
		}
		
		// 2. Verifica che l'orario di prenotazione sia coerente con gli orari di disponibilità del laboratorio
		$ora_inizio = isset($data['ora_inizio']) ? $data['ora_inizio'] : null;
		$ora_fine = isset($data['ora_fine']) ? $data['ora_fine'] : null;
		
		if ($ora_inizio && $ora_fine && $laboratorio['orario_apertura'] && $laboratorio['orario_chiusura']) {
			if ($ora_inizio < $laboratorio['orario_apertura'] || $ora_fine > $laboratorio['orario_chiusura']) {
				$response->getBody()->write(json_encode([
					'error' => 'L\'orario di prenotazione deve essere all\'interno dell\'orario di apertura del laboratorio',
					'orario_laboratorio' => [
						'apertura' => $laboratorio['orario_apertura'],
						'chiusura' => $laboratorio['orario_chiusura']
					]
				]));
				return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
			}
		}
		
		// 3. Verifica che il numero di prenotazioni non superi la capacità del laboratorio per lo stesso giorno e fascia oraria
		$count_stmt = $this->get("db")->prepare(
			"SELECT COUNT(*) as num_prenotazioni 
			FROM prenotazioni 
			WHERE laboratorio_id = :laboratorio_id 
			AND data_prenotazione = :data_prenotazione 
			AND (
				(:ora_inizio BETWEEN ora_inizio AND ora_fine) OR
				(:ora_fine BETWEEN ora_inizio AND ora_fine) OR
				(ora_inizio BETWEEN :ora_inizio AND :ora_fine) OR
				(ora_fine BETWEEN :ora_inizio AND :ora_fine)
			)"
		);
		
		$count_stmt->bindParam(':laboratorio_id', $data['laboratorio_id'], PDO::PARAM_INT);
		$count_stmt->bindParam(':data_prenotazione', $data_prenotazione);
		$count_stmt->bindParam(':ora_inizio', $ora_inizio);
		$count_stmt->bindParam(':ora_fine', $ora_fine);
		$count_stmt->execute();
		$result = $count_stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($result['num_prenotazioni'] >= $laboratorio['capacita']) {
			$response->getBody()->write(json_encode([
				'error' => 'Il numero massimo di prenotazioni per questo laboratorio è stato raggiunto',
				'capacita' => $laboratorio['capacita'],
				'prenotazioni_attuali' => $result['num_prenotazioni']
			]));
			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		
		// Se tutte le verifiche sono passate, procedi con l'inserimento
		$stmt = $this->get("db")->prepare("INSERT INTO prenotazioni (utente_id, laboratorio_id, data_prenotazione, ora_inizio, ora_fine, stato) 
									VALUES (:utente_id, :laboratorio_id, :data_prenotazione, :ora_inizio, :ora_fine, 'in attesa')");
		
		$stmt->bindParam(':utente_id', $data['utente_id'], PDO::PARAM_INT);
		$stmt->bindParam(':laboratorio_id', $data['laboratorio_id'], PDO::PARAM_INT);
		$stmt->bindParam(':data_prenotazione', $data_prenotazione);
		$stmt->bindParam(':ora_inizio', $ora_inizio);
		$stmt->bindParam(':ora_fine', $ora_fine);
		
		$stmt->execute();
		$prenotazione_id = $this->get("db")->lastInsertId();
		
		$response->getBody()->write(json_encode(['success' => true, 'prenotazione_id' => $prenotazione_id]));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
	});
};
