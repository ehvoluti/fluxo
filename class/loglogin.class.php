<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LogLogin extends Cadastro{
	function __construct($login = NULL,$data = NULL,$hora = NULL){
		parent::__construct();
		$this->table = "loglogin";
		$this->primarykey = array("login","data","hora");
		$this->setlogin($login);
		$this->setdata($data);
		$this->sethora($hora);
		if($this->getlogin() != NULL && $this->getdata() != NULL && $this->gethora() != NULL){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getip(){
		return $this->fields["ip"];
	}

	function getdata(){
		return $this->fields["data"];
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setip($value){
		$this->fields["ip"] = value_string($value,30);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_time($value);
	}
}
?>