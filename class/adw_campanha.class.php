<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class adwCampanha extends Cadastro{
	function __construct($idcampanha = NULL){
		parent::__construct();
		$this->table = "adw_campanha";
		$this->primarykey = array("idcampanha");
		$this->setidcampanha($idcampanha);
		if(!is_null($this->getidcampanha())){
			$this->searchbyobject();
		}
	}

	function getidcampanha(){
		return $this->fields["idcampanha"];
	}

	function getcampanha(){
		return $this->fields["campanha"];
	}

	function getdatacriacao($format = FALSE){
		return ($format ? convert_date($this->fields["datacriacao"],"Y-m-d","d/m/Y") : $this->fields["datacriacao"]);
	}

	function setidcampanha($value){
		$this->fields["idcampanha"] = value_numeric($value);
	}

	function setcampanha($value){
		$this->fields["campanha"] = value_string($value);
	}

	function setdatacriacao($value){
		$this->fields["datacriacao"] = value_date($value);
	}
}
?>