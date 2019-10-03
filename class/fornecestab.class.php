<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class FornecEstab extends Cadastro{
	function __construct($codfornec = NULL,$codestabelec = NULL){
		parent::__construct();
		$this->table = "fornecestab";
		$this->primarykey = array("codestabelec","codfornec");
		$this->setcodfornec($codfornec);
		$this->setcodestabelec($codestabelec);
		if($this->getcodfornec() != NULL && $this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdiasentrega(){
		return $this->fields["diasentrega"];
	}

	function getfreqvisita(){
		return $this->fields["freqvisita"];
	}

	function getdisponivel(){
		return $this->fields["disponivel"];
	}

	function getfaturamentominimo($format = FALSE){
		return ($format ? number_format($this->fields["faturamentominimo"],2,",","") : $this->fields["faturamentominimo"]);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdiasentrega($value){
		$this->fields["diasentrega"] = value_numeric($value);
	}

	function setfreqvisita($value){
		$this->fields["freqvisita"] = value_numeric($value);
	}

	function setdisponivel($value){
		$this->fields["disponivel"] = value_string($value,1);
	}

	function setfaturamentominimo($value){
		$this->fields["faturamentominimo"] = value_numeric($value);
	}
}

?>