<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NatOperCarimbo extends Cadastro{
	function __construct($natoperacao = NULL,$codcarimbo = NULL){
		parent::__construct();
		$this->table = "natopercarimbo";
		$this->primarykey = array("natoperacao","codcarimbo");
		$this->setnatoperacao($natoperacao);
		$this->setcodcarimbo($codcarimbo);
		if($this->fields[$this->primarykey[0]] != NULL && $this->fields[$this->primarykey[1]] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getcodcarimbo(){
		return $this->fields["codcarimbo"];
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_numeric($value);
	}

	function setcodcarimbo($value){
		$this->fields["codcarimbo"] = value_numeric($value);
	}
}
?>