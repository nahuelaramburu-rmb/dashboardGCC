<?php
function getStatistics($db) {
    $notInit = $db->query("SELECT id FROM `registro_excel` WHERE `estado` = 'CARGADO'");
    $finished = $db->query("SELECT id FROM `registro_excel` WHERE `estado` = 'FINALIZADO'");
    $inProcess = $db->query("SELECT id FROM `registro_excel` WHERE `estado` = 'EN PROCESO'");
    $inPause = $db->query("SELECT id FROM `registro_excel` WHERE `estado` = 'PAUSADO'");

    $data = [
        'notInit' => $notInit->num_rows,
        'finished' => $finished->num_rows,
        'inProcess' => $inProcess->num_rows,
        'inPause' => $inPause->num_rows
    ];

    return $data;
}