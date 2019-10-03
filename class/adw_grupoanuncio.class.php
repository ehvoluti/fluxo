<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class adwGrupoAnuncio extends Cadastro{
	function __construct($idgrupoanuncio = NULL){
		parent::__construct();
		$this->table = "adw_grupoanuncio";
		$this->primarykey = array("idgrupoanuncio");
		$this->setidgrupoanuncio($idgrupoanuncio);
		if(!is_null($this->getidgrupoanuncio())){
			$this->searchbyobject();
		}
	}

	function getidgrupoanuncio(){
		return $this->fields["idgrupoanuncio"];
	}

	function getanuncio(){
		return $this->fields["anuncio"];
	}

	function getdatacriacao($format = FALSE){
		return ($format ? convert_date($this->fields["datacriacao"],"Y-m-d","d/m/Y") : $this->fields["datacriacao"]);
	}

	function getposmedia($format = FALSE){
		return ($format ? number_format($this->fields["posmedia"],2,",","") : $this->fields["posmedia"]);
	}

	function getcpmin($format = FALSE){
		return ($format ? number_format($this->fields["cpmin"],2,",","") : $this->fields["cpmin"]);
	}

	function getcpmax($format = FALSE){
		return ($format ? number_format($this->fields["cpmax"],2,",","") : $this->fields["cpmax"]);
	}

	function setidgrupoanuncio($value){
		$this->fields["idgrupoanuncio"] = value_string($value);
	}

	function setanuncio($value){
		$this->fields["anuncio"] = value_string($value);
	}

	function setdatacriacao($value){
		$this->fields["datacriacao"] = value_date($value);
	}

	function setposmedia($value){
		$this->fields["posmedia"] = value_numeric($value);
	}

	function setcpmin($value){
		$this->fields["cpmin"] = value_numeric($value);
	}

	function setcpmax($value){
		$this->fields["cpmax"] = value_numeric($value);
	}
}
?>