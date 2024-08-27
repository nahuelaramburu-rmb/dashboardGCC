<?php
require_once '../config/database.php';
include '../funciones.php';
include './init_process.php';

$db = conectarDB();

$proccessIniciated = $db->query(
    "SELECT id
    FROM `registro_excel`
    WHERE `estado` = 'EN PROCESO'"
);

$torRunning = true;

if ($torRunning) {
    $url = "https://numeracionyoperadores.cnmc.es/api/portabilidad/numero_busquedas?tipoBusqueda=movil";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Timeout para cURL
    curl_setopt($ch, CURLOPT_PROXY, "149.50.141.80:8118"); // Usar Privoxy
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Privoxy usa HTTP Proxy

    echo $url;

    $response = curl_exec($ch);

    echo $response;

    if (curl_errno($ch)) {
        echo 'Error en cURL: ' . curl_error($ch);
    } else {
        $responseParse = json_decode($response, true);

        if (isset($responseParse['consultasRestantes']) && intval($responseParse['consultasRestantes']) > 0) {
            $consultas = intval($responseParse['consultasRestantes']);

            while ($row = $proccessIniciated->fetch_assoc()) {
                $registers = $db->query("SELECT * FROM `rel_registro_numeros` WHERE `id_relacional` = '$row[id]' AND `operador` = 'SIN PROCESAR'");

                if ($registers->num_rows > 0) {
                    echo $row['id'];
                    $id = $row['id'];

                    // initProcess($id, $db);

                    $command = "php ./run_process.php $id > /dev/null &";
                    popen($command, 'r');

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
            echo 'No hay consultas restantes disponibles.';
        }
    }
    curl_close($ch);
} else {
    echo "No se pudo iniciar Tor dentro del tiempo esperado.";
}
