<?php

final class ConsultaProduto{

	private $con;
	private $arr_codproduto_verificar = array();
	private $arr_codproduto_encontrado = array();
	private $arr_codproduto_naoencontrado = array();
	private $priopesqproduto;

	function __construct(Connection $con, $priopesqproduto = null){
		$this->con = $con;
		//$this->param_sistema_pdvimpproduto = param("SISTEMA", "PDVIMPPRODUTO", $this->con);
		if(strlen($priopesqproduto) > 0){
			$this->priopesqproduto = $priopesqproduto;
		}else{
			$this->priopesqproduto = param("SISTEMA", "PRIOPESQPRODUTO", $this->con);
		}		
	}

	function addcodproduto($codproduto){
		if(!is_array($codproduto)){
			$codproduto = array($codproduto);
		}
		$this->arr_codproduto_verificar = array_merge($this->arr_codproduto_verificar, $codproduto);
	}

	function consultar($consultar_tabela_produto = TRUE, $consultar_tabela_produtoean = TRUE){
		$this->arr_codproduto_verificar = array_unique($this->arr_codproduto_verificar);

		if($this->priopesqproduto === "0"){
			if($consultar_tabela_produto){
				$this->consultar_codproduto();
			}
			if($consultar_tabela_produtoean){
				$this->consultar_codean();
			}
		}else{
			if($consultar_tabela_produtoean){
				$this->consultar_codean();
			}
			if($consultar_tabela_produto){
				$this->consultar_codproduto();
			}
		}

		$this->arr_codproduto_naoencontrado = array_merge($this->arr_codproduto_verificar);
	}

	private function consultar_codean(){

		// Consulta com a zeros a esquerda
		$arr_codproduto = array();
		foreach($this->arr_codproduto_verificar as $codproduto){
			$arr_codproduto[] = "'{$codproduto}'";
		}

		if(count($arr_codproduto) === 0){
			return TRUE;
		}

		$query = "SELECT codproduto, codean FROM produtoean WHERE codean IN (".implode(", ", $arr_codproduto).")";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $row){
			$i = array_search($row["codean"], $this->arr_codproduto_verificar);
			if($i !== FALSE){
				$this->arr_codproduto_encontrado[$this->arr_codproduto_verificar[$i]] = $row["codproduto"];
				unset($this->arr_codproduto_verificar[$i]);
			}
		}


		// Consulta sem zeros a esquerda
		$arr_codproduto = array();
		foreach($this->arr_codproduto_verificar as $codproduto){
			$arr_codproduto[] = "'".ltrim($codproduto, "0")."'";
		}

		if(count($arr_codproduto) === 0){
			return TRUE;
		}

		$query = "SELECT codproduto, codean FROM produtoean WHERE LTRIM(codean, '0') IN (".implode(", ", $arr_codproduto).")";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $row){
			foreach($this->arr_codproduto_verificar as $i => $codproduto){
				if(ltrim($codproduto, "0") == ltrim($row["codean"], "0")){
					$this->arr_codproduto_encontrado[$codproduto] = $row["codproduto"];
					unset($this->arr_codproduto_verificar[$i]);
				}
			}
		}

		return TRUE;
	}

	private function consultar_codproduto(){
		$arr_codproduto = array();
		foreach($this->arr_codproduto_verificar as $codproduto){
			$arr_codproduto[] = ltrim((int)$codproduto, "0");
		}

		$arr_codproduto = array_filter($arr_codproduto);

		if(count($arr_codproduto) === 0){
			return TRUE;
		}

		$query = "SELECT codproduto FROM produto WHERE codproduto IN (".implode(", ", $arr_codproduto).")";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $row){
			$i = array_search($row["codproduto"], $this->arr_codproduto_verificar);
			if($i !== FALSE){
				$this->arr_codproduto_encontrado[$this->arr_codproduto_verificar[$i]] = $row["codproduto"];
				unset($this->arr_codproduto_verificar[$i]);
			}
		}

		return TRUE;
	}

	function getencontrado(){
		return $this->arr_codproduto_encontrado;
	}

	function getnaoencontrado(){
		return $this->arr_codproduto_naoencontrado;
	}

}