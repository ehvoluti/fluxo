<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class RecebePdv extends Cadastro{
	function __construct($codrecebepdv = NULL){
		parent::__construct();
		$this->table = "recebepdv";
		$this->primarykey = array("codrecebepdv");
		$this->setcodrecebepdv($codrecebepdv);
		if(!is_null($this->getcodrecebepdv())){
			$this->searchbyobject();
		}
	}

	function getcodrecebepdv(){
		return $this->fields["codrecebepdv"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function getcodfinaliz(){
		return $this->fields["codfinaliz"];
	}

	function gettiporecebimento(){
		return $this->fields["tiporecebimento"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getcaixa($format = FALSE){
		return ($format ? number_format($this->fields["caixa"],0,",","") : $this->fields["caixa"]);
	}

	function setcodrecebepdv($value){
		$this->fields["codrecebepdv"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setcodfinaliz($value){
		$this->fields["codfinaliz"] = value_string($value,5);
	}

	function settiporecebimento($value){
		$this->fields["tiporecebimento"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcaixa($value){
		$this->fields["caixa"] = value_numeric($value);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_string($value,6);
	}
}