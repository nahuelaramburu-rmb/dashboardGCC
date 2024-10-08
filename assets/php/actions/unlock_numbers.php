<?php

require_once '../config/database.php';
include '../funciones.php';
require '../../../vendor/autoload.php';

$db = conectarDB();

$postData = json_decode(file_get_contents('php://input'), true);

$arrayNumbers = $postData['numbers'];

foreach ($arrayNumbers as $number) {
    $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '$number[id]'");
}

echo json_encode('success');