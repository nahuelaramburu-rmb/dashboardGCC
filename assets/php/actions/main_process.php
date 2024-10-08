<?php
require_once '../config/database.php';
include '../funciones.php';
include './init_process.php';
// include './new_init_process.php';

$db = conectarDB();

$proccessIniciated = $db->query(
    "SELECT id
    FROM `registro_excel`
    WHERE `estado` = 'EN PROCESO'"
);

$torRunning = true;

if ($torRunning) {
    while ($row = $proccessIniciated->fetch_assoc()) {
        $registers = $db->query("SELECT * FROM `rel_registro_numeros` WHERE `id_relacional` = '$row[id]' AND `operador` = 'SIN PROCESAR'");

        if ($registers->num_rows > 0) {
            echo $row['id'];
            $id = $row['id'];

            // $command = "php ./run_process.php $id > /dev/null &";
            // popen($command, 'r');

            initProcess($id, $db);

            $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id_relacional` = '$id'");
        } else {
            if ($db->query("UPDATE `registro_excel` SET `estado` = 'FINALIZADO' WHERE `id` = '$row[id]'") === TRUE) {
                echo "Registro $id finalizado.";
            } else {
                echo "Error al finalizar el registro $id: " . $db->error;
            }
        }
    }
} else {
    echo "No se pudo iniciar Tor dentro del tiempo esperado.";
}
