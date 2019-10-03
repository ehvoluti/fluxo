<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamEstoque extends Cadastro{
	function __construct($codemitente = NULL){
		parent::__construct();
		$this->table = "paramestoque";
		$this->primarykey = array("codemitente");
		$this->setcodemitente($codemitente);
		if(!is_null($this->getcodemitente())){
			$this->searchbyobject();
		}
	}

	function getcodemitente(){
		return $this->fields["codemitente"];
	}

	function getcodclientevendapdv(){
		return $this->fields["codclientevendapdv"];
	}

	function getcodtpdoctovendapdv(){
		return $this->fields["codtpdoctovendapdv"];
	}

	function getcodtpdoctoinventent(){
		return $this->fields["codtpdoctoinventent"];
	}

	function getcodtpdoctoinventsai(){
		return $this->fields["codtpdoctoinventsai"];
	}

	function getcodtpdoctocomposdes(){
		return $this->fields["codtpdoctocomposdes"];
	}

	function getcodtpdoctocomposdesit(){
		return $this->fields["codtpdoctocomposdesit"];
	}

	function getcodtpdoctocompospro(){
		return $this->fields["codtpdoctocompospro"];
	}

	function getcodtpdoctocomposproit(){
		return $this->fields["codtpdoctocomposproit"];
	}

	function getcodtpdoctotrocapdv(){
		return $this->fields["codtpdoctotrocapdv"];
	}

	function getcodtpdoctovasilhamee(){
		return $this->fields["codtpdoctovasilhamee"];
	}

	function getcodtpdoctovasilhames(){
		return $this->fields["codtpdoctovasilhames"];
	}

	function setcodemitente($value){
		$this->fields["codemitente"] = value_numeric($value);
	}

	function setcodclientevendapdv($value){
		$this->fields["codclientevendapdv"] = value_numeric($value);
	}

	function setcodtpdoctovendapdv($value){
		$this->fields["codtpdoctovendapdv"] = value_numeric($value);
	}

	function setcodtpdoctoinventent($value){
		$this->fields["codtpdoctoinventent"] = value_numeric($value);
	}

	function setcodtpdoctoinventsai($value){
		$this->fields["codtpdoctoinventsai"] = value_numeric($value);
	}

	function setcodtpdoctocomposdes($value){
		$this->fields["codtpdoctocomposdes"] = value_numeric($value);
	}

	function setcodtpdoctocomposdesit($value){
		$this->fields["codtpdoctocomposdesit"] = value_numeric($value);
	}

	function setcodtpdoctocompospro($value){
		$this->fields["codtpdoctocompospro"] = value_numeric($value);
	}

	function setcodtpdoctocomposproit($value){
		$this->fields["codtpdoctocomposproit"] = value_numeric($value);
	}

	function setcodtpdoctotrocapdv($value){
		$this->fields["codtpdoctotrocapdv"] = value_numeric($value);
	}

	function setcodtpdoctovasilhamee($value){
		$this->fields["codtpdoctovasilhamee"] = value_numeric($value);
	}

	function setcodtpdoctovasilhames($value){
		$this->fields["codtpdoctovasilhames"] = value_numeric($value);
	}
}