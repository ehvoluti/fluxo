<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class centroCustoLancto extends Cadastro{
	function __construct($codccusto = NULL, $codlancto = NULL){
		parent::__construct();
		$this->table = "centrocustolancto";
		$this->primarykey = array("codccusto", "codlancto");
		$this->setcodccusto($codccusto);
		$this->setcodlancto($codlancto);
		if(!is_null($this->getcodccusto()) && !is_null($this->getcodlancto())){
			$this->searchbyobject();
		}
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function getcodccusto(){
		return $this->fields["codccusto"];
	}

	function getpercpart($format = FALSE){
		return ($format ? number_format($this->fields["percpart"],2,",","") : $this->fields["percpart"]);
	}

	function getvalpart($format = FALSE){
		return ($format ? number_format($this->fields["valpart"],2,",","") : $this->fields["valpart"]);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function setcodccusto($value){
		$this->fields["codccusto"] = value_numeric($value);
	}

	function setpercpart($value){
		$this->fields["percpart"] = value_numeric($value);
	}

	function setvalpart($value){
		$this->fields["valpart"] = value_numeric($value);
	}
}
?>