<?php
require_once '../config/database.php';
include '../funciones.php';

$db = conectarDB();

$proccessIniciated = $db->query(
    "SELECT id
    FROM `registro_excel`
    WHERE `estado` = 'EN PROCESO'"
);

function restartTor() {
    $stopProcess = popen('net stop Tor > NUL 2>&1', 'r');
    pclose($stopProcess);
    
    $startProcess = popen('net start Tor > NUL 2>&1', 'r');
    pclose($startProcess);
}

function isTorRunning($port = 9150) {
    $output = shell_exec("netstat -an | findstr \"$port\"");
    return strpos($output, 'LISTENING') !== false;
}

restartTor();

$torRunning = false;
$maxRetries = 20;
$retryDelay = 2;

while (!$torRunning && $maxRetries > 0) {
    sleep($retryDelay);

    if (isTorRunning()) {
        $torRunning = true;
    } else {
        $maxRetries--;
    }
}

if ($torRunning) {
    $url = "https://numeracionyoperadores.cnmc.es/api/portabilidad/numero_busquedas?tipoBusqueda=movil";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Timeout para cURL
    curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:9150");
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error en cURL: ' . curl_error($ch);
    } else {
        echo $response;

        $responseParse = json_decode($response, true);

        if (isset($responseParse['consultasRestantes']) && intval($responseParse['consultasRestantes']) > 0) {
            $consultas = intval($responseParse['consultasRestantes']);

            while ($row = $proccessIniciated->fetch_assoc()) {
                echo $row['id'];

                $registers = $db->query("SELECT * FROM `rel_registro_numeros` WHERE `id_relacional` = '$row[id]' AND `operador` = 'SIN PROCESAR'");

                if ($registers->num_rows > 0) {
                    $id = $row['id'];
                    $archivo = __DIR__ . '/init_process.php ' . $id;

                    pclose(popen("start /B php $archivo > NUL 2>&1", "r"));
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
?>
