<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItOrcamentoConf extends Cadastro{
	function __construct($operacao = NULL, $coluna = NULL){
		parent::__construct();
		$this->table = "itorcamento_conf";
		$this->primarykey = array("operacao", "coluna");
		$this->setoperacao($operacao);
		$this->setcoluna($coluna);
		if(!is_null($this->getoperacao()) && !is_null($this->getcoluna())){
			$this->searchbyobject();
		}
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getcoluna(){
		return $this->fields["coluna"];
	}

	function gettab(){
		return $this->fields["tab"];
	}

	function getblock(){
		return $this->fields["block"];
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,2);
	}

	function setcoluna($value){
		$this->fields["coluna"] = value_string($value,40);
	}

	function settab($value){
		$this->fields["tab"] = value_string($value,1);
	}

	function setblock($value){
		$this->fields["block"] = value_string($value,1);
	}
}
?>