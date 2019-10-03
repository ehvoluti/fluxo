<?php
require_file("class/cadastro.class.php");

class Notificacao extends Cadastro{
	function __construct($idnotificacao = NULL){
		parent::__construct();
		$this->table = "notificacao";
		$this->primarykey = array("idnotificacao");
		$this->setidnotificacao($idnotificacao);
		if(!is_null($this->getidnotificacao())){
			$this->searchbyobject();
		}
	}

	function getidnotificacao(){
		return $this->fields["idnotificacao"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function gettexto(){
		return $this->fields["texto"];
	}

	function getdtnotificacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtnotificacao"],"Y-m-d","d/m/Y") : $this->fields["dtnotificacao"]);
	}

	function gethrnotificacao(){
		return substr($this->fields["hrnotificacao"],0,8);
	}

	function getdtvalidade($format = FALSE){
		return ($format ? convert_date($this->fields["dtvalidade"],"Y-m-d","d/m/Y") : $this->fields["dtvalidade"]);
	}

	function gethrvalidade(){
		return substr($this->fields["hrvalidade"],0,8);
	}

	function setidnotificacao($value){
		$this->fields["idnotificacao"] = value_numeric($value);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,50);
	}

	function settexto($value){
		$this->fields["texto"] = value_string($value);
	}

	function setdtnotificacao($value){
		$this->fields["dtnotificacao"] = value_date($value);
	}

	function sethrnotificacao($value){
		$this->fields["hrnotificacao"] = value_time($value);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function sethrvalidade($value){
		$this->fields["hrvalidade"] = value_time($value);
	}
}
?>