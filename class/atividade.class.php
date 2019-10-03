<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Atividade extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "atividade";
		$this->primarykey = "codatividade";
		$this->setcodatividade($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodatividade(){
		return $this->fields["codatividade"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodatividade($value){
		$this->fields["codatividade"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>