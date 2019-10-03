<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamNotaFiscal extends Cadastro{
	function __construct($codestabelec = NULL, $operacao = NULL){
		parent::__construct();
		$this->table = "paramnotafiscal";
		$this->primarykey = array("codestabelec", "operacao");
		$this->setcodestabelec($codestabelec);
		$this->setoperacao($operacao);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getoperacao())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getcodtpdocto(){
		return $this->fields["codtpdocto"];
	}

	function getcodcondpagtoauto(){
		return $this->fields["codcondpagtoauto"];
	}

	function getcodespecieauto(){
		return $this->fields["codespecieauto"];
	}

	function getnatoperacaopfin(){
		return $this->fields["natoperacaopfin"];
	}

	function getnatoperacaopfex(){
		return $this->fields["natoperacaopfex"];
	}

	function getnatoperacaopjin(){
		return $this->fields["natoperacaopjin"];
	}

	function getnatoperacaopjex(){
		return $this->fields["natoperacaopjex"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,2);
	}

	function setcodtpdocto($value){
		$this->fields["codtpdocto"] = value_numeric($value);
	}

	function setcodcondpagtoauto($value){
		$this->fields["codcondpagtoauto"] = value_numeric($value);
	}

	function setcodespecieauto($value){
		$this->fields["codespecieauto"] = value_numeric($value);
	}

	function setnatoperacaopfin($value){
		$this->fields["natoperacaopfin"] = value_string($value,9);
	}

	function setnatoperacaopfex($value){
		$this->fields["natoperacaopfex"] = value_string($value,9);
	}

	function setnatoperacaopjin($value){
		$this->fields["natoperacaopjin"] = value_string($value,9);
	}

	function setnatoperacaopjex($value){
		$this->fields["natoperacaopjex"] = value_string($value,9);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}
}