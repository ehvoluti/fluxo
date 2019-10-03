<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Equivalente extends Cadastro{
	function __construct($codequivalente = NULL){
		parent::__construct();
		$this->table = "equivalente";
		$this->primarykey = array("codequivalente");
		$this->setcodequivalente($codequivalente);
		if(!is_null($this->getcodequivalente())){
			$this->searchbyobject();
		}
	}

	function getcodequivalente(){
		return $this->fields["codequivalente"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodequivalente($value){
		$this->fields["codequivalente"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,80);
	}
}
?>