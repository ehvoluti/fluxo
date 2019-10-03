<?php

require_file("class/cadastro.class.php");

class ControleWebService extends Cadastro{

	function __construct($idcontrolewebservice = NULL){
		parent::__construct();
		$this->table = "controlewebservice";
		$this->primarykey = array("idcontrolewebservice");
		$this->autoinc = TRUE;
		$this->setidcontrolewebservice($idcontrolewebservice);
		if(!is_null($this->getidcontrolewebservice())){
			$this->searchbyobject();
		}
	}

	function getidcontrolewebservice(){
		return $this->fields["idcontrolewebservice"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"], "Y-m-d", "d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"], 0, 8);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getservico(){
		return $this->fields["servico"];
	}

	function getdtresposta($format = FALSE){
		return ($format ? convert_date($this->fields["dtresposta"], "Y-m-d", "d/m/Y") : $this->fields["dtresposta"]);
	}

	function gethrresposta(){
		return substr($this->fields["hrresposta"], 0, 8);
	}

	function getenvio(){
		return $this->fields["envio"];
	}

	function getresposta(){
		return $this->fields["resposta"];
	}

	function getextra(){
		return $this->fields["extra"];
	}

	function setidcontrolewebservice($value){
		$this->fields["idcontrolewebservice"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setservico($value){
		$this->fields["servico"] = value_string($value, 20);
	}

	function setdtresposta($value){
		$this->fields["dtresposta"] = value_date($value);
	}

	function sethrresposta($value){
		$this->fields["hrresposta"] = value_time($value);
	}

	function setenvio($value){
		$this->fields["envio"] = value_string($value);
	}

	function setresposta($value){
		$this->fields["resposta"] = value_string($value);
	}

	function setextra($value){
		$this->fields["extra"] = value_string($value);
	}

}