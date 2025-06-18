<?php

header('Content-Type: application/json; charset=UTF-8');

require_once 'users.php';

echo json_encode($users);