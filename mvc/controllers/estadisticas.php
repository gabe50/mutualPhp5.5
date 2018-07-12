<?php

//Si la variable funcion no tiene ningun valor, se redirecciona al inicio------------
if(!isset($_GET['funcion']))
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


session_start();

if (!isset($_SESSION['usuario'])) {

header('location:login.php');
# code...
}

include ('conexion.php');

$db = new CONEXION();

$use=$_SESSION['usuario'];
$priv=$_SESSION['privilegios'];	
if($priv['estadisticas']=="0")
	{
		header('location:index.php');
		return;
	}
	
//funcion cantidadAtenciones
function cantidadAtenciones(){
	global $use;
	global $priv;
	require_once '../../vendor/autoload.php';
	
		$loader = new Twig_Loader_Filesystem('../views');
	
		$twig = new Twig_Environment($loader, []);
	
		echo $twig->render('/Atenciones/estadisticas_cantatenciones.html', compact('use','priv'));
}


//funcion atencionesAsociados
function asociados(){
	global $use;
	global $priv;
	require_once '../../vendor/autoload.php';
	
		$loader = new Twig_Loader_Filesystem('../views');
	
		$twig = new Twig_Environment($loader, []);
	
		echo $twig->render('/Atenciones/estadisticas_asociados.html', compact('use','priv'));
}

//funcion horarioAtenciones
function horarios(){
	global $use;
	global $priv;
	require_once '../../vendor/autoload.php';
	
		$loader = new Twig_Loader_Filesystem('../views');
	
		$twig = new Twig_Environment($loader, []);
	
		echo $twig->render('/Atenciones/estadisticas_horario.html', compact('use','priv'));
}

//Funciones que son llamadas en los métodos Ajax para las estadisticas (estan en los archivos .html, en las vistas, al final del documento donde estan los scripts)
function cantidadAtencionesProceso(){
	//Datos que recibo desde ajax por el metodo POST
	if(isset($_POST['anio']))
	{
		$anio = $_POST['anio'];
		
		if(isset($_POST['mes']) && isset($_POST['dia']))
			{
				$mes = $_POST['mes'];
				$dia = $_POST['dia'];
			
				//Consulta que me devuelve la cantidad de atenciones que se dieron en ese dia en particular, se le manda tanto el dia como el mes y el año
				$data = $GLOBALS['db']->select("SELECT fec_ate AS fecha, COUNT(*) AS numatenciones 
												FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_ate)='$anio'
												AND MONTH(fec_ate)='$mes'
												AND DAY(fec_ate)='$dia'
												GROUP BY MONTH(fec_ate)");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				echo json_encode($data);
			}
		else
			{
				//Consulta que me devuelve la cantidad de atenciones que se dieron en cada mes, del año enviado
				$data = $GLOBALS['db']->select("SELECT MONTH(fec_ate) AS mes, COUNT(*) AS numatenciones 
												FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_ate)='$anio' 
												GROUP BY MONTH(fec_ate)");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				echo json_encode($data);
			}
	}
	else
			{
				//Consulta que devuelve los años de las atenciones que hay en la BD, son para rellenar el select
				$data = $GLOBALS['db']->select("SELECT fec_ate AS fecha, YEAR(fec_ate) AS anio FROM sigssaludfme_asistencia WHERE YEAR(fec_ate)<>'0000' GROUP BY YEAR(fec_ate) ASC");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				echo json_encode($data);
			}
}


