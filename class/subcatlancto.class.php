<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class SubCatLancto extends Cadastro{
	function __construct($codsubcatlancto = NULL){
		parent::__construct();
		$this->newrelation("subcatlancto","codcatlancto","catlancto","codcatlancto");
		$this->table = "subcatlancto";
		$this->primarykey = "codsubcatlancto";
		$this->setcodsubcatlancto($codsubcatlancto);
		if($this->getcodsubcatlancto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}
	
	function getbloconodre(){
		return $this->fields["bloconodre"];
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}
	
	function setbloconodre($value){
		$this->fields["bloconodre"] = value_numeric($value);
	}	
}
