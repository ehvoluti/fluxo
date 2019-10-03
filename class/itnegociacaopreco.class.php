<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItNegociacaoPreco extends Cadastro{
	function __construct(){
		parent::__construct($codnegociacaopreco = NULL,$codproduto = NULL);
		$this->table = "itnegociacaopreco";
		$this->primarykey = array("codnegociacaopreco","codproduto");
		$this->setcodnegociacaopreco($codnegociacaopreco);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodnegociacaopreco()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}
	
	function getcodnegociacaopreco(){
		return $this->fields["codnegociacaopreco"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getclassfiscal(){
		return $this->fields["classfiscal"];
	}

	function getcodipi(){
		return $this->fields["codipi"];
	}

	function getcodembal(){
		return $this->fields["codembal"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"],2,",","") : $this->fields["precovrj"]);
	}

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"],2,",","") : $this->fields["custotab"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"],2,",","") : $this->fields["valdescto"]);
	}

	function getvalacresc($format = FALSE){
		return ($format ? number_format($this->fields["valacresc"],2,",","") : $this->fields["valacresc"]);
	}

	function getvalfrete($format = FALSE){
		return ($format ? number_format($this->fields["valfrete"],2,",","") : $this->fields["valfrete"]);
	}

	function getvaloferta($format = FALSE){
		return ($format ? number_format($this->fields["valoferta"],2,",","") : $this->fields["valoferta"]);
	}
	
	function getvalseguro($format = FALSE){
		return ($format ? number_format($this->fields["valseguro"],2,",","") : $this->fields["valseguro"]);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"],2,",","") : $this->fields["percdescto"]);
	}

	function getpercacresc($format = FALSE){
		return ($format ? number_format($this->fields["percacresc"],2,",","") : $this->fields["percacresc"]);
	}

	function getpercfrete($format = FALSE){
		return ($format ? number_format($this->fields["percfrete"],2,",","") : $this->fields["percfrete"]);
	}

	function getpercoferta($format = FALSE){
		return ($format ? number_format($this->fields["percoferta"],2,",","") : $this->fields["percoferta"]);
	}
	
	function getpercseguro($format = FALSE){
		return ($format ? number_format($this->fields["percseguro"],2,",","") : $this->fields["percseguro"]);
	}

	function getmargemvrg($format = FALSE){
		return ($format ? number_format($this->fields["margemvrg"],2,",","") : $this->fields["margemvrg"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function setcodnegociacaopreco($value){
		$this->fields["codnegociacaopreco"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setclassfiscal($value){
		$this->fields["classfiscal"] = value_numeric($value);
	}

	function setcodipi($value){
		$this->fields["codipi"] = value_numeric($value);
	}

	function setcodembal($value){
		$this->fields["codembal"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setcustotab($value){
		$this->fields["custotab"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function setvalacresc($value){
		$this->fields["valacresc"] = value_numeric($value);
	}

	function setvalfrete($value){
		$this->fields["valfrete"] = value_numeric($value);
	}

	function setvaloferta($value){
		$this->fields["valoferta"] = value_numeric($value);
	}
	
	function setvalseguro($value){
		$this->fields["valseguro"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setpercacresc($value){
		$this->fields["percacresc"] = value_numeric($value);
	}

	function setpercfrete($value){
		$this->fields["percfrete"] = value_numeric($value);
	}

	function setpercoferta($value){
		$this->fields["percoferta"] = value_numeric($value);
	}

	function setpercseguro($value){
		$this->fields["percseguro"] = value_numeric($value);
	}
	
	function setmargemvrg($value){
		$this->fields["margemvrg"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}