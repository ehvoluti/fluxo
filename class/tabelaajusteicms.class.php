<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TabelaAjusteIcms extends Cadastro{
	function __construct($codajuste = NULL){
		parent::__construct();
		$this->table = "tabelaajusteicms";
		$this->primarykey = array("codajuste");
		$this->setcodajuste($codajuste);
		if(!is_null($this->getcodajuste())){
			$this->searchbyobject();
		}
	}

	function getcodajuste(){
		return $this->fields["codajuste"];
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

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getdataini($format = FALSE){
		return ($format ? convert_date($this->fields["dataini"],"Y-m-d","d/m/Y") : $this->fields["dataini"]);
	}

	function getdatafim($format = FALSE){
		return ($format ? convert_date($this->fields["datafim"],"Y-m-d","d/m/Y") : $this->fields["datafim"]);
	}

	function setcodajuste($value){
		$this->fields["codajuste"] = value_string($value,8);
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

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,1);
	}

	function setdataini($value){
		$this->fields["dataini"] = value_date($value);
	}

	function setdatafim($value){
		$this->fields["datafim"] = value_date($value);
	}
}
?>