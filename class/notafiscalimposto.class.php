<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NotaFiscalImposto extends Cadastro{
	function __construct($idnotafiscal = NULL,$tipoimposto = NULL,$aliquota = NULL){
		parent::__construct();
		$this->table = "notafiscalimposto";
		$this->primarykey = array("idnotafiscal","tipoimposto","aliquota");
		$this->setidnotafiscal($idnotafiscal);
		$this->settipoimposto($tipoimposto);
		$this->setaliquota($aliquota);
		if($this->getidnotafiscal() != NULL && $this->gettipoimposto() != NULL && $this->getaliquota() != NULL){
			$this->searchbyobject();
		}
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function gettipoimposto(){
		return $this->fields["tipoimposto"];
	}

	function getaliquota($format = FALSE){
		return ($format ? number_format($this->fields["aliquota"],2,",","") : $this->fields["aliquota"]);
	}

	function getbase($format = FALSE){
		return ($format ? number_format($this->fields["base"],2,",","") : $this->fields["base"]);
	}

	function getvalorimposto($format = FALSE){
		return ($format ? number_format($this->fields["valorimposto"],2,",","") : $this->fields["valorimposto"]);
	}

	function getreducao($format = FALSE){
		return ($format ? number_format($this->fields["reducao"],2,",","") : $this->fields["reducao"]);
	}

	function getisento($format = FALSE){
		return ($format ? number_format($this->fields["isento"],2,",","") : $this->fields["isento"]);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function settipoimposto($value){
		$this->fields["tipoimposto"] = value_string($value,10);
	}

	function setaliquota($value){
		$this->fields["aliquota"] = value_numeric($value);
	}

	function setbase($value){
		$this->fields["base"] = value_numeric($value);
	}

	function setvalorimposto($value){
		$this->fields["valorimposto"] = value_numeric($value);
	}

	function setreducao($value){
		$this->fields["reducao"] = value_numeric($value);
	}

	function setisento($value){
		$this->fields["isento"] = value_numeric($value);
	}
}
?>