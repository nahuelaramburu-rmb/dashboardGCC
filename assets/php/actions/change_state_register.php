<?php

require_once '../config/database.php';
include '../funciones.php';

$db = conectarDB();
$id = $_GET['id'];
$type = $_GET['type'];
$typeString = '';
$msg = '';

switch($type) {
    case '1':
        $typeString = 'CARGADO';
        $msg = 'Proceso cargado';
        break;

    case '2':
        $typeString = 'EN PROCESO';
        $msg = 'Proceso iniciado';
        break;

    case '3':
        $typeString = 'PAUSADO';
        $msg = 'Proceso pausado';
        break;

    case '4':
        $typeString = 'FINALIZADO';
        $msg = 'Proceso finalizado';
        break;

    default:
        break;
}

$db->query("UPDATE `registro_excel` SET `estado` = '$typeString' WHERE `id` = '$id'");

echo $msg;