<?php
require_file("class/cadastro.class.php");

class NotificacaoUsuario extends Cadastro{
	function __construct($idnotificacao = NULL, $login = NULL){
		parent::__construct();
		$this->table = "notificacaousuario";
		$this->primarykey = array("idnotificacao", "login");
		$this->setidnotificacao($idnotificacao);
		$this->setlogin($login);
		if(!is_null($this->getidnotificacao()) && !is_null($this->getlogin())){
			$this->searchbyobject();
		}
	}

	function getidnotificacao(){
		return $this->fields["idnotificacao"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getciente(){
		return $this->fields["ciente"];
	}

	function getenviado(){
		return $this->fields["enviado"];
	}

	function getdtciencia($format = FALSE){
		return ($format ? convert_date($this->fields["dtciencia"],"Y-m-d","d/m/Y") : $this->fields["dtciencia"]);
	}

	function gethrciencia(){
		return substr($this->fields["hrciencia"],0,8);
	}

	function setidnotificacao($value){
		$this->fields["idnotificacao"] = value_numeric($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setciente($value){
		$this->fields["ciente"] = value_string($value,1);
	}

	function setenviado($value){
		$this->fields["enviado"] = value_string($value,1);
	}

	function setdtciencia($value){
		$this->fields["dtciencia"] = value_date($value);
	}

	function sethrciencia($value){
		$this->fields["hrciencia"] = value_time($value);
	}
}
?>