<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FornecedorCompl extends Cadastro{
	function __construct($codfornec = NULL, $codcomplcadastro = NULL){
		parent::__construct();
		$this->table = "fornecedorcompl";
		$this->primarykey = array("codfornec", "codcomplcadastro");
		$this->setcodfornec($codfornec);
		$this->setcodcomplcadastro($codcomplcadastro);
		if(!is_null($this->getcodfornec()) && !is_null($this->getcodcomplcadastro())){
			$this->searchbyobject();
		}
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodcomplcadastro(){
		return $this->fields["codcomplcadastro"];
	}

	function getvalor(){
		return $this->fields["valor"];
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodcomplcadastro($value){
		$this->fields["codcomplcadastro"] = value_numeric($value);
	}

	function setvalor($value){
		$this->fields["valor"] = value_string($value);
	}
}
?>