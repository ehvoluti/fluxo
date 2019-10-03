<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ContingenciaNFe extends Cadastro{
	function __construct($codcontingencia = NULL){
		parent::__construct();
		$this->table = "contingencianfe";
		$this->primarykey = array("codcontingencia");
		$this->setcodcontingencia($codcontingencia);
		if(!is_null($this->getcodcontingencia())){
			$this->searchbyobject();
		}
	}

	function getcodcontingencia(){
		return $this->fields["codcontingencia"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdataini($format = FALSE){
		return ($format ? convert_date($this->fields["dataini"],"Y-m-d","d/m/Y") : $this->fields["dataini"]);
	}

	function gethoraini(){
		return $this->fields["horaini"];
	}

	function getdatafim($format = FALSE){
		return ($format ? convert_date($this->fields["datafim"],"Y-m-d","d/m/Y") : $this->fields["datafim"]);
	}

	function gethorafim(){
		return $this->fields["horafim"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function setcodcontingencia($value){
		$this->fields["codcontingencia"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdataini($value){
		$this->fields["dataini"] = value_date($value);
	}

	function sethoraini($value){
		$this->fields["horaini"] = value_time($value);
	}

	function setdatafim($value){
		$this->fields["datafim"] = value_date($value);
	}

	function sethorafim($value){
		$this->fields["horafim"] = value_time($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value,1000);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}
}
?>