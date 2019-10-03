<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ValorPadrao extends Cadastro{
	function __construct($tabela = NULL,$campo = NULL){
		parent::__construct();
		$this->table = "valorpadrao";
		$this->primarykey = array("tabela","campo");
		$this->settabela($tabela);
		$this->setcampo($campo);
		if($this->gettabela() != NULL && $this->getcampo() != NULL){
			$this->searchbyobject();
		}
	}

	function gettabela(){
		return $this->fields["tabela"];
	}

	function getcampo(){
		return $this->fields["campo"];
	}
	
	function getvalor(){
		return $this->fields["valor"];
	}

	function settabela($value){
		$this->fields["tabela"] = value_string($value,20);
	}

	function setcampo($value){
		$this->fields["campo"] = value_string($value,20);
	}
	
	function setvalor($value){
		$this->fields["valor"] = value_string($value);
	}
}
?>