<?php
require_once '../config/database.php';
include '../funciones.php';
include './init_process.php';

if (isset($argv[1])) {
    $id = $argv[1];
    $db = conectarDB();
    $ch = generarCH();
    
    initProcess($id, $db, $ch);
} else {
    echo "No ID provided.";
}
