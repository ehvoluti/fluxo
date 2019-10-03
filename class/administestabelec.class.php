<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class AdministEstabelec extends Cadastro{
	function __construct($codestabelec = NULL,$codadminist = NULL){
		parent::__construct();
		$this->table = "administestabelec";
		$this->primarykey = array("codestabelec","codadminist");
		$this->setcodestabelec($codestabelec);
		$this->setcodadminist($codadminist);
		if($this->getcodestabelec() != NULL && $this->getcodadminist() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodadminist(){
		return $this->fields["codadminist"];
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"],2,",","") : $this->fields["percdescto"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"],2,",","") : $this->fields["valdescto"]);
	}

	function gettaxaenvio($format = FALSE){
		return ($format ? number_format($this->fields["taxaenvio"],2,",","") : $this->fields["taxaenvio"]);
	}

	function getdiaenvio(){
		return $this->fields["diaenvio"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodadminist($value){
		$this->fields["codadminist"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function settaxaenvio($value){
		$this->fields["taxaenvio"] = value_numeric($value);
	}

	function setdiaenvio($value){
		$this->fields["diaenvio"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}
}
?>