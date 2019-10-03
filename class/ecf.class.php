<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ecf extends Cadastro{
	function __construct($codecf = NULL){
		parent::__construct();
		$this->table = "ecf";
		$this->primarykey = array("codecf");
		$this->setcodecf($codecf);
		if(!is_null($this->getcodecf())){
			$this->searchbyobject();
		}
	}

	function getcodecf(){
		return $this->fields["codecf"];
	}

	function getnumfabricacao(){
		return $this->fields["numfabricacao"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getmodelo(){
		return $this->fields["modelo"];
	}

	function getcaixa(){
		return $this->fields["caixa"];
	}

	function getnumeroecf(){
		return $this->fields["numeroecf"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getmarca(){
		return $this->fields["marca"];
	}

	function getequipamentofiscal(){
		return $this->fields["equipamentofiscal"];
	}

	function getgeramapa(){
		return $this->fields["geramapa"];
	}

	function setcodecf($value){
		$this->fields["codecf"] = value_numeric($value);
	}

	function setnumfabricacao($value){
		$this->fields["numfabricacao"] = value_string($value,50);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setmodelo($value){
		$this->fields["modelo"] = value_string($value,40);
	}

	function setcaixa($value){
		$this->fields["caixa"] = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->fields["numeroecf"] = value_numeric($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setmarca($value){
		$this->fields["marca"] = value_string($value,40);
	}

	function setequipamentofiscal($value){
		$this->fields["equipamentofiscal"] = value_string($value, 3);
	}

	function setgeramapa($value){
		$this->fields["geramapa"] = value_string($value, 1);
	}
}