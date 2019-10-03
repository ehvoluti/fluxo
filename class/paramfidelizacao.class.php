<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamFidelizacao extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramfidelizacao";
		$this->primarykey = "codestabelec";
		$this->newrelation("paramfidelizacao","codestabelec","estabelecimento","codestabelec");
		$this->setcodestabelec($codestabelec);
		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
    }

    function getcodestabelec(){
        return $this->fields["codestabelec"];
	}
	
	function gettipogeracaopontos(){
		return $this->fields["tipogeracaopontos"];
	}

	function getfatorconversao($format = FALSE){
		return ($format ? number_format($this->fields["fatorconversao"], 4, ",", "") : $this->fields["fatorconversao"]);
	}

	function getdiasvalidadepontos(){
		return $this->fields["diasvalidadepontos"];
	}

    function setcodestabelec($value){
        $this->fields["codestabelec"] = value_numeric($value);
	}
	
	function settipogeracaopontos($value){
		$this->fields["tipogeracaopontos"] = value_string( $value, 1);
	}

	function setfatorconversao($value){
		$this->fields["fatorconversao"] = value_numeric($value);
	}

	function setdiasvalidadepontos($value){
		$this->fields["diasvalidadepontos"] = value_numeric($value);
	}
}