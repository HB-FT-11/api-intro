<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');

// Gestionnaire d'erreurs globales
// Fonction qui sera lancée en cas d'erreur imprévue
set_exception_handler(function (Throwable $ex) {
    // Logs
    file_put_contents('errors/error'.uniqid().'.txt', $ex->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'internal server error',
        'message' => 'An unexpected error has occured, please try again later'
    ]);
    exit;
});

const SUPPORTED_RESOURCES = ["users"];

require_once 'db.php';
$pdo = connect();
// URI :
// /users, /users/2, etc...
// /resource --> collection de cette ressource
// /resource/id --> élément unique (item) de cette ressource
$uri = $_SERVER['REQUEST_URI'];
$uriFragments = explode('/', ltrim($uri, '/'));

// Identification du nom de la ressource
// et de l'ID s'il est présent (null sinon)
$resource = $uriFragments[0];
$id = null; // initialisation de l'ID
if (count($uriFragments) === 2) { // si j'ai un ID dans l'URI
    $id = intval($uriFragments[1]); // Je convertis en int (0 si échoué)
}

if (!in_array($resource, SUPPORTED_RESOURCES)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'Not found',
        'message' => 'The request resource was not found on the server'
    ]);
    exit;
}

// Endpoint : Liste utilisateurs : /users, méthode GET
if ($resource === 'users' && $id === null && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // J'exécute ma requête et j'en récupère un statement
    $stmt = $pdo->query("SELECT * FROM users");
    // De ce statement, j'extraie un ou plusieurs résultats
    // un avec fetch()
    // tous avec fetchAll()
    // Je change ensuite le mode de lecture pour avoir un tableau associatif avec PDO::FETCH_ASSOC
    // $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Sinon, je peux aussi configurer l'instance de PDO à la construction pour qu'elle utilise FETCH_ASSOC par défaut (voir fichier db.php)
    $users = $stmt->fetchAll();

    foreach ($users as &$user) {
        $user['uri'] = '/users/' . $user['id'];
    }

    echo json_encode($users);
}

// Endpoint : item utilisateur lecture : GET /users/{id}
if ($resource === "users" && $id !== null && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Requête
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
    $stmt->execute(['id' => $id]);

    // fetch
    $user = $stmt->fetch();

    // 404 si non trouvé
    if ($user === false) {
        http_response_code(404);
        echo json_encode([
            'status' => 'Not found',
            'message' => 'The requested user was not found in the system'
        ]);
        exit;
    }

    // Ajout URI
    $user['uri'] = '/users/' . $user['id'];

    // json_encode
    echo json_encode($user);
}

const REQUIRED_FIELDS = ["name", "firstname", "email"];

// Endpoint : création d'utilisateur : /users, méthode POST
if ($resource === 'users' && $id === null && $_SERVER["REQUEST_METHOD"] === "POST") {
    // Demande à PHP le contenu du corps de la requête
    // au format brut : texte
    $body = file_get_contents("php://input");
    // Décode le texte brut selon le format JSON
    // Pour en faire un tableau associatif
    $data = json_decode($body, true);

    foreach (REQUIRED_FIELDS as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400); // 400 Bad Request
            echo json_encode([
                'status' => 'invalid data',
                'message' => $field . ' is required'
            ]);
            exit;
        }
    }

    // Validation email
    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        http_response_code(400); // 400 Bad Request
        echo json_encode([
            'status' => 'invalid data',
            'message' => 'email is invalid'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, firstname, email) VALUES (:name, :firstname, :email)");
    $stmt->execute([
        'name' => $data['name'],
        'firstname' => $data['firstname'],
        'email' => $data['email']
    ]);

    // Récupère l'ID du dernier enregistrement inséré en BDD
    $id = intval($pdo->lastInsertId());

    // Reconstruit $data en y indiquant une clé 'uri', une clé 'id',
    // et le contenu initial de $data lui-même (avec le spread operator)
    $data = [
        'uri' => '/users/' . $id,
        'id' => $id,
        ...$data
    ];

    http_response_code(201); // 201 Created
    echo json_encode($data);
}

// Ednpoint : suppression d'utilisateur : /users/{id}, méthode DELETE
if ($resource === 'users' && $id !== null && $_SERVER['REQUEST_METHOD'] === "DELETE") {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
    $stmt->execute(['id' => $id]);

    http_response_code(204); // 204 No Content
}

http_response_code(404);
echo json_encode([
    'status' => 'not found',
    'message' => 'Operation not supported'
]);
