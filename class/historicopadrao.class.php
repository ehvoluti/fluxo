<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class HistoricoPadrao extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "historicopadrao";
		$this->primarykey = "codhistorico";
		$this->setcodhistorico($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodhistorico(){
		return $this->fields["codhistorico"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodhistorico($value){
		$this->fields["codhistorico"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>