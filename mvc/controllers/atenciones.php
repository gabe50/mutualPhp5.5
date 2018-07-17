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
	
	
//Para acceder a cada funcion se debe pasar por parametro una variable de nombre funcion=nombrefuncion; por ejemeplo atenciones.php?funcion=nuevaAtencion		
	
$use=$_SESSION['usuario'];
$priv=$_SESSION['privilegios'];
if($priv['atenciones']=="0")
	{
		header('location:index.php');
		return;
	}

function mostrarListado(){
	global $use;
	global $priv;

	echo $GLOBALS['twig']->render('/Atenciones/nueva_atencion_1.html', compact('use','priv'));

}

		
//funcion verMAS, el cual debe realizar la consulta del asociado seleccionado para mostrar toda su informacion
function verMas()	
{

	global $use;
	global $priv;
	
	//---------------CONSULTA QUE DEVUELVE TODA LA INFO DEL ASOCIADO TITULAR----------------

	if(!isset($_POST['num_doc']))
	{
		$error=[
		'menu'			=>"Atenciones",
		'funcion'		=>"verMas",
		'descripcion'	=>"No se ha ingresado un numero de documento valido."
		];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
	
	$numero_doc = $_POST['num_doc'];

	$datos_basicos = $GLOBALS['db']->select("SELECT nombre, numdoc, sexo, fecnacim, domicilio, casa_nro, barrio, localidad, dpmto
			FROM persona 
			WHERE numdoc = '$numero_doc'");

	if(!$datos_basicos)
	{
		$error=[
		'menu'			=>"Atenciones",
		'funcion'		=>"verMas",
		'descripcion'	=>"No se encuentra a la persona con dni $numero_doc"
		];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}

	$datos_atenciones = $GLOBALS['db']->select("SELECT sigssaludfme_asistencia.numdoc, sigssaludfme_asistencia.nombre,
	sigssaludfme_asistencia.fec_pedido, sigssaludfme_asistencia.hora_pedido, sigssaludfme_asistencia.dessit, sigssaludfme_asistencia.fec_ate,
	sigssaludfme_asistencia.sintomas, sigssaludfme_asistencia.diagnostico, sigssaludfme_asistencia.tratamiento, sigssaludfme_asistencia.hora_aten,
	sigssaludfme_asistencia.profesional
	FROM sigssaludfme_asistencia, socios, persona 
	WHERE persona.numdoc = '$numero_doc' 
	AND socios.id_persona = persona.id_persona
	AND numero_soc = soc_titula");

	$fecha=$datos_basicos[0]['fecnacim'];
	$dias = explode("-", $fecha, 3);
	$dias = mktime(0,0,0,$dias[2],$dias[1],$dias[0]);
	$edad = (int)((time()-$dias)/31556926 );

	$datos_basicos[0]['edad'] = $edad;


	$persona= $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, 
	a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
	FROM fme_adhsrv a, tar_srv b
	WHERE a.codigo = b.idmutual
	AND a.fecha_baja = '0000-00-00'
	AND a.documento = '$numero_doc'");


	//Si no lo encuentra es debido a que no tiene ningun registro en la tabla fme_adhsrv, o sea es pago por tarjeta
	if(!$persona){
		//la persona puede ser titular o adherente
		$persona = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
		FROM tar_srvadherentes a, tar_srv b
		WHERE a.codigo = b.codigo
		AND a.fecha_baja = '0000-00-00'
		AND a.documento = '$numero_doc'");

		$cuenta = $persona[0]['cuenta'];

		$titular = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
		FROM tar_srvadherentes a, tar_srv b
		WHERE a.codigo = b.codigo
		AND a.fecha_baja = '0000-00-00'
		AND a.cuenta = '$cuenta'
		AND a.tipo=1");
		
		if(!$titular){
			//no es titular y pago por tarjeta o sea es adherente
			$datos_servicios=null;

			$datos_servicios_tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
			FROM tar_srvadherentes a, tar_srv b
			WHERE a.codigo = b.codigo
			AND a.fecha_baja = '0000-00-00'
			AND a.cuenta = '$cuenta'
			AND a.tipo<>1");

			echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('datos_basicos', 
			'datos_servicios_tarjeta',
			'datos_servicios', 
			'datos_atenciones',
			'use','priv'));	

			return;
		}
		else{
			//titular y pago por tarjeta
			$datos_servicios=null;

			$datos_servicios_tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
			FROM tar_srvadherentes a, tar_srv b
			WHERE a.codigo = b.codigo
			AND a.fecha_baja = '0000-00-00'
			AND a.cuenta = '$cuenta'
			AND a.tipo = 1");

			echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('datos_basicos', 
			'datos_servicios_tarjeta', 
			'datos_servicios',
			'datos_atenciones',
			'use','priv'));	

			return;
		}
	}
	else{
		//la PERSONA puede ser titular, adherente o particular y al menos paga por cuota
		
		$socnumero = $persona[0]['socnumero'];

		$persona = $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, 
		a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
		FROM fme_adhsrv a, tar_srv b
		WHERE a.codigo = b.idmutual
		AND a.fecha_baja = '0000-00-00'
		AND a.socnumero = '$socnumero'");

		if($socnumero>=100000){
			//es un particular
			$datos_servicios = $GLOBALS['db']->select("SELECT  a.numeral, b.nombre, a.nombre as nombre_persona, a.documento, a.fecnacim, a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
			FROM fme_adhsrv a, tar_srv b
			WHERE a.codigo = b.idmutual
			AND a.fecha_baja = '0000-00-00'
			AND a.socnumero = '$socnumero'
			AND a.socnumero>=100000");


			echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('datos_basicos', 
			'datos_servicios', 
			'datos_atenciones',
			'use','priv'));	

			return;
		}
		else{
			//no es un particular, por lo que es un titular o un adherente
			$tipo = $persona[0]['tipo'];

			if($tipo==1){
				//Es titular y no particular ni adherente

				$datos_servicios = $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
				FROM fme_adhsrv a, tar_srv b
				WHERE a.codigo = b.idmutual
				AND a.fecha_baja = '0000-00-00'
				AND a.socnumero = '$socnumero'
				AND a.tipo=1");

				//tiene tarjeta?
				$datos_servicios_tarjeta=null;
				$tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
				FROM tar_srvadherentes a, tar_srv b
				WHERE a.codigo = b.codigo
				AND a.fecha_baja = '0000-00-00'
				AND a.documento = '$numero_doc'");

				if($tarjeta){
					$cuenta= $tarjeta[0]['cuenta'];

					$datos_servicios_tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
					FROM tar_srvadherentes a, tar_srv b
					WHERE a.codigo = b.codigo
					AND a.fecha_baja = '0000-00-00'
					AND a.cuenta = '$cuenta'
					AND a.tipo=1");
				}

				echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('datos_basicos', 
				'datos_servicios', 
				'datos_atenciones',
				'datos_servicios_tarjeta',
				'use','priv'));	

				return;
			}
			else{
				//Es un adherente y no un particular ni un titular

				$datos_servicios = $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
				FROM fme_adhsrv a, tar_srv b
				WHERE a.codigo = b.idmutual
				AND a.fecha_baja = '0000-00-00'
				AND a.socnumero = '$socnumero'
				AND a.tipo <> 1");

				//tiene tarjeta?
				$datos_servicios_tarjeta=null;
				$tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
				FROM tar_srvadherentes a, tar_srv b
				WHERE a.codigo = b.codigo
				AND a.fecha_baja = '0000-00-00'
				AND a.documento = '$numero_doc'");

				if($tarjeta){
					$cuenta= $tarjeta[0]['cuenta'];

					$datos_servicios_tarjeta = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
					FROM tar_srvadherentes a, tar_srv b
					WHERE a.codigo = b.codigo
					AND a.fecha_baja = '0000-00-00'
					AND a.cuenta = '$cuenta'
					AND a.tipo<>1");
				}

				echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('datos_basicos', 
				'datos_servicios', 
				'datos_atenciones',
				'datos_servicios_tarjeta',
				'use','priv'));	
			}
		}			

	}
}
		

