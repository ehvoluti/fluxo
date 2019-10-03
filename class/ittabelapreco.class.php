<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItTabelaPreco extends Cadastro{
	function __construct($codtabela = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "ittabelapreco";
		$this->primarykey = array("codtabela", "codproduto");
		$this->setcodtabela($codtabela);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodtabela()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getpercpreco($format = FALSE){
		return ($format ? number_format($this->fields["percpreco"],2,",","") : $this->fields["percpreco"]);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setpercpreco($value){
		$this->fields["percpreco"] = value_numeric($value);
	}
}
?>