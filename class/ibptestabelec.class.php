<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class IbptEstabelec extends Cadastro{
	function __construct($codestabelec = NULL, $codigoncm = NULL){
		parent::__construct();
		$this->table = "ibptestabelec";
		$this->primarykey = array("codestabelec", "codigoncm");
		$this->setcodestabelec($codestabelec);
		$this->setcodigoncm($codigoncm);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getcodigoncm())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodigoncm(){
		return $this->fields["codigoncm"];
	}

	function getaliqnacionalfederal($format = FALSE){
		return ($format ? number_format($this->fields["aliqnacionalfederal"],2,",","") : $this->fields["aliqnacionalfederal"]);
	}

	function getaliqimportadofederal($format = FALSE){
		return ($format ? number_format($this->fields["aliqimportadofederal"],2,",","") : $this->fields["aliqimportadofederal"]);
	}

	function getaliqestadual($format = FALSE){
		return ($format ? number_format($this->fields["aliqestadual"],2,",","") : $this->fields["aliqestadual"]);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodigoncm($value){
		$this->fields["codigoncm"] = value_string($value,10);
	}

	function setaliqnacionalfederal($value){
		$this->fields["aliqnacionalfederal"] = value_numeric($value);
	}

	function setaliqimportadofederal($value){
		$this->fields["aliqimportadofederal"] = value_numeric($value);
	}

	function setaliqestadual($value){
		$this->fields["aliqestadual"] = value_numeric($value);
	}
}
?>