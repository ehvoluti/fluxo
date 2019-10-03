<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class RecebePdvLancto extends Cadastro{
	function __construct($codrecebepdv = NULL, $codlancto = NULL){
		parent::__construct();
		$this->table = "recebepdvlancto";
		$this->primarykey = array("codrecebepdv", "codlancto");
		$this->setcodrecebepdv($codrecebepdv);
		$this->setcodlancto($codlancto);
		if(!is_null($this->getcodrecebepdv()) && !is_null($this->getcodlancto())){
			$this->searchbyobject();
		}
	}

	function getcodrecebepdv(){
		return $this->fields["codrecebepdv"];
	}

	function getcodlancto(){
		return $this->fields["codlancto"];
	}

	function gettotalliquido(){
		return $this->fields["totalliquido"];
	}

	function setcodrecebepdv($value){
		$this->fields["codrecebepdv"] = value_numeric($value);
	}

	function setcodlancto($value){
		$this->fields["codlancto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}


}