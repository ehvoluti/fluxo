<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class OperacaoNota extends Cadastro{

	function __construct($operacao = NULL){
		parent::__construct();
		$this->table = "operacaonota";
		$this->primarykey = array("operacao");
		$this->setoperacao($operacao);
		if(!is_null($this->getoperacao())){
			$this->searchbyobject();
		}
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getparceiro(){
		return $this->fields["parceiro"];
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 40);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value, 1);
	}

	function setparceiro($value){
		$this->fields["parceiro"] = value_string($value, 1);
	}

}