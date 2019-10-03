<?php
/*
110110 - Carta de Correção
110111 - Cancelamento
110112 - Encerramento Homologado
110113 - EPEC CT-e
110114 - Inclusão de Condutor
110140 - EPEC NF-e
110160 - Registro Multimodal
210200 - Confirmação da Operação
210210 - Ciência da Operação
210220 - Desconhecimento da Operação
210240 - Operação não Realizada
310620 - Registro de Passagem
510620 - Registro de Passagem BRID
610600 - CT-e Autorizado para NF-e
610501 - Registro de Passagem para NF-e Cancelado
610550 - Registro de Passagem para NF-e RFID
610601 - CT-e Cancelado
610611 - MDF-e Cancelado
990900 - Vistoria Suframa
 *
 */

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_once("../class/notafiscaleletronica.class.php");

class NotafiscalEletronicaEstabelecimento extends Cadastro{
	const SCHEMA_XML_NFE_PROCNFE = "procNFe";
	const SCHEMA_XML_NFE_RESNFE = "resNFe";
	const SCHEMA_XML_NFE_RESEVENTO = "resEvento";
	const SCHEMA_XML_NFE_PROCEVENTONFE = "procEventoNFe";

	function __construct($idnotafiscal = NULL) {
		parent::__construct($idnotafiscal = NULL);
		$this->table = "notafiscaleletronicaestabelecimento";
		$this->primarykey = array("idnfeestabelec");
		$this->setidnfeestabelec($idnotafiscal);
		if($this->getidnfeestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getidnfeestabelec(){
		return $this->fields["idnfeestabelec"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumnotafiscal(){
		return $this->fields["numnotafiscal"];
	}

	function getcnpj(){
		return $this->fields["cnpj"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getdataemissao($format = FALSE){
		if($format){
			$arr_date = explode(" ",$this->fields["dataemissao"]);
		}
		return ($format ? convert_date($arr_date[0], "Y-m-d", "d/m/Y")." ".$arr_date[1] : $this->fields["dataemissao"]);
	}

	function gettotal($format = FALSE){
		return ($format ? number_format($this->fields["total"], 2, ",", "") : $this->fields["total"]);
	}

	function getevento(){
		return $this->fields["evento"];
	}

	function getchavenfe(){
		return $this->fields["chavenfe"];
	}

	function getfinalidade(){
		return $this->fields["finalidade"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getxmlnfe(){
		return $this->fields["xmlnfe"];
	}

	function setidnfeestabelec($value){
		$this->fields["idnfeestabelec"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumnotafiscal($value){
		$this->fields["numnotafiscal"] = value_numeric($value);
	}

	function setcnpj($value){
		$this->fields["cnpj"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 100);
	}

	function setdataemissao($value){
		$this->fields["dataemissao"] = value_datetime($value);
	}

	function settotal($value){
		$this->fields["total"] = value_numeric($value);
	}

	function setevento($value){
		$this->fields["evento"] = value_numeric($value);
	}

	function setchavenfe($value){
		$this->fields["chavenfe"] = value_string($value, 44);
	}

	function setxmlnfe($value){
		$this->fields["xmlnfe"] = value_string($value);
	}

	function setfinalidade($value){
		$this->fields["finalidade"] = value_string($value, 1);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 3);
	}
}
