<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ArqCliente extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "arqcliente";
		$this->primarykey = "codarqcliente";		
		$this->setcodarqcliente($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodarqcliente(){
		return $this->fields["codarqcliente"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getarquivo(){
		return $this->fields["arquivo"];
	}

	function setcodarqcliente($value){
		$this->fields["codarqcliente"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setarquivo($value){
		$this->fields["arquivo"] = value_string($value,60);
	}
}
?>