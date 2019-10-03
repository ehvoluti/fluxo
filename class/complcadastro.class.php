<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ComplCadastro extends Cadastro{
	function __construct($codcomplcadastro = NULL){
		parent::__construct();
		$this->table = "complcadastro";
		$this->primarykey = array("codcomplcadastro");
		$this->setcodcomplcadastro($codcomplcadastro);
		if(!is_null($this->getcodcomplcadastro())){
			$this->searchbyobject();
		}
	}

	function getcodcomplcadastro(){
		return $this->fields["codcomplcadastro"];
	}

	function gettabela(){
		return $this->fields["tabela"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getobrigatorio(){
		return $this->fields["obrigatorio"];
	}
    
    function getordem(){
        return $this->fields["ordem"];
    }

	function setcodcomplcadastro($value){
		$this->fields["codcomplcadastro"] = value_numeric($value);
	}

	function settabela($value){
		$this->fields["tabela"] = value_string($value,40);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setobrigatorio($value){
		$this->fields["obrigatorio"] = value_string($value,1);
	}
    
    function setordem($value){
        $this->fields["ordem"] = value_numeric($value);
    }
}
?>