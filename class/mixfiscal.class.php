<?php

require_once("websac/require_file.php");
require_file("def/function.php");

class MixFiscal{

	private $con;
	private $tipocontribuinte;
	private $arr_consulta;
	private $codproduto;

	function __construct(Connection $con){
		$this->con = $con;
	}

	function consultar(){
		$this->arr_consulta = array();

		// Gerais e PIS/Cofins
		if(strlen($this->codproduto) > 0){
			$filtro = " WHERE codigo_produto = {$this->codproduto}";
		}
		$res = $this->con->query("SELECT * FROM mxf_tmp_pis_cofins {$filtro}");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_consulta[$row["codigo_produto"]]["codproduto"] = $row["codigo_produto"];
			$this->arr_consulta[$row["codigo_produto"]]["codigoncm"] = $row["ncm"];
			$this->arr_consulta[$row["codigo_produto"]]["natreceita"] = $row["cod_natureza_receita"];
			$this->arr_consulta[$row["codigo_produto"]]["piscofinsent"] = array(
				"codcst" => $row["pis_cst_e"],
				"aliqpis" => $row["pis_alq_e"],
				"aliqcofins" => $row["cofins_alq_e"]
			);
			$this->arr_consulta[$row["codigo_produto"]]["piscofinssai"] = array(
				"codcst" => $row["pis_cst_s"],
				"aliqpis" => $row["pis_alq_s"],
				"aliqcofins" => $row["cofins_alq_s"]
			);
		}

		// Classificacao fiscal de entrada
		/*
		$res = $this->con->query("SELECT * FROM mxf_tmp_icms_entrada $filtro");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_consulta[$row["codigo_produto"]]["codproduto"] = $row["codigo_produto"];
			$this->arr_consulta[$row["codigo_produto"]]["classfiscalnfe"] = array(
				"codcst" => $row["ei_cst"],
				"aliqicms" => $row["ei_alq"],
				"aliqredicms" => $row["ei_rbc"],
				"aliqiva" => ($row["tipo_mva"] === "4" ? $row["mva"] : 0),
				"valorpauta" => 0 //($row["tipo_mva"] === "5" ? $row["mva"] : 0)
			);
		}
		*/

		// Classificacao fiscal de saida
		$res = $this->con->query("SELECT * FROM mxf_tmp_icms_saida {$filtro}");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_consulta[$row["codigo_produto"]]["codproduto"] = $row["codigo_produto"];
			$this->arr_consulta[$row["codigo_produto"]]["classfiscalnfs"] = array(
				"codcst" => $row["{$this->tipocontribuinte}_cst"],
				"aliqicms" => $row["{$this->tipocontribuinte}_alq"],
				"aliqredicms" => $row["{$this->tipocontribuinte}_rbc"],
				"aliqiva" => 0,
				"valorpauta" => 0,
				"csosn" => $row["snc_csosn"]
			);
			$this->arr_consulta[$row["codigo_produto"]]["classfiscalpdv"] = $this->arr_consulta[$row["codigo_produto"]]["classfiscalnfs"];
			if(in_array($this->arr_consulta[$row["codigo_produto"]]["classfiscalpdv"]["codcst"], array("10", "60", "70"))){
				$this->arr_consulta[$row["codigo_produto"]]["classfiscalpdv"]["aliqicms"] = 0;
				$this->arr_consulta[$row["codigo_produto"]]["classfiscalpdv"]["aliqredicms"] = 0;
			}
			$this->arr_consulta[$row["codigo_produto"]]["cest"] = $row["cest"];
			$this->arr_consulta[$row["codigo_produto"]]["aliqfcp"] = $row["fecp"];
		}

		return $this->arr_consulta;
	}

	function settipocontribuinte($tipocontribuinte){
		$this->tipocontribuinte = $tipocontribuinte;
	}

	function setcodproduto($codproduto){
		$this->codproduto = $codproduto;
	}
}
