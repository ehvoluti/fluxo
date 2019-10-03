<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class UsuarioLogado extends Cadastro{
	function __construct($sessionid = NULL,$data = NULL,$hora = NULL){
		parent::__construct();
		$this->table = "usuariologado";
		$this->primarykey = array("sessionid","data","hora");
		$this->setsessionid($sessionid);
		$this->setdata($data);
		$this->sethora($hora);
		if($this->getsessionid() != NULL && $this->getdata() != NULL && $this->gethora() != NULL){
			$this->searchbyobject();
		}
	}

	function limparantigos(){
		return $this->con->exec("DELETE FROM ".$this->table." WHERE data < '".date("Y-m-d")."' OR (data = '".date("Y-m-d")."' AND hora < '".date("H:i:s",time() - 15)."')");
	}

	function getip(){
		return $this->fields["ip"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getdata($format = FALSE){
		return ($format ? convert_date($this->fields["data"],"Y-m-d","d/m/Y") : $this->fields["data"]);
	}

	function gethora(){
		return $this->fields["hora"];
	}

	function getsessionid(){
		return $this->fields["sessionid"];
	}

	function setip($value){
		$this->fields["ip"] = value_string($value,20);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setdata($value){
		$this->fields["data"] = value_date($value);
	}

	function sethora($value){
		$this->fields["hora"] = value_time($value);
	}

	function setsessionid($value){
		$this->fields["sessionid"] = value_string($value);
	}
}