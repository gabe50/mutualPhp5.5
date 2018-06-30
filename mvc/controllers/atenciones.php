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

	$resultado = $GLOBALS['db']->select('SELECT socios.beneficio,socios.soc_titula,socios.numero_soc,persona.sexo,persona.nombre,persona.numdoc 
							FROM socios, persona 
							WHERE socios.soc_titula=socios.numero_soc 
							AND persona.id_persona=socios.id_persona;');

							
	$resultado_particulares = $GLOBALS['db']->select('SELECT fme_adhsrv.nombre, fme_adhsrv.documento, persona.sexo, fme_adhsrv.id_persona 
								FROM fme_adhsrv, persona 
								WHERE fme_adhsrv.codigo=021 
								AND persona.id_persona=fme_adhsrv.id_persona;');		
								
	if($resultado || $resultado_particulares)
	{
		echo $GLOBALS['twig']->render('/Atenciones/nueva_atencion_1.html', compact('asociado','resultado','resultado_particulares','use','priv'));
	}
	else
	{
		$error=[
					'menu'			=>"Atenciones",
					'funcion'		=>"Listado de asociados",
					'descripcion'	=>"No se encontraron resultados."
					];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
	}
}

		
//funcion verMAS, el cual debe realizar la consulta del asociado seleccionado para mostrar toda su informacion
function verMas()	
		{
			
			global $use;
			global $priv;

			//NOTA: Para ver si funciona tienen que asociarle un adherente en la tabla socios, ya que en los datos de ejemplo todos son titulares
			//NOTA: Lo que hice fue: en tabla socios en numero_soc=00044 cambiar el campo soc_titula de manera que quede soc_titula=00277
			
			//---------------CONSULTA QUE DEVUELVE TODA LA INFO DEL ASOCIADO TITULAR----------------
			
			$numero_socio = $_GET['num_soc']; //Es el número del socio titular que debe ser tomado del PASO 1
			

				$resultadoTitular = $GLOBALS['db']->select("SELECT socios.id_persona,socios.numero_soc,socios.beneficio,socios.fec_alt,socios.fec_baja,socios.lugar_pago,socios.soc_titula
				,persona.id_persona,persona.nombre,persona.numdoc,persona.cuil,persona.sexo,persona.fecnacim,persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
				,persona.dpmto,persona.tel_fijo,persona.tel_cel,persona.fec_alta AS fec_alta2,persona.fec_baja AS fec_baja2,persona.cbu,persona.banco,persona.usualta
									  FROM socios,persona 
									  WHERE socios.soc_titula = '$numero_socio' 
									  AND socios.id_persona = persona.id_persona
                                      AND socios.numero_soc= socios.soc_titula");
									 

			if(!$resultadoTitular)
			{
				$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"verMas",
				'descripcion'	=>"No se encuentra al titular $numero_socio"
				];
				echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
				return;
			}
			
			///---FUNCIÓN PARA CALCULAR EDAD----
			
			$fecha=$resultadoTitular[0]['fecnacim'];
			$dias = explode("-", $fecha, 3);
			
			// $dias[0] es el año
			// $dias[1] es el mes
			// $dias[2] es el dia
			
			// mktime toma los datos en el orden (0,0,0, mes, dia, año) 
			$dias = mktime(0,0,0,$dias[1],$dias[2],$dias[0]);
			$edad = (int)((time()-$dias)/31556926 );
			$resultadoTitular[0]['edad']=$edad;
			
			///---FIN FUNCIÓN PARA CALCULAR EDAD----
			
			$estado[0]='1';
			$estado[1]='1';
			$estado[2]='1';
			$estado[3]='1';
			$estado[4]='1';
			
			//---------------CONSULTA QUE DEVUELVE TODA LA INFO DE LOS SERVICIOS DEL ASOCIADO TITULAR----------------
			
			//Por cuota
			$resultadoTitularServicios1 = $GLOBALS['db']->select("SELECT socios.id_persona,socios.numero_soc,socios.beneficio,socios.fec_alt,socios.fec_baja,socios.lugar_pago,socios.soc_titula
				,persona.id_persona,persona.nombre,persona.numdoc,persona.cuil,persona.sexo,persona.fecnacim,persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
				,persona.dpmto,persona.tel_fijo,persona.tel_cel,persona.fec_alta AS fec_alta2,persona.fec_baja AS fec_baja2,persona.cbu,persona.banco,persona.usualta
				,fme_adhsrv.codigo,fme_adhsrv.parentesco,fme_adhsrv.periodoini,fme_adhsrv.periodofin,fme_adhsrv.motivobaja,fme_adhsrv.documento
				,tar_srv.nombre AS nombreplan,tar_srv.idmutual 
									   FROM socios,persona,fme_adhsrv,tar_srv 
									   WHERE socios.soc_titula = '$numero_socio' 
									   AND socios.id_persona = persona.id_persona
                                       AND socios.numero_soc= socios.soc_titula
									   AND fme_adhsrv.socnumero = socios.soc_titula
									   AND fme_adhsrv.codigo = tar_srv.idmutual");
									   
			if(!$resultadoTitularServicios1)
				$estado[0]='0';
			
			//Por tarjeta
			$resultadoTitularServicios2 = $GLOBALS['db']->select("SELECT socios.id_persona,socios.numero_soc,socios.beneficio,socios.fec_alt,socios.fec_baja,socios.lugar_pago,socios.soc_titula
				,persona.id_persona,persona.nombre,persona.numdoc,persona.cuil,persona.sexo,persona.fecnacim,persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
				,persona.dpmto,persona.tel_fijo,persona.tel_cel,persona.fec_alta AS fec_alta2,persona.fec_baja AS fec_baja2,persona.cbu,persona.banco,persona.usualta
				,tar_srv.nombre AS nombreplan,tar_srv.codigo AS codigotarsrv, tar_srvadherentes.codigo, tar_srvadherentes.parentesco 
									   FROM socios,persona,tar_srv, tar_srvadherentes 
									   WHERE socios.soc_titula = '$numero_socio' 
									   AND socios.id_persona = persona.id_persona
                                       AND socios.numero_soc= socios.soc_titula
									   AND tar_srvadherentes.estado = 1
									   AND tar_srvadherentes.socnumero = socios.soc_titula 
									   AND tar_srvadherentes.codigo = tar_srv.codigo
									   AND tar_srvadherentes.tipo = 1");
			
			if(!$resultadoTitularServicios2)
				$estado[1]='0';
			
			
			//---------------CONSULTA QUE DEVUELVE TODA LA INFO DE LOS ADHERENTES DEL ASOCIADO TITULAR CON APORTES POR CUOTA----------------
			

		   $resultadoAdherentes1 = $GLOBALS['db']->select("SELECT socios.id_persona,socios.numero_soc,socios.beneficio,socios.fec_alt,socios.fec_baja,socios.lugar_pago,socios.soc_titula
				,persona.id_persona,persona.nombre,persona.numdoc,persona.cuil,persona.sexo,persona.fecnacim,persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
				,persona.dpmto,persona.tel_fijo,persona.tel_cel,persona.fec_alta AS fec_alta2,persona.fec_baja AS fec_baja2,persona.cbu,persona.banco,persona.usualta
				,fme_adhsrv.codigo,fme_adhsrv.parentesco,fme_adhsrv.periodoini,fme_adhsrv.periodofin,fme_adhsrv.motivobaja,fme_adhsrv.documento
				,tar_srv.nombre AS nombreplan,tar_srv.idmutual 
									   FROM socios,persona,fme_adhsrv,tar_srv 
									   WHERE socios.soc_titula = '$numero_socio'
									   AND socios.numero_soc != socios.soc_titula
									   AND socios.id_persona = persona.id_persona
									   AND fme_adhsrv.socnumero = socios.numero_soc 
									   AND fme_adhsrv.codigo = tar_srv.idmutual");
			
			if(!$resultadoAdherentes1)
				$estado[2]='0';
			
			//---------------CONSULTA QUE DEVUELVE TODA LA INFO DE LOS ADHERENTES DEL ASOCIADO TITULAR CON APORTES POR TARJETA----------------

			$resultadoAdherentes2 = $GLOBALS['db']->select("SELECT socios.id_persona,socios.numero_soc,socios.beneficio,socios.fec_alt,socios.fec_baja,socios.lugar_pago,socios.soc_titula
				,persona.id_persona,persona.nombre,persona.numdoc,persona.cuil,persona.sexo,persona.fecnacim,persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
				,persona.dpmto,persona.tel_fijo,persona.tel_cel,persona.fec_alta AS fec_alta2,persona.fec_baja AS fec_baja2,persona.cbu,persona.banco,persona.usualta
				,tar_srv.nombre AS nombreplan,tar_srv.codigo AS codigotarsrv, tar_srvadherentes.codigo, tar_srvadherentes.parentesco 
									   FROM socios,persona,tar_srv, tar_srvadherentes 
									   WHERE socios.soc_titula = '$numero_socio'
									   AND socios.numero_soc != socios.soc_titula
									   AND tar_srvadherentes.estado = 1
									   AND socios.id_persona = persona.id_persona
									   AND tar_srvadherentes.socnumero = socios.soc_titula 
									   AND tar_srvadherentes.codigo = tar_srv.codigo");	

			

								   
			
			if(!$resultadoAdherentes2)
				$estado[3]='0';
		
		    
			//---------------CONSULTA QUE DEVUELVE EL LISTADO DE TODAS LAS ASISTENCIAS----------------
			
			//NOTA: Para que puedan ver si funciona o no hacer la prueba con el siguiente ejemplo:
			// En la tabla sigssaludsigssaludfme_asistencia modifiquen en cualquier lado y pongan alguno con doctitu = 06948018 (o busquen cualquier DNI de un socio titular y usen ese)
			// Cuando prueben el sistema vayan al ver más de Barrionuevo Samuel y van a ver el listado de atenciones que tiene asociado
			
			$asistencias = $GLOBALS['db']->select("SELECT sigssaludsigssaludfme_asistencia.doctitu, sigssaludsigssaludfme_asistencia.numdoc, sigssaludsigssaludfme_asistencia.nombre,
									  sigssaludsigssaludfme_asistencia.fec_pedido, sigssaludsigssaludfme_asistencia.hora_pedido, sigssaludsigssaludfme_asistencia.dessit, sigssaludsigssaludfme_asistencia.fec_ate,
									  sigssaludsigssaludfme_asistencia.sintomas, sigssaludsigssaludfme_asistencia.diagnostico, sigssaludsigssaludfme_asistencia.tratamiento, sigssaludsigssaludfme_asistencia.hora_aten,
									  sigssaludsigssaludfme_asistencia.profesional
									  FROM sigssaludsigssaludfme_asistencia, socios, persona 
									  WHERE soc_titula = '$numero_socio' 
									  AND socios.id_persona = persona.id_persona
									  AND numero_soc = soc_titula
									  AND persona.numdoc = sigssaludsigssaludfme_asistencia.doctitu");
			
			if(!$asistencias)
				$estado[4]='0';
			
			
			

			echo $GLOBALS['twig']->render('/Atenciones/perfil.html', compact('resultadoTitular', 
																  'resultadoTitularServicios1', 
																  'resultadoTitularServicios2', 
																  'resultadoAdherentes1',
																  'resultadoAdherentes2',
																  'asistencias',
																  'estado','use','priv'));	
			
			
		}
		
		
function verMasParticular()	
		{
			
			global $use;
			global $priv;
			//---------------CONSULTA QUE DEVUELVE TODA LA INFO DEL PARTICULAR----------------
			
			$id_persona = $_GET['persona']; //Es el número del particular
			
			$particular = $GLOBALS['db']->select("SELECT fme_adhsrv.id_persona, fme_adhsrv.codigo, persona.nombre, persona.sexo,persona.fecnacim, 
			persona.domicilio,persona.casa_nro,persona.barrio,persona.localidad,persona.codpostal
			FROM persona, fme_adhsrv
			WHERE persona.id_persona='$id_persona'
			AND fme_adhsrv.codigo=021 
			AND fme_adhsrv.id_persona=persona.id_persona");

			
			if(!$particular)
			{
				$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"verMas",
				'descripcion'	=>"No se encuentra al particular $id_persona"
				];
				echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
				return;
			}
			
			$fecha=$particular[0]['fecnacim'];
			$dias = explode("-", $fecha, 3);
			$dias = mktime(0,0,0,$dias[2],$dias[1],$dias[0]);
			$edad = (int)((time()-$dias)/31556926 );
			$particular[0]['edad']=$edad;
			
			$estado='1';
			
			
			//---------------CONSULTA QUE DEVUELVE EL LISTADO DE TODAS LAS ASISTENCIAS----------------
			
			$asistencias = $GLOBALS['db']->select("SELECT sigssaludfme_asistencia.numdoc, sigssaludfme_asistencia.nombre,
									  sigssaludfme_asistencia.fec_pedido, sigssaludfme_asistencia.hora_pedido, sigssaludfme_asistencia.dessit, sigssaludfme_asistencia.fec_ate,
									  sigssaludfme_asistencia.sintomas, sigssaludfme_asistencia.diagnostico, sigssaludfme_asistencia.tratamiento, sigssaludfme_asistencia.hora_aten,
									  sigssaludfme_asistencia.profesional
									  FROM sigssaludfme_asistencia, persona, fme_adhsrv
									  WHERE persona.id_persona='$id_persona'
									  AND persona.numdoc = sigssaludfme_asistencia.numdoc
									  AND fme_adhsrv.codigo=021 ");
			
			if(!$asistencias)
				$estado='0';
			
			
			

			echo $GLOBALS['twig']->render('/Atenciones/perfil_particular.html', compact('particular',
																  'asistencias',
																  'estado','use','priv'));	
			
			
		}
	
	
//funcion mostrarFormulario, que debe mostrar el formulario con los datos del asociado seleccionado
//se debe pasar por parametro el la variable 

function mostrarFormulario()
{
	global $use;
	global $priv;

	if(!isset($_GET['num_soc']))
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se recibió num_soc como parametro de la función"
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use'));	
		return;
	}

	
	$numero_socio = $_GET['num_soc'];
	$cod_servicio = '020';

	$resultado = $GLOBALS['db']->select("SELECT * FROM socios, persona
										WHERE socios.numero_soc = '$numero_socio'
										AND socios.id_persona=persona.id_persona");

	$id_persona;
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
					'nro'		=>	$res['numero_soc'],
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
					'cod_serv'	=>	$cod_servicio,
					'id_persona' => $res['id_persona']
					];
			if($res['numero_soc']==$res['soc_titula'])
			{
				$persona['doctit'] = $res['numdoc'];
			}
			else
			{
				$soc_titular=$res['soc_titula'];
				$resultado2 = $GLOBALS['db']->select("SELECT * FROM socios, persona
										WHERE socios.numero_soc='$soc_titular'
										AND persona.id_persona=socios.id_persona");
				foreach($resultado2 as $res2)
				{
					$persona['doctit'] = $res2['numdoc'];
				}
			}
			$id_persona=$res['id_persona'];
		}	
	}
	else
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se encontraron datos para el socio '$numero_socio'"
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


function mostrarFormularioParticular()
{
	global $use;
	global $priv;
	if(!isset($_GET['id_persona']))
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se recibió id_persona como parametro de la función"
				];
		echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
		return;
	}

	
	$id_persona = $_GET['id_persona'];
	$cod_servicio = '021';

	$resultado = $GLOBALS['db']->select("SELECT * FROM persona, fme_adhsrv
										WHERE persona.id_persona = '$id_persona'
										AND fme_adhsrv.codigo=021");

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
					'cod_serv'	=>	$cod_servicio,
					'id_persona' => $res['id_persona']
					];
			$id_persona=$res['id_persona'];
		}	
	}
	else
	{
		$error=[
				'menu'			=>"Atenciones",
				'funcion'		=>"mostrarFormulario",
				'descripcion'	=>"No se encontraron datos para el particular '$id_persona'"
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

	echo $GLOBALS['twig']->render('/Atenciones/nueva_atencion_formulario_particular.html', compact('persona','historia','profesionales','use','priv'));
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

	if(isset($_POST['doctit']) && isset($_POST['nro'])){
		
		$cod_ser=$_POST['cod_serv'];
		$doctitu=$_POST['doctit'];
		$numdoc=$_POST['doc'];
		$nombre=$_POST['nombre'];
		$fec_pedido=$_POST['fecha'];
		$hora_pedido=$_POST['hora'];
		$dessit=$_POST['desc'];
		$profesional=$_POST['prof'];
		$sexo=$_POST['sexo'];
		$tel=$_POST['tel'];
		$id_persona= $_POST['id_persona'];
		$nro=$_POST['nro'];		//nro es el numero de asociado

		$profesional_explode = explode('|',$profesional); //$profesional_explode[0] es nombre y $profesional_explode[1] es id_profesional
		
		$resultado=$GLOBALS['db']->query("INSERT INTO sigssaludfme_asistencia (cod_ser,doctitu,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional)
				VALUES ('$cod_ser','$doctitu','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]')");
		
		$res2=$GLOBALS['db']->select("SELECT idnum FROM sigssaludfme_asistencia WHERE idnum=LAST_INSERT_ID()");//obtentemos el id_atencion del ultimo insert realizado
		
		$id_atencion=$res2[0]['idnum']; 

		if(!$resultado)
		{
			$error=[
					'menu'			=>"Atenciones",
					'funcion'		=>"generarAtencion",
					'descripcion'	=>"No se pudo realizar la consulta: INSERT INTO sigssaludfme_asistencia (cod_ser,doctitu,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional)
					VALUES ('$cod_ser','$doctitu','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]')"
					];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
		}
		
		header('Location: ./nueva_atencion_finalizar.php?id_atencion='.$id_atencion);
		return;
	}
	
	
	//SI NO SE SETEA DOCTIT, ES PORQUE ES UN PARTICULAR ENTONCES:
	else{
				
		$cod_ser=$_POST['cod_serv'];
		$doctitu='';
		$numdoc=$_POST['doc'];
		$nombre=$_POST['nombre'];
		$fec_pedido=$_POST['fecha'];
		$hora_pedido=$_POST['hora'];
		$dessit=$_POST['desc'];
		$profesional=$_POST['prof'];
		$sexo=$_POST['sexo'];
		$tel=$_POST['tel'];
		$id_persona= $_POST['id_persona'];
		$nro='';		//nro es el numero de asociado

		$profesional_explode = explode('|',$profesional); //$profesional_explode[0] es nombre y $profesional_explode[1] es id_profesional
		
		$resultado=$GLOBALS['db']->query("INSERT INTO sigssaludfme_asistencia (cod_ser,doctitu,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional)
				VALUES ('$cod_ser','$doctitu','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]')");
		
		$res2=$GLOBALS['db']->select("SELECT idnum FROM sigssaludfme_asistencia WHERE idnum=LAST_INSERT_ID()");//obtentemos el id_atencion del ultimo insert realizado
				
		$id_atencion=$res2[0]['idnum']; 

		if(!$resultado)
		{
			$error=[
					'menu'			=>"Atenciones",
					'funcion'		=>"generarAtencion",
					'descripcion'	=>"No se pudo realizar la consulta: INSERT INTO sigssaludfme_asistencia (cod_ser,doctitu,numdoc,nombre,fec_pedido,hora_pedido,dessit,profesional,domicilio,casa_nro,barrio,localidad,codpostal,dpmto,id_persona, id_profesional)
					VALUES ('$cod_ser','$doctitu','$numdoc','$nombre','$fec_pedido','$hora_pedido','$dessit','$profesional_explode[0]','$dom','$nrocasa','$barrio','$localidad','$cod_postal','$dpto','$id_persona','$profesional_explode[1]')"
					];
			echo $GLOBALS['twig']->render('/Atenciones/error.html', compact('error','use','priv'));	
			return;
		}

		$persona=[
			'cod_serv'	=>	$cod_ser,
			'fecha'		=>	$fec_pedido,
			'hora'		=>	$hora_pedido,
			'nro'		=>	$nro,
			'nombre'	=>	$nombre,
			'sexo'		=>	$sexo,
			'tel'		=>	$tel,
			'doc'		=>	$numdoc,
			'doctit'	=>	$doctitu,
			'dom'		=>	$dom,
			'nro_casa'		=>	$nrocasa,
			'barrio'		=>	$barrio,
			'localidad'		=>	$localidad,
			'cod_postal'	=>	$cod_postal,
			'dpmto'			=>	$dpto,
			'prof'		=>	$profesional,
			'desc'		=>	$dessit
		];
		
		header('Location: ./nueva_atencion_finalizar.php?id_atencion='.$id_atencion);
		return;
	}

	
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

	$resultado=$GLOBALS['db']->select("SELECT sigssaludfme_asistencia.cod_ser, sigssaludfme_asistencia.doctitu, sigssaludfme_asistencia.numdoc, sigssaludfme_asistencia.nombre, sigssaludfme_asistencia.fec_pedido, 
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
		$doctitu=$res['doctitu'];	
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

	$pdf->SetXY(70,260);
	$pdf->Cell(20, 8, utf8_decode('D.N.I. del titular: '.$doctitu), 0, 'L');

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