<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FidelidadePontos extends Cadastro{

	function __construct($codfidelidadepontos = NULL){
		parent::__construct();
		$this->table = "fidelidadepontos";
		$this->primarykey = array("codfidelidadepontos");
		$this->setcodfidelidadepontos($codfidelidadepontos);
		$this->setorder("dataexpiracao");
		if($this->getcodfidelidadepontos() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodfidelidadepontos(){
		return $this->fields["codfidelidadepontos"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getdatamovimento($format = FALSE){
		return ($format ? convert_date($this->fields["datamovimento"], "Y-m-d", "d/m/Y") : $this->fields["datamovimento"]);
	}

	function getvalorcompra($format = FALSE){
		return ($format ? number_format($this->fields["valorcompra"], 2, ",", "") : $this->fields["valorcompra"]);
	}

	function getfatorconversao($format = FALSE){
		return ($format ? number_format($this->fields["fatorconversao"], 4, ",", "") : $this->fields["fatorconversao"]);
	}

	function getpontosgerados(){
		return $this->fields["pontosgerados"];
	}

	function getdataexpiracao($format = FALSE){
		return ($format ? convert_date($this->fields["dataexpiracao"], "Y-m-d", "d/m/Y") : $this->fields["dataexpiracao"]);
	}

	function getdataresgate($format = FALSE){
		return ($format ? convert_date($this->fields["dataresgate"], "Y-m-d", "d/m/Y") : $this->fields["dataresgate"]);
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodfidelidaderesgate(){
		return $this->fields["codfidelidaderesgate"];
	}

	function setcodfidelidadepontos($value){
		$this->fields["codfidelidadepontos"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setdatamovimento($value){
		$this->fields["datamovimento"] = value_date($value);
	}

	function setvalorcompra($value){
		$this->fields["valorcompra"] = value_numeric($value);
	}

	function setfatorconversao($value){
		$this->fields["fatorconversao"] = value_numeric($value);
	}

	function setpontosgerados($value){
		$this->fields["pontosgerados"] = value_numeric($value);
	}

	function setdataexpiracao($value){
		$this->fields["dataexpiracao"] = value_date($value);
	}

	function setdataresgate($value){
		$this->fields["dataresgate"] = value_date($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodfidelidaderesgate($value){
		$this->fields["codfidelidaderesgate"] = value_numeric($value);
	}
}
