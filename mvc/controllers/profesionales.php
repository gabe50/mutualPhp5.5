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
	if($priv['profesionales']=="0")
	{
		header('location:index.php');
		return;
	}
	
function mostrarListado(){
	global $use;
	global $priv;

	$profesionales = $GLOBALS['db']->select('SELECT * FROM sigssaludfme_profesionales, sigssaludfme_persona
								WHERE sigssaludfme_profesionales.id_persona=sigssaludfme_persona.id_persona');								
	if($profesionales)
	{
		$i=0;
		foreach($profesionales as $res){
			if($res['sexo']=='M'){
				$profesionales[$i]['sexo']='Masculino';
			}
			else{
				$profesionales[$i]['sexo']='Femenino';
			}
			$i++;
		}

	}

	$exito=0;
		if(isset($_GET['exito'])){
			$exito=1;
		}

		$eliminado=0;
		if(isset($_GET['eliminado'])){
			$eliminado=1;
		}

	echo $GLOBALS['twig']->render('/Atenciones/profesionales_listado.html', compact('profesionales', 'exito', 'eliminado','use','priv'));

}

function verMas(){
	global $use;
	global $priv;

	if(!isset($_GET['id_profesional']))
	{
		$error=[
				'menu'			=>"profesionales",
				'funcion'		=>"Perfil del profesional",
				'descripcion'	=>"No se encontraron resultados."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));
	}
	$id_profesional=$_GET['id_profesional'];
	
	$profesional = $GLOBALS['db']->select("SELECT * FROM sigssaludfme_profesionales, sigssaludfme_persona
								WHERE sigssaludfme_profesionales.id_persona=sigssaludfme_persona.id_persona
								AND sigssaludfme_profesionales.id_profesional='$id_profesional' ");
								
	if(!$profesional){
		$error=[
				'menu'			=>"profesionales",
				'funcion'		=>"Perfil del profesional",
				'descripcion'	=>"No se encontraron resultados."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));
	}
	
	$profesional[0]['fech_creacion'] = date("d/m/Y", strtotime($profesional[0]['fech_alta']));
	
	
	///---FUNCIÓN PARA CALCULAR EDAD----
	
	$fecha=$profesional[0]['fecnacim'];
	$dias = explode("-", $fecha, 3);
	
	// $dias[0] es el año
	// $dias[1] es el mes
	// $dias[2] es el dia
	
	// mktime toma los datos en el orden (0,0,0, mes, dia, año) 
	$dias = mktime(0,0,0,$dias[1],$dias[2],$dias[0]);
	$edad = (int)((time()-$dias)/31556926 );
	$profesional[0]['edad']=$edad;
	
	///---FIN FUNCIÓN PARA CALCULAR EDAD----

	$modificar=0;
	if(isset($_GET['modificar'])){
		$modificar=1;
	}

	$modprofesional=0;
	if(isset($_GET['modprofesional'])){
		$modprofesional=1;
	}
	
	echo $GLOBALS['twig']->render('/Atenciones/profesionales_perfil.html', compact('profesional','modificar','modprofesional','use','priv'));
	
}

function mostrarFormulario(){
	global $use;
	global $priv;
	echo $GLOBALS['twig']->render('/Atenciones/nuevo_profesional_formulario.html', compact('use','priv'));
	return;
}

