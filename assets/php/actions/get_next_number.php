<?php

require_once '../config/database.php';
include '../funciones.php';
require '../../../vendor/autoload.php';

$db = conectarDB();

$arrayNumbers = [];

$proccessIniciated = $db->query(
    "SELECT id
    FROM `registro_excel`
    WHERE `estado` = 'EN PROCESO'"
);

while ($row = $proccessIniciated->fetch_assoc()) {
    $number = $db->query(
        "SELECT id, numero
        FROM `rel_registro_numeros`
        WHERE `id_relacional` = '$row[id]'
        AND `operador` = 'SIN PROCESAR'
        AND `bloqueado` = '0'
        LIMIT 10"
    );

    while ($rowChild = $number->fetch_assoc()) {
        $queryComp = $db->query("SELECT id, numero FROM `rel_registro_numeros` WHERE `id` != '$rowChild[id]' AND `NUMERO` = '$rowChild[numero]' AND `operador` != 'SIN PROCESAR'");
        $operatorTemp = '';

        while ($rowComp = $queryComp->fetch_assoc()) {
            $operatorTemp = $rowComp['operador'];
        }

        if ($operatorTemp) {
            $db->query("UPDATE `rel_registro_numeros` SET `operador` = '$operatorTemp' WHERE `id` = '$rowChild[id]'");
        } else {
            array_push($arrayNumbers, [
                'id' => $rowChild['id'],
                'number' => $rowChild['numero']
            ]);
    
            $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '1' WHERE `id` = '$rowChild[id]'");
        }
    }
}

echo json_encode($arrayNumbers);