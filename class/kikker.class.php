<?php

require_once("websac/require_file.php");
require_file("def/function.php");

class Kikker{

	private $con;
	private $dtmovto;
	private $arr_codestabelec;
	private $arr_codproduto;
	private $zipname;
	private $dirname_root;
	private $dirname_pedidos;
	private $dirname_processados;
	private $dirname_processar;

	function __construct(Connection $con){
		$this->con = $con;
		$this->dtinicial = null;
		$this->dtfinal = null;
		$this->arr_codestabelec = array();
		$this->arr_codproduto = array();

		$this->dirname_root = __DIR__."/../temp/kikker/";
		$this->dirname_pedidos = $this->dirname_root."/kikker_pedidos/";
		$this->dirname_processados = $this->dirname_root."/kikker_uploaded/";
		$this->dirname_processar = $this->dirname_root."/kikker_processar/";

		$this->preparar_diretorio();
	}

	public function addcodestabelec($codestabelec){
		if(!is_array($codestabelec)){
			$codestabelec = array($codestabelec);
		}
		$this->arr_codestabelec = array_merge($this->arr_codestabelec, $codestabelec);
	}

	public function addcodproduto($codproduto){
		if(!is_array($codproduto)){
			$codproduto = array($codproduto);
		}
		$this->arr_codproduto = array_merge($this->arr_codproduto, $codproduto);
	}

