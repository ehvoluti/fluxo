<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class CotacaoFornec extends Cadastro{
	function __construct($codcotacao = NULL, $codfornec = NULL){
		parent::__construct();
		$this->table = "cotacaofornec";
		$this->primarykey = array("codcotacao", "codfornec");
		$this->setcodcotacao($codcotacao);
		$this->setcodfornec($codfornec);
		if(!is_null($this->getcodcotacao()) && !is_null($this->getcodfornec())){
			$this->searchbyobject();
		}
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}
}
?>