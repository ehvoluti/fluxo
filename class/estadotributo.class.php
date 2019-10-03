<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class EstadoTributo extends Cadastro{
	function __construct($uf = NULL, $regimetributario = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "estadotributo";
		$this->primarykey = array("uf", "regimetributario", "codproduto");
		$this->setuf($uf);
		$this->setregimetributario($regimetributario);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getuf()) && !is_null($this->getregimetributario()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getregimetributario(){
		return $this->fields["regimetributario"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodcfnfe(){
		return $this->fields["codcfnfe"];
	}

	function getcodcfnfs(){
		return $this->fields["codcfnfs"];
	}

	function getcodcfpdv(){
		return $this->fields["codcfpdv"];
	}

	function getcodpiscofinsent(){
		return $this->fields["codpiscofinsent"];
	}

	function getcodpiscofinssai(){
		return $this->fields["codpiscofinssai"];
	}

	function getcodipi(){
		return $this->fields["codipi"];
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->fields["aliqiva"],4,",","") : $this->fields["aliqiva"]);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}

	function setregimetributario($value){
		$this->fields["regimetributario"] = value_string($value,1);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodcfnfe($value){
		$this->fields["codcfnfe"] = value_numeric($value);
	}

	function setcodcfnfs($value){
		$this->fields["codcfnfs"] = value_numeric($value);
	}

	function setcodcfpdv($value){
		$this->fields["codcfpdv"] = value_numeric($value);
	}

	function setcodpiscofinsent($value){
		$this->fields["codpiscofinsent"] = value_numeric($value);
	}

	function setcodpiscofinssai($value){
		$this->fields["codpiscofinssai"] = value_numeric($value);
	}

	function setcodipi($value){
		$this->fields["codipi"] = value_numeric($value);
	}

	function setaliqiva($value){
		$this->fields["aliqiva"] = value_numeric($value);
	}
}
?>