	public function carregar_produtos(){
		$query = "SELECT codproduto FROM produto WHERE foralinha = 'N'";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->addcodproduto($row["codproduto"]);
		}
		return true;
	}

	private function compactar_arquivos(){
		if(!$this->verificar_dtmovto() || !$this->verificar_codestabelec()){
			return false;
		}

		$estabelecimento = objectbytable("estabelecimento", $this->arr_codestabelec[0], $this->con);
		$estabelecimento_nome = str_replace(" ", "_", strtolower($estabelecimento->getnome()));

		$dtmovto = convert_date($this->dtmovto, "Y-m-d", "Ymd");
		$this->zipname = "{$this->dirname_processar}/{$dtmovto}_cw_{$estabelecimento_nome}.zip";

		if(is_file($this->zipname)){
			unlink($this->zipname);
		}

		try{
			$zip = new ZipArchive();
			$zip->open($this->zipname, ZIPARCHIVE::CREATE);

			$arr_filename = glob($this->dirname_processar."/*.csv");
			foreach($arr_filename as $filename){
				$zip->addFile($filename, basename($filename));
			}
			$zip->close();
		}catch(Exception $ex){
			$_SESSION["ERROR"] = $ex->getMessage();
			return false;
		}

		foreach($arr_filename as $filename){
			unlink($filename);
		}

		chmod($this->zipname, 0777);

		return true;
	}

	private function criar_arquivo($tabela, $dados){
		$filename = "{$this->dirname_processar}/{$tabela}.csv";
		$file = fopen($filename, "w+");
		foreach($dados as $i => $dado){
			// Escreve o cabecalho se for a primeira linha do arquivo
			if($i === 0){
				$arr_cabecalho = array_keys($dado);
				fwrite($file, implode(";", $arr_cabecalho)."\r\n");
			}
			// Escreve os valores
			fwrite($file, implode(";", $dado)."\r\n");
		}
		fclose($file);
		chmod($filename, 0777);
		return true;
	}

	private function criar_diretorio($dirname){
		if(!is_dir($dirname)){
			mkdir($dirname);
		}
		chmod($dirname, 0777);
	}

	public function enviar(){
		if(!$this->compactar_arquivos()){
			return false;
		}

		return true;
	}

	public function gerar_estoque(){
		if(!$this->verificar_dtmovto() || !$this->verificar_codestabelec() || !$this->verificar_arr_codproduto()){
			return false;
		}

		$query = "SELECT DISTINCT produtoestab.codestabelec, produtoestab.codproduto, produtoestab.custorep, ";
		$query .= "	produtoestab.precovrj, produtoestab.preventrada, produtoestab.prevsaida, ";
		$query .= "	saldo(produtoestab.codestabelec, produtoestab.codproduto, '{$this->dtmovto}') AS saldo ";
		$query .= "FROM produtoestab ";
		$query .= "WHERE produtoestab.codestabelec IN (".implode(", ", $this->arr_codestabelec).") ";
		$query .= "	AND produtoestab.codproduto IN (".implode(", ", $this->arr_codproduto).") ";
		$query .= "ORDER BY produtoestab.codestabelec, produtoestab.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_estoque = array();

		foreach($arr as $row){
			$dados_estoque[] = array(
				"COD_PRODUTO" => $row["codproduto"],
				"COD_LOJA" => $row["codestabelec"],
				"DATA_ESTOQUE" => $this->dtmovto,
				"ESTOQUE_ATUAL" => round($row["saldo"], 4),
				"PENDENCIA_VENDA" => round($row["prevsaida"], 4),
				"PEDIDO_PENDENTE" => round($row["preventrada"], 4),
				"CUSTO_UNIT_ULT_ENTRADA" => round($row["custorep"], 2),
				"PRECO_UNIT_VENDA" => round($row["precovrj"], 2)
			);
		}

		if(!$this->criar_arquivo("estoque", $dados_estoque)){
			return false;
		}

		return true;
	}

	public function gerar_fornecedor(){
		if(!$this->verificar_codestabelec() || !$this->verificar_arr_codproduto()){
			return false;
		}

		$query = "SELECT DISTINCT prodfornec.codproduto, prodfornec.codfornec, fornecedor.nome AS fornecedor, ";
		$query .= "  fornecestab.faturamentominimo, fornecestab.codestabelec, fornecestab.diasentrega ";
		$query .= "FROM prodfornec ";
		$query .= "INNER JOIN fornecedor ON (prodfornec.codfornec = fornecedor.codfornec) ";
		$query .= "INNER JOIN fornecestab ON (fornecedor.codfornec = fornecestab.codfornec) ";
		$query .= "WHERE fornecestab.codestabelec IN (".implode(", ", $this->arr_codestabelec).") ";
		$query .= "  AND prodfornec.codproduto IN (".implode(", ", $this->arr_codproduto).") ";
		$query .= "  AND prodfornec.principal = 'S'";
		$query .= "ORDER BY prodfornec.codproduto, prodfornec.codfornec ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_fornecedor = array();

		foreach($arr as $row){
			$dados_fornecedor[] = array(
				"COD_PRODUTO" => $row["codproduto"],
				"COD_FORNECEDOR" => $row["codfornec"],
				"COD_LOJA" => $row["codestabelec"],
				"NOME_FORNECEDOR" => $row["fornecedor"],
				"PRAZO_ENTREGA" => $row["diasentrega"],
				"PEDIDO_MIN" => round($row["faturamentominimo"], 2)
			);
		}

		if(!$this->criar_arquivo("fornecedor", $dados_fornecedor)){
			return false;
		}

		return true;
	}

	public function gerar_mercadologico(){
		$query = "SELECT DISTINCT departamento.coddepto, departamento.nome AS departamento, ";
		$query .= "	grupoprod.codgrupo, grupoprod.descricao AS grupoprod, ";
		$query .= "	subgrupo.codsubgrupo, subgrupo.descricao AS subgrupo ";
		$query .= "FROM subgrupo ";
		$query .= "INNER JOIN grupoprod USING (codgrupo) ";
		$query .= "INNER JOIN departamento USING (coddepto) ";
		$query .= "ORDER BY departamento.coddepto, grupoprod.codgrupo, subgrupo.codsubgrupo ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_mercadologico = array();

		foreach($arr as $row){
			$dados_mercadologico[] = array(
				"COD_ERP_DEPARTAMENTO" => "1",
				"NOME_DEPARTAMENTO" => "GERAL",
				"COD_ERP_SETOR" => $row["coddepto"],
				"NOME_SETOR" => $row["departamento"],
				"COD_ERP_GRUPO" => $row["codgrupo"],
				"NOME_GRUPO" => $row["grupoprod"],
				"COD_ERP_SUBGRUPO" => $row["codsubgrupo"],
				"NOME_SUBGRUPO" => $row["subgrupo"]
			);
		}

		if(!$this->criar_arquivo("mercadologico", $dados_mercadologico)){
			return false;
		}

		return true;
	}

	public function gerar_movimento(){
		if(!$this->verificar_dtmovto() || !$this->verificar_codestabelec() || !$this->verificar_arr_codproduto()){
			return false;
		}

		$query = "SELECT movimento.codestabelec, movimento.codproduto, movimento.quantidade, movimento.qtdeunidade, ";
		$query .= "	movimento.preco AS precovrj, movimento.custorep, movimento.codtpdocto, movimento.dtmovto, ";
		$query .= "	movimento.tipo ";
		$query .= "FROM movimento ";
		$query .= "WHERE movimento.dtmovto = '{$this->dtmovto}' ";
		$query .= "	AND movimento.codestabelec IN (".implode(", ", $this->arr_codestabelec).") ";
		$query .= "	AND movimento.codproduto IN (".implode(", ", $this->arr_codproduto).") ";
		$query .= "ORDER BY movimento.dtmovto, movimento.hrmovto, movimento.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_movimento = array();

		foreach($arr as $row){
			$dados_movimento[] = array(
				"COD_PRODUTO" => $row["codproduto"],
				"COD_LOJA" => $row["codestabelec"],
				"DATA" => $row["dtmovto"],
				"CODIGO_MOVIMENTO" => $row["codtpdocto"],
				"QTDE" => round(($row["quantidade"] * $row["qtdeunidade"]), 4),
				"VALOR_UNIT" => round(($row["tipo"] === "E" ? $row["custorep"] : $row["precovrj"]), 2),
				"VALOR_ICMS_UNIT" => 0,
				"PIS" => 0
			);
		}

		if(!$this->criar_arquivo("movimento", $dados_movimento)){
			return false;
		}

		return true;
	}

	public function gerar_pedido(){
		// Carrega o parametro com os valores padroes
		$ini = parse_ini_string(param("INTEGRACAO", "KIKKER", $this->con));
		$codespecie_def = $ini["CODESPECIE"];
		$codcondpagto_def = $ini["CODCONDPAGTO"];
		if(strlen($codespecie_def) === 0 ){
			$_SESSION["ERROR"] = "Informe o código da forma de pagamento no parâmetro referente a integração com o Kikker.";
			return false;
		}
		if(strlen($codcondpagto_def) === 0 ){
			$_SESSION["ERROR"] = "Informe o código da condição de pagamento no parâmetro referente a integração com o Kikker.";
			return false;
		}

		// Carrega o conteudo do arquivo
		$filename = $this->dirname_pedidos.date("Ymd")."_pedidos.csv";
		if(!file_exists($filename)){
			return true;
		}
		$arr_linha = file($filename);
		array_shift($arr_linha);

		// Carrega os dados necessarios do arquivo
		$arr_dados = array();
		foreach($arr_linha as $linha){
			$arr_valor = explode(";", $linha);

			$arr_dados[] = array(
				"dtemissao" => $arr_valor[0],
				"codfornec" => $arr_valor[1],
				"codestabelec" => $arr_valor[3],
				"codproduto" => $arr_valor[4],
				"quantidade" => $arr_valor[6],
				"dtentrega" => $arr_valor[12]
			);
		}

		if(count($arr_dados) === 0){
			return true;
		}

		// Remove os produtos com quantidade zero
		foreach($arr_dados as $i => $dados){
			if((float) $dados["quantidade"] === 0){
				unset($arr_dados[$i]);
			}
		}

		// Remove os pedidos ja existentes
		$arr_where_pedido = array();
		foreach($arr_dados as $dados){
			$arr_where_pedido[] = "({$dados["codestabelec"]}, {$dados["codfornec"]}, '{$dados["dtemissao"]}')";
		}
		$arr_where_pedido = array_unique($arr_where_pedido);
		$query = "SELECT codestabelec, codparceiro AS codfornec FROM pedido WHERE operacao = 'CP' AND (codestabelec, codparceiro, dtemissao) IN (".implode(", ", $arr_where_pedido).")";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			foreach($arr_dados as $i => $dados){
				if($row["codestabelec"] === $dados["codestabelec"] && $row["codfornec"] === $dados["codfornec"]){
					unset($arr_dados[$i]);
				}
			}
		}
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega os estabelecimentos
		$arr_codestabelec = array();
		foreach($arr_dados as $dados){
			$arr_codestabelec[] = $dados["codestabelec"];
		}
		$arr_estabelecimento = object_array_key(objectbytable("estabelecimento", null, $this->con), $arr_codestabelec);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega os fornecedores
		$arr_codfornec = array();
		foreach($arr_dados as $dados){
			$arr_codfornec[] = $dados["codfornec"];
		}
		$arr_fornecedor = object_array_key(objectbytable("fornecedor", null, $this->con), $arr_codfornec);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega as naturezas de operacao
		$arr_natoperacao = array("1.102", "2.202");
		foreach($arr_fornecedor as $fornecedor){
			if(strlen($fornecedor->getnatoperacao()) > 0){
				$arr_natoperacao[] = $fornecedor->getnatoperacao();
			}
		}
		$arr_natoperacao = object_array_key(objectbytable("natoperacao", null, $this->con), $arr_natoperacao);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega os produtos
		$arr_codproduto = array();
		foreach($arr_dados as $dados){
			$arr_codproduto[] = $dados["codproduto"];
		}
		$arr_produto = object_array_key(objectbytable("produto", null, $this->con), $arr_codproduto);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega as embalagens de compra
		$arr_embalagem = array();
		foreach($arr_produto as $produto){
			$arr_codembal[] = $produto->getcodembalcpa();
		}
		$arr_embalagem = object_array_key(objectbytable("embalagem", null, $this->con), $arr_codembal);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega os dados dos produtos por estabelecimento
		$arr_keyprodutoestab = array();
		foreach($arr_dados as $dados){
			$arr_keyprodutoestab[] = array($dados["codestabelec"], $dados["codproduto"]);
		}
		$arr_produtoestab = object_array_key(objectbytable("produtoestab", null, $this->con), $arr_keyprodutoestab);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega as classificacoes fiscais
		$arr_codcf = array();
		foreach($arr_produto as $produto){
			$arr_codcf[] = $produto->getcodcfnfe();
			$arr_codcf[] = $produto->getcodcfnfs();
		}
		$arr_classfiscal = object_array_key(objectbytable("classfiscal", null, $this->con), $arr_codcf);
