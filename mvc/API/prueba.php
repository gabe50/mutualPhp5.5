<?php
include ('../controllers/conexion.php');
$db = new CONEXION();
$resultado = $GLOBALS['db']->select('SELECT socios.beneficio,socios.soc_titula,socios.numero_soc,persona.sexo,persona.nombre,persona.numdoc 
FROM socios, persona 
WHERE socios.soc_titula=socios.numero_soc 
AND persona.id_persona=socios.id_persona;');


$arreglo["data"]=$resultado;

echo json_encode($arreglo);
?>