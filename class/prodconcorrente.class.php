<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ProdConcorrente extends Cadastro{
	function __construct($codconcorrente = NULL, $codproduto = NULL){
		parent::__construct();
		$this->table = "prodconcorrente";
		$this->primarykey = array("codconcorrente", "codproduto");
		$this->setcodconcorrente($codconcorrente);
		$this->setcodproduto($codproduto);
		if(!is_null($this->getcodconcorrente()) && !is_null($this->getcodproduto())){
			$this->searchbyobject();
		}
	}

	function getcodconcorrente(){
		return $this->fields["codconcorrente"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"],2,",","") : $this->fields["preco"]);
	}

	function geturl(){
		return $this->fields["url"];
	}

	function getdtatualizacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtatualizacao"],"Y-m-d","d/m/Y") : $this->fields["dtatualizacao"]);
	}

	function gethratualizacao(){
		return substr($this->fields["hratualizacao"],0,8);
	}
	
	function getativo(){
		return $this->fields["ativo"];
	}

	function setcodconcorrente($value){
		$this->fields["codconcorrente"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
	}

	function seturl($value){
		$this->fields["url"] = value_string($value,500);
	}

	function setdtatualizacao($value){
		$this->fields["dtatualizacao"] = value_date($value);
	}

	function sethratualizacao($value){
		$this->fields["hratualizacao"] = value_time($value);
	}
	
	function setativo($value){
		$this->fields["ativo"] = value_string($value,1);
	}
}
?>