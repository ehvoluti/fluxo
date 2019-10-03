<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdutoCompl extends Cadastro{
	function __construct($codproduto = NULL, $codcomplcadastro = NULL){
		parent::__construct();
		$this->table = "produtocompl";
		$this->primarykey = array("codproduto", "codcomplcadastro");
		$this->setcodproduto($codproduto);
		$this->setcodcomplcadastro($codcomplcadastro);
		if(!is_null($this->getcodproduto()) && !is_null($this->getcodcomplcadastro())){
			$this->searchbyobject();
		}
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodcomplcadastro(){
		return $this->fields["codcomplcadastro"];
	}

	function getvalor(){
		return $this->fields["valor"];
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodcomplcadastro($value){
		$this->fields["codcomplcadastro"] = value_numeric($value);
	}

	function setvalor($value){
		$this->fields["valor"] = value_string($value);
	}
}
?>