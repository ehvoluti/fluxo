<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class StatusCliente extends Cadastro{
	function __construct($codstatus = NULL){
		parent::__construct();
		$this->table = "statuscliente";
		$this->primarykey = array("codstatus");
		$this->setcodstatus($codstatus);
		if(!is_null($this->getcodstatus())){
			$this->searchbyobject();
		}
	}

	function getcodstatus(){
		return $this->fields["codstatus"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getbloqueado(){
		return $this->fields["bloqueado"];
	}

	function getinfostatus(){
		return $this->fields["infostatus"];
	}

	function getaviso(){
		return $this->fields["aviso"];
	}

	function setaviso($value){
		$this->fields["aviso"] = value_numeric($value);
	}

	function setcodstatus($value){
		$this->fields["codstatus"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setbloqueado($value){
		$this->fields["bloqueado"] = value_string($value,1);
	}

	function setinfostatus($value){
		$this->fields["infostatus"] = value_string($value,6);
	}
}
?>