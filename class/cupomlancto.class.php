<?php
require_file("class/cadastro.class.php");

class CupomLancto extends Cadastro{
	function __construct($idcupomlancto = NULL){
		parent::__construct();
		$this->table = "cupomlancto";
		$this->autoinc = true;
		$this->primarykey = array("idcupomlancto");
		$this->setidcupomlancto($idcupomlancto);
		if(!is_null($this->getidcupomlancto())){
			$this->searchbyobject();
		}
	}

	function getidcupom(){
		return $this->fields["idcupom"];
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function getcodfinaliz(){
		return $this->fields["codfinaliz"];
	}

	function getidcupomlancto(){
		return $this->fields["idcupomlancto"];
	}

	function getdocliquidacao(){
		return $this->fields["docliquidacao"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getdtvencto($format = FALSE){
		return ($format ? convert_date($this->fields["dtvencto"],"Y-m-d","d/m/Y") : $this->fields["dtvencto"]);
	}

	function getvalordescto($format = FALSE){
		return ($format ? number_format($this->fields["valordescto"],2,",","") : $this->fields["valordescto"]);
	}

	function getvalorpago($format = FALSE){
		return ($format ? number_format($this->fields["valorpago"],2,",","") : $this->fields["valorpago"]);
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function setidcupom($value){
		$this->fields["idcupom"] = value_numeric($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setcodfinaliz($value){
		$this->fields["codfinaliz"] = value_string($value,5);
	}

	function setidcupomlancto($value){
		$this->fields["idcupomlancto"] = value_numeric($value);
	}

	function setdocliquidacao($value){
		$this->fields["docliquidacao"] = value_string($value,30);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,200);
	}

	function setdtvencto($value){
		$this->fields["dtvencto"] = value_date($value);
	}

	function setvalordescto($value){
		$this->fields["valordescto"] = value_numeric($value);
	}

	function setvalorpago($value){
		$this->fields["valorpago"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}
}