echo __LINE__."<br>"; flush(); ob_flush();
		// Pre carrega os PIS/Cofins
		$arr_codpiscofins = array();
		foreach($arr_produto as $produto){
			$arr_codpiscofins[] = $produto->getcodpiscofinsent();
		}
		$arr_piscofins = object_array_key(objectbytable("piscofins", null, $this->con), $arr_codpiscofins);
echo __LINE__."<br>"; flush(); ob_flush();
		// Organiza os dados para gerar os pedidos
		$arr_dados_aux = array();
		foreach($arr_dados as $dados){
			$key = $dados["codestabelec"].";".$dados["codfornec"].";".$dados["dtemissao"];
			if(!isset($arr_dados_aux[$key])){
				$arr_dados_aux[$key] = array(
					"codestabelec" => $dados["codestabelec"],
					"codfornec" => $dados["codfornec"],
					"dtemissao" => $dados["dtemissao"],
					"dtentrega" => $dados["dtentrega"],
					"item" => array()
				);
			}
			$arr_dados_aux[$key]["item"][] = array(
				"codproduto" => $dados["codproduto"],
				"quantidade" => $dados["quantidade"]
			);
		}
		$arr_dados = $arr_dados_aux;
echo __LINE__."<br>"; flush(); ob_flush();
		// Operacao da nota fiscal
		$operacaonota = objectbytable("operacaonota", "CP", $this->con);

		// Inicia um transacao no banco de dados
		$this->con->start_transaction();
