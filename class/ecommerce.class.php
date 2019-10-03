<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ecommerce extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "ecommerce";
		$this->primarykey = array("codestabelec");
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getservidor(){
		return $this->fields["servidor"];
	}

	function getoauthkey(){
		return $this->fields["oauthkey"];
	}

	function getoauthsecret(){
		return $this->fields["oauthsecret"];
	}

	function getqueryproduto(){
		return $this->fields["queryproduto"];
	}

	function getquerycliente(){
		return $this->fields["querycliente"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setservidor($value){
		$this->fields["servidor"] = value_string($value,100);
	}

	function setoauthkey($value){
		$this->fields["oauthkey"] = value_string($value,100);
	}

	function setoauthsecret($value){
		$this->fields["oauthsecret"] = value_string($value,100);
	}

	function setqueryproduto($value){
		$this->fields["queryproduto"] = value_string($value);
	}

	function setquerycliente($value){
		$this->fields["querycliente"] = value_string($value);
	}
}
