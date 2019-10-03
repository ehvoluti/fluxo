<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class InstrucaoBancaria extends Cadastro{
	function __construct($codigoinstrucao = NULL){
		parent::__construct();
		$this->table = "instrucaobancaria";
		$this->primarykey = array("codigoinstrucao");
		$this->setcodigoinstrucao($codigoinstrucao);
		if(!is_null($this->getcodigoinstrucao())){
			$this->searchbyobject();
		}
	}

	function getcodigoinstrucao(){
		return $this->fields["codigoinstrucao"];
	}

	function getcodoficial(){
		return $this->fields["codoficial"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getinstrucao(){
		return $this->fields["instrucao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodigoinstrucao($value){
		$this->fields["codigoinstrucao"] = value_numeric($value);
	}

	function setcodoficial($value){
		$this->fields["codoficial"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value, 2);
	}

	function setinstrucao($value){
		$this->fields["instrucao"] = value_string($value, 2);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 100);
	}
}
?>