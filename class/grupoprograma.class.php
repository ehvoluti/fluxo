<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class GrupoPrograma extends Cadastro{
	function __construct($codgrupo = NULL, $idtable = NULL){
		parent::__construct();
		$this->table = "grupoprograma";
		$this->primarykey = array("codgrupo","idtable");
		$this->setcodgrupo($codgrupo);
		$this->setidtable($idtable);
		if(!is_null($this->getcodgrupo()) && !is_null($this->getidtable())){
			$this->searchbyobject();
		}
	}

	function getidtable(){
		return $this->fields["idtable"];
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getincluir(){
		return $this->fields["incluir"];
	}

	function geteditar(){
		return $this->fields["editar"];
	}

	function getdeletar(){
		return $this->fields["deletar"];
	}

	function getclonar(){
		return $this->fields["clonar"];
	}

	function getpesquisar(){
		return $this->fields["pesquisar"];
	}

	function setidtable($value){
		$this->fields["idtable"] = value_string($value,30);
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setincluir($value){
		$this->fields["incluir"] = value_string($value,1);
	}

	function seteditar($value){
		$this->fields["editar"] = value_string($value,1);
	}

	function setdeletar($value){
		$this->fields["deletar"] = value_string($value,1);
	}

	function setclonar($value){
		$this->fields["clonar"] = value_string($value,1);
	}

	function setpesquisar($value){
		$this->fields["pesquisar"] = value_string($value,1);
	}
}
?>