<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class OrcamentoPedido extends Cadastro{
	function __construct(){
		parent::__construct();
		$this->table = "orcamentopedido";
		$this->primarykey = array("codestabelec","numpedido","codorcamento");
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getcodorcamento(){
		return $this->fields["codorcamento"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setcodorcamento($value){
		$this->fields["codorcamento"] = value_numeric($value);
	}
}
?>