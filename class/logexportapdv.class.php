<?php
require_file("class/cadastro.class.php");

class LogExportaPdv extends Cadastro{
	function __construct($idlogexportapdv = NULL){
		parent::__construct();
		$this->table = "logexportapdv";
		$this->primarykey = array("idlogexportapdv");
		$this->setidlogexportapdv($idlogexportapdv);
		if(!is_null($this->getidlogexportapdv())){
			$this->searchbyobject();
		}
	}

	public function save() {
		$this->con->start_transaction();
		if(!parent::save()){
			$this->con->rollback();
			return FALSE;
		}
	}

	function getidlogexportapdv(){
		return $this->fields["idlogexportapdv"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function gettipogeracao(){
		return $this->fields["tipogeracao"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getproduto(){
		return $this->fields["produto"];
	}

	function getcliente(){
		return $this->fields["cliente"];
	}

	function getvendedor(){
		return $this->fields["vendedor"];
	}

	function getfinalizadora(){
		return $this->fields["finalizadora"];
	}

	function getdatainicial($format = FALSE){
		return ($format ? convert_date($this->fields["datainicial"],"Y-m-d","d/m/Y") : $this->fields["datainicial"]);
	}

	function gethorainicial(){
		return substr($this->fields["horainicial"],0,8);
	}

	function getdatafinal($format = FALSE){
		return ($format ? convert_date($this->fields["datafinal"],"Y-m-d","d/m/Y") : $this->fields["datafinal"]);
	}

	function gethorafinal(){
		return substr($this->fields["horafinal"],0,8);
	}

	function getalteracaourgente(){
		return $this->fields["alteracaourgente"];
	}

	function setidlogexportapdv($value){
		$this->fields["idlogexportapdv"] = value_numeric($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function settipogeracao($value){
		$this->fields["tipogeracao"] = value_string($value,1);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value,1);
	}

	function setproduto($value){
		$this->fields["produto"] = value_string($value,1);
	}

	function setcliente($value){
		$this->fields["cliente"] = value_string($value,1);
	}

	function setvendedor($value){
		$this->fields["vendedor"] = value_string($value,1);
	}

	function setfinalizadora($value){
		$this->fields["finalizadora"] = value_string($value,1);
	}

	function setdatainicial($value){
		$this->fields["datainicial"] = value_date($value);
	}

	function sethorainicial($value){
		$this->fields["horainicial"] = value_time($value);
	}

	function setdatafinal($value){
		$this->fields["datafinal"] = value_date($value);
	}

	function sethorafinal($value){
		$this->fields["horafinal"] = value_time($value);
	}

	function setalteracaourgente($value){
		$this->fields["alteracaourgente"] = value_string($value,1);
	}
}
?>