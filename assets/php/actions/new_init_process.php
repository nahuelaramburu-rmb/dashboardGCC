<?php
require '../../../vendor/autoload.php';

use Nesk\Puphpeteer\Puppeteer;

function newInitProcess($id, $db){
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

        $numero = urlencode($row['numero']);

        $db->query("UPDATE `rel_registro_numeros` SET `bloqueado` = '0' WHERE `id` = '$row[id]'");

        try {
            $puppeteer = new Puppeteer();
            $browser = $puppeteer->launch([
                'headless' => false, // Modo sin cabeza, cambia a false para ver el navegador en acción
                // 'args' => ['--proxy-server=http://149.50.141.80:8118'] // Reemplaza con tu URL y puerto del proxy
            ]);
            $page = $browser->newPage();
            $page->goto('https://numeracionyoperadores.cnmc.es/portabilidad/movil', ['waitUntil' => 'networkidle2']);
        
            // Esperar 5 segundos
            sleep(6);
        
            // Espera hasta que el botón esté visible (COOKIES)
            $page->waitForSelector('.cookie-consent-banner .v-btn--variant-elevated', ['visible' => true]);
        
            sleep(3);
        
            $page->mouse->move(rand(0, 800), rand(0, 600));
        
            sleep(2);
        
            // Haz clic en el botón (COOKIES)
            $page->click('.cookie-consent-banner .v-btn--variant-elevated');
        
            // Esperar 5 segundos
            sleep(4);
        
            // Hacer scroll hasta el final de la página
            $page->evaluate('window.scrollTo(0, document.body.scrollHeight)');
        
            // Esperar 5 segundos
            sleep(3);
        
            // Hacer scroll 10 px mas arriba
            $page->evaluate('window.scrollBy(0, -10)');
        
            // Esperar 5 segundos
            sleep(7);
        
            $page->mouse->move(rand(0, 800), rand(0, 600));
        
            sleep(2);
        
            // Espera hasta que el input esté visible (NUMERO)
            $page->waitForSelector('.v-input__control input', ['visible' => true]);
        
            // Clickear el input (NUMERO)
            $page->click('.v-input__control input');
        
            // Esperar 5 segundos
            sleep(3);
        
            // Escribir en input (NUMERO)
            $page->type('.v-input__control input', $numero);
        
            sleep(1);
        
            $page->mouse->move(rand(0, 800), rand(0, 600));
        
            // Esperar 5 segundos
            sleep(6);
        
            // Hacer scroll 10 px mas arriba
            $page->evaluate('window.scrollBy(0, -10)');
        
            // Esperar 5 segundos
            sleep(2);
        
            // Mover el mouse como un usuario
            $page->mouse->move(rand(0, 800), rand(0, 600));
        
            // Esperar 5 segundos
            sleep(5);
        
            // Mover el mouse como un usuario
            $page->mouse->move(rand(0, 800), rand(0, 600));
        
            // Esperar 5 segundos
            sleep(5);
        
            // Realiza hover varias veces
            for ($i = 0; $i < 3; $i++) { // Cambia el número de repeticiones si es necesario
                $page->hover('.v-container .v-row .v-col-lg-4 > :nth-of-type(2)');
                sleep(1);
            }
        
            // Clickear el input captcha
            $page->click('.v-container .v-row .v-col-lg-4 > :nth-of-type(2)');
        
            // Esperar 5 segundos
            sleep(5);
        
            // Tocar boton submit
            $page->click('.v-container .v-row .v-col button');
        
            // Esperar 5 segundos
            sleep(5);
        
            // Obtener texto de parrafo
            $elementContent = $page->evaluate('document.querySelector(".v-container .v-row .v-col-lg-8 .v-card > :nth-of-type(4)") ? document.querySelector(".v-container .v-row .v-col-lg-8 .v-card > :nth-of-type(4)").textContent : "false"');
        
            echo $elementContent;
        
            if($elementContent !== 'false'){
                $updateQuery = "UPDATE `rel_registro_numeros` SET `operador` = '$elementContent' WHERE `id` = '$row[id]'";
                $db->query($updateQuery);
            }
        
            $browser->close();
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}