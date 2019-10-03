<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuaRestricao extends Cadastro{
	function __construct($login = NULL,$idtable = NULL){
		parent::__construct();
		$this->table = "usuarestricao";
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

	function getidtable(){
		return $this->fields["idtable"];
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setidtable($value){
		$this->fields["idtable"] = value_string($value,30);
	}
}
?>