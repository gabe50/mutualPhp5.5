<?php
//Si la variable funcion no tiene ningun valor, se redirecciona al inicio------------

session_start();

$use=$_SESSION['usuario'];
$priv=$_SESSION['privilegios'];

if (!isset($_SESSION['usuario'])) {

header('location:login.php');
# code...
}

if($priv['atenciones']=="0")
{
    header('location:index.php');
    return;
}

if(!isset($_GET['id_atencion']))
{
    header('location:index.php');
    return;
}
else
{
    require_once '../../vendor/autoload.php';

    $loader = new Twig_Loader_Filesystem('../views');

    $twig = new Twig_Environment($loader, []);
}



$id_atencion=$_GET['id_atencion'];

echo $GLOBALS['twig']->render('/Atenciones/nueva_atencion_finalizar.html', compact('id_atencion','use','priv'));	
?>