<?php
require_file("class/cadastro.class.php");

class Arquivo extends Cadastro{
	function __construct($codarquivo = NULL){
		parent::__construct();
		$this->table = "arquivo";
		$this->primarykey = array("codarquivo");
		$this->setcodarquivo($codarquivo);
		if(!is_null($this->getcodarquivo())){
			$this->searchbyobject();
		}
	}

	function getcodarquivo(){
		return $this->fields["codarquivo"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getconteudo(){
		return $this->fields["conteudo"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function setcodarquivo($value){
		$this->fields["codarquivo"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value);
	}

	function setconteudo($value){
		$this->fields["conteudo"] = value_string($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}
}
?>