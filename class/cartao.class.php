<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Cartao extends Cadastro{
	function __construct($codcartao = NULL){
		parent::__construct();
		$this->table = "cartao";
		$this->primarykey = "codcartao";
		$this->setcodcartao($codcartao);
		if($this->getcodcartao() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcartao(){
		return $this->fields["codcartao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getpontos($format = FALSE){
		return ($format ? number_format($this->fields["pontos"],2,",","") : $this->fields["pontos"]);
	}

	function getadmpropria(){
		return $this->fields["admpropria"];
	}

	function gettpcartao(){
		return $this->fields["tpcartao"];
	}

	function getfinalizadora(){
		return $this->fields["finalizadora"];
	}

	function setcodcartao($value){
		$this->fields["codcartao"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setpontos($value){
		$this->fields["pontos"] = value_numeric($value);
	}

	function setadmpropria($value){
		$this->fields["admpropria"] = value_string($value,1);
	}

	function settpcartao($value){
		$this->fields["tpcartao"] = value_string($value,1);
	}

	function setfinalizadora($value){
		$this->fields["finalizadora"] = value_string($value,3);
	}
}
?>