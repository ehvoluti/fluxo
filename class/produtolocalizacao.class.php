<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdutoLocalizacao extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "produtolocalizacao";
		$this->primarykey = "idprodutolocalizacao";
		$this->setidprodutolocalizacao($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getidprodutolocalizacao(){
		return $this->fields["idprodutolocalizacao"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}
	
	function getcodproduto(){
		return $this->fields["codproduto"];
	}
	
	function getlocalizacao(){
		return $this->fields["localizacao"];
	}
	
	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}
				
	function setidprodutolocalizacao($value){
		$this->fields["idprodutolocalizacao"] = value_numeric($value);
	}
	
	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}
	
	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setlocalizacao($value){
		$this->fields["localizacao"] = value_string($value,15);
	}
	
	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>