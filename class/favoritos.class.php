<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Favoritos extends Cadastro{
	function __construct($login = NULL,$idtable = NULL){
		parent::__construct();
		$this->table = "favoritos";
		$this->primarykey = array("login","idtable");
		$this->setlogin($login);
		$this->setidtable($idtable);
		if($this->getlogin() != NULL && $this->getidtable() != NULL){
			$this->searchbyobject();
		}
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function gettitle(){
		return $this->fields["title"];
	}

	function getidtable(){
		return $this->fields["idtable"];
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

	function settitle($value){
		$this->fields["title"] = value_string($value,100);
	}

	function setidtable($value){
		$this->fields["idtable"] = value_string($value,30);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_time($value);
	}
}
?>