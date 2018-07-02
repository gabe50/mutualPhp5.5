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
	if($priv['historia']=="0")
	{
		header('location:index.php');
		return;
	}
	
function mostrarListado(){
	global $use;
	global $priv;
	$personas = $GLOBALS['db']->select('SELECT * FROM persona');								
	if($personas)
	{
		$exito=0;
		if(isset($_GET['exito'])){
			$exito=1;
		}
		echo $GLOBALS['twig']->render('/Atenciones/historia_listado.html', compact('exito','use','priv'));
	}
	else
	{
		$error=[
				'menu'			=>"Historia Clinica",
				'funcion'		=>"Listado de personas",
				'descripcion'	=>"No se encontraron resultados."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
	}
}

function mostrarListadoAjax(){
	$resultado = $GLOBALS['db']->select('SELECT * FROM persona');

	$arreglo["data"]=$resultado;

	echo json_encode($arreglo);
}

function verMas(){
	global $use;
	global $priv;
	if(!isset($_GET['id_persona']))
	{
		$error=[
				'menu'			=>"Historia Clinica",
				'funcion'		=>"Perfil de usuarios",
				'descripcion'	=>"No se encontraron resultados."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));
	}
	$id_persona=$_GET['id_persona'];
	
	$resultado = $GLOBALS['db']->select("SELECT * FROM persona
								WHERE id_persona='$id_persona' ");

	if($resultado)
	{
		foreach($resultado as $res)
		{
			$persona =[
					'sexo'		=>	$res['sexo'],
					'nombre'	=>	$res['nombre'],
					'doc' 		=>	$res['numdoc'],
					'tel' 		=>	$res['tel_fijo'],
					'cel'		=>	$res['tel_cel'],
					'dom'		=>	$res['domicilio'],
					'nro_casa'		=>	$res['casa_nro'],
					'barrio'		=>	$res['barrio'],
					'localidad'		=>	$res['localidad'],
					'cod_postal'		=>	$res['codpostal'],
					'dpmto'		=>	$res['dpmto'],
					'id_persona' => $res['id_persona']
					];
			$id_persona=$res['id_persona'];
		}	
	}
	else
	{
		$error=[
			'menu'			=>"Historia Clinica",
			'funcion'		=>"Perfil de persona",
			'descripcion'	=>"No se encontraron resultados."
			];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));
		return;
	}

	$resultado_historia = $GLOBALS['db']->select("SELECT * FROM sigssaludfme_historia_clinica
										WHERE id_persona='$id_persona'");
	if($resultado_historia){
		foreach($resultado_historia as $res_historia){
			$historia =[
					'paperas'		=>	$res_historia['paperas'],
					'rubeola'		=>	$res_historia['rubeola'],
					'varicela'	=>	$res_historia['varicela'],
					'epilepsia' 		=>	$res_historia['epilepsia'],
					'hepatitis' 		=>	$res_historia['hepatitis'],
					'sinusitis'		=>	$res_historia['sinusitis'],
					'diabetes' 	=>	$res_historia['diabetes'],
					'apendicitis'		=>	$res_historia['apendicitis'],
					'amigdalitis'		=>	$res_historia['amigdalitis'],
					'comidas'		=>	$res_historia['comidas'],
					'medicamentos'		=>	$res_historia['medicamentos'],
					'otras'		=>	$res_historia['otras'],
					];
					
		}
	}
	else{
		$historia =[
				'paperas'		=>	0,
				'rubeola'		=>	0,
				'varicela'	=>	0,
				'epilepsia' 		=>	0,
				'hepatitis' 		=>	0,
				'sinusitis'		=>	0,
				'diabetes' 	=>	0,
				'apendicitis'		=>	0,
				'amigdalitis'		=>	0,
				'comidas'		=>	'',
				'medicamentos'		=>	'',
				'otras'		=>	'',
				];
	}

	
	echo $GLOBALS['twig']->render('/Atenciones/historia_perfil.html', compact('persona','historia','use','priv'));
	
}


function modificarHistoria(){
	global $use;
	global $priv;
	$id_persona=$_POST['id_persona'];

	if(isset($_POST['paperas'])){
		$paperas=1;
	}
	else{
		$paperas=0;
	}
	if(isset($_POST['rubeola'])){
		$rubeola=1;
	}
	else{
		$rubeola=0;
	}
	if(isset($_POST['varicela'])){
		$varicela=1;
	}
	else{
		$varicela=0;
	}
	if(isset($_POST['epilepsia'])){
		$epilepsia=1;
	}
	else{
		$epilepsia=0;
	}
	if(isset($_POST['hepatitis'])){
		$hepatitis=1;
	}
	else{
		$hepatitis=0;
	}
	if(isset($_POST['sinusitis'])){
		$sinusitis=1;
	}
	else{
		$sinusitis=0;
	}
	if(isset($_POST['diabetes'])){
		$diabetes=1;
	}
	else{
		$diabetes=0;
	}
	if(isset($_POST['apendicitis'])){
		$apendicitis=1;
	}
	else{
		$apendicitis=0;
	}
	if(isset($_POST['amigdalitis'])){
		$amigdalitis=1;
	}
	else{
		$amigdalitis=0;
	}
	
	$comidas=$_POST['comidas'];
	$medicamentos=$_POST['medicamentos'];
	$otras=$_POST['otras'];
	$id_persona=$_POST['id_persona'];
	
	$resultado = $GLOBALS['db']->select("SELECT * FROM sigssaludfme_historia_clinica
									WHERE id_persona = '$id_persona' ");

	if($resultado)
	{
		$res=$GLOBALS['db']->query("UPDATE sigssaludfme_historia_clinica SET 
		paperas='$paperas',rubeola='$rubeola',varicela='$varicela',epilepsia='$epilepsia',
		hepatitis='$hepatitis',sinusitis='$sinusitis',diabetes='$diabetes',
		apendicitis='$apendicitis',amigdalitis='$amigdalitis',comidas='$comidas',
		medicamentos='$medicamentos',otras='$otras'
		WHERE id_persona='$id_persona'");
	}
	else{
		$res=$GLOBALS['db']->query("INSERT INTO sigssaludfme_historia_clinica (id_persona,paperas,rubeola,varicela,epilepsia,
		hepatitis,sinusitis,diabetes,apendicitis,amigdalitis,comidas,medicamentos,otras)
				VALUES ('$id_persona','$paperas','$rubeola','$varicela','$epilepsia',
				'$hepatitis','$sinusitis','$diabetes','$apendicitis','$amigdalitis',
				'$comidas','$medicamentos','$otras')");
	}

	header('Location: ./historia.php?funcion=mostrarListado&exito');

}
	

	
//llamada a la funcion con el parametro pasado por la url.	
	$_GET['funcion']();
//luego de que se ejecutó la funcion, se cierra la bd
	$db->cerrar_sesion();	
?>