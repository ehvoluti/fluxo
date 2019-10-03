<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Balanca extends Cadastro{
	function __construct($codbalanca = NULL){
		parent::__construct();
		$this->table = "balanca";
		$this->primarykey = "codbalanca";
		$this->setcodbalanca($codbalanca);
		if($this->getcodbalanca() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodbalanca(){
		return $this->fields["codbalanca"];
	}

	function getnome(){
		return $this->fields["nome"];
	}
	
	function getpluean(){
		return $this->fields["pluean"];
	}
	
	function gettamcodnfp(){
		return $this->fields["tamcodnfp"];
	}
	
        function getfornec(){
		return $this->fields["fornec"];
	}
	
        function getfigura(){
		return $this->fields["figura"];
	}

	function setcodbalanca($value){
		$this->fields["codbalanca"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}
	
	function setpluean($value){
		$this->fields["pluean"] = value_string($value,1);
	}
	
	function settamcodnfp($value){
		$this->fields["tamcodnfp"] = value_numeric($value);
	}
	
	function setfornec($value){
		$this->fields["fornec"] = value_string($value);
	}
	
	function setfigura($value){
		$this->fields["figura"] = value_string($value);
	}
}