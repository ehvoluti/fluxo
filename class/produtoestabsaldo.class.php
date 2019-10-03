<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdutoEstabSaldo extends Cadastro{
	function __construct($data = NULL,$codestabelec = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "produtoestabsaldo";
		$this->primarykey = array("data","codestabelec","codproduto");
		$this->setdata($data);
		$this->setcodestabelec($codestabelec);
		$this->setcodproduto($codproduto);
		if($this->getdata() != NULL && $this->getcodestabelec() != NULL && $this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getsaldo($format = FALSE){
		return ($format ? number_format($this->fields["saldo"],2,",","") : $this->fields["saldo"]);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setsaldo($value){
		$this->fields["saldo"] = value_numeric($value);
	}
}
?>