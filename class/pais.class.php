<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Pais extends Cadastro{
	function __construct($codpais = NULL){
		parent::__construct();
		$this->table = "pais";
		$this->primarykey = array("codpais");
		$this->setcodpais($codpais);
		if(!is_null($this->getcodpais())){
			$this->searchbyobject();
		}
	}

	function getcodpais(){
		return $this->fields["codpais"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function setcodpais($value){
		$this->fields["codpais"] = value_string($value,5);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,60);
	}
}
?>