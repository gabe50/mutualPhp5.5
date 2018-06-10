<?php
require_once '../../vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('../views');

$twig = new Twig_Environment($loader, []);
	



session_start();

if (isset($_SESSION['usuario']) && isset($_SESSION['privilegios'])) {
	//comprobar si tiene algun privilegio
	if($privilegios[0]['atenciones']=="0" && $privilegios[0]['estadisticas']=="0" && $privilegios[0]['usuarios']=="0" && $privilegios[0]['profesionales']=="0" && $privilegios[0]['historia']=="0"){
		session_destroy();
		$_SESSION = array();
		header('Location: login.php');
	}

	else{
		header('location:index.php');
	}
	
}

$errore = '';

//para comprobar que enviemos informacion.
if ($_SERVER['REQUEST_METHOD'] == 'POST'){

	$usuario = filter_var( strtolower($_POST['usuario']), FILTER_SANITIZE_STRING);
	$password = $_POST['password'];
	//$password = hash('sha512', $password);


	include ('conexion.php');

	$db = new CONEXION();

	$resultado = $GLOBALS['db']->select("
		SELECT * FROM sigssaludfme_usuarios WHERE usuario = '$usuario' AND password = '$password' LIMIT 1");


	if ( $resultado != false) {


		$id_usuario=$resultado[0]['id_usuario'];

		$privilegios = $GLOBALS['db']->select("SELECT * FROM sigssaludfme_privilegios WHERE id_usuario = '$id_usuario' LIMIT 1");

		if( $privilegios != false){
			//Comprobar si tiene algun permiso para acceder
			if($privilegios[0]['atenciones']=="0" && $privilegios[0]['estadisticas']=="0" && $privilegios[0]['usuarios']=="0" && $privilegios[0]['profesionales']=="0" && $privilegios[0]['historia']=="0")
			{
				$errore.= 'El usuario no posee ningun persmiso aun.';
				echo $GLOBALS['twig']->render('/Atenciones/login.html', compact('errore'));	
				session_destroy();
				$_SESSION = array();
				return;
			}
			$_SESSION['usuario'] = $usuario;
			$_SESSION['privilegios'] = [
				'atenciones'		=>$privilegios[0]['atenciones'],
				'estadisticas'		=>$privilegios[0]['estadisticas'],
				'usuarios'			=>$privilegios[0]['usuarios'],
				'profesionales'		=>$privilegios[0]['profesionales'],
				'historia'			=>$privilegios[0]['historia']
				];

			header('Location:index.php');	
		}
		else{
	
			$errore.= 'El usuario no posee ningun persmiso aun.';
			echo $GLOBALS['twig']->render('/Atenciones/login.html', compact('errore'));	
			session_destroy();
			$_SESSION = array();
			return;
		}

		
	} else{

		//Seria mostrarlo abajo  los errores ...porque el cuadro de alert es molesto...
			$errore.= 'El usuario y/o la contraseña son incorrectos';
			echo $GLOBALS['twig']->render('/Atenciones/login.html', compact('errore'));	
			return;


		//	echo '<script type="text/javascript">'; echo 'alert("El usuario y/o la contraseña son incorrectos")'; echo '</script>';
		//$errores.= '<li>El usuario y/o la contraseña son incorrectos</li>';

	}

}


	
	echo $twig->render('/Atenciones/login.html', compact('usuario'));
?>