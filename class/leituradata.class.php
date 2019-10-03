<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class LeituraData extends Cadastro{
	function __construct($dtmovto = NULL, $codestabelec = NULL){
		parent::__construct();
		$this->table = "leitura_data";
		$this->primarykey = array("dtmovto", "codestabelec");
		$this->setdtmovto($dtmovto);
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getdtmovto()) && !is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],2,",","") : $this->fields["quantidade"]);
	}

	function getvalorvenda($format = FALSE){
		return ($format ? number_format($this->fields["valorvenda"],2,",","") : $this->fields["valorvenda"]);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setvalorvenda($value){
		$this->fields["valorvenda"] = value_numeric($value);
	}
}
?>