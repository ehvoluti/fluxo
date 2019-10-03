<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdutoEstab extends Cadastro{

	function __construct($codestabelec = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "produtoestab";
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

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"], 2, ",", "") : $this->fields["custotab"]);
	}

	function getfrete($format = FALSE){
		return ($format ? number_format($this->fields["frete"], 4, ",", "") : $this->fields["frete"]);
	}

	function getfretemedio($format = FALSE){
		return ($format ? number_format($this->fields["fretemedio"], 4, ",", "") : $this->fields["fretemedio"]);
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdtinativo($format = FALSE){
		return ($format ? convert_date($this->fields["dtinativo"], "Y-m-d", "d/m/Y") : $this->fields["dtinativo"]);
	}

	function getsldinicio($format = FALSE){
		return ($format ? number_format($this->fields["sldinicio"], 4, ",", "") : $this->fields["sldinicio"]);
	}

	function getsldentrada($format = FALSE){
		return ($format ? number_format($this->fields["sldentrada"], 4, ",", "") : $this->fields["sldentrada"]);
	}

	function getsldsaida($format = FALSE){
		return ($format ? number_format($this->fields["sldsaida"], 4, ",", "") : $this->fields["sldsaida"]);
	}

	function getprecoant($format = FALSE){
		return ($format ? number_format($this->fields["precoant"], 4, ",", "") : $this->fields["precoant"]);
	}

	function getqtdelimite($format = FALSE){
		return ($format ? number_format($this->fields["qtdelimite"], 4, ",", "") : $this->fields["qtdelimite"]);
	}

	function getdtpreco($format = FALSE){
		return ($format ? convert_date($this->fields["dtpreco"], "Y-m-d", "d/m/Y") : $this->fields["dtpreco"]);
	}

	function getdtcusto($format = FALSE){
		return ($format ? convert_date($this->fields["dtcusto"], "Y-m-d", "d/m/Y") : $this->fields["dtcusto"]);
	}

	function getdiasvalidade($format = FALSE){
		return ($format ? number_format($this->fields["diasvalidade"], 2, ",", "") : $this->fields["diasvalidade"]);
	}

	function getqtdatacado($format = FALSE){
		return ($format ? number_format($this->fields["qtdatacado"], 2, ",", "") : $this->fields["qtdatacado"]);
	}

	function getdtultvda($format = FALSE){
		return ($format ? convert_date($this->fields["dtultvda"], "Y-m-d", "d/m/Y") : $this->fields["dtultvda"]);
	}

	function getqtdultvda($format = FALSE){
		return ($format ? number_format($this->fields["qtdultvda"], 4, ",", "") : $this->fields["qtdultvda"]);
	}

	function getdtultcpa($format = FALSE){
		return ($format ? convert_date($this->fields["dtultcpa"], "Y-m-d", "d/m/Y") : $this->fields["dtultcpa"]);
	}

	function getqtdultcpa($format = FALSE){
		return ($format ? number_format($this->fields["qtdultcpa"], 2, ",", "") : $this->fields["qtdultcpa"]);
	}

	function getqtdcprpend($format = FALSE){
		return ($format ? number_format($this->fields["qtdcprpend"], 4, ",", "") : $this->fields["qtdcprpend"]);
	}

	function getqtdvenpend($format = FALSE){
		return ($format ? number_format($this->fields["qtdvenpend"], 4, ",", "") : $this->fields["qtdvenpend"]);
	}

	function getpreventrada($format = FALSE){
		return ($format ? number_format($this->fields["preventrada"], 2, ",", "") : $this->fields["preventrada"]);
	}

	function getprevsaida($format = FALSE){
		return ($format ? number_format($this->fields["prevsaida"], 2, ",", "") : $this->fields["prevsaida"]);
	}

	function getcustorep($format = FALSE){
		return ($format ? number_format($this->fields["custorep"], 2, ",", "") : $this->fields["custorep"]);
	}

	function getprecoatc($format = FALSE){
		return ($format ? number_format($this->fields["precoatc"], 2, ",", "") : $this->fields["precoatc"]);
	}

	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"], 2, ",", "") : $this->fields["precovrj"]);
	}

	function getprecoatcof($format = FALSE){
		return ($format ? number_format($this->fields["precoatcof"], 2, ",", "") : $this->fields["precoatcof"]);
	}

	function getprecovrjof($format = FALSE){
		return ($format ? number_format($this->fields["precovrjof"], 2, ",", "") : $this->fields["precovrjof"]);
	}

	function getvendamedia($format = FALSE){
		return ($format ? number_format($this->fields["vendamedia"], 4, ",", "") : $this->fields["vendamedia"]);
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function getcustosemimp($format = FALSE){
		return ($format ? number_format($this->fields["custosemimp"], 2, ",", "") : $this->fields["custosemimp"]);
	}

	function getcustomedrep($format = FALSE){
		return ($format ? number_format($this->fields["customedrep"], 2, ",", "") : $this->fields["customedrep"]);
	}

	function getcustomedsemimp($format = FALSE){
		return ($format ? number_format($this->fields["customedsemimp"], 2, ",", "") : $this->fields["customedsemimp"]);
	}

	function getprecopdv($format = FALSE){
		return ($format ? number_format($this->fields["precopdv"], 2, ",", "") : $this->fields["precopdv"]);
	}

	function getmargematc($format = FALSE){
		return ($format ? number_format($this->fields["margematc"], 4, ",", "") : $this->fields["margematc"]);
	}

	function getmargemvrj($format = FALSE){
		return ($format ? number_format($this->fields["margemvrj"], 4, ",", "") : $this->fields["margemvrj"]);
	}

	function getsldatual($format = FALSE){
		return ($format ? number_format($this->fields["sldatual"], 4, ",", "") : $this->fields["sldatual"]);
	}

	function geturgente(){
		return $this->fields["urgente"];
	}

	function getdisponivel(){
		return $this->fields["disponivel"];
	}

	function getestminimo($format = FALSE){
		return ($format ? number_format($this->fields["estminimo"], 4, ",", "") : $this->fields["estminimo"]);
	}

	function getestmaximo($format = FALSE){
		return ($format ? number_format($this->fields["estmaximo"], 4, ",", "") : $this->fields["estmaximo"]);
	}

	function getsincpdv(){
		return $this->fields["sincpdv"];
	}

	function getcurva(){
		return $this->fields["curva"];
	}

	function getprecoorigem(){
		return $this->fields["origempreco"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcustotab($value){
		$this->fields["custotab"] = value_numeric($value);
	}

	function setfrete($value){
		$this->fields["frete"] = value_numeric($value);
	}

	function setfretemedio($value){
		$this->fields["fretemedio"] = value_numeric($value);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdtinativo($value){
		$this->fields["dtinativo"] = value_date($value);
	}

	function setdiasvalidade($value){
		$this->fields["diasvalidade"] = value_numeric($value);
	}

	function setsldinicio($value){
		$this->fields["sldinicio"] = value_numeric($value);
	}

	function setsldentrada($value){
		$this->fields["sldentrada"] = value_numeric($value);
	}

	function setsldsaida($value){
		$this->fields["sldsaida"] = value_numeric($value);
	}

	function setprecoant($value){
		$this->fields["precoant"] = value_numeric($value);
	}

	function setqtdelimite($value){
		$this->fields["qtdelimite"] = value_numeric($value);
	}

	function setdtpreco($value){
		$this->fields["dtpreco"] = value_date($value);
	}

	function setdtcusto($value){
		$this->fields["dtcusto"] = value_date($value);
	}

	function setdtultvda($value){
		$this->fields["dtultvda"] = value_date($value);
	}

	function setqtdultvda($value){
		$this->fields["qtdultvda"] = value_numeric($value);
	}

	function setdtultcpa($value){
		$this->fields["dtultcpa"] = value_date($value);
	}

	function setqtdultcpa($value){
		$this->fields["qtdultcpa"] = value_numeric($value);
	}

	function setqtdcprpend($value){
		$this->fields["qtdcprpend"] = value_numeric($value);
	}

	function setqtdvenpend($value){
		$this->fields["qtdvenpend"] = value_numeric($value);
	}

	function setpreventrada($value){
		$this->fields["preventrada"] = value_numeric($value);
	}

	function setprevsaida($value){
		$this->fields["prevsaida"] = value_numeric($value);
	}

	function setcustorep($value){
		$this->fields["custorep"] = value_numeric($value);
	}

	function setqtdatacado($value){
		$this->fields["qtdatacado"] = value_numeric($value);
	}

	function setprecoatc($value){
		$this->fields["precoatc"] = value_numeric($value);
	}

	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setprecoatcof($value){
		$this->fields["precoatcof"] = value_numeric($value);
	}

	function setprecovrjof($value){
		$this->fields["precovrjof"] = value_numeric($value);
	}

	function setvendamedia($value){
		$this->fields["vendamedia"] = value_numeric($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_string($value);
	}

	function setcustosemimp($value){
		$this->fields["custosemimp"] = value_numeric($value);
	}

	function setcustomedrep($value){
		$this->fields["customedrep"] = value_numeric($value);
	}

	function setcustomedsemimp($value){
		$this->fields["customedsemimp"] = value_numeric($value);
	}

	function setprecopdv($value){
		$this->fields["precopdv"] = value_numeric($value);
	}

	function setmargematc($value){
		$this->fields["margematc"] = value_numeric($value);
	}

	function setmargemvrj($value){
		$this->fields["margemvrj"] = value_numeric($value);
	}

	function setsldatual($value){
		$this->fields["sldatual"] = value_numeric($value);
	}

	function seturgente($value){
		$this->fields["urgente"] = value_string($value, 1);
	}

	function setdisponivel($value){
		$this->fields["disponivel"] = value_string($value, 1);
	}

	function setestminimo($value){
		$this->fields["estminimo"] = value_numeric($value);
	}

	function setestmaximo($value){
		$this->fields["estmaximo"] = value_numeric($value);
	}

	function setsincpdv($value){
		$this->fields["sincpdv"] = value_numeric($value);
	}

	function setcurva($value){
		$this->fields["curva"] = value_string($value, 1);
	}

	function setorigempreco($value){
		$this->fields["origempreco"] = value_string($value, 40);
	}

}