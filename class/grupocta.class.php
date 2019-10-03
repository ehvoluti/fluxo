<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoCta extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "grupocta";
		$this->primarykey = "codgrcta";
		$this->setcodgrcta($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodgrcta(){
		return $this->fields["codgrcta"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodgrcta($value){
		$this->fields["codgrcta"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>