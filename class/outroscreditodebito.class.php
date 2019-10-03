<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class OutrosCreditoDebito extends Cadastro{
	function __construct($codoutroscreditodebito = NULL){
		parent::__construct();
		$this->table = "outroscreditodebito";
		$this->primarykey = array("codoutroscreditodebito");
		$this->setcodoutroscreditodebito($codoutroscreditodebito);
		if(!is_null($this->getcodoutroscreditodebito())){
			$this->searchbyobject();
		}
	}

	function getcodoutroscreditodebito(){
		return $this->fields["codoutroscreditodebito"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtdocumento($format = FALSE){
		return ($format ? convert_date($this->fields["dtdocumento"],"Y-m-d","d/m/Y") : $this->fields["dtdocumento"]);
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getcodajuste(){
		return $this->fields["codajuste"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getvalor($format = FALSE){
		return ($format ? number_format($this->fields["valor"],4,",","") : $this->fields["valor"]);
	}

	function getdescricaoajuste(){
		return $this->fields["descricaoajuste"];
	}

	function getcodgia(){
		return $this->fields["codgia"];
	}

	function getfundamentolegal(){
		return $this->fields["fundamentolegal"];
	}

	function setcodoutroscreditodebito($value){
		$this->fields["codoutroscreditodebito"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtdocumento($value){
		$this->fields["dtdocumento"] = value_date($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,2);
	}

	function setcodajuste($value){
		$this->fields["codajuste"] = value_string($value,8);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,1);
	}

	function setvalor($value){
		$this->fields["valor"] = value_numeric($value);
	}

	function setdescricaoajuste($value){
		$this->fields["descricaoajuste"] = value_string($value);
	}

	function setcodgia($value){
		$this->fields["codgia"] = value_string($value,6);
	}

	function setfundamentolegal($value){
		$this->fields["fundamentolegal"] = value_string($value);
	}
}
?>