function registrarProfesional(){
	global $use;
	global $priv;

	$use=$_SESSION['usuario']; //Usuario que realiza la creacion del nuevo profesional
	
	$nombre=$_POST['nombre'];
	$doc=$_POST['doc'];
	$sexo=$_POST['sexo'];
	$fech_nac=$_POST['fech_nac'];
	$tel_fijo=$_POST['fijo'];
	$tel_celu=$_POST['celu'];
	
	$dom=$_POST['dom'];
	$nrocasa=$_POST['nrocasa'];
	$barrio=$_POST['barrio'];
	$localidad=$_POST['localidad'];
	$cod_postal=$_POST['codpostal'];
	$dpto=$_POST['dpto'];
	
	$matr=$_POST['matricula'];
	$espec=$_POST['especialidad'];
	
	date_default_timezone_set('America/Argentina/Catamarca');
	$fec_alta=date("Y")."-".date("m")."-".date("d");
	
	$GLOBALS['db']->startCommit();

	$resultado=$GLOBALS['db']->query("INSERT INTO sigssaludfme_persona (nombre,numdoc,sexo,fecnacim,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,tel_fijo,tel_cel,fec_alta,usualta)
				VALUES ('$nombre','$doc','$sexo','$fech_nac','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$tel_fijo','$tel_celu','$fec_alta','$use')");

	if(!$resultado)
	{
		$error=[
				'menu'			=>"profesionales",
				'funcion'		=>"registrarProfesional",
				'descripcion'	=>"No se pudo registrar el profesional, error tabla persona INSERT INTO sigssaludfme_persona (nombre,numdoc,sexo,fecnacim,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,tel_fijo,tel_cel,fec_alta,usualta)
				VALUES ('$nombre','$doc','$sexo','$fech_nac','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$tel_fijo','$tel_celu','$fec_alta','$use')"
				];
		$GLOBALS['db']->rollback();
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
	
	
	
	$resultado2=$GLOBALS['db']->query("INSERT INTO sigssaludfme_profesionales (id_persona, matricula, especialidad, fech_alta, activo)
				VALUES (LAST_INSERT_ID(),'$matr','$espec','$fec_alta','1')");
				
	if(!$resultado2)
	{

		$error=[
				'menu'			=>"profesionales",
				'funcion'		=>"registrarProfesional",
				'descripcion'	=>"No se pudo crear el usuario, error tabla usuarios"
				];
		$GLOBALS['db']->rollback();
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
		
	$GLOBALS['db']->commit();
	header('Location: ./profesionales.php?funcion=mostrarListado&exito');

}

function modificarProfesional(){
	global $use;
	global $priv;

	$id_profesional=$_POST['id_profesional'];
	$espec=$_POST['espec'];
	$matricula=$_POST['matricula'];

	if(isset($_POST['activo'])){
		$activo=1;
	}
	else{
		$activo=0;
	}

	$res=$GLOBALS['db']->query("UPDATE sigssaludfme_profesionales SET 
	matricula='$matricula', especialidad='$espec', activo='$activo'
	WHERE id_profesional='$id_profesional'");

	if(!$res){
		$error=[
			'menu'			=>"profesionales",
			'funcion'		=>"Modificar datos del profesional",
			'descripcion'	=>"No se pudo modificar los datos del profesional ".$id_profesional
			];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
	}
	


	header('Location: ./profesionales.php?funcion=verMas&id_profesional='.$id_profesional.'&modprofesional');
}

function modificarPersonaProfesional(){
	global $use;
	global $priv;

	$id_profesional=$_POST['id_profesional'];
	$id_persona=$_POST['id_persona'];
	$nombre=$_POST['nombre'];
	$doc=$_POST['doc'];
	$sexo=$_POST['sexo'];
	$fech_nac=$_POST['fech_nac'];
	$tel_fijo=$_POST['fijo'];
	$tel_celu=$_POST['celu'];
	
	$dom=$_POST['dom'];
	$nrocasa=$_POST['nrocasa'];
	$barrio=$_POST['barrio'];
	$localidad=$_POST['localidad'];
	$cod_postal=$_POST['codpostal'];
	$dpto=$_POST['dpto'];

	$res=$GLOBALS['db']->query("UPDATE sigssaludfme_persona SET nombre='$nombre', numdoc='$doc', sexo='$sexo', fecnacim='$fech_nac', domicilio='$dom',
	casa_nro='$nrocasa', barrio='$barrio', localidad='$localidad', codpostal='$cod_postal', dpmto='$dpto', tel_fijo='$tel_fijo', tel_cel='$tel_celu'
	WHERE id_persona='$id_persona'");

	if(!$res){
		$error=[
			'menu'			=>"profesionales",
			'funcion'		=>"ModificarPersonaProfesional",
			'descripcion'	=>"No se pudo modificar los datos de la persona ".$id_persona
			];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
	}
	


	header('Location: ./profesionales.php?funcion=verMas&id_profesional='.$id_profesional.'&modificar');
}


function eliminarProfesional(){
	global $use;
	global $priv;

	$id_profesional=$_POST['id_profesional'];
	$persona = $GLOBALS['db']->select("SELECT id_persona FROM sigssaludfme_profesionales
								WHERE id_profesional='$id_profesional'");
	
	if(!$persona){
		$error=[
			'menu'			=>"profesionales",
			'funcion'		=>"EliminarProfesional",
			'descripcion'	=>"No se pudo encontrar los datos de la persona del profesional  ".$id_profesional
			];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
	}

	$id_persona=$persona[0]['id_persona'];

	$GLOBALS['db']->startCommit();

	$res=$GLOBALS['db']->query("DELETE FROM sigssaludfme_persona
	WHERE id_persona='$id_persona'");

	$res1=$GLOBALS['db']->query("DELETE FROM sigssaludfme_profesionales
	WHERE id_profesional='$id_profesional'");

	if(!$res && !$res1){
		$error=[
			'menu'			=>"profesionales",
			'funcion'		=>"EliminarProfesional",
			'descripcion'	=>"No se pudo elminar al profesional:  ".$id_profesional
			];

			$GLOBALS['db']->rollback();
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
	}


	$GLOBALS['db']->commit();
	header('Location: ./profesionales.php?funcion=mostrarListado&eliminado');
}

	
//llamada a la funcion con el parametro pasado por la url.	
	$_GET['funcion']();
//luego de que se ejecutó la funcion, se cierra la bd
	$db->cerrar_sesion();	
?>