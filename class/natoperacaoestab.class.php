<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NatOperacaoEstab extends Cadastro{
	function __construct($natoperacao = NULL,$codestabelec = NULL){
		parent::__construct();
		$this->table = "natoperacaoestab";
		$this->primarykey = array("natoperacao","codestabelec");
		$this->setnatoperacao($natoperacao);
		$this->setcodestabelec($codestabelec);
		if($this->getnatoperacao() != NULL && $this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodconta(){
		return $this->fields["codconta"];
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodconta($value){
		$this->fields["codconta"] = value_numeric($value);
	}
}
?>