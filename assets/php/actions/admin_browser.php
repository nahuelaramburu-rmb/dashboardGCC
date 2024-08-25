<?php
function realizarPeticionesConTor($urlBase, $params, $numeroPeticiones = 99) {
    for ($i = 0; $i < $numeroPeticiones; $i++) {
        // Construir la URL con los parámetros
        $queryParams = http_build_query($params);
        $url = $urlBase . '?' . $queryParams;

        // Realizar la petición usando cURL y Tor
        $respuesta = realizarPeticionConTor($url);

        // Procesar la respuesta si es necesario
        // ...

        echo "Petición #$i realizada a: $url\n";
    }

    // Cerrar y reiniciar Tor
    reiniciarTorBrowser();
}

function realizarPeticionConTor($url) {
    $torProxy = "127.0.0.1:9050"; // Asumiendo que Tor está escuchando en este puerto

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PROXY, $torProxy);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function iniciarTorBrowser() {
    // Ejecutar el comando para iniciar Tor Browser
    shell_exec("tor-browser &");
    sleep(10); // Esperar un tiempo para que Tor Browser se inicie correctamente
}

function cerrarTorBrowser() {
    // Ejecutar el comando para cerrar Tor Browser
    shell_exec("pkill -f tor-browser");
}

function reiniciarTorBrowser() {
    cerrarTorBrowser();
    iniciarTorBrowser();
}

// Ejemplo de uso
$urlBase = "http://example.com";
$params = [
    'param1' => 'value1',
    'param2' => 'value2'
];

// Iniciar Tor Browser
iniciarTorBrowser();

// Realizar 99 peticiones
realizarPeticionesConTor($urlBase, $params);

// Cerrar Tor Browser
cerrarTorBrowser();

?>