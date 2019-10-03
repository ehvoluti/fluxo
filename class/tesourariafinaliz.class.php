<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TesourariaFinaliz extends Cadastro{
	function __construct($codtesouraria = NULL, $codfinaliz = NULL){
		parent::__construct();
		$this->table = "tesourariafinaliz";
		$this->primarykey = array("codtesouraria", "codfinaliz");
		$this->setcodtesouraria($codtesouraria);
		$this->setcodfinaliz($codfinaliz);
		if(!is_null($this->getcodtesouraria()) && !is_null($this->getcodfinaliz())){
			$this->searchbyobject();
		}
	}

	function getcodtesouraria(){
		return $this->fields["codtesouraria"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodfinaliz(){
		return $this->fields["codfinaliz"];
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"],2,",","") : $this->fields["valortotal"]);
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function getfundocaixa($format = FALSE){
		return ($format ? number_format($this->fields["fundocaixa"],2,",","") : $this->fields["fundocaixa"]);
	}

	function getvalortotalpdv($format = FALSE){
		return ($format ? number_format($this->fields["valortotalpdv"], 2, ",", "") : $this->fields["valortotalpdv"]);
	}

	function setcodtesouraria($value){
		$this->fields["codtesouraria"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodfinaliz($value){
		$this->fields["codfinaliz"] = value_string($value,5);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function setfundocaixa($value){
		$this->fields["fundocaixa"] = value_numeric($value);
	}

	function setvalortotalpdv($value){
		$this->fields["valortotalpdv"] = value_numeric($value);
	}
}
?>