<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItInventarioTemp extends Cadastro{

	function __construct($codinventario = NULL, $codproduto = NULL, $contagem = NULL){
		parent::__construct();
		$this->table = "itinventariotemp";
		$this->primarykey = array("codinventario", "codproduto", "contagem");
		$this->setcodinventario($codinventario);
		$this->setcodproduto($codproduto);
		$this->setcontagem($contagem);
		if($this->getcodinventario() != NULL && $this->getcodproduto() != NULL && $this->getcontagem() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodinventario(){
		return $this->fields["codinventario"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getcheckbox(){
		return $this->fields["checkbox"];
	}

	function getembalagem($format = FALSE){
		return ($format ? number_format($this->fields["embalagem"], 2, ",", "") : $this->fields["embalagem"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"], 2, ",", "") : $this->fields["quantidade"]);
	}

	function getacumulado($format = FALSE){
		return ($format ? number_format($this->fields["acumulado"], 2, ",", "") : $this->fields["acumulado"]);
	}

	function getcontagem(){
		return $this->fields["contagem"];
	}

	function setcodinventario($value){
		$this->fields["codinventario"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setcheckbox($value){
		$this->fields["checkbox"] = value_string($value, 1);
	}

	function setembalagem($value){
		$this->fields["embalagem"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setacumulado($value){
		$this->fields["acumulado"] = value_numeric($value);
	}

	function setcontagem($value){
		$this->fields["contagem"] = value_numeric($value);
	}

}