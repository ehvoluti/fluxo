<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ofx extends Cadastro{
	function __construct($codbanco = NULL, $idofx = NULL, $pagrec = NULL){
		parent::__construct();
		$this->table = "ofx";
		$this->primarykey = array("codbanco", "idofx", "pagrec");
		$this->setcodbanco($codbanco);
		$this->setidofx($idofx);
		$this->setpagrec($pagrec);
		if(!is_null($this->getcodbanco()) && !is_null($this->getidofx()) && !is_null($this->getpagrec())){
			$this->searchbyobject();
		}
	}

	function getidofx(){
		return $this->fields["idofx"];
	}

	function getdtreconc($format = FALSE){
		return ($format ? convert_date($this->fields["dtreconc"],"Y-m-d","d/m/Y") : $this->fields["dtreconc"]);
	}

	function getvalortotal($format = FALSE){
		return ($format ? number_format($this->fields["valortotal"],2,",","") : $this->fields["valortotal"]);
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getpagrec(){
		return $this->fields["pagrec"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function setidofx($value){
		$this->fields["idofx"] = value_string($value);
	}

	function setdtreconc($value){
		$this->fields["dtreconc"] = value_date($value);
	}

	function setvalortotal($value){
		$this->fields["valortotal"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value);
	}

	function setpagrec($value){
		$this->fields["pagrec"] = value_string($value,1);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}
}