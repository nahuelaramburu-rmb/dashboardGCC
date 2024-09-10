<?php
// $id = $argv[1]; // Obtén el ID pasado como argumento
$id = $_GET['id'];
// Incluye la conexión a la base de datos
require_once '../config/database.php';

$db = conectarDB();

initProcess($id, $db);

function initProcess($id, $db)
{
    $data = [];

    $proccessIniciated = $db->query(
        "SELECT numero, id 
        FROM `rel_registro_numeros`
        WHERE id_relacional = '$id'
        AND `operador` = 'SIN PROCESAR'
        AND `bloqueado` = '0'
        LIMIT 1"
    );

    while ($row = $proccessIniciated->fetch_assoc()) {
        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '1' WHERE `id` = '$row[id]'");
        array_push($data, $row);
    }

    for ($i = 0; $i < count($data); $i++) {
        $row = $data[$i];

        // Nombre del script de Python
        $script = 'web_interaction.py';
        $numero = urlencode($row['numero']);

        echo $numero;

        // Comando para ejecutar el script Python
        $command = escapeshellcmd("python $script $numero");

        // Ejecutar el comando y capturar la salida
        $output = shell_exec($command);

        echo '<pre>' . $output . '</pre>';

        $operatorReceibed = '';

        $output = mb_convert_encoding($output, 'UTF-8', 'auto'); // Asegurarse de que la cadena esté en UTF-8
        preg_match('/OPERATOR:\s*(.+)/u', $output, $matches);

        if (!empty($matches)) {
            $operator = trim($matches[1]);
            $operatorReceibed = $operator;
        }

        if ($operatorReceibed !== '') {

            if($operatorReceibed === 'TELEF?NICA M?VILES ESPA?A, S.A. UNIPERSONAL'){
                $operatorReceibed = 'TELEFÓNICA MÓVILES ESPAÑA, S.A. UNIPERSONAL';
            }

            if($operatorReceibed === 'VODAFONE ESPA?A, S.A. UNIPERSONAL'){
                $operatorReceibed = 'VODAFONE ESPAÑA, S.A. UNIPERSONAL';
            }

            if($operatorReceibed === 'XFERA M?VILES, S.A. UNIPERSONAL'){
                $operatorReceibed = 'XFERA MÓVILES, S.A. UNIPERSONAL';
            }

            echo $operatorReceibed;

            $updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$operatorReceibed', `bloqueado` = '0' WHERE `id` = '$row[id]'";

            if ($db->query($updateQuery) === false) {
                echo 'Error en la actualización SQL: ' . $db->error;
            }
        }
        return;
    }
}
