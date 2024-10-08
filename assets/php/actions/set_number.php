<?php

require_once '../config/database.php';
include '../funciones.php';
require '../../../vendor/autoload.php';

$db = conectarDB();

header('Content-Type: application/json');

// Leer y decodificar los datos JSON del cuerpo de la petición
$postData = json_decode(file_get_contents('php://input'), true);

$id = $postData['id'];
$operator = $postData['operator'];

// Actualizar el registro en la base de datos
$updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$operator', `bloqueado` = '0' WHERE `id` = '$id'";
$result = $db->query($updateQuery);

// Responder con un JSON
$response = [
    'status' => 'success',
    'message' => "ID: $id, Number: $number recibidos correctamente."
];

echo json_encode($response);
