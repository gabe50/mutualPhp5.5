<?php

		//Conexion a DB
		
		class CONEXION {
			
		var $host;
		var $dbUser;
		var $dbPass;
		var $dbName;
		var $dbConn;
		var $connectError;
		var $errorMsg;
		
	
		function CONEXION()
		{
			$this->host 	= 'localhost';
			$this->dbUser 	= 'root';
			$this->dbPass	= '';
			$this->dbName	= 'fme_mutual';
			$this->connect();
		}

		function connect() {
			// Realiza la conexion con el servidor
			if (!$this->dbConn = mysqli_connect($this->host,$this->dbUser,$this->dbPass,$this->dbName)) {
				$this->errorMsg = 'Could not connect to server';
				$this->connectError = true;
			}
			if (!mysqli_set_charset($this->dbConn, "utf8")) {
				printf("Error cargando el conjunto de caracteres utf8: %s\n", mysqli_error($this->dbConn));
			} 
		}
		

		function isError() 
		{
			if ( $this->connectError ) { return true; }
			$this->errorMsg = mysqli_error($this->dbConn);
			
			if ( empty($this->errorMsg) ) {
				return false;
			} else {
				return true;
			}
		}
	

		function  query($sql) 
		{
			if (!$this->query = @mysqli_query($this->dbConn,$sql)) {
				$this->errorMsg = 'Query failed: ' . mysqli_error($this->dbConn) . ' SQL: ' . $sql;
			}
			return $this->query;
		}
    
		function select($sql) 
		{
			if (!$query = mysqli_query($this->dbConn, $sql)) {
				$this->errorMsg = 'Query failed: ' . mysqli_error($this->dbConn) . ' SQL: ' . $sql;return false;	}
			if(mysqli_num_rows($query)>0){
			$resultado=array();
			while( $row = mysqli_fetch_array($query,MYSQLI_ASSOC) ) {
			   array_push($resultado,$row);
			   }
			 }else{
				return null;
			 }
			 return $resultado;
		}
		
		function cerrar_sesion() 
		{
			mysqli_close($this->dbConn);
		}

		//Funcionaes para realizar el commit
		function startCommit(){
			mysqli_autocommit($this->dbConn, FALSE);
		}

		function commit(){
			mysqli_commit($this->dbConn);
		}

		function rollback(){
			mysqli_rollback($this->dbConn);
		}
    
}


		

		/*//abrir conexion
		$link =mysql_connect("localhost","root","")or die('Error:No se pudo conectar'.mysql_error());
		echo "Conexion  con exito";
		//seleccionar DB
		mysql_select_db('fme_mutual') or die ("Error:Al seleccionar la DB");
		
		
		//Desde aca 
		//creacion de consulta a tabla sigssaludfme_asistencia dentro de DB fme_mutual
		$query="SELECT * FROM sigssaludfme_asistencia";
		$result =mysql_query($query) or die ('Consulta fallida:' .mysql_error());
		//creacion de una tabla a modo ilustrativo
		echo"<table>\n";
		echo "<tr><td>idnum</td><td>cod_ser</td><td>nroordate</td><td>doctitu</td><td>numdoc</td><td>nombre</td><td>fec_pedido</td><td>hora_pedido</td><td>dessit</td><td>fec_ate</td><td>sintomas</td><td>diagnostico</td><td>tratamiento</td><td>hora_aten</td><td>profesional</td><td>feccanasis</td><td>horacanasis</td><td>motivo</td><td>cuenta</td><td>idafiliado</td><td>estado</td></tr> \n"; 
		while($line = mysql_fetch_array($result,MYSQL_ASSOC)){
			echo"\t<tr>\n";

			foreach ($line as $col_value){
				echo "\t\t<td>$col_value</td>\n";
			}
			echo "\t</tr>\n";
}
		echo "</table>\n";
		//liberar consulta/result
		mysql_free_result($result);
		//Hasta aca esta de mas es solo para probar la conexion .



		//cerrar conexion
		mysql_close($link);*/

?>