<?php

require_once("websac/require_file.php");
require_file("def/function.php");
require_file("class/wserror.class.php");
require_file("class/logerror.class.php");

final class Connection extends PDO{

	public $dbtype;
	public $dbuser;
	public $dbpass;
	public $dbhost;
	public $dbname;
	public $dbport;
	private $error;
	private $transaction;

	function __construct($param_1 = NULL, $param_2 = NULL, $param_3 = NULL){
		$this->transaction = 0;
		if(!is_string($param_1)){

			if(file_exists("support/config.ini")){
				$file = parse_ini_file("support/config.ini");
			}elseif(file_exists("../support/config.ini")){
				$file = parse_ini_file("../support/config.ini");
			}elseif(file_exists("../../support/config.ini")){
				$file = parse_ini_file("../../support/config.ini");
			}else{
				$this->error = new WsError(100);
				$this->error->finalize();
			}

			$this->dbtype = $file["dbtype"];
			$this->dbuser = $file["dbuser"];
			$this->dbpass = $file["dbpass"];
			$this->dbhost = $file["dbhost"];
			$this->dbname = $file["dbname"];
			$this->dbport = $file["dbport"];

			$x = 0; // Contador de tentativas de conexao
			$x_max = 50; // Maximo de tentavas de conexoes
			$while_end = FALSE; // Flag que identifica se deve sair do laco
			while(true){
				try{
					switch($this->dbtype){
						case "mysql" : parent::__construct("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
							break;
						case "pgsql" : parent::__construct("pgsql:host={$this->dbhost} dbname={$this->dbname} user={$this->dbuser} password={$this->dbpass} ".(strlen($this->dbport) > 0 ? "port={$this->dbport}" : ""));
							break;
						case "sqlite" : parent::__construct("sqlite:{$this->dbname}");
							break;
						case "firebird" : parent::__construct("firebird:dbname={$this->dbname}", $this->dbuser, $this->dbpass);
							break;
						case "oci" : parent::__construct("oci:dbname={$this->dbname}", $this->dbuser, $this->dbpass);
							break;
						case "mssql" : parent::__construct("mssql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
							break;
					}
					$while_end = method_exists($this, "query");
					if(!$while_end){
						$x++;
						if($x > $x_max){
							die("<code><b>Erro ao conectar com o banco de dados:<br>host:</b> ".$file["dbhost"]."<br><b>dbname:</b> ".$file["dbname"]."<br><b>user:</b> ".$file["dbuser"]."</code>");
						}
						usleep(100000); // 10 centecimos de segundo
					}
				}catch(exception $e){
					$x++;
					if($x > $x_max){
						$this->error = new WsError();
						$this->error->setoriginal($e);
						$this->error->finalize();
					}
					usleep(250000); // 25 centecimos de segundo
				}
				if($while_end){
					break;
				}
			}
		}else{
			$param = explode(":", $param_1);
			$this->dbtype = $param[0];
			try{
				parent::__construct($param_1, $param_2, $param_3);
			}catch(Exception $e){
				$this->error = new WsError();
				$this->error->setoriginal($e);
			}
		}
//		$this->query("SET NAMES 'utf8'");
		//parent::setAttribute(parent::ATTR_ERRMODE,parent::ERRMODE_EXCEPTION);
	}

	function exec($statement){
		return $this->query($statement);
	}

	function query($statement){
		// Murilo - 27/10/2017
		// Tratamento criado para capturar local que esta enviando essa instrucao
		if(trim($statement) === "SELECT  pedido.* FROM pedido"){
			LogError::register("Registrado instrução:\r\n{$statement}");
		}

		try{
			$result = parent::query($statement);
			if($result === false){
				$error = $this->errorInfo();
				LogError::register("Erro ao executar instrução no banco de dados:\r\n\r\n{$statement}\r\n\r\n{$error[2]}");
			}
		}catch(Exception $e){
			LogError::register("Erro ao executar instrução no banco de dados:\r\n\r\n{$statement}\r\n\r\n{$e->getMessage()}");
			$result = false;
		}
		return $result;
	}

	function getdbname(){
		return $this->dbname;
	}

	function start_transaction(){
		if($this->transaction == 0){
			parent::beginTransaction();
		}
		$this->transaction++;
	}

	function commit(){
		if($this->transaction == 1){
			parent::commit();
		}elseif($this->transaction == 0){
			die(messagebox("error", "Erro na transa&ccedil;&atilde;o com o banco de dados", "N&atilde;o &eacute; poss&iacute;vel aceitar uma transa&ccedil;&atilde;o que n&atilde;o foi iniciada no banco de dados."));
		}
		$this->transaction--;
	}

	function rollback(){
		if($this->transaction == 1){
			parent::rollBack();
		}elseif($this->transaction == 0){
			die(messagebox("error", "Erro na transa&ccedil;&atilde;o com o banco de dados", "N&atilde;o &eacute; poss&iacute;vel retroceder uma transa&ccedil;&atilde;o que n&atilde;o foi iniciada no banco de dados."));
		}
		$this->transaction--;
	}

}
