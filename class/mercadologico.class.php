<?php

require_file("class/cadastro.class.php");

class Mercadologico extends Cadastro{

	function __construct($idmercadologico = null){
		parent::__construct();
		$this->table = "mercadologico";
		$this->primarykey = array("idmercadologico");
		$this->autoinc = true;
		$this->setidmercadologico($idmercadologico);
		if(!is_null($this->getidmercadologico())){
			$this->searchbyobject();
		}
	}

	function getidmercadologico(){
		return $this->fields["idmercadologico"];
	}

	function getdtcriacao($format = false){
		return ($format ? convert_date($this->fields["dtcriacao"], "Y-m-d", "d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"], 0, 8);
	}

	function getnivel(){
		return $this->fields["nivel"];
	}

	function getidmercadologicopai(){
		return $this->fields["idmercadologicopai"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setidmercadologico($value){
		$this->fields["idmercadologico"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setnivel($value){
		$this->fields["nivel"] = value_numeric($value);
	}

	function setidmercadologicopai($value){
		$this->fields["idmercadologicopai"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 100);
	}

}
