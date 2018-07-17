<?php
include ('conexion.php');

$db = new CONEXION();

//$db->query("ALTER TABLE sigssaludfme_asistencia ADD (socnumero CHAR(10), cuenta INT(9))");

//Para mostrar que campos tiene una tabla

$resultado = $db->query("DESCRIBE sigssaludfme_asistencia");

while($row = mysqli_fetch_array($resultado,MYSQLI_ASSOC)){
    echo ($row['Field']).'</br>';
}


/*$persona= $db->select("SELECT documento, codigo, COUNT(*) c FROM fme_adhsrv WHERE fecha_baja = '0000-00-00' GROUP BY documento, codigo HAVING c > 1;");

foreach($persona as $res){
    echo $res['documento'].'__________'.$res['c'].'</br>';
}
*/
/*$persona= $db->select("SELECT * FROM fme_adhsrv WHERE documento = 05242110");
foreach($persona as $res){
    echo var_dump($res).'</br>';
}
*/
?>