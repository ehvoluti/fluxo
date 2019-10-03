<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NumeroSerie extends Cadastro{
	function __construct($codproduto = NULL, $numeroserie = NULL, $data = NULL, $hora = NULL){
		parent::__construct();
		$this->table = "numeroserie";
		$this->primarykey = array("codproduto", "numeroserie", "data", "hora");
		$this->setcodproduto($codproduto);
		$this->setnumeroserie($numeroserie);
		$this->setdata($data);
		$this->sethora($hora);
		if(!is_null($this->getcodproduto()) && !is_null($this->getnumeroserie()) && !is_null($this->getdata()) && !is_null($this->gethora())){
			$this->searchbyobject();
		}
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getnumeroserie(){
		return $this->fields["numeroserie"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getcodmovimento(){
		return $this->fields["codmovimento"];
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function getcodorcamento(){
		return $this->fields["codorcamento"];
	}
	
	function getidcupom(){
		return $this->fields["idcupom"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setnumeroserie($value){
		$this->fields["numeroserie"] = value_string($value,50);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setcodmovimento($value){
		$this->fields["codmovimento"] = value_numeric($value);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_string($value);
	}

	function setcodorcamento($value){
		$this->fields["codorcamento"] = value_numeric($value);
	}
	
	function setidcupom($value){
		$this->fields["idcupom"] = value_numeric($value);
	}
}
?>