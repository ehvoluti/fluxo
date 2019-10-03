<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItCotacao extends Cadastro{
	function __construct($codcotacao = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "itcotacao";
		$this->primarykey = array("codcotacao", "codproduto");
		$this->setcodcotacao($codcotacao);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodcotacao()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getqtdeunidade($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidade"],4,",","") : $this->fields["qtdeunidade"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setqtdeunidade($value){
		$this->fields["qtdeunidade"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}
}
?>