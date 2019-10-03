<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GuiaGnre extends Cadastro{

	function __construct($idnotafiscal = NULL){
		parent::__construct();
		$this->table = "guiagnre";
		$this->primarykey = array("idnotafiscal");
		$this->setidnotafiscal($idnotafiscal);
		if(!is_null($this->getidnotafiscal())){
			$this->searchbyobject();
		}
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getvalorguia($format = FALSE){
		return ($format ? number_format($this->fields["valorguia"], 2, ",", "") : $this->fields["valorguia"]);
	}

	function getdtvencto($format = FALSE){
		return ($format ? convert_date($this->fields["dtvencto"], "Y-m-d", "d/m/Y") : $this->fields["dtvencto"]);
	}

	function getdtpagto($format = FALSE){
		return ($format ? convert_date($this->fields["dtpagto"], "Y-m-d", "d/m/Y") : $this->fields["dtpagto"]);
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

	function setvalorguia($value){
		$this->fields["valorguia"] = value_numeric($value);
	}

	function setdtvencto($value){
		$this->fields["dtvencto"] = value_date($value);
	}

	function setdtpagto($value){
		$this->fields["dtpagto"] = value_date($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

}