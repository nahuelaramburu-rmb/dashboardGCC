<?php
function initProcess($id, $db, $CHGenerated){
    $data = [];

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

    for ($i = 0; $i < count($data); $i++) {
        $row = $data[$i];

        $numero = urlencode($row['numero']);
        $url = "https://numeracionyoperadores.cnmc.es/api/portabilidad/movil?numero=$numero&captchaLoad=$CHGenerated";

        echo $url;
        echo "<br>";

        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '$row[id]'");

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_PROXY, "149.50.141.80:8118");
        // curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

        $response = curl_exec($ch);

        echo $response;

        if ($response === false) {
            echo 'Error en cURL: ' . curl_error($ch);
        } else {
            $responseArray = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($responseArray['operador']['nombre'])) {
                $operator = $db->real_escape_string($responseArray['operador']['nombre']);

                $updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$operator' WHERE `id` = '$row[id]'";

                if ($db->query($updateQuery) === false) {
                    echo 'Error en la actualización SQL: ' . $db->error;
                }
            } else {
                echo 'Error al decodificar el JSON o falta de datos en la respuesta';
                if(isset($responseArray['numero'])){
                    $notExist = false;

                    for ($i = 0; $i < count($responseArray['numero']); $i++) {
                        if ($responseArray['numero'][$i] === 'El número de teléfono debe constar de 9 dígitos' || $responseArray['numero'][$i] === 'Debe introducir un número de teléfono móvil válido') {
                            $notExist = true;
                        }
                    }

                    if ($notExist) {
                        $db->query("UPDATE `rel_registro_numeros` SET `operador` = 'NO EXISTE' WHERE `id` = '$row[id]'");
                    }
                }
            }
        }

        curl_close($ch);
    }
}