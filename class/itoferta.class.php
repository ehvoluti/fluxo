<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItOferta extends Cadastro{
	function __construct($codoferta = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "itoferta";
		$this->primarykey = array("codoferta", "codproduto");
		$this->setcodoferta($codoferta);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodoferta()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}
	
	function getcodoferta(){
		return $this->fields["codoferta"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function setcodoferta($value){
		$this->fields["codoferta"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}
}
?>