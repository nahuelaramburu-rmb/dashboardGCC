<?php

function generarCodigoAleatorio($longitud = 10) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    $max = strlen($caracteres) - 1;

    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[rand(0, $max)];
    }

    return $codigo;
}

function debuguear($debug){
    echo '<pre>';
    var_dump($debug);
    echo '</pre>';

    exit;
}

function generarCH($longitud = 256) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}