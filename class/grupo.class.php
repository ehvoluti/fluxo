<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Grupo extends Cadastro{
	function __construct($codgrupo = NULL){
		parent::__construct();
		$this->table = "grupo";
		$this->primarykey = array("codgrupo");
		$this->setcodgrupo($codgrupo);
		if($this->getcodgrupo() != NULL){
			$this->searchbyobject();
		}
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getloginresp(){
		return $this->fields["loginresp"];
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setloginresp($value){
		$this->fields["loginresp"] = value_string($value,10);
	}
}
?>