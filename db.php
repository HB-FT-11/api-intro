<?php

function connect(): PDO
{
    // Chargement des informations de connexion
    [
        'DB_HOST' => $host,
        'DB_PORT' => $port,
        'DB_NAME' => $dbname,
        'DB_USER' => $user,
        'DB_PASSWORD' => $password,
        'DB_CHARSET' => $charset,
    ] = parse_ini_file(__DIR__ . '/db.ini');

    // Construction du DSN (Data Source Name)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

    // Création et renvoi de l'instance de PDO
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}