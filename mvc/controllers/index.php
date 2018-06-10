<?php
require_once '../../vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('../views');

$twig = new Twig_Environment($loader, []);




//---------------Esto se repetiria para cada uno de los php que añadimos//

session_start();

if (!isset($_SESSION['usuario'])) {

header('location:login.php');
# code...
}



include ('conexion.php');

$db = new CONEXION();
	

$use=$_SESSION['usuario'];
$priv=$_SESSION['privilegios'];


echo $GLOBALS['twig']->render('/Atenciones/index.html', compact('use','priv'));

//
?>