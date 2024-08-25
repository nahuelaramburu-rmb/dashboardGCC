<?php

require_once '../config/database.php';
include '../funciones.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$db = conectarDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];
    $dateNow = date("Y-m-d");
    $newRandomCode = '';

    do {
        $newRandomCode = generarCodigoAleatorio(10);
        $result = $db->query("SELECT COUNT(*) FROM `registro_excel` WHERE `codigo` = '$newRandomCode'");
        $codeInDb = $result->fetch_row()[0];
    } while ($codeInDb > 0);

    $db->query("INSERT INTO `registro_excel` (`codigo`, `estado`, `fecha`, `registros`) VALUES ('$newRandomCode', 'CARGADO', '$dateNow', '0')");

    $idRegister = $db->insert_id;

    $numbers = [];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        $allowedExtensions = ['xls', 'xlsx'];
        if (in_array($fileExtension, $allowedExtensions)) {
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);

                $worksheet = $spreadsheet->getActiveSheet();

                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    foreach ($cellIterator as $cell) {
                        if($cell->getValue() != null) {
                            array_push($numbers, $cell->getValue());
                        }
                    }
                }

                $db->query("UPDATE `registro_excel` SET `registros` = '" . count($numbers) . "' WHERE `id` = '$idRegister'");

                mysqli_begin_transaction($db);

                for($i = 0; $i < count($numbers); $i++) {
                    $query = "INSERT INTO `rel_registro_numeros` (`id_relacional`, `numero`, `operador`) VALUES ('$idRegister', '{$numbers[$i]}', 'SIN PROCESAR')";
                    if (!mysqli_query($db, $query)) {
                        throw new Exception(mysqli_error($db));
                    }
                }
                
                mysqli_commit($db);

                header('Location: ../../../index.php');
            } catch (Exception $e) {
                echo 'Error al leer el archivo Excel: ', $e->getMessage();
            }
        } else {
            echo 'Error: Solo se permiten archivos Excel con extensiones .xls o .xlsx';
        }
    } else {
        echo 'Error al subir el archivo.';
    }
} else {
    echo 'No se ha subido ningún archivo.';
}