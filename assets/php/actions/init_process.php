<?php
require_once '../config/database.php';
include '../funciones.php';

// if (isset($_GET['id'])) {
    // $id = $_GET['id'];

if (isset($_SERVER['argv'][1])) {
    $id = $_SERVER['argv'][1];

    $data = [];

    $db = conectarDB();

    $proccessIniciated = $db->query(
        "SELECT numero, id 
        FROM `rel_registro_numeros`
        WHERE id_relacional = '$id'
        AND `operador` = 'SIN PROCESAR'
        AND `bloqueado` = '0'
        LIMIT 50"
    );

    while ($row = $proccessIniciated->fetch_assoc()) {
        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '1' WHERE `id` = '$row[id]'");
        array_push($data, $row);
    }

    for($i = 0; $i < count($data); $i++) {
        $row = $data[$i];

        $CHGenerated = generarCH();
        $numero = urlencode($row['numero']);
        $url = "https://numeracionyoperadores.cnmc.es/api/portabilidad/movil?numero=$numero&captchaLoad=$CHGenerated";

        echo $url;
        echo "<br>";

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar cURL para usar Tor como proxy
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9150"); // Proxy Tor
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME); // Tipo de proxy SOCKS5

        $response = curl_exec($ch);

        echo $response;

        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '$row[id]'");

        if ($response === false) {
            error_log('Error en cURL: ' . curl_error($ch));
        } else {
            $responseArray = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($responseArray['operador']['nombre'])) {
                $operator = $db->real_escape_string($responseArray['operador']['nombre']);

                $updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$operator' WHERE `id` = '$row[id]'";

                if ($db->query($updateQuery) === false) {
                    error_log('Error en la actualización SQL: ' . $db->error);
                }
            } else {
                echo 'Error al decodificar el JSON o falta de datos en la respuesta';
            }
        }

        curl_close($ch);
    }
}
