<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class cotacaoTemp extends Cadastro{
	function __construct($codestabelec = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "cotacaotemp";
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

	function getlido(){
		return $this->fields["lido"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setlido($value){
		$this->fields["lido"] = value_string($value,1);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
