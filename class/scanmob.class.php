<?php

require_once("websac/require_file.php");
require_file("def/require_php.php");

class ScanMob{

	private $con;
	private $email;
	private $senha;
	private $server = "http://scanmob.com.br";
	private $server_subdir = "/serv/erp/";

	function __construct($con){
		$this->con = $con;

		$param = param("INTEGRACAO", "SCANMOB", $this->con);
		$param = explode(";", $param);
		$this->email = trim($param[0]);
		$this->senha = trim($param[1]);
	}

	function enviar_coleta_quantidade($object){
		$referencia = "";
		$descricao = "";
		$observacao = "";

		$arr_itlote = array();
		switch($object->gettablename()){
			case "inventario":
				$inventario = $object;

				$referencia = "I".$inventario->getcodinventario();
				$descricao = $inventario->getdescricao();

				$query = "SELECT produtoean.codean AS codproduto, produto.descricaofiscal AS descricao, ";
				$query .= "	produto.codproduto AS referencia ";
				$query .= "FROM produtoean ";
				$query .= "INNER JOIN produto ON (produtoean.codproduto = produto.codproduto) ";
				$query .= "INNER JOIN itinventario ON (produto.codproduto = itinventario.codproduto) ";
				$query .= "WHERE itinventario.codinventario = ".$inventario->getcodinventario()." ";
				$query .= "ORDER BY produto.codproduto, produtoean.codean ";

				$res = $this->con->query($query);
				$arr_itlote = $res->fetchAll(2);

				break;
		}

		// Ajusta a descricao dos itens do lote
		foreach($arr_itlote as $i => $itlote){
			$arr_itlote[$i]["descricao"] = str_replace("%", "", utf8_encode($itlote["descricao"]));
		}

		// Inclusao do lote
		$json_in = json_encode(array(
			"identificacao" => array(
				"email" => $this->email,
				"senha" => $this->senha
			),
			"lote" => array(
				array(
					"referencia" => $referencia,
					"tipo" => "0",
					"descricao" => $descricao,
					"observacao" => $observacao
				)
			)
		));
		$json_out = $this->webservice("lote_incluir", $json_in);
		if(!$this->verificar_erro($json_out)){
			return FALSE;
		}

		// Inclusao dos itens do lote
		$arr_arr_itlote = array_chunk($arr_itlote, 1000);
		foreach($arr_arr_itlote as $arr_itlote){
			$json_in = json_encode(array(
				"identificacao" => array(
					"email" => $this->email,
					"senha" => $this->senha
				),
				"referencia" => $referencia,
				"itlote" => $arr_itlote
			));
			$json_out = $this->webservice("itlote_incluir", $json_in);
			if(!$this->verificar_erro($json_out)){
				return FALSE;
			}
		}

		return TRUE;
	}

	function receber_coleta_quantidade($object){
		$referencia = "";

		$arr_itlote = array();
		switch($object->gettablename()){
			case "inventario":
				$referencia = "I".$object->getcodinventario();
				break;
		}


		$json_in = json_encode(array(
			"identificacao" => array(
				"email" => $this->email,
				"senha" => $this->senha
			),
			"referencia" => array($referencia)
		));

		$json_out = $this->webservice("itlote_listar", $json_in);

		if(isset($json_out["status"])){
			switch($json_out["status"]){
				case "0":
					$lote = array_shift($json_out["lote"]);
					if(is_array($lote)){
						return $lote["itlote"];
					}else{
						$_SESSION["ERROR"] = "O lote de refer&ecirc;ncia n&atilde;o p&ocirc;de ser encontrado no ScanMob.";
						return FALSE;
					}
					return $json_out["lote"][0];
					break;
				case "2":
					$_SESSION["ERROR"] = $json_out["erro"];
					return FALSE;
					break;
			}
		}else{
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel obter uma resposta do ScanMob.";
			return FALSE;
		}
	}

	private function verificar_erro($json){
		if(isset($json["status"])){
			if($json["status"] == "2"){
				$_SESSION["ERROR"] = $json["erro"];
				return FALSE;
			}
		}else{
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel obter uma resposta do ScanMob.";
			return FALSE;
		}
		return TRUE;
	}

	private function webservice($service, $json){
		$ch = curl_init($this->server.$this->server_subdir.$service.".php");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		$json = curl_exec($ch);
		curl_close($ch);

		return json_decode($json, TRUE);
	}

}