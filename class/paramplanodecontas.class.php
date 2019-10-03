<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamPlanodeContas extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramplanodecontas";
		$this->primarykey = array("codestabelec");
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodconta_valordescto_pag(){
		return $this->fields["codconta_valordescto_pag"];
	}

	function getcodconta_valoracresc_pag(){
		return $this->fields["codconta_valoracresc_pag"];
	}

	function getcodconta_valorabatimento_pag(){
		return $this->fields["codconta_valorabatimento_pag"];
	}

	function getcodconta_valorjuros_pag(){
		return $this->fields["codconta_valorjuros_pag"];
	}

	function getcodconta_valoricms_pag(){
		return $this->fields["codconta_valoricms_pag"];
	}

	function getcodconta_valordescto_rec(){
		return $this->fields["codconta_valordescto_rec"];
	}

	function getcodconta_valoracresc_rec(){
		return $this->fields["codconta_valoracresc_rec"];
	}

	function getcodconta_valorabatimento_rec(){
		return $this->fields["codconta_valorabatimento_rec"];
	}

	function getcodconta_valorjuros_rec(){
		return $this->fields["codconta_valorjuros_rec"];
	}

	function getcodconta_valoricms_rec(){
		return $this->fields["codconta_valoricms_rec"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodconta_valordescto_pag($value){
		$this->fields["codconta_valordescto_pag"] = value_numeric($value);
	}

	function setcodconta_valoracresc_pag($value){
		$this->fields["codconta_valoracresc_pag"] = value_numeric($value);
	}

	function setcodconta_valorabatimento_pag($value){
		$this->fields["codconta_valorabatimento_pag"] = value_numeric($value);
	}

	function setcodconta_valorjuros_pag($value){
		$this->fields["codconta_valorjuros_pag"] = value_numeric($value);
	}

	function setcodconta_valoricms_pag($value){
		$this->fields["codconta_valoricms_pag"] = value_numeric($value);
	}

	function setcodconta_valordescto_rec($value){
		$this->fields["codconta_valordescto_rec"] = value_numeric($value);
	}

	function setcodconta_valoracresc_rec($value){
		$this->fields["codconta_valoracresc_rec"] = value_numeric($value);
	}

	function setcodconta_valorabatimento_rec($value){
		$this->fields["codconta_valorabatimento_rec"] = value_numeric($value);
	}

	function setcodconta_valorjuros_rec($value){
		$this->fields["codconta_valorjuros_rec"] = value_numeric($value);
	}

	function setcodconta_valoricms_rec($value){
		$this->fields["codconta_valoricms_rec"] = value_numeric($value);
	}
}