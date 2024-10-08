<?php
include '../funciones.php';
require_once '../config/database.php';

$db = conectarDB();
$id = $_GET['id'];

initProcess($id, $db);

function initProcess($id, $db)
{
    // Inicializar el array para almacenar datos
    $data = [];

    // Consulta para obtener los números pendientes de procesar
    $proccessIniciated = $db->query(
        "SELECT numero, id 
        FROM `rel_registro_numeros`
        WHERE id_relacional = '$id'
        AND `operador` = 'SIN PROCESAR'
        AND `bloqueado` = '0'
        LIMIT 1"
    );

    // Verifica si la consulta fue exitosa
    if (!$proccessIniciated) {
        die('Error en la consulta SQL: ' . $db->error);
    }

    // Almacenar resultados y marcar registros como bloqueados
    while ($row = $proccessIniciated->fetch_assoc()) {
        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '1' WHERE `id` = '" . $row['id'] . "'");
        $data[] = $row;
    }

    echo "Numeros pendientes de procesar: " . count($data);

    // Procesar cada número en el array de resultados
    foreach ($data as $row) {
        // Comprobar si el numero ya existe en la db
        $exists = $db->query("SELECT operador FROM `rel_registro_numeros` WHERE `numero` = '" . $db->real_escape_string($row['numero']) . "' AND `operador` != 'SIN PROCESAR'");
        $procesed = false;

        while ($existsRow = $exists->fetch_assoc()) {
            $operator = $existsRow['operador'];
            $procesed = true;

            $db->query("UPDATE `rel_registro_numeros` SET `operador` = '" . $operator . "', `bloqueado` = '0' WHERE `id` = '" . $row['id'] . "'");
        }

        // Si ya fue procesado, continuar al siguiente
        if ($procesed) {
            continue;
        }

        // Configuración de la URL y los parámetros
        $flask_url = "http://149.50.141.80/run_script";
        $numero = $row['numero'];
        $url_con_parametros = $flask_url . "?numero=" . urlencode($numero);

        // Configurar y ejecutar la solicitud cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_con_parametros);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        // Manejo de errores en la solicitud cURL
        if (curl_errno($ch)) {
            echo 'Error en la solicitud cURL: ' . curl_error($ch);
            $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '" . $row['id'] . "'");
        } else {
            // Decodificar la respuesta JSON
            $responseData = json_decode($response, true);

            // Verificar si la respuesta contiene 'operator'
            if (isset($responseData['operator'])) {
                echo 'Respuesta recibida: ' . $responseData['operator'];
                $operatorReceibed = $db->real_escape_string($responseData['operator']);
                $updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$operatorReceibed', `bloqueado` = '0' WHERE `id` = '" . $row['id'] . "'";
                if ($db->query($updateQuery) === false) {
                    echo 'Error en la actualización SQL: ' . $db->error;
                } else {
                    echo "Operador actualizado: " . $operatorReceibed;
                }
            } else {
                // Si no hay operador, desbloquear el registro
                $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '" . $row['id'] . "'");
                echo 'No se recibió el operador en la respuesta';
            }
        }

        // Cerrar la conexión cURL
        curl_close($ch);
    }
}
