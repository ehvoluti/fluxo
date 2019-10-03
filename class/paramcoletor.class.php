<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamColetor extends Cadastro{
	function __construct($codestabelec = NULL){
		parent::__construct();
		$this->table = "paramcoletor";
		$this->primarykey = array("codestabelec");
		$this->setcodestabelec($codestabelec);
		if(!is_null($this->getcodestabelec())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getsepdecimal(){
		return $this->fields["sepdecimal"];
	}

	function getexp_pos_codproduto(){
		return $this->fields["exp_pos_codproduto"];
	}

	function getexp_tam_codproduto(){
		return $this->fields["exp_tam_codproduto"];
	}

	function getexp_pos_codean(){
		return $this->fields["exp_pos_codean"];
	}

	function getexp_tam_codean(){
		return $this->fields["exp_tam_codean"];
	}

	function getexp_pos_descricao(){
		return $this->fields["exp_pos_descricao"];
	}

	function getexp_tam_descricao(){
		return $this->fields["exp_tam_descricao"];
	}

	function getexp_pos_preco(){
		return $this->fields["exp_pos_preco"];
	}

	function getexp_tam_preco(){
		return $this->fields["exp_tam_preco"];
	}

	function getexp_dec_preco(){
		return $this->fields["exp_dec_preco"];
	}

	function getexp_pos_estoque(){
		return $this->fields["exp_pos_estoque"];
	}

	function getexp_tam_estoque(){
		return $this->fields["exp_tam_estoque"];
	}

	function getexp_dec_estoque(){
		return $this->fields["exp_dec_estoque"];
	}

	function getimp_pos_codproduto(){
		return $this->fields["imp_pos_codproduto"];
	}

	function getimp_tam_codproduto(){
		return $this->fields["imp_tam_codproduto"];
	}

	function getimp_pos_codean(){
		return $this->fields["imp_pos_codean"];
	}

	function getimp_tam_codean(){
		return $this->fields["imp_tam_codean"];
	}

	function getimp_pos_quantidade(){
		return $this->fields["imp_pos_quantidade"];
	}

	function getimp_tam_quantidade(){
		return $this->fields["imp_tam_quantidade"];
	}

	function getimp_dec_quantidade(){
		return $this->fields["imp_dec_quantidade"];
	}

	function getexp_nomearquivo(){
		return $this->fields["exp_nomearquivo"];
	}

	function getimp_nomearquivo(){
		return $this->fields["imp_nomearquivo"];
	}
	
	function getimp_separador(){
		return $this->fields["imp_separador"];
	}
	
	function getimp_pos_preco(){
		return $this->fields["imp_pos_preco"];
	}

	function getimp_tam_preco(){
		return $this->fields["imp_tam_preco"];
	}
	
	function getimp_dec_preco(){
		return $this->fields["imp_dec_preco"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setsepdecimal($value){
		$this->fields["sepdecimal"] = value_string($value,1);
	}

	function setexp_pos_codproduto($value){
		$this->fields["exp_pos_codproduto"] = value_numeric($value);
	}

	function setexp_tam_codproduto($value){
		$this->fields["exp_tam_codproduto"] = value_numeric($value);
	}

	function setexp_pos_codean($value){
		$this->fields["exp_pos_codean"] = value_numeric($value);
	}

	function setexp_tam_codean($value){
		$this->fields["exp_tam_codean"] = value_numeric($value);
	}

	function setexp_pos_descricao($value){
		$this->fields["exp_pos_descricao"] = value_numeric($value);
	}

	function setexp_tam_descricao($value){
		$this->fields["exp_tam_descricao"] = value_numeric($value);
	}

	function setexp_pos_preco($value){
		$this->fields["exp_pos_preco"] = value_numeric($value);
	}

	function setexp_tam_preco($value){
		$this->fields["exp_tam_preco"] = value_numeric($value);
	}

	function setexp_dec_preco($value){
		$this->fields["exp_dec_preco"] = value_numeric($value);
	}

	function setexp_pos_estoque($value){
		$this->fields["exp_pos_estoque"] = value_numeric($value);
	}

	function setexp_tam_estoque($value){
		$this->fields["exp_tam_estoque"] = value_numeric($value);
	}

	function setexp_dec_estoque($value){
		$this->fields["exp_dec_estoque"] = value_numeric($value);
	}

	function setimp_pos_codproduto($value){
		$this->fields["imp_pos_codproduto"] = value_numeric($value);
	}

	function setimp_tam_codproduto($value){
		$this->fields["imp_tam_codproduto"] = value_numeric($value);
	}

	function setimp_pos_codean($value){
		$this->fields["imp_pos_codean"] = value_numeric($value);
	}

	function setimp_tam_codean($value){
		$this->fields["imp_tam_codean"] = value_numeric($value);
	}

	function setimp_pos_quantidade($value){
		$this->fields["imp_pos_quantidade"] = value_numeric($value);
	}

	function setimp_tam_quantidade($value){
		$this->fields["imp_tam_quantidade"] = value_numeric($value);
	}

	function setimp_dec_quantidade($value){
		$this->fields["imp_dec_quantidade"] = value_numeric($value);
	}

	function setexp_nomearquivo($value){
		$this->fields["exp_nomearquivo"] = value_string($value,40);
	}

	function setimp_nomearquivo($value){
		$this->fields["imp_nomearquivo"] = value_string($value,40);
	}
	
	function setimp_separador($value){
		$this->fields["imp_separador"] = value_string($value,1);
	}
		
	function setimp_pos_preco($value){
		$this->fields["imp_pos_preco"] = value_numeric($value);
	}

	function setimp_tam_preco($value){
		$this->fields["imp_tam_preco"] = value_numeric($value);
	}
	
	function setimp_dec_preco($value){
		$this->fields["imp_dec_preco"] = value_numeric($value);
	}

}
?>