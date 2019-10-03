<?php
require_file("class/cadastro.class.php");

class Historico extends Cadastro{
	function __construct($codhistorico = NULL){
		parent::__construct();
		$this->table = "historico";
		$this->primarykey = array("codhistorico");
		$this->autoinc = TRUE;
		$this->setcodhistorico($codhistorico);
		if(!is_null($this->getcodhistorico())){
			$this->searchbyobject();
		}
	}

	function getcodhistorico(){
		return $this->fields["codhistorico"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function gettabela(){
		return $this->fields["tabela"];
	}

	function getchave(){
		return $this->fields["chave"];
	}

	function getregistroold(){
		return $this->fields["registroold"];
	}

	function getregistronew(){
		return $this->fields["registronew"];
	}

	function setcodhistorico($value){
		$this->fields["codhistorico"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value,1);
	}

	function settabela($value){
		$this->fields["tabela"] = value_string($value,100);
	}

	function setchave($value){
		$this->fields["chave"] = value_string($value,100);
	}

	function setregistroold($value){
		$this->fields["registroold"] = value_string($value);
	}

	function setregistronew($value){
		$this->fields["registronew"] = value_string($value);
	}
}
?>