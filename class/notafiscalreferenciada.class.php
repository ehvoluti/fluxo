<?php
require_file("class/cadastro.class.php");

class NotaFiscalReferenciada extends Cadastro{
	function __construct($idnotafiscalreferenciada = NULL){
		parent::__construct();
		$this->table = "notafiscalreferenciada";
		$this->primarykey = array("idnotafiscalreferenciada");
		$this->setidnotafiscalreferenciada($idnotafiscalreferenciada);
		if(!is_null($this->getidnotafiscalreferenciada())){
			$this->searchbyobject();
		}
	}

	function getidnotafiscalreferenciada(){
		return $this->fields["idnotafiscalreferenciada"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getidnotafiscalref(){
		return $this->fields["idnotafiscalref"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function setidnotafiscalreferenciada($value){
		$this->fields["idnotafiscalreferenciada"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setidnotafiscalref($value){
		$this->fields["idnotafiscalref"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}
}
?>