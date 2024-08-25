<?php
include '../funciones.php';

$user = $_POST['user'];
$password = $_POST['pass'];

if($user == 'admin' && $password == 'Clave.2024') {
    session_start();
    
    $_SESSION['user'] = $user;

    header('Location: /index.php');
} else {
    header('Location: /login.php');
}