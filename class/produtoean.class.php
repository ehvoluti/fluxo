<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdutoEan extends Cadastro{
	function __construct($codean = NULL){
		parent::__construct();
		$this->table = "produtoean";
		$this->primarykey = array("codean");
		$this->setcodean($codean);
		if($this->getcodean() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodean(){
		return $this->fields["codean"];
	}

	function getsequencia($format = FALSE){
		return ($format ? number_format($this->fields["sequencia"],2,",","") : $this->fields["sequencia"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],2,",","") : $this->fields["quantidade"]);
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}
    
    function gethoralog(){
		return $this->fields["horalog"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodean($value){
		$this->fields["codean"] = value_string($value,20);
	}

	function setsequencia($value){
		$this->fields["sequencia"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
    
    function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}
}
?>