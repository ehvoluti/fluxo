<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class SituacaoLancto extends Cadastro{
	function __construct($codsituacao = NULL){
		parent::__construct();
		$this->table = "situacaolancto";
		$this->primarykey = array("codsituacao");
		$this->setcodsituacao($codsituacao);
		if(!is_null($this->getcodsituacao())){
			$this->searchbyobject();
		}
	}

	function getcodsituacao(){
		return $this->fields["codsituacao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getcodstatuscliente(){
		return $this->fields["codstatuscliente"];
	}

	function setcodsituacao($value){
		$this->fields["codsituacao"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}
	
	function setcodstatuscliente($value){
		$this->fields["codstatuscliente"] = value_numeric($value);
	}
}
?>