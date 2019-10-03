<?php

require_file("class/cadastro.class.php");

class ParamPontoOperacao extends Cadastro{

	function __construct($operacao = NULL){
		parent::__construct();
		$this->table = "parampontooperacao";
		$this->primarykey = array("operacao");
		$this->setoperacao($operacao);
		if(!is_null($this->getoperacao())){
			$this->searchbyobject();
		}
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getdispvaldescto(){
		return $this->fields["dispvaldescto"];
	}

	function getdisppercdescto(){
		return $this->fields["disppercdescto"];
	}

	function getdispvalacresc(){
		return $this->fields["dispvalacresc"];
	}

	function getdisppercacresc(){
		return $this->fields["disppercacresc"];
	}

	function getdispvalfrete(){
		return $this->fields["dispvalfrete"];
	}

	function getdisppercfrete(){
		return $this->fields["disppercfrete"];
	}

	function getdispnatoperacao(){
		return $this->fields["dispnatoperacao"];
	}

	function getdispcomplemento(){
		return $this->fields["dispcomplemento"];
	}

	function getdisptransportadora(){
		return $this->fields["disptransportadora"];
	}

	function getdispfinanceiro(){
		return $this->fields["dispfinanceiro"];
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getinformarepresentante(){
		return $this->fields["informarepresentante"];
	}	

	function getstatusemanalise(){
		return $this->fields["statusemanalise"];
	}		

	function getmostranatoperacao(){
		return $this->fields["mostranatoperacao"];
	}		

	function getmostratabpreco(){
		return $this->fields["mostratabpreco"];
	}		
	
	function getcodestabelecpadrao(){
		return $this->fields["codestabelecpadrao"];
	}	

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setdispvaldescto($value){
		$this->fields["dispvaldescto"] = value_string($value, 1);
	}

	function setdisppercdescto($value){
		$this->fields["disppercdescto"] = value_string($value, 1);
	}

	function setdispvalacresc($value){
		$this->fields["dispvalacresc"] = value_string($value, 1);
	}

	function setdisppercacresc($value){
		$this->fields["disppercacresc"] = value_string($value, 1);
	}

	function setdispvalfrete($value){
		$this->fields["dispvalfrete"] = value_string($value, 1);
	}

	function setdisppercfrete($value){
		$this->fields["disppercfrete"] = value_string($value, 1);
	}

	function setdispnatoperacao($value){
		$this->fields["dispnatoperacao"] = value_string($value, 1);
	}

	function setdispcomplemento($value){
		$this->fields["dispcomplemento"] = value_string($value, 1);
	}

	function setdisptransportadora($value){
		$this->fields["disptransportadora"] = value_string($value, 1);
	}

	function setdispfinanceiro($value){
		$this->fields["dispfinanceiro"] = value_string($value, 1);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setinformarepresentante($value){
		$this->fields["informarepresentante"] = value_string($value, 1);
	}

	function setstatusemanalise($value){
		$this->fields["statusemanalise"] = value_string($value, 1);
	}	

	function setmostranatoperacao($value){
		$this->fields["mostranatoperacao"] = value_string($value, 1);
	}	

	function setmostratabpreco($value){
		$this->fields["mostratabpreco"] = value_string($value, 1);
	}
	
	function setcodestabelecpadrao($value){
		$this->fields["codestabelecpadrao"] = value_numeric($value);
	}
}