function mostrarFormulario()
{
	global $use;
	global $priv;

	if(!isset($_GET['num_doc']))
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se ha ingresado un numero de documento valido."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use'));	
		return;
	}

	
	$numero_doc = $_GET['num_doc'];

	$resultado = $GLOBALS['db']->select("SELECT * FROM persona
										WHERE numdoc = '$numero_doc'");

	if(!$resultado)
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se se encuentra a la persona con documento '$numero_doc'."
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use'));	
		return;
	}


	//Verificacion de que la persona cumpla con el servicio activo.
	$persona= $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, 
	a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
	FROM fme_adhsrv a, tar_srv b
	WHERE a.codigo = b.idmutual
	AND a.fecha_baja = '0000-00-00'
	AND a.documento = '$numero_doc'");


	if(!$persona){
	//Si no lo encuentra es debido a que no tiene ningun registro en la tabla fme_adhsrv, o sea es pago por tarjeta
		$persona = $GLOBALS['db']->select("SELECT *
		FROM tar_srvadherentes a, tar_srv b
		WHERE a.codigo = b.codigo
		AND a.fecha_baja = '0000-00-00'
		AND a.documento = '$numero_doc'
		AND (a.codigo = '3' OR a.codigo = '48')");

		if(!$persona){
			$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"La persona con documento '$numero_doc' no tiene el servicio de salud activo."
				];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use'));	
			return;
		}
	}
	else{
			//La persona tiene el pago por cuota
			$persona = $GLOBALS['db']->select("SELECT *
			FROM fme_adhsrv a, tar_srv b
			WHERE a.codigo = b.idmutual
			AND a.fecha_baja = '0000-00-00'
			AND a.documento = '$numero_doc'
			AND (a.codigo = '020' OR a.codigo = '884')");

			if(!$persona){
				$error=[
					'menu'			=>"Atenciones",
					'funcion'		=>"mostrarFormulario",
					'descripcion'	=>"La persona con documento '$numero_doc' no tiene el servicio de salud activo."
					];
				echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use'));	
				return;
			}		
			
	}
	
	$codigo_servicio=$persona[0]['codigo'];

	$persona=null;

	if($resultado)
	{
		date_default_timezone_set('America/Argentina/Catamarca');
		$fecha['year']=date("Y");
		$fecha['mon']=date("m");
		$fecha['mday']=date("d");
		$fecha['hours']=date("H");
		$fecha['minutes']=date("i");
		foreach($resultado as $res)
		{
			$persona =[
					'sexo'		=>	$res['sexo'],
					'nombre'	=>	$res['nombre'],
					'doc' 		=>	$res['numdoc'],
					'tel' 		=>	$res['tel_fijo'],
					'cel'		=>	$res['tel_cel'],
					'fecha' 	=>	$fecha,
					'dom'		=>	$res['domicilio'],
					'nro_casa'		=>	$res['casa_nro'],
					'barrio'		=>	$res['barrio'],
					'localidad'		=>	$res['localidad'],
					'cod_postal'		=>	$res['codpostal'],
					'dpmto'		=>	$res['dpmto'],
					'id_persona' => $res['id_persona'],
					'cod_serv' => $codigo_servicio
					];
			$id_persona=$res['id_persona'];
		}	
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

	$profesionales = $GLOBALS['db']->select("SELECT * FROM sigssaludfme_profesionales,sigssaludfme_persona
										WHERE sigssaludfme_profesionales.id_persona = sigssaludfme_persona.id_persona AND sigssaludfme_profesionales.activo=1");

	if(!$profesionales){
		$error=[
			'menu'			=>"Atenciones",
			'funcion'		=>"mostrarFormulario",
			'descripcion'	=>"No hay ningun profesional que pueda realizar la atención"
			];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}
	

	echo $GLOBALS['twig']->render('/Atenciones/nueva_atencion_formulario.html', compact('persona','historia','profesionales','use','priv'));
}

	
//funcion generarAtencion, que se ejecuta tras completar el formulario
function generarAtencion()
{   
	global $use;
	global $priv;

		//DISTINGUE ENTRE ATENCION A DOMICILIO O ATENCION EN CONSULTORIO
		if(isset($_POST['atencion_domicilio'])){
			$dom=$_POST['dom'];
			$nrocasa=$_POST['nrocasa'];
			$barrio=$_POST['barrio'];
			$localidad=$_POST['localidad'];
			$cod_postal=$_POST['codpostal'];
			$dpto=$_POST['dpto'];
		}
		else{
			$dom='EN CONSULTORIO';
			$nrocasa='';
			$barrio='';
			$localidad='';
			$cod_postal='';
			$dpto='';
		}		
		$cod_ser=$_POST['cod_serv'];
		$numdoc=$_POST['doc'];
		$nombre=$_POST['nombre'];
		$fec_pedido=$_POST['fecha'];
		$hora_pedido=$_POST['hora'];
		$dessit=$_POST['desc'];
		$profesional=$_POST['prof'];
		$sexo=$_POST['sexo'];
		$tel=$_POST['tel'];
		$id_persona= $_POST['id_persona'];

		$profesional_explode = explode('|',$profesional); //$profesional_explode[0] es nombre y $profesional_explode[1] es id_profesional

		//--------------COMIENZA LA BUSQUEDA DE LA CUENTA Y DEL SOCNUM
		$persona= $GLOBALS['db']->select("SELECT a.numeral, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fecnacim, 
		a.codigo, a.estado, a.importe, a.codsrimp, a.socnumero
		FROM fme_adhsrv a, tar_srv b
		WHERE a.codigo = b.idmutual
		AND a.fecha_baja = '0000-00-00'
		AND a.documento = '$numdoc'");
		$socnumero = $persona[0]['socnumero'];
		if(is_null($socnumero)){
			$socnumero='NULL';
		}

		$persona = $GLOBALS['db']->select("SELECT a.codigo, b.nombre, a.fecha_alta, a.nombre as nombre_persona, a.documento, a.fechanac, a.estado, a.importe, a.cuenta
		FROM tar_srvadherentes a, tar_srv b
		WHERE a.codigo = b.codigo
		AND a.fecha_baja = '0000-00-00'
		AND a.documento = '$numdoc'");
		$cuenta = $persona[0]['cuenta'];
		if(is_null($cuenta)){
			$cuenta='NULL';
		}
		
		//--------------TERMINA LA BUSQUEDA DE LA CUENTA Y DEL SOCNUM
		
		$resultado=$GLOBALS['db']->query("INSERT INTO sigssaludfme_asistencia (cod_ser,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional, socnumero, cuenta)
				VALUES ('$cod_ser','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]', $socnumero, $cuenta)");
		
		$res2=$GLOBALS['db']->select("SELECT idnum FROM sigssaludfme_asistencia WHERE idnum=LAST_INSERT_ID()");//obtentemos el id_atencion del ultimo insert realizado
				
		$id_atencion=$res2[0]['idnum']; 

		if(!$resultado)
		{
			$error=[
					'menu'			=>"Atenciones",
					'funcion'		=>"generarAtencion",
					'descripcion'	=>"No se pudo realizar la consulta: (INSERT INTO sigssaludfme_asistencia (cod_ser,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional, socnumero, cuenta)
					VALUES ('$cod_ser','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]', $socnumero, $cuenta))"
					];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
		}
		
		header('Location: ./nueva_atencion_finalizar.php?id_atencion='.$id_atencion);
		return;
}

function generarPDF()
{
	global $use;
	global $priv;

	if(!isset($_GET['id_atencion']))
	{
		return;
	}
	$id_atencion=$_GET['id_atencion'];

	$resultado=$GLOBALS['db']->select("SELECT sigssaludfme_asistencia.cod_ser, sigssaludfme_asistencia.numdoc, sigssaludfme_asistencia.nombre, sigssaludfme_asistencia.fec_pedido, 
	sigssaludfme_asistencia.hora_pedido, sigssaludfme_asistencia.dessit, sigssaludfme_asistencia.profesional, sigssaludfme_asistencia.domicilio, sigssaludfme_asistencia.casa_nro, sigssaludfme_asistencia.barrio, 
	sigssaludfme_asistencia.localidad, sigssaludfme_asistencia.codpostal, sigssaludfme_asistencia.dpmto, sigssaludfme_asistencia.id_persona, persona.sexo, persona.tel_fijo, persona.tel_cel, sigssaludfme_asistencia.fec_ate 
	FROM sigssaludfme_asistencia, persona 
	WHERE sigssaludfme_asistencia.id_persona= persona.id_persona
	AND sigssaludfme_asistencia.idnum='$id_atencion'");

	if(!$resultado)
	{
		$error=[
		'menu'			=>"Atenciones",
		'funcion'		=>"PDF",
		'descripcion'	=>"No se encuentra la atención con código: $id_atencion"
		];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}

	foreach($resultado as $res){
		$cod_ser=$res['cod_ser'];	
		$numdoc=$res['numdoc'];		
		$nombre=$res['nombre'];	
		$fec_pedido=$res['fec_pedido'];
		$hora_pedido=$res['hora_pedido'];
		$dessit=$res['dessit'];
		$profesional=$res['profesional'];
		if($res['sexo']=='1'){
			$sexo="Masculino";	
		}
		else{
			$sexo="Femenino";
		}
			
		$tel=$res['tel_fijo'];
		$tel=$tel.$res['tel_cel'];
		$dom=$res['domicilio'];
		$nrocasa=$res['casa_nro'];
		$barrio=$res['barrio'];
		$localidad=$res['localidad'];
		$cod_postal=$res['codpostal'];
		$dpto=$res['dpmto'];
		$id_persona=$res['id_persona'];
		$fec_ate=$res['fec_ate'];
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
	
	//---------------Generar PDF -------------------------------
	
	require('../../fpdf/fpdf.php');
	
	$pdf=new FPDF('P', 'pt', 'A4');
	$pdf->AddPage();
	
	$pdf->AddFont('Century','','CENTURY.php');

	//Texto de Título
	$pdf->SetXY(70, 100);
	$pdf->AddFont('Roboto','','Roboto-Regular.php');
	$pdf->SetFont('Roboto','',22);
	$pdf->Cell(0,20,'SOLICITUD DE ASISTENCIA','0','0','');

	$pdf->SetFont('Roboto','',14);

	$pdf->SetXY(70,130);
	$pdf->Cell(15, 8, utf8_decode('Fecha: '.$fec_pedido), 0, '');

	$pdf->SetXY(70,150);
	$pdf->Cell(15, 8, utf8_decode('Hora: '.$hora_pedido), 0, '');	
	
	//De aqui en adelante se colocan distintos métodos
	//para diseñar el formato.
	 
	
	$pdf->SetFont('Century','',12);

	//$pdf->SetFont('Century','', 12);
	 
	$pdf->SetXY(70,220);
	$pdf->Cell(10, 8, utf8_decode('Nombre del paciente: '.$nombre), 0, 'L');

	$pdf->SetXY(70,240);
	$pdf->Cell(19, 8, utf8_decode('D.N.I. del paciente: '.$numdoc), 0, 'L');

	$pdf->SetXY(70,280);
	$pdf->Cell(10, 8, utf8_decode('Sexo del paciente: '.$sexo), 0, 'L');

	$pdf->SetXY(70,300);
	$pdf->Cell(10, 8, utf8_decode($tel), 0, 'L');
	
	$pdf->SetXY(70,340);
	$pdf->Cell(10, 8, utf8_decode('Domicilio: '.$dom), 0, 'L');
	
	$pdf->SetXY(70,360);
	$pdf->Cell(10, 8, utf8_decode('Nº Casa: '.$nrocasa), 0, 'L');
	
	$pdf->SetXY(70,380);
	$pdf->Cell(10, 8, utf8_decode('Barrio: '.$barrio), 0, 'L');
	
	$pdf->SetXY(70,400);
	$pdf->Cell(10, 8, utf8_decode('Localidad: '.$localidad), 0, 'L');
	
	$pdf->SetXY(70,420);
	$pdf->Cell(10, 8, utf8_decode('Codigo postal: '.$cod_postal), 0, 'L');
	
	$pdf->SetXY(70,440);
	$pdf->Cell(10, 8, utf8_decode('Dpto: '.$dpto), 0, 'L');
	
	$pdf->SetXY(70,480);
	$pdf->Cell(10, 8, utf8_decode('Profesional: '.$profesional), 0, 'L');
	
	$pdf->SetXY(70,500);
	$pdf->Cell(10, 8, utf8_decode('Situacion: '.$dessit), 0, 'L');

	//historia clinica
	$pdf->SetXY(70,540);
	$pdf->Cell(10, 8, utf8_decode('Paperas '), 0, 'L');

	$pdf->SetXY(70,560);
	$pdf->Cell(10, 8, utf8_decode('Rubeola '), 0, 'L');

	$pdf->SetXY(70,580);
	$pdf->Cell(10, 8, utf8_decode('Varicela '), 0, 'L');

	$pdf->SetXY(250,540);
	$pdf->Cell(10, 8, utf8_decode('Epilepsia '), 0, 'L');

	$pdf->SetXY(250,560);
	$pdf->Cell(10, 8, utf8_decode('Hepatitis '), 0, 'L');

	$pdf->SetXY(250,580);
	$pdf->Cell(10, 8, utf8_decode('Sinusitis '), 0, 'L');

	$pdf->SetXY(420,540);
	$pdf->Cell(10, 8, utf8_decode('Diabetes '), 0, 'L');

	$pdf->SetXY(420,560);
	$pdf->Cell(10, 8, utf8_decode('Apendicitis '), 0, 'L');

	$pdf->SetXY(420,580);
	$pdf->Cell(10, 8, utf8_decode('Amigdalitis '), 0, 'L');

	$pdf->SetXY(70,610);
	$pdf->Cell(10, 8, utf8_decode('Comidas: '.$historia['comidas']), 0, 'L');

	$pdf->SetXY(70,630);
	$pdf->Cell(10, 8, utf8_decode('Medicamentos: '.$historia['medicamentos']), 0, 'L');

	$pdf->SetXY(70,650);
	$pdf->Cell(10, 8, utf8_decode('Otras: '.$historia['otras']), 0, 'L');

	$pdf->Line(70,320,525,320);
	$pdf->Line(70,460,525,460);
	$pdf->Line(70,520,525,520);

	
	$pdf->SetXY(130,540);
	if($historia['paperas'] == 1)
		$check = "4"; else $check = "";
		$pdf->SetFont('ZapfDingbats','', 10);
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(130,560);
	if($historia['rubeola'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(130,580);
	if($historia['varicela'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(320,540);
	if($historia['epilepsia'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(320,560);
	if($historia['hepatitis'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(320,580);
	if($historia['sinusitis'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(500,540);
	if($historia['diabetes'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(500,560);
	if($historia['apendicitis'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);

	$pdf->SetXY(500,580);
	if($historia['amigdalitis'] == true)
		$check = "4"; else $check = "";
		$pdf->Cell(10, 10, $check, 1, 0);	
	
	if($fec_ate==NULL){
		$pdf->Image('../../static/images/back.png','0','0','595','841','PNG');
	}
	else{
		$pdf->Image('../../static/images/back_finalizado.png','0','0','595','841','PNG');
	}
	$pdf->Image('../../static/images/logo_MutualFME.png','425','100','100','100','PNG');//LOGO
	

	

	$pdf->Output(); //Salida al navegador	
}




	
//llamada a la funcion con el parametro pasado por la url.	
	$_GET['funcion']();
//luego de que se ejecutó la funcion, se cierra la bd
	$db->cerrar_sesion();	
?>