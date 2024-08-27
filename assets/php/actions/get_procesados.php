<?php
require_once '../config/database.php';
include '../funciones.php';

$id = $_GET['id'];

$db = conectarDB();

$query = "SELECT COUNT(*) AS total FROM `rel_registro_numeros` WHERE `id_relacional` = '$id' AND `operador` != 'SIN PROCESAR'";
$result = $db->query($query);

$queryTotal = "SELECT COUNT(*) AS total FROM `rel_registro_numeros` WHERE `id_relacional` = '$id'";
$resultTotal = $db->query($queryTotal);

if ($result) {
    $row = $result->fetch_assoc();
    $count = $row['total'];

    $rowTotal = $resultTotal->fetch_assoc();
    $total = $rowTotal['total'];
    // echo $count;

    $value = round($count / $total * 100, 2);

    echo 
        '<td>
            <div class="progress-container">
                <progress class="progress-bar" value="' . $value . '" max="100"></progress>
                <span class="progress-text">' . $value . '%</span>
            </div>
        </td>';
}