echo __LINE__."<br>"; flush(); ob_flush();
		// Percorre os dados para criar os pedidos
		foreach($arr_dados as $dados){
			$estabelecimento = $arr_estabelecimento[$dados["codestabelec"]];
			$fornecedor = $arr_fornecedor[$dados["codfornec"]];

			// Verifica a natureza de operacao
			if(false && strlen($fornecedor->getnatoperacao()) > 0){
				$natoperacao = $fornecedor->getnatoperacao();
			}else{
				if($estabelecimento->getuf() === $fornecedor->getuf()){
					$natoperacao = "1.102";
				}else{
					$natoperacao = "2.102";
				}
			}
			$natoperacao = $arr_natoperacao[$natoperacao];

			// Monta o pedido de compras
			$pedido = objectbytable("pedido", null, $this->con);
			$pedido->setoperacao("CP");
			$pedido->setstatus("P");
			$pedido->setcodestabelec($estabelecimento->getcodestabelec());
			$pedido->settipoparceiro("F");
			$pedido->setcodparceiro($fornecedor->getcodfornec());
			$pedido->setnatoperacao($natoperacao->getnatoperacao());
			$pedido->setdtemissao($dados["dtemissao"]);
			$pedido->setdtentrega($dados["dtentrega"]);
			$pedido->setbonificacao("N");
			$pedido->setcodespecie(strlen($fornecedor->getcodespecie()) > 0 ? $fornecedor->getcodespecie() : $codespecie_def);
			$pedido->setcodcondpagto(strlen($fornecedor->getcodcondpagto()) > 0 ? $fornecedor->getcodcondpagto() : $codcondpagto_def);

			// Classe usada para capturar as tributacoes dos produtos
			$tributacaoproduto = new TributacaoProduto($this->con);
			$tributacaoproduto->setoperacaonota($operacaonota);
			$tributacaoproduto->setestabelecimento($estabelecimento);
			$tributacaoproduto->setnatoperacao($natoperacao);
			$tributacaoproduto->setparceiro($fornecedor);

			// Classe que calcula os totais dos itens
			$itemcalculo = new ItemCalculo($this->con);
			$itemcalculo->setestabelecimento($estabelecimento);
			$itemcalculo->setoperacaonota($operacaonota);
			$itemcalculo->setnatoperacao($natoperacao);
			$itemcalculo->setparceiro($fornecedor);

			// Cria os itens do pedido
			$arr_itpedido = array();
			$seqitem = 1;
			foreach($dados["item"] as $item){
				// Carrega os objetos relacionados aos produtos
				$produto = $arr_produto[$item["codproduto"]];
				$produtoestab = $arr_produtoestab["{$estabelecimento->getcodestabelec()};{$produto->getcodproduto()}"];
				$embalagem = $arr_embalagem[$produto->getcodembalcpa()];
				$classfiscalnfe = $arr_classfiscal[$produto->getcodcfnfe()];
				$classfiscalnfs = $arr_classfiscal[$produto->getcodcfnfs()];
				$piscofins = $arr_piscofins[$produto->getcodpiscofinsent()];

				// Busca os dados tributarios do produto
				$tributacaoproduto->setproduto($produto);
				$tributacaoproduto->buscar_dados();

				// Monta o item do pedido
				$itpedido = objectbytable("itpedido", null, $this->con);
				$itpedido->setseqitem($seqitem++);
				$itpedido->setcodproduto($item["codproduto"]);
				$itpedido->setquantidade($item["quantidade"]);
				$itpedido->setcodunidade($embalagem->getcodunidade());
				$itpedido->setqtdeunidade($embalagem->getquantidade());
				$itpedido->setpreco($produtoestab->getcustotab());
				$itpedido->settipoipi($tributacaoproduto->gettipoipi());
				$itpedido->setvalipi($tributacaoproduto->getvalipi());
				$itpedido->setpercipi($tributacaoproduto->getpercipi());
				$itpedido->settptribicms($tributacaoproduto->gettptribicms());
				$itpedido->setredicms($tributacaoproduto->getredicms());
				$itpedido->setaliqicms($tributacaoproduto->getaliqicms());
				$itpedido->setaliqiva($tributacaoproduto->getaliqiva());
				$itpedido->setvalorpauta($tributacaoproduto->getvalorpauta());
				$itpedido->setaliqpis($piscofins->getaliqpis());
				$itpedido->setaliqcofins($piscofins->getaliqcofins());
				$itpedido->setnatoperacao($tributacaoproduto->getnatoperacao()->getnatoperacao());
				$itpedido->setaliqicmsinter(0);
				$itpedido->setaliqfcpufdest(0);
				$itpedido->setaliqicmsufdest(0);
				$itpedido->setaliqicminterpart(0);

				// Calcula os totais do item
				$itemcalculo->setitem($itpedido);
				$itemcalculo->setclassfiscalnfe($classfiscalnfe);
				$itemcalculo->setclassfiscalnfs($classfiscalnfs);
				$itemcalculo->calcular();

				// Adiciona o item na lista
				$arr_itpedido[] = $itpedido;
			}

			// Inclui os itens no pedido
			$pedido->flag_itpedido(true);
			$pedido->itpedido = $arr_itpedido;

			// Calcula os totais dos pedidos
			$pedido->calcular_totais();

			// Grava o pedido
			if(!$pedido->save()){
				pre($dados);
				pre($pedido);
				$this->con->rollback();
				return false;
			}
		}
