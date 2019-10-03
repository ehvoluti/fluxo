<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuarioDepartamento extends Cadastro{
	function __construct($login = NULL, $coddepto = NULL){
		parent::__construct();
		$this->table = "usuariodepartamento";
		$this->primarykey = array("login", "coddepto");
		$this->setlogin($login);
		$this->setcoddepto($coddepto);
		if(!is_null($this->getlogin()) && !is_null($this->getcoddepto())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcoddepto(){
		return $this->fields["coddepto"];
	}

	function getdisponivel(){
		return $this->fields["disponivel"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcoddepto($value){
		$this->fields["coddepto"] = value_numeric($value);
	}

	function setdisponivel($value){
		$this->fields["disponivel"] = value_string($value,1);
	}
}
?>