function atencionesAsociadosProceso(){	
	//Datos que recibo desde ajax por el metodo POST
	if(isset($_POST['anio']))
	{
		$anio = $_POST['anio'];
		
		if(isset($_POST['mes']))
			{
				$mes = $_POST['mes'];
				
				if(isset($_POST['dia']))
					{
						$dia = $_POST['dia']; 
			
						//Consulta que me devuelve la cantidad de atenciones que recibió cada asociado ese DÍA en particular, con el nombre del asociado y su número de documento
						$data = $GLOBALS['db']->select("SELECT tablaAUX.cantidad AS cantidad, persona.numdoc AS numdoc, persona.nombre AS nombre 
														FROM (SELECT numdoc, nombre, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
														WHERE YEAR(fec_pedido)='$anio'
														AND MONTH(fec_pedido)='$mes'
														AND DAY(fec_pedido)='$dia'
														GROUP BY numdoc) AS tablaAUX 
														INNER JOIN persona on persona.numdoc = tablaAUX.numdoc ORDER BY cantidad DESC");
						 
						//Devuelvo los datos solicitados por el metodo ajax
						
						echo json_encode($data);
				}
				else
				{
					//Consulta que me devuelve la cantidad de atenciones que recibió cada asociado ese MES en particular, con el nombre del asociado y su numero de documento
					$data = $GLOBALS['db']->select("SELECT tablaAUX.cantidad AS cantidad, persona.numdoc AS numdoc, persona.nombre AS nombre 
													FROM (SELECT numdoc, nombre, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
													WHERE YEAR(fec_pedido)='$anio'
													AND MONTH(fec_pedido)='$mes'
													GROUP BY numdoc) AS tablaAUX 
													INNER JOIN persona on persona.numdoc = tablaAUX.numdoc ORDER BY cantidad DESC");
					 
					//Devuelvo los datos solicitados por el metodo ajax
					
					echo json_encode($data);
				}
			}
		else
			{
				//Consulta que me devuelve la cantidad de atenciones que se dieron en cada mes, del año enviado
				$data = $GLOBALS['db']->select("SELECT tablaAUX.cantidad AS cantidad, persona.numdoc AS numdoc, persona.nombre AS nombre 
												FROM (SELECT numdoc, nombre, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_pedido)='$anio' 
												GROUP BY numdoc) AS tablaAUX 
												INNER JOIN persona on persona.numdoc = tablaAUX.numdoc ORDER BY cantidad DESC");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				
				echo json_encode($data);
			}
	}
}

function horarioAtencionesProceso(){
	//Datos que recibo desde ajax por el metodo POST
	if(isset($_POST['anio']))
	{
		$anio = $_POST['anio'];
		
		if(isset($_POST['mes']))
			{
				$mes = $_POST['mes'];
				
				if(isset($_POST['dia']))
					{
						$dia = $_POST['dia']; 
			
						//Consulta que me devuelve las horas de los pedidos y la cantidad de atenciones que se dieron en esos horarios
						$data = $GLOBALS['db']->select("SELECT hora_pedido, HOUR(hora_pedido) AS hora, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_pedido)='$anio'													
												AND MONTH(fec_pedido)='$mes'
												AND DAY(fec_pedido)='$dia'
												GROUP BY HOUR(hora_pedido) 
												ORDER BY hora");
						 
						//Devuelvo los datos solicitados por el metodo ajax
						echo json_encode($data);
				}
				else
				{
					//Consulta que me devuelve las horas de los pedidos y la cantidad de atenciones que se dieron en esos horarios
					$data = $GLOBALS['db']->select("SELECT hora_pedido, HOUR(hora_pedido) AS hora, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_pedido)='$anio'													
												AND MONTH(fec_pedido)='$mes'
												GROUP BY HOUR(hora_pedido) 
												ORDER BY hora");
					 
					//Devuelvo los datos solicitados por el metodo ajax
					echo json_encode($data);
				}
			}
		else
			{
				//Consulta que me devuelve las horas de los pedidos y la cantidad de atenciones que se dieron en esos horarios
				$data = $GLOBALS['db']->select("SELECT hora_pedido, HOUR(hora_pedido) AS hora, COUNT(*) AS cantidad FROM sigssaludfme_asistencia 
												WHERE YEAR(fec_pedido)='$anio' 
												GROUP BY HOUR(hora_pedido) 
												ORDER BY hora");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				echo json_encode($data);
			}
	}
	else
			{
				//Consulta que devuelve los años de las atenciones que hay en la BD, son para rellenar el select
				$data = $GLOBALS['db']->select("SELECT fec_pedido AS fecha, YEAR(fec_pedido) AS anio FROM sigssaludfme_asistencia WHERE YEAR(fec_ate)<>'0000' GROUP BY YEAR(fec_pedido) ASC");
				 
				//Devuelvo los datos solicitados por el metodo ajax
				echo json_encode($data);
			}
}

//llamada a la funcion con el parametro pasado por la url.	
$_GET['funcion']();
//luego de que se ejecutó la funcion, se cierra la bd
$db->cerrar_sesion();
?>