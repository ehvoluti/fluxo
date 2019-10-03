<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class IcmsPdv extends Cadastro{
	function __construct($codestabelec = NULL, $tipoicms = NULL, $aliqicms = NULL, $redicms = NULL){
		parent::__construct();
		$this->table = "icmspdv";
		$this->primarykey = array("codestabelec", "tipoicms", "aliqicms", "redicms");
		$this->setcodestabelec($codestabelec);
		$this->settipoicms($tipoicms);
		$this->setaliqicms($aliqicms);
		$this->setredicms($redicms);
		if(!is_null($this->getcodestabelec()) && !is_null($this->gettipoicms()) && !is_null($this->getaliqicms()) && !is_null($this->getredicms())){
			$this->searchbyobject();
		}
	}

	function gettipoicms(){
		return $this->fields["tipoicms"];
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],2,",","") : $this->fields["aliqicms"]);
	}

	function getinfpdv(){
		return $this->fields["infpdv"];
	}

	function getredicms($format = FALSE){
		return ($format ? number_format($this->fields["redicms"],2,",","") : $this->fields["redicms"]);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function settipoicms($value){
		$this->fields["tipoicms"] = value_string($value,1);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setinfpdv($value){
		$this->fields["infpdv"] = value_string($value,3);
	}

	function setredicms($value){
		$this->fields["redicms"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}
}
?>