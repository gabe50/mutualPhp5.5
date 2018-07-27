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
	if($priv['atenciones']=="0")
	{
		header('location:index.php');
		return;
	}
	
function mostrarListado()
{
	global $use;
	global $priv;
	$asistencias1 = $GLOBALS['db']->select("SELECT sigssaludfme_asistencia.idnum, sigssaludfme_asistencia.nombre, sigssaludfme_asistencia.numdoc, persona.numdoc, persona.sexo, sigssaludfme_asistencia.fec_pedido
									  FROM sigssaludfme_asistencia, persona 
									  WHERE persona.numdoc = sigssaludfme_asistencia.numdoc AND sigssaludfme_asistencia.fec_ate IS NULL ORDER BY sigssaludfme_asistencia.fec_ate,sigssaludfme_asistencia.hora_aten ASC");
									  
	$asistencias2 = $GLOBALS['db']->select("SELECT sigssaludfme_asistencia.idnum, sigssaludfme_asistencia.nombre, sigssaludfme_asistencia.numdoc, persona.numdoc, persona.sexo, sigssaludfme_asistencia.fec_pedido
									  FROM sigssaludfme_asistencia, persona 
									  WHERE persona.numdoc = sigssaludfme_asistencia.numdoc AND sigssaludfme_asistencia.fec_ate IS NOT NULL ORDER BY sigssaludfme_asistencia.fec_ate,sigssaludfme_asistencia.hora_aten ASC");
	
	if(!$asistencias1 && !$asistencias2)
	{
		$error=[
		'menu'			=>"Atenciones",
		'funcion'		=>"mostrarListado",
		'descripcion'	=>"No se encontraron resultados de atenciones."
		];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
	
	$asistencias;
	$i=0;
	if($asistencias1)
	{
		foreach($asistencias1 as $res1)
		{
			$asistencias[$i]['nro']		=	$res1['idnum'];
			$asistencias[$i]['nombre']	=	$res1['nombre'];
			$asistencias[$i]['dni']		=	$res1['numdoc'];
			if($res1['sexo']==1)
			{
				$asistencias[$i]['sexo']	=	'Femenino';
			}
			else
			{
				$asistencias[$i]['sexo']	=	'Masculino';
			}
			$asistencias[$i]['estado']	=	'0';
			$asistencias[$i]['fecha']	=	$res1['fec_pedido'];
			$i=$i+1;
		}
	}

	if($asistencias2)
	{
		foreach($asistencias2 as $res1)
		{
			$asistencias[$i]['nro']		=	$res1['idnum'];
			$asistencias[$i]['nombre']	=	$res1['nombre'];
			$asistencias[$i]['dni']		=	$res1['numdoc'];
			if($res1['sexo']==1)
			{
				$asistencias[$i]['sexo']	=	'Femenino';
			}
			else
			{
				$asistencias[$i]['sexo']	=	'Masculino';
			}
			$asistencias[$i]['estado']	=	'1';
			$asistencias[$i]['fecha']	=	$res1['fec_pedido'];
			$i=$i+1;
		}
	}

echo $GLOBALS['twig']->render('/Atenciones/listado_atencion.html',compact ('asistencias','use','priv'));
}

function verMas()
{
	global $use;
	global $priv;

	if(!isset($_GET['num_asist']))
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"verMas Listado_atencion",
				'descripcion'	=>"No se recibió num_asist como parametro de la función"
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
	$numero_asistencia=$_GET['num_asist'];
	
	
	
		$asistencia = $GLOBALS['db']->select("SELECT sigssaludfme_asistencia.cod_ser, sigssaludfme_asistencia.fec_pedido, sigssaludfme_asistencia.hora_pedido, 
													 sigssaludfme_asistencia.nombre, persona.sexo, sigssaludfme_asistencia.numdoc,
													 sigssaludfme_asistencia.profesional, sigssaludfme_asistencia.dessit, sigssaludfme_asistencia.fec_ate, sigssaludfme_asistencia.hora_aten,
													 sigssaludfme_asistencia.sintomas, sigssaludfme_asistencia.tratamiento, sigssaludfme_asistencia.diagnostico, 
													 sigssaludfme_asistencia.domicilio, sigssaludfme_asistencia.casa_nro, sigssaludfme_asistencia.barrio, sigssaludfme_asistencia.localidad,
													 sigssaludfme_asistencia.codpostal, sigssaludfme_asistencia.dpmto, sigssaludfme_asistencia.id_persona
									  FROM sigssaludfme_asistencia, persona 
									  WHERE sigssaludfme_asistencia.idnum='$numero_asistencia' AND persona.numdoc = sigssaludfme_asistencia.numdoc");

		if(!$asistencia)
		{
			$error=[
			'menu'			=>"Atenciones",
			'funcion'		=>"verMas",
			'descripcion'	=>"No se encontraron resultados para la atencion '$numero_asistencia'."
			];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
		}

		if($asistencia[0]['fec_ate']=="")
			$estado=0;
		else
			$estado=1;

		$persona=[
		'cod_serv'	=>	$asistencia[0]['cod_ser'],
		'fecha'		=>	$asistencia[0]['fec_pedido'],
		'hora'		=>	$asistencia[0]['hora_pedido'],
		'nombre'	=>	$asistencia[0]['nombre'],
		'doc'		=>	$asistencia[0]['numdoc'],
		'prof'		=>	$asistencia[0]['profesional'],
		'desc'		=>	$asistencia[0]['dessit'],
		'fech_ate'		=>	$asistencia[0]['fec_ate'],
		'hora_ate'		=>	$asistencia[0]['hora_aten'],
		'sintomas'		=>	$asistencia[0]['sintomas'],
		'tratamiento'		=>	$asistencia[0]['tratamiento'],
		'diagnostico'		=>	$asistencia[0]['diagnostico'],
		'dom'			=>	$asistencia[0]['domicilio'],
		'nro_casa'		=>	$asistencia[0]['casa_nro'],
		'barrio'		=>	$asistencia[0]['barrio'],
		'localidad'		=>	$asistencia[0]['localidad'],
		'cod_postal'	=>	$asistencia[0]['codpostal'],
		'dpmto'			=>	$asistencia[0]['dpmto'],
		'num_asistencia'	=>	$numero_asistencia,
		'id_persona'	=>	$asistencia[0]['id_persona']
	];
	if($asistencia[0]['sexo']==1)
		$persona['sexo']='Femenino';
	else
		$persona['sexo']='Masculino';
	
	$id_persona = $asistencia[0]['id_persona'];

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

echo $GLOBALS['twig']->render('/Atenciones/listado_atencion_formulario.html',compact ('persona','estado','historia','use','priv'));
}


function finalizarAtencion()
{
	global $use;
	global $priv;

	$num_asistencia=$_POST['num_asistencia'];
	$fecha_ate=$_POST['fech_ate'];		
	$hora_ate=$_POST['hora_ate'];
	$sintomas=$_POST['sintomas'];
	$diagnostico=$_POST['diagnostico'];
	$tratamiento=$_POST['tratamiento'];
	$resultado=$GLOBALS['db']->query("UPDATE sigssaludfme_asistencia SET fec_ate='$fecha_ate',sintomas='$sintomas',diagnostico='$diagnostico',tratamiento='$tratamiento',hora_aten='$hora_ate'
										WHERE idnum='$num_asistencia'");
	
	if(!$resultado)
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"FinalizarAtencion",
				'descripcion'	=>"No se pudo realizar la consulta: UPDATE sigssaludfme_asistencia SET fec_ate='$fecha_ate',sintomas='$sintomas',diagnostico='$diagnostico',tratamiento='$tratamiento',hora_aten='$hora_ate'
										WHERE idnum='$num_asistencia'"
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}

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

	$_GET['num_asist']=$num_asistencia;
	verMas();
}


//llamada a la funcion con el parametro pasado por la url.	
	$_GET['funcion']();
//luego de que se ejecutó la funcion, se cierra la bd
	$db->cerrar_sesion();	
	
?>