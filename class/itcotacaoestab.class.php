<?php
require_file("class/cadastro.class.php");

class ItCotacaoEstab extends Cadastro{
	function __construct($codcotacao = NULL, $codproduto = NULL, $codestabelec = NULL){
		parent::__construct();
		$this->table = "itcotacaoestab";
		$this->primarykey = array("codcotacao", "codproduto", "codestabelec");
		$this->setcodcotacao($codcotacao);
		$this->setcodproduto($codproduto);
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getcodcotacao()) && !is_null($this->getcodproduto()) && !is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}
}
?>