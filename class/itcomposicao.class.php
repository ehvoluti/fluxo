<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItComposicao extends Cadastro{
	function __construct($codcomposicao = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "itcomposicao";
		$this->primarykey = array("codcomposicao","codproduto");
		$this->setcodcomposicao($codcomposicao);
		$this->setcodproduto($codproduto);
		if($this->getcodcomposicao() != NULL && $this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcomposicao(){
		return $this->fields["codcomposicao"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getpartcusto($format = FALSE){
		return ($format ? number_format($this->fields["partcusto"],4,",","") : $this->fields["partcusto"]);
	}

	function setcodcomposicao($value){
		$this->fields["codcomposicao"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setpartcusto($value){
		$this->fields["partcusto"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}
}
?>