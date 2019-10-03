<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Sazonal extends Cadastro{
	function __construct($codsazonal = NULL){
		parent::__construct();
		$this->table = "sazonal";
		$this->primarykey = array("codsazonal");
		$this->setcodsazonal($codsazonal);
		if(!is_null($this->getcodsazonal())){
			$this->searchbyobject();
		}
	}

	function getcodsazonal(){
		return $this->fields["codsazonal"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getmensagem(){
		return $this->fields["mensagem"];
	}

	function getdtalerta($format = FALSE){
		return ($format ? convert_date($this->fields["dtalerta"],"Y-m-d","d/m/Y") : $this->fields["dtalerta"]);
	}

	function gettipodata(){
		return $this->fields["tipodata"];
	}

	function getdtinicio($format = FALSE){
		return ($format ? convert_date($this->fields["dtinicio"],"Y-m-d","d/m/Y") : $this->fields["dtinicio"]);
	}

	function getdtfinal($format = FALSE){
		return ($format ? convert_date($this->fields["dtfinal"],"Y-m-d","d/m/Y") : $this->fields["dtfinal"]);
	}

	function getmes01(){
		return $this->fields["mes01"];
	}

	function getmes02(){
		return $this->fields["mes02"];
	}

	function getmes03(){
		return $this->fields["mes03"];
	}

	function getmes04(){
		return $this->fields["mes04"];
	}

	function getmes05(){
		return $this->fields["mes05"];
	}

	function getmes06(){
		return $this->fields["mes06"];
	}

	function getmes07(){
		return $this->fields["mes07"];
	}

	function getmes08(){
		return $this->fields["mes08"];
	}

	function getmes09(){
		return $this->fields["mes09"];
	}

	function getmes10(){
		return $this->fields["mes10"];
	}

	function getmes11(){
		return $this->fields["mes11"];
	}

	function getmes12(){
		return $this->fields["mes12"];
	}

	function setcodsazonal($value){
		$this->fields["codsazonal"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setmensagem($value){
		$this->fields["mensagem"] = value_string($value,300);
	}

	function setdtalerta($value){
		$this->fields["dtalerta"] = value_date($value);
	}

	function settipodata($value){
		$this->fields["tipodata"] = value_string($value,2);
	}

	function setdtinicio($value){
		$this->fields["dtinicio"] = value_date($value);
	}

	function setdtfinal($value){
		$this->fields["dtfinal"] = value_date($value);
	}

	function setmes01($value){
		$this->fields["mes01"] = value_string($value,1);
	}

	function setmes02($value){
		$this->fields["mes02"] = value_string($value,1);
	}

	function setmes03($value){
		$this->fields["mes03"] = value_string($value,1);
	}

	function setmes04($value){
		$this->fields["mes04"] = value_string($value,1);
	}

	function setmes05($value){
		$this->fields["mes05"] = value_string($value,1);
	}

	function setmes06($value){
		$this->fields["mes06"] = value_string($value,1);
	}

	function setmes07($value){
		$this->fields["mes07"] = value_string($value,1);
	}

	function setmes08($value){
		$this->fields["mes08"] = value_string($value,1);
	}

	function setmes09($value){
		$this->fields["mes09"] = value_string($value,1);
	}

	function setmes10($value){
		$this->fields["mes10"] = value_string($value,1);
	}

	function setmes11($value){
		$this->fields["mes11"] = value_string($value,1);
	}

	function setmes12($value){
		$this->fields["mes12"] = value_string($value,1);
	}
}
?>