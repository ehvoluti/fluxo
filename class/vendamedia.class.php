<?php

require_file("class/dberror.class.php");

class VendaMedia{

	private $con;
	private $codestabelec;
	private $codproduto;
	private $dias_trabalhados;
	private $data_inicial;
	private $param_estoque_vendamedia;
	private $calcular; // Se deve calcular a venda media (parametro)

	function __construct($con){
		$this->con = $con;
		$this->param_estoque_vendamedia = param("ESTOQUE", "VENDAMEDIA", $this->con);
		$this->calcular = ($this->param_estoque_vendamedia > 0);
		if($this->calcular){
			$this->data_inicial = date("Y-m-d", mktime(0, 0, 0, date("m"), (date("d") - $this->param_estoque_vendamedia), date("Y")));
		}
	}

	function atualizar(){
		if(!$this->calcular){
			return TRUE;
		}
		if($this->dias_trabalhados > 0){
			$res = $this->con->query("SELECT (SUM(quantidade) / ".$this->dias_trabalhados.") FROM consvendadia WHERE codestabelec = ".$this->codestabelec." AND codproduto = ".$this->codproduto." AND dtmovto > '{$this->data_inicial}'");
			$venda_media = $res->fetchColumn();
			if(strlen($venda_media) == 0){
				$venda_media = 0;
			}
		}else{
			$venda_media = 0;
		}
		// Nao e o correto, mas na linha abaixo e executada um UPDATE direto no banco por questao de velocidade
		if($this->con->exec("UPDATE produtoestab SET vendamedia = ".$venda_media." WHERE codestabelec = ".$this->codestabelec." AND codproduto = ".$this->codproduto)){
			return TRUE;
		}else{
			$dberror = new DbError($this->con);
			$_SESSION["ERROR"] = $dberror->getmessage();
			return FALSE;
		}
	}

	function setcodestabelec($codestabelec){
		$this->codestabelec = $codestabelec;
		if($this->calcular){
			$res = $this->con->query("SELECT COUNT(*) FROM diastrabalhados WHERE codestabelec = ".$this->codestabelec." AND data > '{$this->data_inicial}'");
			$this->dias_trabalhados = $res->fetchColumn();
		}
	}

	function setcodproduto($codproduto){
		$this->codproduto = $codproduto;
	}

}
