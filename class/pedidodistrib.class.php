<?php
require_file("class/cadastro.class.php");

class PedidoDistrib extends Cadastro{
	function __construct($idpedidodistrib = NULL){
		parent::__construct();
		$this->table = "pedidodistrib";
		$this->primarykey = array("idpedidodistrib");
		$this->setidpedidodistrib($idpedidodistrib);
		if(!is_null($this->getidpedidodistrib())){
			$this->searchbyobject();
		}
	}

	function getidpedidodistrib(){
		return $this->fields["idpedidodistrib"];
	}

	function getiditpedidocp(){
		return $this->fields["iditpedidocp"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"],4,",","") : $this->fields["quantidade"]);
	}

	function getiditpedidots(){
		return $this->fields["iditpedidots"];
	}

	function setidpedidodistrib($value){
		$this->fields["idpedidodistrib"] = value_numeric($value);
	}

	function setiditpedidocp($value){
		$this->fields["iditpedidocp"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setiditpedidots($value){
		$this->fields["iditpedidots"] = value_numeric($value);
	}
}
?>