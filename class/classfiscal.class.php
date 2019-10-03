<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ClassFiscal extends Cadastro{
	function __construct($codcf = NULL){
		parent::__construct();
		$this->table = "classfiscal";
		$this->primarykey = array("codcf");
		$this->setcodcf($codcf);
		if(!is_null($this->getcodcf())){
			$this->searchbyobject();
		}
	}

	function gerar_descricao(){
		if(strlen($this->gettptribicms()) == 0){
			return NULL;
		}else{
			$descricao  = $this->gettptribicms()." ";
			$descricao .= number_format($this->getaliqicms(),2,",",".")."% ";
			if($this->getaliqredicms() > 0){
				$descricao .= "-".number_format($this->getaliqredicms(),2,",",".")."% ";
			}
			if($this->gettptribicms == "F" && $this->getaliqiva() > 0){
				$descricao .= "IVA ".number_format($this->getaliqiva(),2,",",".")." ";
			}
			$descricao .= "CST ".$this->getcodcst();
			return $descricao;
		}
	}

	function save($object = null){
		if(strlen($this->getdescricao()) == 0){
			$this->setdescricao($this->gerar_descricao());
		}
		return parent::save($object);
	}

	function getcodcf(){
		return $this->fields["codcf"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getaliqicms($format = FALSE){
		if(substr($this->fields["aliqicms"],-2) == "00"){
			return ($format ? number_format($this->fields["aliqicms"], 2, ",", "") : $this->fields["aliqicms"]);
		}else{
			return ($format ? number_format($this->fields["aliqicms"], 4, ",", "") : $this->fields["aliqicms"]);
		}
	}

	function getaliqredicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqredicms"], 4, ",", "") : $this->fields["aliqredicms"]);
	}

	function getcodcst(){
		return $this->fields["codcst"];
	}

	function gettptribicms(){
		return $this->fields["tptribicms"];
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->fields["aliqiva"], 4, ",", "") : $this->fields["aliqiva"]);
	}

	function getvalorpauta($format = FALSE){
		return ($format ? number_format($this->fields["valorpauta"], 4, ",", "") : $this->fields["valorpauta"]);
	}

	function getaliqii($format = FALSE){
		return ($format ? number_format($this->fields["aliqii"], 4, ",", "") : $this->fields["aliqii"]);
	}

	function getforcarcst(){
		return $this->fields["forcarcst"];
	}

	function getcsosn(){
		return $this->fields["csosn"];
	}

	function getmotivodesoneracao(){
		return $this->fields["motivodesoneracao"];
	}

	function setcodcf($value){
		$this->fields["codcf"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 40);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setaliqredicms($value){
		$this->fields["aliqredicms"] = value_numeric($value);
	}

	function setcodcst($value){
		$this->fields["codcst"] = value_string($value, 3);
	}

	function settptribicms($value){
		$this->fields["tptribicms"] = value_string($value, 1);
	}

	function setaliqiva($value){
		$this->fields["aliqiva"] = value_numeric($value);
	}

	function setvalorpauta($value){
		$this->fields["valorpauta"] = value_numeric($value);
	}

	function setaliqii($value){
		$this->fields["aliqii"] = value_numeric($value);
	}

	function setforcarcst($value){
		$this->fields["forcarcst"] = value_string($value, 1);
	}

	function setcsosn($value){
		$this->fields["csosn"] = value_string($value,3);
	}

	function setmotivodesoneracao($value){
		$this->fields["motivodesoneracao"] = value_string($value, 1);
	}
}
