<?php
function getRegisters($db) {
    $data = [];

    $result = $db->query("SELECT * FROM `registro_excel`");

    while ($row = $result->fetch_assoc()) {
        $registers = $db->query("SELECT * FROM `rel_registro_numeros` WHERE `id_relacional` = '$row[id]' AND `operador` != 'SIN PROCESAR'");

        $row['registros_procesados'] = $registers->num_rows;

        array_push($data, $row);
    }

    return $data;
}