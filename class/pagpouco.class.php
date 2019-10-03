<?php

class PagPouco{

	private $con;
	private $log;
	private $url = "http://api.pagpouco.com/v1/inclusao-preco/";
	private $senha;
	private $parametro;
	private $codestabelec;
	private $estabelecimento;
	private $arr_codproduto;
	private $dthrsinc;
	private $primeirasincronizacao;

	function __construct(Connection $con){
		$this->con = $con;
		$this->arr_codproduto = array();

		$this->log = new Log("pagpouco");
		$this->log->clear();

		$this->parametro = objectbytable("parametro", array("INTEGRACAO", "PAGPOUCO"), $this->con);
		$param = parse_ini_string($this->parametro->getvalor());
		$this->setcodestabelec($param["CODESTABELEC"]);
		$this->senha = $param["SENHA"];
		$this->dthrsinc = $param["DTHRSINC"];

		$this->primeirasincronizacao = strlen(trim($this->dthrsinc) === 0);
	}

	function addcodproduto($codproduto){
		$this->arr_codproduto[] = $codproduto;
	}

	private function carregar_ultimos_produtos(){
		if(strlen(trim($this->dthrsinc)) > 0){
			$query = implode("", array(
				"SELECT DISTINCT codproduto ",
				"FROM logpreco ",
				"WHERE codestabelec = {$this->codestabelec} ",
				"  AND tipo = 'PV' ",
				"  AND (data||' '||hora)::TIMESTAMP >= '{$this->dthrsinc}' "
			));
		}else{
			$query = "SELECT codproduto FROM produto";
		}
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_codproduto[] = $row["codproduto"];
		}
	}

	private function parametro_atualizar($nome, $valor){
		$param = parse_ini_string($this->parametro->getvalor());
		$param[$nome] = $valor;
		$arr_ini = array();
		foreach($param as $name => $value){
			$arr_ini[] = $name." = ".$value;
		}
		$this->parametro->setvalor(implode("\n", $arr_ini));
		return $this->parametro->save();
	}

	private function service($data){
		// Registra log de envio
		$this->log->write("Enviando dados: ".json_encode($data));

		// Executa o webservice
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);

		// Registra log de resposta
		$this->log->write("Dados recebidos: ".json_encode($result));

		// Verifica se retornou com sucesso
		$success = (strpos($result, "sucesso") !== false);
		if($success){
			return true;
		}else{
			$_SESSION["ERROR"] = $result;
			return false;
		}
	}

	function setcodestabelec($codestabelec){
		$this->codestabelec = $codestabelec;
	}

	function sincronizar(){
		// Registra log
		$this->log->write("Iniciando processo.");

		// Carrega o estabelecimento
		$this->estabelecimento = objectbytable("estabelecimento", $this->codestabelec, $this->con);

		// Carrega os produtos alterados caso a lista de codigos esteja vazia
		if(count($this->arr_codproduto) === 0){
			$this->carregar_ultimos_produtos();
		}

		// Verifica se existe algum produto para sincronizar
		if(count($this->arr_codproduto) === 0){
			return true;
		}

		if(strlen($this->dthrsinc) > 0){
			$query_dthrsinc = "SELECT SUBSTR((data||' '||hora), 1, 19) FROM logpreco WHERE codestabelec = produtoestab.codestabelec AND codproduto = produtoestab.codproduto AND tipo = 'PV' AND (data||' '||hora)::TIMESTAMP >= '{$this->dthrsinc}' ORDER BY data, hora LIMIT 1";
		}else{
			$query_dthrsinc = "SELECT SUBSTR((data||' '||hora), 1, 19) FROM logpreco WHERE codestabelec = produtoestab.codestabelec AND codproduto = produtoestab.codproduto AND tipo = 'PV' ORDER BY data, hora LIMIT 1";
		}

		// Carrega os produtos
		$query = implode("", array(
			"SELECT *, ",
			"	(CASE WHEN codean IS NULL OR LENGTH(LTRIM(codean, '0')) < 8 THEN 'S' ELSE 'N' END) AS granel ",
			"FROM ( ",
			"	SELECT produto.codproduto, ",
			"		(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto ORDER BY LENGTH(LTRIM(codean, '0')) DESC LIMIT 1), ",
			"		produtoestab.precovrj, ",
			"		(SELECT EXTRACT(EPOCH FROM (data||' '||hora)::TIMESTAMP)::INT FROM logpreco WHERE codestabelec = produtoestab.codestabelec AND codproduto = produtoestab.codproduto AND tipo = 'PV' ORDER BY data DESC, hora DESC LIMIT 1) AS atualizacao, ",
			"		({$query_dthrsinc}) AS dthrsinc ",
			"	FROM produtoestab ",
			"	INNER JOIN produto USING (codproduto) ",
			"	WHERE produtoestab.codestabelec = {$this->codestabelec} ",
			"		AND produtoestab.codproduto IN (".implode(", ", $this->arr_codproduto).") ",
			"		AND produtoestab.precovrj > 0 ",
			"		AND produtoestab.disponivel = 'S' ",
			"		AND produto.foralinha = 'N' ",
			"	ORDER BY dthrsinc ",
			") AS produto "
		));
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		// Grava no log o total de produtos a serem sincronizados
		$this->log->write("Total de produtos a serem sincronizados: ".count($arr));

		// Percorre todos os produtos
		foreach($arr as $row){
			$data = array(
				"SENHA" => $this->senha, // Senha do parceiro
				"CODIGO[]" => ($row["granel"] === "S" ? $row["codproduto"] : $row["codean"]), // Codigo de barras do produto, quando for de balanca deve informar PLU
				"CNPJ[]" => removeformat($this->estabelecimento->getcpfcnpj()), // CNPJ do estabelecimento
				"PRECO[]" => $row["precovrj"], // Preco atual do produto
				"ATUALIZACAO[]" => (true || $this->primeirasincronizacao ? time() : $row["atualizacao"]), // Data de hora da atualizacao de preco
				"GRANEL[]" => ($row["granel"] === "S" ? "1" : "0") // 0: produto com codigo de barras, 1: produto sem codigo de barras
			);
			if(!$this->service($data)){
				return false;
			}
			$this->parametro_atualizar("DTHRSINC", $row["dthrsinc"]);
		}

		// Retorna sucesso
		$this->log->write("Sincronizacao finalizada com sucesso!");
		return true;
	}

}