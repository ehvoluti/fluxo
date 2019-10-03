<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LogDelProduto extends Cadastro{
	function __construct($codproduto = NULL, $data = NULL, $hora = NULL){
		parent::__construct();
		$this->table = "logdelproduto";
		$this->primarykey = array("codproduto", "data", "hora");
		$this->setcodproduto($codproduto);
		$this->setdata($data);
		$this->sethora($hora);
		if(!is_null($this->getcodproduto()) && !is_null($this->getdata()) && !is_null($this->gethora())){
			$this->searchbyobject();
		}
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}
	
	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_string($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}
	
	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,20);
	}
}
?>