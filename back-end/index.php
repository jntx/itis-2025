<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require 'vendor/autoload.php';

$app = AppFactory::create();

$app->get('/hello', function (Request $request, Response $response) {
    $response->getBody()->write('Hello World');
	
    return $response;
});

/*
// Configurazione del container per la connessione al database
$container = $app->getContainer();
$container['db'] = function () {
    $host = '127.0.0.1';
    $dbname = 'itis2025'; // Sostituisci con il nome del tuo database
    $username = 'root';          // Sostituisci con il tuo username
    $password = 'passdiroot';              // Sostituisci con la tua password
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
};

// $app = new \Slim\App($container);

// Endpoint per ottenere l'elenco dei laboratori
$app->get('/api/laboratori', function (Request $request, Response $response, array $args) {
    $stmt = $this->db->query("SELECT * FROM laboratori");
    $laboratori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($laboratori));

	return $response->withHeader('Content-Type', 'application/json');
});

// Endpoint per ottenere i dettagli di un laboratorio tramite ID
$app->get('/api/laboratori/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $stmt = $this->db->prepare("SELECT * FROM laboratori WHERE id = :id");
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
    
    $stmt = $this->db->prepare("INSERT INTO prenotazioni (utente_id, laboratorio_id, data_prenotazione, ora_inizio, ora_fine, stato) 
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

*/
// Avvio dell'applicazione
$app->run();
