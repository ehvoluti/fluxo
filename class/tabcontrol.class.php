<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TabControl extends Cadastro{
	function __construct($rotina = NULL,$campo = NULL){
		parent::__construct();
		$this->table = "tabcontrol";
		$this->primarykey = array("rotina","campo");
		$this->setrotina($rotina);
        $this->setcampo($campo);
		if(!is_null($this->getrotina()) && !is_null($this->getcampo())){
			$this->searchbyobject();
		}
	}

	function getrotina(){
		return $this->fields["rotina"];
	}

	function getcampo(){
		return $this->fields["campo"];
	}

	function setrotina($value){
		$this->fields["rotina"] = value_string($value,40);
	}

	function setcampo($value){
		$this->fields["campo"] = value_string($value,40);
	}
}
?>