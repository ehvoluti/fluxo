<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamComissao extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramcomissao";
		$this->primarykey = "codestabelec";
		$this->newrelation("paramcomissao","codestabelec","estabelecimento","codestabelec");
		$this->setcodestabelec($codestabelec);
		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function gettipocomissao(){
		return $this->fields["tipocomissao"];
	}

	function getcodcatlancto(){
		return $this->fields["codcatlancto"];
	}

	function getcodsubcatlancto(){
		return $this->fields["codsubcatlancto"];
	}

	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function settipocomissao($value){
		$this->fields["tipocomissao"] = value_string($value,1);
	}

	function setcodcatlancto($value){
		$this->fields["codcatlancto"] = value_numeric($value);
	}

	function setcodsubcatlancto($value){
		$this->fields["codsubcatlancto"] = value_numeric($value);
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}
}
?>