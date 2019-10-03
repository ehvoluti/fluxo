<?php

require_file("class/cadastro.class.php");

class FrenteCaixa extends Cadastro{

	function __construct($codfrentecaixa = NULL){
		parent::__construct();
		$this->table = "frentecaixa";
		$this->primarykey = array("codfrentecaixa");
		$this->setcodfrentecaixa($codfrentecaixa);
		if(!is_null($this->getcodfrentecaixa())){
			$this->searchbyobject();
		}
	}

	function getcodfrentecaixa(){
		return $this->fields["codfrentecaixa"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getbalancaean(){
		return $this->fields["balancaean"];
	}

	function gettipodescricao(){
		return $this->fields["tipodescricao"];
	}

	function getversao(){
		return $this->fields["versao"];
	}

	function gettipocodproduto(){
		return $this->fields["tipocodproduto"];
	}

	function getparametro(){
		return $this->fields["parametro"];
	}

	function setcodfrentecaixa($value){
		$this->fields["codfrentecaixa"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 40);
	}

	function setbalancaean($value){
		$this->fields["balancaean"] = value_string($value, 1);
	}

	function settipodescricao($value){
		$this->fields["tipodescricao"] = value_string($value, 1);
	}

	function setversao($value){
		$this->fields["versao"] = value_string($value, 10);
	}

	function settipocodproduto($value){
		$this->fields["tipocodproduto"] = value_string($value, 1);
	}

	function setparametro($value){
		$this->fields["parametro"] = value_string($value);
	}
}