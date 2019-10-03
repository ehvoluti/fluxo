<?php
class AtualizacaoFiscal{
	private $con;
	private $arr_produto = array();
	
	function __construct($con){
		$this->con = $con;
	}
	
	function addproduto($produto){
		if(is_object($produto)){
			$this->arr_produto[] = $produto;
		}
	}
}
?>