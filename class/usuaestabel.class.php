<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuaEstabel extends Cadastro{
	function __construct($login = NULL,$codestabelec = NULL){
		parent::__construct();
		$this->table = "usuaestabel";
		$this->primarykey = array("login","codestabelec");
		$this->setlogin($login);
		$this->setcodestabelec($codestabelec);
		if($this->getlogin() != NULL && $this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}
	
	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}
}
?>