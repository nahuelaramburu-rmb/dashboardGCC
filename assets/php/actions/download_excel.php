<?php

require_once '../config/database.php';
include '../funciones.php';
require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$db = conectarDB();
$id = $_GET['id'];

$data = $db->query("SELECT numero, operador FROM `rel_registro_numeros` WHERE `id_relacional` = '$id' ORDER BY `operador` ASC");

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'NUMERO');
$sheet->setCellValue('B1', 'OPERADOR');

$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);

$row = 2;

while ($rowe = $data->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $rowe['numero']);
    $sheet->setCellValue('B' . $row, $rowe['operador']);
    $row++;
}

$date = date("Y-m-d-H-i-s");

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="registros-' . $date . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

$writer->save('php://output');

$db->close();