echo __LINE__."<br>"; flush(); ob_flush();
		// Apaga o arquivo importado
		unlink($filename);

		// Finaliza o processo com sucesso
		$this->con->commit();
		return true;
	}

	public function gerar_produto(){
		if(!$this->verificar_arr_codproduto()){
			return false;
		}

		$query = "SELECT DISTINCT produto.codproduto, produto.descricaofiscal, departamento.coddepto, grupoprod.codgrupo, ";
		$query .= "	subgrupo.codsubgrupo, unidadecpa.sigla AS unidadecpa, embalagemcpa.quantidade AS embalagemcpa, ";
		$query .= "	unidadevda.sigla AS unidadevda, embalagemvda.quantidade AS embalagemvda, produto.diasvalidade, ";
		$query .= "	(SELECT codfornec FROM prodfornec WHERE codproduto = produto.codproduto AND principal = 'S' LIMIT 1) AS codfornec, ";
		$query .= "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codean, ";
		$query .= "	(CASE ";
		$query .= "		WHEN (SELECT COUNT(codproduto) FROM composicao WHERE tipo IN ('P', 'V') AND codproduto = produto.codproduto) > 0 THEN 3 ";
		$query .= "		WHEN (SELECT COUNT(itcomposicao.codproduto) FROM itcomposicao INNER JOIN composicao USING (codcomposicao) WHERE composicao.tipo IN ('P', 'V') AND itcomposicao.codproduto = produto.codproduto) > 0 THEN 2 ";
		$query .= "		ELSE 1 ";
		$query .= "	END) AS classificacao ";
		$query .= "FROM produto ";
		$query .= "LEFT JOIN departamento USING (coddepto) ";
		$query .= "LEFT JOIN grupoprod USING (codgrupo) ";
		$query .= "LEFT JOIN subgrupo USING (codsubgrupo) ";
		$query .= "LEFT JOIN embalagem AS embalagemcpa ON (produto.codembalcpa = embalagemcpa.codembal) ";
		$query .= "LEFT JOIN unidade AS unidadecpa ON (embalagemcpa.codunidade = unidadecpa.codunidade) ";
		$query .= "LEFT JOIN embalagem AS embalagemvda ON (produto.codembalvda = embalagemvda.codembal) ";
		$query .= "LEFT JOIN unidade AS unidadevda ON (embalagemvda.codunidade = unidadevda.codunidade) ";
		$query .= "WHERE codproduto IN (".implode(", ", $this->arr_codproduto).") ";
		$query .= "ORDER BY produto.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_produto = array();

		foreach($arr as $row){
			$dados_produto[] = array(
				"COD_ERP" => $row["codproduto"],
				"DESCRICAO" => $row["descricaofiscal"],
				"COD_ERP_DEPARTAMENTO" => 1,
				"COD_ERP_SETOR" => $row["coddepto"],
				"COD_ERP_GRUPO" => $row["codgrupo"],
				"COD_ERP_SUB_GRUPO" => $row["codsubgrupo"],
				"UNIDADE_DE_VENDA" => $row["unidadevda"],
				"QTDE_VENDA" => $row["embalagemvda"],
				"UNIDADE_DE_COMPRA" => $row["unidadecpa"],
				"QTDE_COMPRA" => $row["embalagemcpa"],
				"CLASSIFICACAO" => $row["classificacao"],
				"PRAZO_VALIDADE" => $row["diasvalidade"],
				"PRAZO_ACEITACAO" => NULL,
				"PRAZO_RETIRADA_GONDOLA" => NULL,
				"COD_FORNECEDOR" => $row["codfornec"],
				"EAN" => $row["codean"]
			);
		}

		if(!$this->criar_arquivo("produtos", $dados_produto)){
			return false;
		}

		return true;
	}

	public function gerar_produto_loja(){
		if(!$this->verificar_codestabelec() || !$this->verificar_arr_codproduto()){
			return false;
		}

		$query = "SELECT DISTINCT produtoestab.codestabelec, produtoestab.codproduto, produtoestab.disponivel, produto.foralinha, ";
		$query .= "	produto.datainclusao, produto.dtforalinha, unidade.sigla AS unidade, embalagem.quantidade AS embalagem ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "LEFT JOIN embalagem ON (produto.codembalcpa = embalagem.codembal) ";
		$query .= "LEFT JOIN unidade USING (codunidade) ";
		$query .= "WHERE produtoestab.codestabelec IN (".implode(", ", $this->arr_codestabelec).") ";
		$query .= "	AND produtoestab.codproduto IN (".implode(", ", $this->arr_codproduto).") ";
		$query .= "ORDER BY produtoestab.codestabelec, produtoestab.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_produto_loja = array();

		foreach($arr as $row){
			$dados_produto_loja[] = array(
				"COD_LOJA" => $row["codestabelec"],
				"COD_PRODUTO" => $row["codproduto"],
				"ABASTECIMENTO" => NULL,
				"COD_CD" => NULL,
				"LINHA" => ($row["disponivel"] === "S" && $row["foralinha"] === "N"),
				"DATA_CADASTRO" => $row["datainclusao"],
				"DATA_SAIU_LINHA" => $row["dtforalinha"],
				"UNID_TRANSFERENCIA" => $row["unidade"],
				"QTDE_UNID_TRANSFERENCIA" => $row["embalagem"],
				"PEDIDO_MIN" => NULL,
				"FRENTE_LINEAR" => 1,
				"CAPACID_GANDOLA" => 1
			);
		}

		if(!$this->criar_arquivo("produtos_loja", $dados_produto_loja)){
			return false;
		}

		return true;
	}

	public function gerar_tipomovimento(){
		$query = "SELECT * FROM tipodocumento ORDER BY codtpdocto";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$dados_tipomovimento = array();

		foreach($arr as $row){
			$dados_tipomovimento[] = array(
				"CODIGO_TIPO_MOVIMENTO" => $row["codtpdocto"],
				"DESCRICAO_TIPO_MOVIMENTO" => $row["descricao"]
			);
		}

		if(!$this->criar_arquivo("tipomovimento", $dados_tipomovimento)){
			return false;
		}

		return true;
	}

	public function gerar_tudo(){
		if(!$this->gerar_estoque()){
			return false;
		}
		if(!$this->gerar_fornecedor()){
			return false;
		}
		if(!$this->gerar_mercadologico()){
			return false;
		}
		if(!$this->gerar_movimento()){
			return false;
		}
		if(!$this->gerar_produto()){
			return false;
		}
		if(!$this->gerar_produto_loja()){
			return false;
		}
		if(!$this->gerar_tipomovimento()){
			return false;
		}
		if(!$this->gerar_pedido()){
			return false;
		}
		return true;
	}

	private function preparar_diretorio(){
		$this->criar_diretorio($this->dirname_root);
		$this->criar_diretorio($this->dirname_processar);
		$this->criar_diretorio($this->dirname_processados);
		$this->criar_diretorio($this->dirname_pedidos);

		$arr_filename = glob($this->dirname_processar."/*");
		foreach($arr_filename as $filename){
			if(is_file($filename) && substr($filename, -4) !== ".zip"){
				unlink($filename);
			}
		}
	}

	public function setdtmovto($dtmovto){
		$this->dtmovto = value_date($dtmovto);
	}

	private function verificar_arr_codproduto(){
		if(count($this->arr_codproduto) === 0){
			$_SESSION["ERROR"] = "Por favor, informe ao menos um produto.";
			return false;
		}else{
			return true;
		}
	}

	private function verificar_codestabelec(){
		if(count($this->arr_codestabelec) === 0){
			$_SESSION["ERROR"] = "Por favor, informe ao menos um estabelecimento.";
			return false;
		}else{
			return true;
		}
	}

	private function verificar_dtmovto(){
		if(is_null($this->dtmovto)){
			$_SESSION["ERROR"] = "Por favor, informe a dtmovto.";
			return false;
		}else{
			return true;
		}
	}

}