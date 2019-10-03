<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItInventario extends Cadastro{
	function __construct($codinventario = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "itinventario";
		$this->primarykey = array("codinventario","codproduto");
		$this->setorder("itinventario.ordem");
		$this->setcodinventario($codinventario);
		$this->setcodproduto($codproduto);
		if($this->getcodinventario() != NULL && $this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodinventario(){
		return $this->fields["codinventario"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getsldatual($format = FALSE){
		return ($format ? number_format($this->fields["sldatual"],2,",","") : $this->fields["sldatual"]);
	}

	function getsldinventario($format = FALSE){
		return ($format ? number_format($this->fields["sldinventario"],2,",","") : $this->fields["sldinventario"]);
	}
	
	function getsldinventario2($format = FALSE){
		return ($format ? number_format($this->fields["sldinventario2"],2,",","") : $this->fields["sldinventario2"]);
	}
	
	function getcusto($format = FALSE){
		return ($format ? number_format($this->fields["custo"],2,",","") : $this->fields["custo"]);
	}
	
	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}
	
	function getordem(){
		return $this->fields["ordem"];
	}
	
	function getinventariado(){
		return $this->fields["inventariado"];
	}

	function setcodinventario($value){
		$this->fields["codinventario"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setsldatual($value){
		$this->fields["sldatual"] = value_numeric($value);
	}

	function setsldinventario($value){
		$this->fields["sldinventario"] = value_numeric($value);
	}
	
	function setsldinventario2($value){
		$this->fields["sldinventario2"] = value_numeric($value);
	}
	
	function setcusto($value){
		$this->fields["custo"] = value_numeric($value);
	}
	
	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}
	
	function setordem($value){
		$this->fields["ordem"] = value_numeric($value);
	}
	
	function setinventariado($value){
		$this->fields["inventariado"] = value_string($value,1);
	}
}
?>