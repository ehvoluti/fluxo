<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamFiscal extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramfiscal";
		$this->primarykey = "codestabelec";
		$this->newrelation("paramfiscal","codestabelec","estabelecimento","codestabelec");
		$this->setcodestabelec($codestabelec);
		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getambientenfe(){
		return $this->fields["ambientenfe"];
	}

	function getnummaparesumo(){
		return $this->fields["nummaparesumo"];
	}

	function getnumnotanfis(){
		return $this->fields["numnotanfis"];
	}

	function getserienfis(){
		return $this->fields["serienfis"];
	}

	function getlivrocontregistro(){
		return $this->fields["livrocontregistro"];
	}

	function getlivromodelo(){
		return $this->fields["livromodelo"];
	}

	function getlivronumordem(){
		return $this->fields["livronumordem"];
	}

	function getlivronumpagina(){
		return $this->fields["livronumpagina"];
	}

	function getnumnotafiss(){
		return $this->fields["numnotafiss"];
	}

	function getseriefiss(){
		return $this->fields["seriefiss"];
	}

	function getdtfechafiscal($format = FALSE){
		return ($format ? convert_date($this->fields["dtfechafiscal"],"Y-m-d","d/m/Y") : $this->fields["dtfechafiscal"]);
	}

	function getdatainicioblocok($format = FALSE){
		return ($format ? convert_date($this->fields["datainicioblocok"],"Y-m-d","d/m/Y") : $this->fields["datainicioblocok"]);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value,3);
	}

	function setambientenfe($value){
		$this->fields["ambientenfe"] = value_string($value,1);
	}

	function setnummaparesumo($value){
		$this->fields["nummaparesumo"] = value_numeric($value);
	}

	function setnumnotanfis($value){
		$this->fields["numnotanfis"] = value_numeric($value);
	}

	function setserienfis($value){
		$this->fields["serienfis"] = value_string($value,3);
	}

	function setlivrocontregistro($value){
		$this->fields["livrocontregistro"] = value_numeric($value);
	}

	function setlivromodelo($value){
		$this->fields["livromodelo"] = value_string($value,6);
	}

	function setlivronumordem($value){
		$this->fields["livronumordem"] = value_numeric($value);
	}

	function setlivronumpagina($value){
		$this->fields["livronumpagina"] = value_numeric($value);
	}

	function setnumnotafiss($value){
		$this->fields["numnotafiss"] = value_numeric($value);
	}

	function setseriefiss($value){
		$this->fields["seriefiss"] = value_string($value, 4);
	}

	function setdtfechafiscal($value){
		$this->fields["dtfechafiscal"] = value_date($value);
	}

	function setdatainicioblocok($value){
		$this->fields["datainicioblocok"] = value_date($value);
	}
}
