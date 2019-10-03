<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class adwPalavra extends Cadastro{
	function __construct($idpalavra = NULL){
		parent::__construct();
		$this->table = "adw_palavra";
		$this->primarykey = array("idpalavra");
		$this->setidpalavra($idpalavra);
		if(!is_null($this->getidpalavra())){
			$this->searchbyobject();
		}
	}

	function getidpalavra(){
		return $this->fields["idpalavra"];
	}

	function getidcampanha(){
		return $this->fields["idcampanha"];
	}

	function getidanuncio(){
		return $this->fields["idanuncio"];
	}

	function getpalavra(){
		return $this->fields["palavra"];
	}

	function getdatacriacao($format = FALSE){
		return ($format ? convert_date($this->fields["datacriacao"],"Y-m-d","d/m/Y") : $this->fields["datacriacao"]);
	}

	function setidpalavra($value){
		$this->fields["idpalavra"] = value_numeric($value);
	}

	function setidcampanha($value){
		$this->fields["idcampanha"] = value_numeric($value);
	}

	function setidanuncio($value){
		$this->fields["idanuncio"] = value_numeric($value);
	}

	function setpalavra($value){
		$this->fields["palavra"] = value_string($value);
	}

	function setdatacriacao($value){
		$this->fields["datacriacao"] = value_date($value);
	}
}
?>