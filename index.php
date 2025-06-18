<?php

header('Content-Type: application/json; charset=UTF-8');

require_once 'db.php';
$pdo = connect();

// J'exécute ma requête et j'en récupère un statement
$stmt = $pdo->query("SELECT * FROM users");
// De ce statement, j'extraie un ou plusieurs résultats
// un avec fetch()
// tous avec fetchAll()
// Je change ensuite le mode de lecture pour avoir un tableau associatif avec PDO::FETCH_ASSOC
// $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Sinon, je peux aussi configurer l'instance de PDO à la construction pour qu'elle utilise FETCH_ASSOC par défaut (voir fichier db.php)
$users = $stmt->fetchAll();

echo json_encode($users);