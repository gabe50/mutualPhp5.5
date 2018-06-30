<?php
include ('../controllers/conexion.php');
$db = new CONEXION();
$resultado = $GLOBALS['db']->select('SELECT * FROM persona');


$arreglo["data"]=$resultado;

echo json_encode($arreglo);
?>