<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Unidade extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "unidade";
		$this->primarykey = "codunidade";
		$this->setcodunidade($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getsigla(){
		return $this->fields["sigla"];
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setsigla($value){
		$this->fields["sigla"] = value_string(strtoupper($value),2);
	}
}
?>