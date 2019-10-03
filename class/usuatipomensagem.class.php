<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuaTipoMensagem extends Cadastro{
	function __construct($login = NULL, $codtipomensagem = NULL, $diasaviso = NULL){
		parent::__construct();
		$this->table = "usuatipomensagem";
		$this->primarykey = array("login", "codtipomensagem");
		$this->setlogin($login);
		$this->setcodtipomensagem($codtipomensagem);
		$this->setdiasaviso($diasaviso);
		if(!is_null($this->getlogin()) && !is_null($this->getcodtipomensagem())){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcodtipomensagem(){
		return $this->fields["codtipomensagem"];
	}

	function getdiasaviso(){
		return $this->fields["diasaviso"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setcodtipomensagem($value){
		$this->fields["codtipomensagem"] = value_numeric($value);
	}

	function setdiasaviso($value){
		$this->fields["diasaviso"] = value_numeric($value);
	}
}
?>