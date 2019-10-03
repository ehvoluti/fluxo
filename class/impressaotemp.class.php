<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class impressaoTemp extends Cadastro{
	function __construct($codestabelec = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "impressaotemp";
		$this->primarykey = array("codestabelec", "codproduto");
		$this->setcodestabelec($codestabelec);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function getquantidade(){
		return $this->fields["quantidade"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}
}
