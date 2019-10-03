<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class Siac{

	private $con;
	private $con_siac;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	private $db_format_date = "m/d/Y 00:00:00";
	private $db_format_datetime = "m/d/Y H:i:s";
	private $db_format_onlydate = "m/d/Y";

	function __construct(){
		$this->limpar_dados();
	}

	function getpdvvenda(){
		return $this->pdvvenda;
	}

	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

	private function log($text){
		$file = fopen("../temp/siac.log", "a+");
		fwrite($file, date("d/m/Y H:i:s")."\r\n");
		fwrite($file, $text."\r\n\r\n");
		fclose($file);
	}

	private function mssql_param($query, $arr_param){
		foreach($arr_param as $param){
			$pos = strpos($query, "?");
			if($pos !== FALSE){
				if(!is_numeric($param)){
					if(strlen($param) === 0){
						$param = "NULL";
					}else{
						$param = "'".str_replace("'", "''", $param)."'";
					}
				}
				$query = substr($query, 0, $pos).$param.substr($query, ($pos + 1));
			}
		}
		return $query;
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

	function siac_conectar($nome_arquivo){
		if(!isset($this->con_siac) || is_null($this->con_siac)){
			$file = parse_ini_file($nome_arquivo);
			if(!$this->con_siac = mssql_connect($file["dbhost"], $file["dbuser"], $file["dbpass"])){
				$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel conectar ao servidor SQL Server.\n\n".mssql_get_last_message();
				return FALSE;
			}
			if(!@mssql_select_db($file["dbname"], $this->con_siac)){
				$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel conectar ao banco de dados {$file["dbname"]}.\n\n".mssql_get_last_message();
				return FALSE;
			}
			mssql_query("set ANSI_NULL_DFLT_ON ON", $this->con_siac);
		}
		return TRUE;
	}

	// Inicia uma transacao no banco de dados
	function siac_starttransaction(){
		mssql_query("BEGIN TRANSACTION", $this->con_siac);
	}

	// Cancela uma transacao no banco de dados
	function siac_rollback(){
		mssql_query("ROLLBACK", $this->con_siac);
	}

	// Confirma uma transacao no banco de dados
	function siac_commit(){
		mssql_query("COMMIT", $this->con_siac);
	}

	private function descricao($descricao){
		$descricao = removespecial($descricao);
		$descricao = preg_replace("/[^a-z0-9]/i", " ", $descricao);
		$descricao = str_replace("  ", " ", $descricao);
		$descricao = trim($descricao);
		return $descricao;
	}

	function exportar_cliente(){
		switch($this->tipo_exportacao()){
			case "A": // Arquivo
				$this->exportar_cliente_arquivo();
				break;
			case "B": // Banco de dados
				$this->exportar_cliente_bancodados();
				break;
			default: // Erro
				return FALSE;
				break;
		}
	}

	function exportar_cliente_arquivo(){
		// Gera arquivo de clientes
		setprogress(0, "Buscando clientes", TRUE);
		$arr_linha = array();
		$query = "SELECT cliente.razaosocial, cliente.cpfcnpj, cliente.enderres, cliente.bairrores, cidade.nome AS cidade, cliente.ufres, cliente.cepres, ";
		$query .= "	cliente.foneres, cliente.contato, cliente.rgie, cliente.tppessoa ";
		$query .= "FROM cliente ";
		$query .= "INNER JOIN cidade ON (cliente.codcidaderes = cidade.codcidade) ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando clientes: ".($i + 1)." de ".sizeof($arr));
			$linha = "I"; // Tipo de operacao (I = inclusao; D = exclusao; A = alteracao)

			$linha .= str_pad(substr(removeformat($row["cpfcnpj"]), 0, 15), 15, "0", STR_PAD_LEFT); // CPF/CNPJ
			$linha .= str_pad(substr(removespecial($row["razaosocial"]), 0, 30), 30, " ", STR_PAD_RIGHT); // Razao social
			$linha .= str_pad(substr(removespecial($row["enderres"]), 0, 40), 40, " ", STR_PAD_RIGHT); // Endereco do cliente
			$linha .= str_pad(substr(removespecial($row["bairrores"]), 0, 15), 15, " ", STR_PAD_RIGHT); // Bairro do cliente
			$linha .= str_pad(substr(removespecial($row["cidade"]), 0, 20), 20, " ", STR_PAD_RIGHT); // Municipio do cliente
			$linha .= str_pad(substr($row["ufres"], 0, 2), 2, " ", STR_PAD_RIGHT); // Estado (area fiscal)
			$linha .= str_pad(substr(removeformat($row["cepres"]), 0, 8), 8, " ", STR_PAD_LEFT); // CEP do cliente
			$linha .= str_pad(substr($row["foneres"], 1, 2), 4, " ", STR_PAD_LEFT); // DDD do telefone
			$linha .= str_pad(removeformat(substr($row["foneres"], 5, 9)), 8, " ", STR_PAD_LEFT); // Telefone do cliente
			$linha .= str_pad(substr($row["contato"], 0, 10), 10, " ", STR_PAD_RIGHT); // Contato do cliente
			$linha .= str_repeat(" ", 14); // Inscricao municipal
			if($row["tppessoa"] == "J"){
				$linha .= str_pad(substr(removeformat($row["rgie"]), 0, 14), 14, " ", STR_PAD_RIGHT); // Inscricao estadual
			}else{
				$linha .= str_repeat(" ", 14); // Inscricao estadual
			}
			$linha .= str_repeat("0", 3); // Vencimento 1
			$linha .= str_repeat("0", 3); // Vencimento 2
			$linha .= str_repeat("0", 3); // Vencimento 3
			$linha .= str_repeat("0", 3); // Vencimento 4
			$linha .= str_repeat("0", 3); // Vencimento 5
			$arr_linha[] = $linha;
		}
		$this->pdvconfig->file_create("cliente.asc", $arr_linha);

		// Gera arquivo de convenios
		$arr_linha = array();
		// Header
		$linha = "#"; // Tipo do registro
		$linha .= date("dmY"); // Data de geracao do arquivo (ddmmyyyy)
		$linha .= str_pad($this->pdvconfig->getestabelecimento()->getcodestabelec(), 4, "0", STR_PAD_LEFT); // Codigo do estabelecimento
		$linha .= "T"; // Tipo (T = total; P = parcial)
		$linha .= "CNV"; // Tipo do arquivo
		$linha .= str_repeat(" ", 29); // Filler
		$linha .= "#"; // Tipo do registro
		$arr_linha[] = $linha;
		// Detalhe
		$query = "SELECT codcliente, nome ";
		$query .= "FROM cliente ";
		$query .= "WHERE convenio = 'S' ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando convenios: ".($i + 1)." de ".sizeof($arr));
			$linha = "1"; // Tipo de registro
			$linha .= "I"; // Codigo da operacao (A = alteracao; E = exclusao; I = inclusao)
			$linha .= str_pad($row["codcliente"], 4, "0", STR_PAD_LEFT); // Codigo da empresa
			$linha .= str_pad(substr($row["nome"], 0, 40), 40, " ", STR_PAD_RIGHT); // Nome da empresa
			$linha .= "1"; //Tipo do registro
			$arr_linha[] = $linha;
		}
		// Footer
		$linha = "@"; // Tipo do registro
		$linha .= str_pad(sizeof($arr) + 2, 6, "0", STR_PAD_LEFT); // Quantidade de registros (incluindo header e footer)
		$linha .= "CNV"; // Tipo do arquivo
		$linha .= str_repeat(" ", 36); // Filler
		$linha .= "@"; // Tipo do registro
		$arr_linha[] = $linha;
		$this->pdvconfig->file_create("convenio.cnv", $arr_linha);
	}

	function exportar_cliente_bancodados(){

	}

	function exportar_produto(){
		switch($this->tipo_exportacao()){
			case "A": // Arquivo
				$this->exportar_produto_arquivo();
				return TRUE;
				break;
			case "B": // Banco de dados
				return $this->exportar_produto_bancodados();
				break;
			default: // Erro
				return FALSE;
				break;
		}
	}

	private function exportar_produto_arquivo(){
		// Busca os produtos
		setprogress(0, "Buscando produtos", TRUE);
		$arr_linha = array();
		$produto = array();

		$versao = substr($this->pdvconfig->getfrentecaixa()->getversao(), 0, 1);
		if(empty($versao)){
			$versao = 2;
		}

		$query = "SELECT produto.datainclusao, produto.codproduto, ".$this->pdvconfig->sql_descricao().", classfiscal.tptribicms, classfiscal.aliqicms, classfiscal.aliqredicms, ";
		$query .= "	classfiscal.codcst, produto.pesado, produto.pesounid, ".$this->pdvconfig->sql_tipopreco().", produto.precovariavel, unidade.sigla AS unidade, produtoestab.sldatual, produto.coddepto, produto.codgrupo, produto.codsubgrupo, ";
		$query .= " piscofins.aliqcofins, piscofins.aliqpis, produtoestab.custorep AS custo, classfiscal.tptribicms, produto.vasilhame, embalagem.descricao AS embalagem, produtoestab.precoatc AS preco_atacado, produtoestab.precovrjof AS preco_promocional, ";
		$query .= "	CAST(produtoean.codean AS bigint), ncm.codigoncm, ncm.aliqmedia, produto.pesounid, composicao.codcomposicao ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		$query .= "INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "LEFT JOIN composicao ON (produto.codproduto = composicao.codproduto AND composicao.tipo = 'V' AND composicao.explosaoauto = 'S') ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produto.foralinha = 'N' ";
		$query .= "	AND produto.gerapdv = 'S' ";
		$query .= "	AND LENGTH(produtoean.codean) <= 13 ";
		if(param("ESTOQUE", "CARGAITEMCOMESTOQ", $this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$query .= " ORDER BY produto.codproduto ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		// Busca os codigos de barras
		$where = array();
		if($this->pdvconfig->getfrentecaixa()->getbalancaean() == "N"){
			$where[] = "produto.pesado = 'N'";
		}

		// Busca as informacoes de tributacoes
		$res = $this->con->query("SELECT * FROM icmspdv WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec());
		$arr_icmspdv = $res->fetchAll(2);

		$linha = date("dmy"); // Data atual
		$linha .= str_pad($this->pdvconfig->getestabelecimento()->getcodestabelec(), 4, "0", STR_PAD_LEFT); // Codigo da loja da carga
		$linha .= ($this->pdvconfig->produto_parcial() ? "P" : "T"); // Indica se carga total ou parcial
		$linha .= "N"; // PLU universal
		if($versao == 3){
			$linha .= "00005"; //Versão
		}
		$linha .= str_repeat(" ", 169); // Filler
		$arr_linha[] = $linha; // Adiciona a linha cabecalho do arquivo
		// Lista de codigo de produto exportados
		$arr_codproduto = array();

		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando produtos: ".($i + 1)." de ".sizeof($arr));

			$arr_codproduto[] = $row["codproduto"];

			// Acha a tributacao certa do produto
			$_icmspdv = "N";
			foreach($arr_icmspdv as $row_icmspdv){
				if($row_icmspdv["tipoicms"] == $row["tptribicms"] && $row_icmspdv["aliqicms"] == $row["aliqicms"]){
					if($row["tptribicms"] == "R"){
						if(round($row_icmspdv["redicms"], 2) == round($row["aliqredicms"], 2)){
							$_icmspdv = "S";
							break;
						}
					}else{
						$_icmspdv = "S";
						break;
					}
				}
			}
			// Verifica a tributacao no cadastro de PDV
			if($_icmspdv == "N"){
				echo messagebox("error", "", "N&atilde;o encontrado informa&ccedil;&otilde;es tributarias para o PDV do produto <b>".$row["codproduto"]."</b>: \n\n <b>Tipo de Tributa&ccedil;&atilde;o</b> = ".$row["tptribicms"]."\n<b>Aliquota </b> = ".$row["aliqicms"]."\n <b>Aliquota de Redu&ccedil;&atilde;o</b> = ".$row["aliqredicms"]."\n\n <a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributa&ccedil;&atilde;o do PDV.");
				die();
			}

			// Se o produto for de balanca, nao envia o codigo de barras
			if($row["pesado"] == "S"){
				$row["codean"] = $row["codproduto"];
			}


			if($this->pdvconfig->produto_parcial()){
				if(compare_date($this->pdvconfig->getdatalog(), $row["datainclusao"], "Y-m-d", "<=")){
					if(!in_array($row["codproduto"], $produto)){
						$operacao = "I";
						$produto[] = $row["codproduto"];
					}else{
						$operacao = "E";
					}
				}else{
					$operacao = "A";
				}
			}else{
				if(!in_array($row["codproduto"], $produto)){
					$operacao = "I";
					$produto[] = $row["codproduto"];
				}else{
					$operacao = "E";
				}
			}

			$linha = $operacao;  // Tipo da operacao
			$linha .= ($versao == 3) ? str_pad($row["codproduto"], 14, "0", STR_PAD_LEFT) : str_pad($row["codproduto"], 7, "0", STR_PAD_LEFT); // Codigo interno
			$linha .= ($versao == 3) ? str_pad(substr(trim($row["codean"]), 0, 14), 14, "0", STR_PAD_LEFT) : str_pad(substr(trim($row["codean"]), 0, 13), 13, "0", STR_PAD_LEFT); // Codigo EAN
			$linha .= "0000"; // Codigo de PLU
			$linha .= $this->valor_texto($this->descricao($row["descricao"]), 20); // Descricao resumida
			$linha .= $this->valor_texto($this->descricao($row["descricaofiscal"]), 35); // Descricao completa
			$linha .= str_pad(substr($row["unidade"], 0, 2), 2, " ", STR_PAD_RIGHT); // Unidade de venda do produto
			if($versao == 2){
				$linha .= "0001";
			}else{
				$linha .= (strlen($row["coddepto"]) > 4) ? substr($row["coddepto"], 0, 4) : str_pad($row["coddepto"], 4, "0", STR_PAD_LEFT); // Secao
			}
			if($versao == 2){
				$linha .= "0001";
			}else{
				$linha .= (strlen($row["codgrupo"]) > 4) ? substr($row["codgrupo"], 0, 4) : str_pad($row["codgrupo"], 4, "0", STR_PAD_LEFT); // Grupo
			}
			if($versao == 2){
				$linha .= "0001";
			}else{
				$linha .= (strlen($row["codsubgrupo"]) > 4) ? substr($row["codsubgrupo"], 0, 4) : str_pad($row["codsubgrupo"], 4, "0", STR_PAD_LEFT); // Subgrupo
			}
			$linha .= str_pad(number_format($row["preco"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preco de venda
			$linha .= (strlen($row["codcomposicao"]) > 0 ? "03" : ($row["pesounid"] == "P" ? "01" : "00")); // Tipo do produto (00 = normal; 01 => balanca; 03 => cesta)
			$linha .= str_pad(substr($row["codcst"], 0, 1), 1, "0", STR_PAD_RIGHT);  // Origem do produto
			$linha .= ($row["aliqpis"] > 0 || $row["aliqcofins"] > 0 ? "S" : "N");  // Indica se o produto o produto e tributado no pis/cofins
			$linha .= "N";  // Indica se o produto e de marca propria
			$linha .= "S";  // Indica se gera codigo interno no PDV
			$linha .= "0000"; // Agrupamento
			$linha .= str_pad(number_format($row["custo"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Custo
			$linha .= str_pad(substr($row_icmspdv["infpdv"], 0, 2), 2, " ", STR_PAD_RIGHT); // Tributacao ('I ','F ','N ','T0','T1', ...)
			$linha .= "00"; // Reducao
			$linha .= $row["vasilhame"]; // Indica se o produto e um vasilhame
			$linha .= "N"; // Flag se quantidade obrigatoria
			$linha .= $row["pesado"]; // Indica se o produto e balanca ou nao
			$linha .= "N"; // Indica se e permitida dar desconto no item
			$linha .= "S"; // Indica se quantidade autorizada
			$linha .= $row["precovariavel"]; // Indica se permite digitacao de preco no PDV
			$linha .= ($versao == 3) ? str_pad($row["codvasilhame"], 14, "0", STR_PAD_LEFT) : str_pad($row["codvasilhame"], 7, "0", STR_PAD_LEFT); // Codigo do vasilhame
			$linha .= ($versao == 3) ? str_pad(substr($row["unidade"], 0, 11), 11, " ", STR_PAD_RIGHT) : str_pad(substr($row["embalagem"], 0, 11), 11, " ", STR_PAD_RIGHT); // Descricao da embalagem
			if($versao == 2){
				$linha .= "0"; // Tipo de PLU
				$linha .= str_repeat(" ", 2); // Filler
				$linha .= date("dmy"); // Data atual
				$linha .= "T"; // Produto de producao propria (P = propria; T = terceiros)
				$linha .= str_pad(substr(removeformat($row["codigoncm"]), 0, 10), 10, "0", STR_PAD_RIGHT); // NCM
				$linha .= str_repeat("0", 9); // Filler
			}elseif($versao == 3){
				$linha .= $row["pesado"]; // Flag Pesagem Obrigatória
				$linha .= "N"; // Alerta de Quantidade
				$linha .= "N"; // Pré-Venda
				$linha .= date("dmy"); // Data atual
				$linha .= "T"; // Produto de producao propria (P = propria; T = terceiros)
				//$linha .= "00"; // Filler
				$linha .= str_pad(substr(removeformat($row["codigoncm"]), 0, 10), 10, "0", STR_PAD_LEFT); // NCM


				$aux = explode(".", $row["aliqpis"]);
				if(strlen($aux[0]) == 1){
					$aliqpis = "0".$aux[0].$aux[1];
				}
				if($aliqpis < 6){
					$cnt = 6 - strlen($aliqpis);
					for($i = 0; $i < $cnt; ++$i){
						$pis = $aliqpis."0";
					}
				}else{
					$pis = substr($aliqpis, 0, 6);
				}

				$aux = explode(".", $row["aliqcofins"]);
				if(strlen($aux[0]) == 1){
					$aliqcofins = "0".$aux[0].$aux[1];
				}
				if($aliqcofins < 6){
					$cnt = 6 - strlen($aliqcofins);
					for($i = 0; $i < $cnt; ++$i){
						$cofins = $aliqcofins."0";
					}
				}else{
					$cofins = substr($aliqcofins, 0, 6);
				}
				$linha .= $pis; // Aliquota PIS
				$linha .= $cofins; // Aliquota Cofins
				$linha .= "000000"; // Quantidade Atacado
				$linha .= str_pad(number_format($row["precoatacado"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preço Atacado
				$linha .= str_pad(number_format($row["preco_promocional"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preço Promocional
				$linha .= 5102; // CFOP
				$linha .= str_pad(number_format($row["aliqmedia"], 2, "", ""), 4, "0", STR_PAD_LEFT); // Aliquota Média
				$linha .= "  ";
			}
			$arr_linha[] = $linha;

//			if($operacao == "I" && $versao == 3 && !$this->pdvconfig->produto_parcial()){  /***************** Alterado *************/
			if($operacao == "I" && $versao == 3 && $this->pdvconfig->produto_parcial()){
				$linha = "A";  // Tipo da operacao
				$linha .= ($versao == 3) ? str_pad($row["codproduto"], 14, "0", STR_PAD_LEFT) : str_pad($row["codproduto"], 7, "0", STR_PAD_LEFT); // Codigo interno
				$linha .= ($versao == 3) ? str_pad(substr(trim($row["codean"]), 0, 14), 14, "0", STR_PAD_LEFT) : str_pad(substr(trim($row["codean"]), 0, 13), 13, "0", STR_PAD_LEFT); // Codigo EAN
				$linha .= "0000"; // Codigo de PLU
				$linha .= $this->valor_texto($this->descricao($row["descricao"]), 20); // Descricao resumida
				$linha .= $this->valor_texto($this->descricao($row["descricaofiscal"]), 35); // Descricao completa
				$linha .= str_pad(substr($row["unidade"], 0, 2), 2, " ", STR_PAD_RIGHT); // Unidade de venda do produto
				$linha .= (strlen($row["coddepto"]) > 4) ? substr($row["coddepto"], 0, 4) : str_pad($row["coddepto"], 4, "0", STR_PAD_LEFT); // Secao
				$linha .= (strlen($row["codgrupo"]) > 4) ? substr($row["codgrupo"], 0, 4) : str_pad($row["codgrupo"], 4, "0", STR_PAD_LEFT); // Grupo
				$linha .= (strlen($row["codsubgrupo"]) > 4) ? substr($row["codsubgrupo"], 0, 4) : str_pad($row["codsubgrupo"], 4, "0", STR_PAD_LEFT); // Subgrupo

				$linha .= str_pad(number_format($row["preco"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preco de venda
				$linha .= (strlen($row["codcomposicao"]) > 0 ? "03" : ($row["pesounid"] == "P" ? "01" : "00")); // Tipo do produto (00 = normal; 01 => balanca; 03 => cesta)
				$linha .= str_pad(substr($row["codcst"], 0, 1), 1, "0", STR_PAD_RIGHT);  // Origem do produto
				$linha .= ($row["aliqpis"] > 0 || $row["aliqcofins"] > 0 ? "S" : "N");  // Indica se o produto o produto e tributado no pis/cofins
				$linha .= "N";  // Indica se o produto e de marca propria
				$linha .= "S";  // Indica se gera codigo interno no PDV
				$linha .= "0000"; // Agrupamento
				$linha .= str_pad(number_format($row["custo"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Custo
				$linha .= str_pad(substr($row_icmspdv["infpdv"], 0, 2), 2, " ", STR_PAD_RIGHT); // Tributacao ('I ','F ','N ','T0','T1', ...)
				$linha .= "00"; // Reducao
				$linha .= $row["vasilhame"]; // Indica se o produto e um vasilhame
				$linha .= "N"; // Flag se quantidade obrigatoria
				$linha .= $row["pesado"]; // Indica se o produto e balanca ou nao
				$linha .= "N"; // Indica se e permitida dar desconto no item
				$linha .= "S"; // Indica se quantidade autorizada
				$linha .= $row["precovariavel"]; // Indica se permite digitacao de preco no PDV
				$linha .= ($versao == 3) ? str_pad($row["codvasilhame"], 14, "0", STR_PAD_LEFT) : str_pad($row["codvasilhame"], 7, "0", STR_PAD_LEFT); // Codigo do vasilhame
				$linha .= ($versao == 3) ? str_pad(substr($row["unidade"], 0, 11), 11, " ", STR_PAD_RIGHT) : str_pad(substr($row["embalagem"], 0, 11), 11, " ", STR_PAD_RIGHT); // Descricao da embalagem
				if($versao == 2){
					$linha .= "0"; // Tipo de PLU
					$linha .= str_repeat(" ", 2); // Filler
					$linha .= date("dmy"); // Data atual
					$linha .= "T"; // Produto de producao propria (P = propria; T = terceiros)
					$linha .= str_pad(substr(removeformat($row["codigoncm"]), 0, 10), 10, "0", STR_PAD_RIGHT); // NCM
					$linha .= str_repeat("0", 9); // Filler
				}elseif($versao == 3){
					$linha .= $row["pesado"]; // Flag Pesagem Obrigatória
					$linha .= "N"; // Alerta de Quantidade
					$linha .= "N"; // Pré-Venda
					$linha .= date("dmy"); // Data atual
					$linha .= "T"; // Produto de producao propria (P = propria; T = terceiros)
					//$linha .= "00"; // Filler
					$linha .= str_pad(substr(removeformat($row["codigoncm"]), 0, 10), 10, "0", STR_PAD_LEFT); // NCM


					$aux = explode(".", $row["aliqpis"]);
					if(strlen($aux[0]) == 1){
						$aliqpis = "0".$aux[0].$aux[1];
					}
					if($aliqpis < 6){
						$cnt = 6 - strlen($aliqpis);
						for($i = 0; $i < $cnt; ++$i){
							$pis = $aliqpis."0";
						}
					}else{
						$pis = substr($aliqpis, 0, 6);
					}

					$aux = explode(".", $row["aliqcofins"]);
					if(strlen($aux[0]) == 1){
						$aliqcofins = "0".$aux[0].$aux[1];
					}
					if($aliqcofins < 6){
						$cnt = 6 - strlen($aliqcofins);
						for($i = 0; $i < $cnt; ++$i){
							$cofins = $aliqcofins."0";
						}
					}else{
						$cofins = substr($aliqcofins, 0, 6);
					}
					$linha .= $pis; // Aliquota PIS
					$linha .= $cofins; // Aliquota Cofins
					$linha .= "000000"; // Quantidade Atacado
					$linha .= str_pad(number_format($row["precoatacado"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preço Atacado
					$linha .= str_pad(number_format($row["preco_promocional"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preço Promocional
					$linha .= 5102; // CFOP
					$linha .= str_pad(number_format($row["aliqmedia"], 2, "", ""), 4, "0", STR_PAD_LEFT); // Aliquota Média
					$linha .= "  ";
				}
				$arr_linha[] = $linha;
			}
		}

		$param_siacuniqarquivo = param("FRENTECAIXA", "SIACUNIQARQUIVO", $con); // Parametro que verifica se o arquivo sera gerado em arquivo unico
		if($param_siacuniqarquivo == 0){
			$x = 0;
			$i = 0;
			$n = 0;
			$arr_linha_aux = array();
			$header = array_shift($arr_linha);
			while(sizeof($arr_linha) > 0){
				$linha = array_shift($arr_linha);
				if(!is_array($arr_linha_aux[$n])){
					$arr_linha_aux[$n] = array($header);
				}
				$arr_linha_aux[$n][] = $linha;
				$i++;
				if($i == 20000){
					$i = 0;
					$n++;
				}
				if(++$x == 1000000){
					break;
				}
			}
			foreach($arr_linha_aux as $n => $arr_linha){
				$this->pdvconfig->file_create("cadastro_".$n.".asc", $arr_linha);
			}
		}else{
			$this->pdvconfig->file_create("cadastro.asc", $arr_linha);
		}

		// Busca as cestas-basicas
		setprogress(0, "Buscando cestas basicas", TRUE);
		$arr_linha = array();

		$linha = date("dmy"); // Data de geracao do arquivo
		$linha .= str_pad($this->pdvconfig->getestabelecimento()->getcodestabelec(), 4, "0", STR_PAD_LEFT); // Numero da loja
		$linha .= "T"; // Tipo da importacao (T = total; P = parcial)
		$linha .= "00000"; // Versao do layout
		$linha .= str_repeat(" ", 33); // Filler
		$arr_linha[] = $linha;

		$query = "SELECT composicao.codproduto AS codprodutopai, itcomposicao.codproduto AS codprodutofilho, itcomposicao.quantidade, ";
		$query .= "	(CASE WHEN composicao.tipopreco = 'A' THEN produtoestab.precoatc ELSE produtoestab.precovrj END) AS preco ";
		$query .= "FROM itcomposicao ";
		$query .= "INNER JOIN composicao ON (itcomposicao.codcomposicao = composicao.codcomposicao) ";
		$query .= "INNER JOIN produtoestab ON (itcomposicao.codproduto = produtoestab.codproduto) ";
		$query .= "INNER JOIN produto ON (itcomposicao.codproduto = produto.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND composicao.tipo = 'V' ";
		$query .= "	AND composicao.explosaoauto = 'S' ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando cestas: ".($i + 1)." de ".sizeof($arr));
			$linha = "I"; // Tipo de operacao (I = insercao; A = alteracao; D = exclusao; P = insere um produto; X - exclui um produto)
			$linha .= str_pad($row["codprodutopai"], 7, "0", STR_PAD_LEFT); // Codigo da cesta
			$linha .= str_pad($row["codprodutofilho"], 7, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad(number_format($row["quantidade"], 2, "", ""), 6, "0", STR_PAD_LEFT); // Quantidade
			$linha .= str_pad(number_format($row["preco"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preco unitario do produto
			$linha .= str_repeat(" ", 20); // Filler
			$arr_linha[] = $linha;
		}
		$this->pdvconfig->file_create("cadastro.ces", $arr_linha);

		// ARQUIVO DE PROMOÇÃO

		$linha = "#"; // Tipo do Registro
		$linha .= date("dmY"); // Data de Geração
		$linha .= "0001"; // Loja
		$linha .= "P"; // Tipo da carga (T=Total;P=Parcial)
		$linha .= "PRO"; // Tipo do Arquivo
		$linha .= "02"; // Versão do Layout
		$linha .= str_pad("0", 108, "0", STR_PAD_RIGHT); // Filler
		$linha .= "#"; // Tipo do Registro

		$arr_linha = array();
		$arr_linha[] = $linha;

		$query = "SELECT oferta.codoferta,datainicio,datafinal,descricao ";
		$query .= "FROM oferta ";
		$query .= "INNER JOIN ofertaestab ON oferta.codoferta = ofertaestab.codoferta ";
		$query .= "WHERE ofertaestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec();
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$oferta = array();
		foreach($arr as $i => $row){
			$linha = "1"; // Tipo do Registro
			$linha .= "02"; // Tipo da Promoção
			$linha .= (strlen($row["codoferta"]) > 8) ? substr($row["codoferta"], 0, 8) : str_pad($row["codoferta"], 8, "0", STR_PAD_LEFT); // Código da Promoção
			$linha .= "E"; // Código da operação
			$linha .= (strlen($row["descricao"]) > 20) ? substr($row["descricao"], 0, 20) : str_pad($row["descricao"], 20, " ", STR_PAD_RIGHT); // Descrição da Promoção
			$linha .= $this->valor_data($row["datainicio"]); // Data Início
			$linha .= $this->valor_data($row["datafinal"]); // Data Final
			$linha .= str_pad("0", 79, "0", STR_PAD_RIGHT); // Filler
			$linha .= "1"; // Tipo do Registro
			$arr_linha[] = $linha;
			$oferta[] = $row["codoferta"];
			$linha = "1"; // Tipo do Registro
			$linha .= "02"; // Tipo da Promoção
			$linha .= (strlen($row["codoferta"]) > 8) ? substr($row["codoferta"], 0, 8) : str_pad($row["codoferta"], 8, "0", STR_PAD_LEFT); // Código da Promoção
			$linha .= "I"; // Código da operação
			$linha .= (strlen($row["descricao"]) > 20) ? substr($row["descricao"], 0, 20) : str_pad($row["descricao"], 20, " ", STR_PAD_RIGHT); // Descrição da Promoção
			$linha .= $this->valor_data($row["datainicio"]); // Data Início
			$linha .= $this->valor_data($row["datafinal"]); // Data Final
			$linha .= str_pad("0", 79, "0", STR_PAD_RIGHT); // Filler
			$linha .= "1"; // Tipo do Registro
			$arr_linha[] = $linha;
		}

		$linha = "2"; // Tipo do Registro
		$linha .= "02"; // Tipo da Promocao
		$linha .= $this->valor_numerico_siac($codoferta, 0, 8); // Codigo da promocao
		$linha .= $this->valor_numerico_siac(0, 0, 68); // Brancos
		$linha .= $this->valor_numerico_siac(0, 0, 4); // Codigo da categoria 1
		$linha .= $this->valor_numerico_siac(0, 0, 4); // Codigo da categoria 19
		$linha .= "2"; // Tipo do registro
		$arr_linha[] = $linha;


		foreach($oferta as $of){
			$query = "SELECT codproduto,preco FROM itoferta WHERE codoferta = ".$of;
			$produto_oferta = $this->con->query($query);
			$itoferta = $produto_oferta->fetchAll(2);
			foreach($itoferta as $itens){
				$linha = "3"; // Tipo do Registro
				$linha .= "02"; // Tipo da Promoção
				$linha .= (strlen($of) > 8) ? substr($of, 0, 8) : str_pad($of, 8, "0", STR_PAD_LEFT); // Código da Promoção
				$linha .= (strlen($itens["codproduto"]) > 14) ? substr($itens["codproduto"], 0, 14) : str_pad($itens["codproduto"], 14, "0", STR_PAD_LEFT); // Código interno do produto
				$linha .= str_pad(number_format($itens["preco"], 2, "", ""), 10, "0", STR_PAD_LEFT); // Preço Promocional
				$linha .= str_pad("0", 20, "0", STR_PAD_RIGHT); // Filler
				$linha .= "3"; // Tipo do Registro
				$arr_linha[] = $linha;
			}
		}
		$cnt = count($arr_linha) + 1;
		$linha = "@";
		$linha .= str_pad($cnt, 6, "0", STR_PAD_LEFT);
		$linha .= "PRO";
		$linha .= str_pad("A", 117, "A", STR_PAD_RIGHT);
		$linha .= "@";
		$arr_linha[] = $linha;

		$this->pdvconfig->file_create("ARTIGOS.EXP", $arr_linha);

		$this->pdvconfig->atualizar_precopdv($arr_codproduto);

		return TRUE;
	}

	private function exportar_produto_bancodados(){
		setprogress(0, "Conectando ao SIAC", TRUE);

		$param_sistema_codigocw = param("SISTEMA", "CODIGOCW", $this->con);

		$this->siac_starttransaction();

		// Cria um novo processo
		$query = "INSERT INTO IntControle (codModulo, codLoja, codStatus, dtHrProcesso, indicImpIntegracaoSiac, indicImpTotal) VALUES (?, ?, ?, ?, ?, ?)";
		$param = array("14", $this->pdvconfig->getestabelecimento()->getcodestabelec(), "1", date($this->db_format_datetime), "S", ($this->pdvconfig->produto_parcial() ? "N" : "S"));
		if(!mssql_query($this->mssql_param($query, $param), $this->con_siac)){
			$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntControle.\n\n".mssql_get_last_message();
			$this->siac_rollback();
			$this->log($this->mssql_param($query, $param));
			return FALSE;
		}

		// Verifica qual o proximo id de processo deve ser utilizado
		$res = mssql_query("SELECT MAX(idProcesso) AS idProcesso FROM IntControle", $this->con_siac);
		$row = mssql_fetch_assoc($res);
		$idprocesso = $row["idProcesso"];

		/*		 * *****************************
		  P R O D U T O S   E   P R E C O S
		 * ***************************** */
		setprogress(0, "Buscando produtos", TRUE);

		$arr_column_pro = array("idProcesso", "codInterno", "codElemento", "descEmbalagem", "codUnidade", "descProduto", "descResumida", "indicMarcaPropria", "indicPisCofinsZero", "codTipoProduto", "codOrigemProduto", "codGrupo", "codSubGrupo", "indicGerarCodPesoVar", "indicProducaoPropria", "NCM", "codValidado", "indicAlertaQuantidade", "indicPossuiMensagemAssociada", "indicLiberacaoPrecoObrigatoria", "CEST", "indicPesagemObrigatoria", "indicProdutoCardapio");
		$arr_column_cod = array("idProcesso", "codVenda", "codInterno", "codTipo", "codOperacao", "codValidado");
		//$arr_column_loj = array("idProcesso", "codInterno", "codLoja", "indicPermiteDigitacaoPreco", "indicPermiteDigitacaoDesconto", "codDigitaQtde", "codOperacao", "codValidado", "codLegenda", "indicVendeCodInterno", "indicPermitePorPreVenda", "percTributacao", "indicPermSomenteAtravesCesta", "indicConsultaEstoqueOnline", "indicSolicitaExpedicao", "descProdutoRegional", "descResumidaRegional", "percTribEst", "percTribMun");
		$arr_column_loj = array("idProcesso", "codInterno", "codLoja", "indicPermiteDigitacaoPreco", "indicPermiteDigitacaoDesconto", "codDigitaQtde", "codOperacao", "codValidado", "codLegenda", "indicVendeCodInterno", "indicPermitePorPreVenda", "percTributacao", "indicPermSomenteAtravesCesta", "indicConsultaEstoqueOnline", "indicSolicitaExpedicao", "descProdutoRegional", "descResumidaRegional", "cstPis", "cstCofins", "percPis", "percCofins", "CFOP", "pRedBCEfetiva", "pICMSEfetiva");
		$arr_column_pre = array("idProcesso", "codInterno", "dtInicioValidade", "codLoja", "valorPrecoVenda", "valorPrecoCusto", "codOperacao", "codValidado");

		$query_pro = "INSERT INTO IntPluProduto (".implode(", ", $arr_column_pro).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_pro))))).")";
		$query_cod = "INSERT INTO IntPluCodigo (".implode(", ", $arr_column_cod).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_cod))))).")";
		$query_loj = "INSERT INTO IntPluProdutoLoja (".implode(", ", $arr_column_loj).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_loj))))).")";
		$query_pre = "INSERT INTO IntPluPrecoLoja (".implode(", ", $arr_column_pre).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_pre))))).")";

		// Carrega os produtos
		$query = "SELECT produto.codproduto, produto.descricaofiscal, produto.descricao, produto.coddepto, produto.codgrupo, produto.codsubgrupo, ";
		$query .= "	unidade.sigla AS unidade, piscofins.aliqpis, piscofins.aliqcofins, produto.pesado, classfiscal.codcst AS csticms, ncm.codigoncm, ";
		$query .= "	produto.precovariavel, produtoestab.codestabelec, ".$this->pdvconfig->sql_tipopreco().", classfiscal.tptribicms, ";
		$query .= "	classfiscal.aliqicms, classfiscal.aliqredicms, produtoestab.custorep, embalagem.descricao AS embalagem, produto.pesounid, ";
		$query .= "	COALESCE((CASE WHEN produto.aliqmedia IS NOT NULL THEN produto.aliqmedia ELSE ncm.aliqmedia END),0) AS aliqmedia, ";
		$query .= "	COALESCE(ibptestabelec.aliqestadual, 0) AS aliqestadual, cest(ncm.codigoncm) AS cest, piscofins.codcst AS cstpiscofins, ";
		$query .= " composicao.codcomposicao ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "LEFT JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "LEFT JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
		$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "LEFT JOIN ibptestabelec ON (produtoestab.codestabelec = ibptestabelec.codestabelec AND ncm.codigoncm = ibptestabelec.codigoncm) ";
		$query .= "LEFT JOIN composicao ON (produto.codproduto = composicao.codproduto AND composicao.tipo = 'V' AND composicao.explosaoauto = 'S') ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produto.foralinha = 'N' ";
		$query .= "	AND produto.gerapdv = 'S' ";
		$query .= "	AND produtoestab.precovrj > 0 ";
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
//		$query .= "	AND produto.codproduto = 7437 ";
		$query .= "ORDER BY produto.codproduto ";
		$res = $this->con->query($query);
		$arr_pro = $res->fetchAll(2);

		// Carrega os eans
		$query = "SELECT codproduto, codean ";
		$query .= "FROM produtoean ";
		$query .= "WHERE LENGTH(codean) <= 13 ";
		$query .= "	AND LENGTH(LTRIM(codean,'0')) > 7 ";
		$query .= "ORDER BY codproduto ";
		$res = $this->con->query($query);
		$arr_ean_aux = $res->fetchAll(2);
		$arr_ean_aux2 = array();
		foreach($arr_ean_aux as $row_ean){
			$arr_ean_aux2[ltrim($row_ean["codean"], "0")] = $row_ean;
		}
		$arr_ean = array();
		foreach($arr_ean_aux2 as $row_ean){
			$arr_ean[$row_ean["codproduto"]][] = $row_ean;
		}

		// Busca as informacoes de tributacoes
		$res = $this->con->query("SELECT * FROM icmspdv WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec());
		$arr_icm = $res->fetchAll(2);

		// Lista de produtos exportados
		$arr_codproduto = array();

		// Percorre todos os produtos
		foreach($arr_pro as $i => $row_pro){
			setprogress((($i + 1) / sizeof($arr_pro) * 100), "Exportando produtos: ".($i + 1)." de ".sizeof($arr_pro));

			// Inclui codigo do produto na lista de exportados
			$arr_codproduto[] = $row_pro["codproduto"];

			// Acha a tributacao correta do produto
			$achou_icm = FALSE;
			foreach($arr_icm as $j => $row_icm){
				if($row_icm["tipoicms"] == $row_pro["tptribicms"] && $row_icm["aliqicms"] == $row_pro["aliqicms"]){
					$achou_icm = TRUE;
					if($row_icm["tptribicms"] == "R"){
						if($row_icm["redicms"] == $row_pro["aliqredicms"]){
							break;
						}
					}else{
						break;
					}
				}
			}
			if(!$achou_icm){
				$_SESSION["ERROR"] = "N&atilde;o encontrado informa&ccedil;&otilde;es tributarias para o PDV do produto <b>".$row_pro["codproduto"]."</b>: \n\n <b>Tipo de Tributa&ccedil;&atilde;o</b> = ".$row_pro["tptribicms"]."\n<b>Aliquota </b> = ".$row_pro["aliqicms"]."\n <b>Aliquota de Redu&ccedil;&atilde;o</b> = ".$row_pro["aliqredicms"]."\n\n <a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributa&ccedil;&atilde;o do PDV.";
				$this->siac_rollback();
				return FALSE;
			}

			// Exporta o cadastro de produtos
			if(strlen($row_pro["codcomposicao"]) > 0 ){
				$codTipoProduto = "03";
			}elseif($row_pro["pesado"] == "S" && $row_pro["pesounid"] == "U"){
				/*
				Murilo 15/02/2019
				Tratamento criado para não dar problema no Infanger.
				Ficou combinado que eles reetiquetariam os produtos para funcionar como os outros cliente SIAC.
				*/
				if($param_sistema_codigocw === "1093"){
					$codTipoProduto = "00";
				}else{
					$codTipoProduto = "04";
				}
			}elseif($row_pro["pesounid"] == "P"){
				$codTipoProduto = "01";
			}else{
				$codTipoProduto = "00";
			}
			$param_pro = array($idprocesso, $row_pro["codproduto"], $row_pro["coddepto"], $row_pro["embalagem"], $row_pro["unidade"], substr($this->descricao($row_pro["descricaofiscal"]), 0, 40), substr($this->descricao($row_pro["descricao"]), 0, 20), "N", ($row_pro["aliqpis"] == 0 ? "S" : "N"), $codTipoProduto, substr($row_pro["csticms"], 0, 1), "1", "1", $row_pro["pesado"], "N", str_pad(removeformat($row_pro["codigoncm"]), 1, "0"), "0", "N", "N", "N", removeformat($row_pro["cest"]), $row_pro["pesado"], "N");
			if(!mssql_query($this->mssql_param($query_pro, $param_pro), $this->con_siac)){
				$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluProduto.\n\n".mssql_get_last_message();
				$this->siac_rollback();
				$this->log($this->mssql_param($query_pro, $param_pro));
				return FALSE;
			}

			// Exporta os EAN's
			if(is_array($arr_ean[$row_pro["codproduto"]])){
				foreach($arr_ean[$row_pro["codproduto"]] as $row_ean){
					$param_cod = array($idprocesso, $row_ean["codean"], $row_ean["codproduto"], (strlen(ltrim($row_ean["codean"], "0")) > 7 ? "1" : "2"), "I", "0");
					if(!mssql_query($this->mssql_param($query_cod, $param_cod), $this->con_siac)){
						$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluCodigo.\n\n".mssql_get_last_message()."\n\nProduto ".$row_ean["codproduto"]."\nEAN ".$row_ean["codean"];
						$this->siac_rollback();
						$this->log($this->mssql_param($query_cod, $param_cod));
						return FALSE;
					}
				}
			}

			// Exporta os dados do produto por estabelecimento
			//$param_loj = array($idprocesso, $row_pro["codproduto"], $row_pro["codestabelec"], $row_pro["precovariavel"], "N", "2", "I", "0", $row_icm["infpdv"], "S", "N", $row_pro["aliqmedia"], "N", "N", "N", substr($this->descricao($row_pro["descricaofiscal"]), 0, 40), substr($this->descricao($row_pro["descricao"]), 0, 20), $row_pro["aliqestadual"], 0);
			$param_loj = array($idprocesso, $row_pro["codproduto"], $row_pro["codestabelec"], $row_pro["precovariavel"], "S", "2", "I", "0", $row_icm["infpdv"], "S", "N", $row_pro["aliqmedia"], "N", "N", "N", substr($this->descricao($row_pro["descricaofiscal"]), 0, 40), substr($this->descricao($row_pro["descricao"]), 0, 20), $row["cstpiscofins"], $row["cstpiscofins"], $row_pro["aliqpis"], $row_pro["aliqcofins"], ($row_pro["tptribicms"] === "F" ? "5405" : "5102"), $row_pro["aliqredicms"], $row_pro["aliqicms"]);
			if(!mssql_query($this->mssql_param($query_loj, $param_loj), $this->con_siac)){
				$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluProdutoLoja.\n\n".mssql_get_last_message();
				$this->siac_rollback();
				$this->log($this->mssql_param($query_loj, $param_loj));
				return FALSE;
			}

			// Exporta o preco dos produtos
			$param_pre = array($idprocesso, $row_pro["codproduto"], date($this->db_format_date), $row_pro["codestabelec"], $row_pro["preco"], $row_pro["custorep"], "I", "0");
			if(!mssql_query($this->mssql_param($query_pre, $param_pre), $this->con_siac)){
				$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluPrecoLoja.\n\n".mssql_get_last_message();
				$this->siac_rollback();
				$this->log($this->mssql_param($query_pre, $param_pre));
				return FALSE;
			}
		}

		/*		 * **********************************
		  C O M P O S I C O E S   D E   P R O D U T O
		 * *************************************** */
		setprogress(0, "Buscando composicoes", TRUE);

		$arr_column_ces = array("idProcesso", "codCesta", "codLoja", "codOperacao", "codValidado");
		$arr_column_com = array("idProcesso", "codCesta", "codInternoProduto", "qtdeProduto", "codOperacao", "codValidado");
		$arr_column_pre = array("idProcesso", "codCesta", "codInternoProduto", "codLoja", "dtInicioValidade", "valorPrecoComponenteCesta", "codOperacao", "codValidado");

		$query_ces = "INSERT INTO IntPluCestaLoja (".implode(", ", $arr_column_ces).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_ces))))).")";
		$query_com = "INSERT INTO IntPluCestaComponente (".implode(", ", $arr_column_com).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_com))))).")";
		$query_pre = "INSERT INTO IntPluCestaCompPrecoLoja (".implode(", ", $arr_column_pre).") VALUES (".implode(", ", explode(" ", trim(str_repeat("? ", sizeof($arr_column_pre))))).")";

		// Carrega as composicoes
		$composicao = objectbytable("composicao", NULL, $this->con);
		$composicao->settipo("V");
		$composicao->setexplosaoauto("S");
		$arr_composicao = object_array($composicao);
		foreach($arr_composicao as $i => $composicao){
			setprogress((($i + 1) / sizeof($arr_composicao) * 100), "Exportando composicoes: ".($i + 1)." de ".sizeof($arr_composicao));

			// Carrega os dados do produto pai na loja
			$produtoestab_pai = objectbytable("produtoestab", array($this->pdvconfig->getestabelecimento()->getcodestabelec(), $composicao->getcodproduto()), $this->con);

			// Exporta os dados das composicoes
			$param_ces = array($idprocesso, $composicao->getcodproduto(), $this->pdvconfig->getestabelecimento()->getcodestabelec(), "I", "0");
			if(!mssql_query($this->mssql_param($query_ces, $param_ces), $this->con_siac)){
				$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluCestaLoja.\n\n".mssql_get_last_message();
				$this->siac_rollback();
				$this->log($this->mssql_param($query_ces, $param_ces));
				return FALSE;
			}

			// Carrega os itens da composicao
			$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
			$itcomposicao->setcodcomposicao($composicao->getcodcomposicao());
			$arr_itcomposicao = object_array($itcomposicao);

			$precototal = 0;
			$arr_produtoestab = array();
			foreach($arr_itcomposicao as $itcomposicao){
				$produtoestab = objectbytable("produtoestab", array($this->pdvconfig->getestabelecimento()->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con);
				$arr_produtoestab[$itcomposicao->getcodproduto()] = $produtoestab;
				$precototal += $itcomposicao->getquantidade() * $produtoestab->getprecovrj();
			}
			$fator_preco = $produtoestab_pai->getprecovrj() / $precototal;

			foreach($arr_itcomposicao as $itcomposicao){
				$produtoestab = objectbytable("produtoestab", array($this->pdvconfig->getestabelecimento()->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con);

				// Exporta os dados dos itens da composicao
				$param_com = array($idprocesso, $composicao->getcodproduto(), $itcomposicao->getcodproduto(), $itcomposicao->getquantidade(), "I", "0");
				if(!mssql_query($this->mssql_param($query_com, $param_com), $this->con_siac)){
					$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluCestaComponente.\n\n".mssql_get_last_message();
					$this->siac_rollback();
					$this->log($this->mssql_param($query_com, $param_com));
					return FALSE;
				}

				// Exporta os precos dos itens na composicao
				$param_pre = array($idprocesso, $composicao->getcodproduto(), $itcomposicao->getcodproduto(), $this->pdvconfig->getestabelecimento()->getcodestabelec(), date($this->db_format_date), ($produtoestab->getprecovrj() * $fator_preco), "I", "0");
				//var_dump($param_pre);
				if(!mssql_query($this->mssql_param($query_pre, $param_pre), $this->con_siac)){
					$_SESSION["ERROR"] = "Erro ao incluir registro na tabela IntPluCestaCompPrecoLoja.\n\n".mssql_get_last_message();
					$this->siac_rollback();
					$this->log($this->mssql_param($query_pre, $param_pre));
					return FALSE;
				}
			}
		}

		// Atualiza o processo para liberar para o SIAC processar a importacao
		if(!mssql_query("UPDATE IntControle SET codStatus = 2 WHERE idProcesso = ".$idprocesso, $this->con_siac)){
			$_SESSION["ERROR"] = mssql_get_last_message();
			$this->siac_rollback();
			return FALSE;
		}

		// Aceitas as informacoes no banco de dados do SIAC
		$this->siac_commit();

		// Atualiza o preco PDV dos produto
		$this->pdvconfig->atualizar_precopdv($arr_codproduto);

		return TRUE;
	}

	function importar_maparesumo($nome_arquivo, $arquivo_obrigatorio = FALSE){
		if(strtolower(basename($nome_arquivo)) != "ecf.asc"){
			if(!$this->siac_conectar($nome_arquivo)){
				return FALSE;
			}
			return $this->importar_maparesumo_bancodados();
		}else{
			return $this->importar_maparesumo_arquivo($nome_arquivo, $arquivo_obrigatorio);
		}
	}

	function importar_maparesumo_arquivo($nome_arquivo, $arquivo_obrigatorio = FALSE){
		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();
		$paramfiscal = objectbytable("paramfiscal", $codestabelec, $this->con);
		if(!file_exists($nome_arquivo)){
			if($arquivo_obrigatorio){
				$_SESSION["ERROR"] = "Arquivo do mapa resumo do ECF n&atilde;o foi encontrado.";
				return FALSE;
			}else{
				return TRUE;
			}
		}

		$res = $this->con->query("SELECT DISTINCT dtmovto FROM maparesumo WHERE codestabelec = ".$codestabelec);
		$arr = $res->fetchAll(2);
		$arr_dtmovto = array();
		foreach($arr as $row){
			$arr_dtmovto[] = convert_date($row["dtmovto"], "Y-m-d", "d/m/Y");
		}

		$icmspdv = objectbytable("icmspdv", NULL, $this->con);
		$icmspdv->setcodestabelec($codestabelec);
		$arr_icmspdv = object_array($icmspdv);

		$arr_ecf = array();
		$ecf = objectbytable("ecf", NULL, $this->con);
		$arr_ecf = object_array($ecf);

		$arr_linha = read_file($nome_arquivo);

		$header = array_shift($arr_linha);
		$dtmovto = substr($header, 0, 6);
		$dtmovto = convert_date($dtmovto, "dmy", "d/m/Y");

		if(in_array($dtmovto, $arr_dtmovto)){
			return TRUE;
		}

		$this->con->start_transaction();
		foreach($arr_linha as $linha){
			setprogress(($i + 1) / sizeof($arr_linha) * 100, "Importando mapa resumo: ".($i + 1)." de ".sizeof($arr_linha));
			if(strlen(trim($linha)) == 0){
				continue;
			}
			$caixa = substr($linha, 0, 3); // Numero do caixa
			$numfechamentoz = substr($linha, 3, 6); // Numero do fechamento Z do PDV
			$numsequencialpdv = substr($linha, 9, 6); // Numero sequencial de operacao de abertura do PDV
			$numsequencialz = substr($linha, 15, 6); // Numero sequencial de operacao de fechamento Z
			$numclientes = substr($linha, 21, 6); // Numero de clientes atendidos
			$totalcancelado = substr($linha, 27, 13) / 100; // Valor total cancelado
			$totaldesconto = substr($linha, 40, 13) / 100; // Valor total de desconto
			$totalbruto = substr($linha, 53, 13) / 100; // Valor total de venda (bruta)
			$gtinicial = substr($linha, 66, 18) / 100; // GT de vendas inicial
			$gtfinal = substr($linha, 84, 18) / 100; // GT de venda final
			$gtcancinicial = substr($linha, 102, 18) / 100; // GT de cancelamento inicial
			$gtcancfinal = substr($linha, 120, 18) / 100; // GT de cancelamento final
			$gtdescinicial = substr($linha, 138, 18) / 100; // GT de desconto inicial
			$gtdescfinal = substr($linha, 156, 18) / 100; // GT de desconto final
			$gtcancdescinicial = substr($linha, 174, 18) / 100; // GT de cancelamento de desconto inicial
			$gtcancdescfinal = substr($linha, 192, 18) / 100; // GT de cancelamento de desconto final
			$contador_reinicio_operacao = (substr($linha, 367, 6) == 0) ? 1 : substr($linha, 367, 6); // Contador de Reinicio de operação

			$pos = 210;
			$arr_tributacao = array();
			for($i = 0; $i < 24; $i++){
				$arr_tributacao[] = array(
					"legenda" => substr($linha, $pos, 2), // Legenda
					"aliquota" => substr($linha, $pos + 2, 4) / 100, // Aliquota
					"total" => substr($linha, $pos + 6, 13) / 100 // Valor total
				);
				$pos += 19;
			}

			$versaoecf = substr($linha, 1251, 16); // Versao do firmware do ECF
			$serieecf = substr($linha, 1267, 25); // Numero de serie do ECF
			$totalpiscofins = substr($linha, 1292, 13) / 100; // Valor total de PIS/Cofins
			$contreinicio = substr($linha, 1305, 6); // Contador de reinicio de operacao

			$serieecf = trim($serieecf);
			if(strlen($serieecf) == 0){
				continue;
			}

			$achou_ecf = FALSE;
			foreach($arr_ecf as $ecf){
				if($ecf->getnumfabricacao() == $serieecf){
					$achou_ecf = TRUE;
					break;
				}
			}
			if(!$achou_ecf){
				$ecf = objectbytable("ecf", NULL, $this->con);
				$ecf->setcodestabelec($codestabelec);
				$ecf->setnumfabricacao($serieecf);
				$ecf->setcaixa($caixa);
				$ecf->setequipamentofiscal("ECF");
				if(!$ecf->save()){
					$this->con->rollback();
					return FALSE;
				}
				$arr_ecf[] = $ecf;
			}

			$maparesumo = objectbytable("maparesumo", NULL, $this->con);
			$maparesumo->setcodestabelec($codestabelec);
			$maparesumo->setnumeroreducoes($numfechamentoz);
			$maparesumo->setcuponsfiscemit($numclientes);
			$maparesumo->setcaixa($caixa);
			$maparesumo->setnumeroecf($caixa);
			$maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
			$maparesumo->setdtmovto($dtmovto);
			$maparesumo->setcodecf($ecf->getcodecf());
			$maparesumo->setnumeroecf($ecf->getcaixa());
			$maparesumo->setreiniciofim($contador_reinicio_operacao);
			$reinicioini = $contador_reinicio_operacao + 1;
			$maparesumo->setreinicioini($reinicioini);
			$maparesumo->setoperacaoini($numsequencialpdv);
			$maparesumo->setoperacaofim($numsequencialz);
			$maparesumo->setgtinicial($gtinicial);
			$maparesumo->setgtfinal($gtfinal);
			$maparesumo->settotalbruto($totalbruto);
			$maparesumo->settotalcupomcancelado($totalcancelado);
			$maparesumo->settotaldescontocupom($totaldesconto);
			$maparesumo->settotalliquido($totalbruto - $totalcancelado - $totaldesconto);
			$maparesumo->setnumseriefabecf($serieecf);
			if(!$maparesumo->save()){
				$this->con->rollback();
				return FALSE;
			}
			foreach($arr_tributacao as $tributacao){
				$tptribicms = substr($tributacao["legenda"], 0, 1);
				$aliqicms = (float) $tributacao["aliquota"];
				$totalliquido = (float) $tributacao["total"];

				if(strlen(trim($tptribicms)) == 0){
					continue;
				}

				if($tptribicms == "T"){
					$totalicms = $totalliquido * $aliqicms / 100;
				}else{
					$totalicms = 0;
				}
				$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
				$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
				$maparesumoimposto->settptribicms($tptribicms);
				$maparesumoimposto->setaliqicms($aliqicms);
				$maparesumoimposto->settotalliquido($totalliquido);
				$maparesumoimposto->settotalicms($totalicms);
				if(!$maparesumoimposto->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		$paramfiscal->setnummaparesumo($paramfiscal->getnummaparesumo() + 1);
		if(!$paramfiscal->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	function importar_maparesumo_bancodados(){
		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();
		$paramfiscal = objectbytable("paramfiscal", $codestabelec, $this->con);

		// Data do movimento
		$dtmovto = $this->pdvconfig->getdtmovto();
		//$dtmovto = convert_date($dtmovto, "Y-m-d", "d/m/Y");
		// Carrega todos os ECFs
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($codestabelec);
		$ecf->setstatus("A");
		$arr_ecf = object_array($ecf);

		// Carrega as tributacoes do PDV
		$icmspdv = objectbytable("icmspdv", NULL, $this->con);
		$icmspdv->setcodestabelec($codestabelec);
		$arr_icmspdv = object_array($icmspdv);

		// Verifica quais caixa devem ser lidos
		$arr_caixa = array();
		$res = mssql_query("SELECT DISTINCT NumTerminal FROM IntReducaoZ WHERE CodLoja = ".$codestabelec, $this->con_siac);
		while($row = mssql_fetch_assoc($res)){
			$arr_caixa[] = $row["NumTerminal"];
		}

		$this->con->start_transaction();

		// Percorre todos os caixas e importa os dados do mesmo
		foreach($arr_caixa as $caixa){
			$arr_where = array();
			$arr_where[] = "CodLoja = ".$codestabelec;
			$arr_where[] = "NumTerminal = ".$caixa;
			$arr_where[] = "DataMovto = '".$dtmovto." 00:00:00.000'";
			$query = "SELECT NumSerieImpressora, NumTerminal, NumSeqOperacaoAbertura, NumSeqOperacaoFechamentoZ, ";
			$query .= "	NumContadorReinicioOperacao, GtVendaInicial, GtVendaFinal, GtCancelInicial, GtCancelFinal, ";
			$query .= "	GtDescInicial, GtDescFinal, NumFechamentoZ, CodLoja, ";
			$query .= "	CONVERT(VARCHAR(20), DataMovto, 120) AS DataMovto ";
			$query .= "FROM IntReducaoZ ";
			$query .= "WHERE ".implode(" AND ", $arr_where);
			$res = mssql_query($query, $this->con_siac);
			$arr = array();
			while($row = mssql_fetch_assoc($res)){
				$arr[] = $row;
			}
			foreach($arr as $row){

				// Procura pela ECF, caso nao encontre, cadastra automaticamente
				$achou = FALSE;
				foreach($arr_ecf as $ecf){
					if($ecf->getcaixa() == $row["NumTerminal"] && $ecf->getnumfabricacao() == $row["NumSerieImpressora"]){
						$achou = TRUE;
						break;
					}
				}
				if($achou){
					// Se for SAT, nao grava o mapa resumo
					if($ecf->getequipamentofiscal() === "SAT"){
						continue;
					}
				}else{
					$ecf = objectbytable("ecf", NULL, $this->con);
					$ecf->setcodestabelec($codestabelec);
					$ecf->setnumfabricacao($row["NumSerieImpressora"]);
					$ecf->setcaixa($row["NumTerminal"]);
					$ecf->setequipamentofiscal("ECF");
					if(!$ecf->save()){
						$this->con->rollback();
						return FALSE;
					}
					$arr_ecf[] = $ecf;
				}

				$arr_datamovto = explode(" ", $row["DataMovto"]);
				$datamovto = value_date($arr_datamovto[0]);

				$maparesumo = objectbytable("maparesumo", NULL, $this->con);
				$maparesumo->setcodestabelec($codestabelec);
				$maparesumo->setcaixa($row["NumTerminal"]);
				$maparesumo->setdtmovto($datamovto);
				$maparesumo->setoperacaoini($row["NumSeqOperacaoAbertura"]);
				$maparesumo->setoperacaofim($row["NumSeqOperacaoFechamentoZ"]);
				$maparesumo->setreiniciofim($row["NumContadorReinicioOperacao"]);
				$maparesumo->setcuponsfiscemit($row["NumSeqOperacaoFechamentoZ"] - $row["NumSeqOperacaoAbertura"]);
				$maparesumo->setnumeroreducoes($row["NumFechamentoZ"]);
				$maparesumo->setgtinicial($row["GtVendaInicial"]);
				$maparesumo->setgtfinal($row["GtVendaFinal"]);
				$maparesumo->settotalbruto($row["GtVendaFinal"] - $row["GtVendaInicial"]);
				$maparesumo->settotalcupomcancelado($row["GtCancelFinal"] - $row["GtCancelInicial"]);
				$maparesumo->settotaldescontocupom($row["GtDescFinal"] - $row["GtDescInicial"]);
				$maparesumo->settotalliquido($maparesumo->gettotalbruto() - $maparesumo->gettotalcupomcancelado() - $maparesumo->gettotaldescontocupom());
				$maparesumo->setnumseriefabecf($ecf->getnumfabricacao());
				$maparesumo->setcodecf($ecf->getcodecf());
				$maparesumo->setnummaparesumo($row["NumFechamentoZ"]);
				$maparesumo->setnumeroecf($ecf->getnumeroecf());

				if(!$maparesumo->save()){
					$this->con->rollback();
					return false;
				}

				$arr_where = array();
				$arr_where[] = "DataMovto = '".convert_date($datamovto, "Y-m-d", $this->db_format_date)."'";
				$arr_where[] = "CodLoja = ".$row["CodLoja"];
				$arr_where[] = "NumTerminal = ".$row["NumTerminal"];
				$query = "SELECT * FROM IntReducaoZTrib WHERE ".implode(" AND ", $arr_where);
				$res_trib = mssql_query($query, $this->con_siac);
				if($res_trib === false){
					$_SESSION["ERROR"] = "Houve um erro ao executar a instrução SQL:<br>{$query}<br><br>".mssql_get_last_message();
					$this->con->rollback();
					return false;
				}
				$arr_trib = array();
				while($row_trib = mssql_fetch_assoc($res_trib)){
					$arr_trib[] = $row_trib;
				}
				foreach($arr_trib as $row_trib){
					$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
					$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
					$maparesumoimposto->settptribicms(substr($row_trib["CodLegendaTrib"], 0, 1));
					$maparesumoimposto->setaliqicms($row_trib["ValorAliquota"]);
					$maparesumoimposto->settotalliquido($row_trib["ValorBaseCalculo"]);
					$maparesumoimposto->settotalicms($row_trib["ValorImposto"]);
					if(!$maparesumoimposto->save()){
						$this->con->rollback();
						return false;
					}
				}
			}
		}

		$paramfiscal->setnummaparesumo($paramfiscal->getnummaparesumo() + 1);
		if(!$paramfiscal->save()){
			$this->con->rollback();
			return false;
		}

		$this->con->commit();
		return TRUE;
	}

	function importar_venda($nome_arquivo, $leituraonline = FALSE, $dtmovto = NULL, $caixa = NULL, $cupom = NULL){
		if(strtolower(basename($nome_arquivo)) != "moviment.asc"){
			if(!$this->siac_conectar($nome_arquivo)){
				return FALSE;
			}
			return $this->importar_venda_bancodados($leituraonline, $dtmovto, $caixa, $cupom);
		}else{
			return $this->importar_venda_arquivo($nome_arquivo);
		}
	}

	private function importar_venda_arquivo($nome_arquivo){
		$this->limpar_dados();
		$arr_linha = array();
		if(!file_exists($nome_arquivo)){
			$_SESSION["ERROR"] = "O arquivo <b>".$nome_arquivo."</b> n&atilde;o pode ser encontrado.";
			return FALSE;
		}
		$file = fopen($nome_arquivo, "r");
		while(!feof($file)){
			$arr_linha[] = fgets($file, 4096);
		}
		fclose($file);

		$header = array_shift($arr_linha);
		$dtmovto = substr($header, 0, 6);
		$dtmovto = convert_date($dtmovto, "dmy", "d/m/Y");

		foreach($arr_linha as $i => $linha){
			setprogress($i / sizeof($arr_linha) * 100, "Lendo arquivo de vendas: ".($i + 1)." de ".sizeof($arr_linha));
			$tipo_registro = substr($linha, 0, 2);
			switch($tipo_registro){
				case "HV": // Header de venda
				case "HC": // Header de cupom
					$caixa = substr($linha, 2, 3); // Numero do caixa
					$cupom = substr($linha, 5, 6); // Numero do cupom
					$numtalao = substr($linha, 11, 6); // Numero do talao de venda
					$codfunc = substr($linha, 17, 6); // Codigo do funcionario
					$hrinicio = substr($linha, 23, 6); // Hora de inicio da venda
					$numdocto_1 = substr($linha, 29, 29); // Numero do documento (id primario)
					$numdocto_2 = substr($linha, 58, 29); // Numero do documento (id secundario)
					$codcategoria = substr($linha, 87, 4); // Codigo da categoria
					$tipopbm = substr($linha, 91, 1); // Tipo da PBM
					$codempresa = substr($linha, 92, 4); // Codigo da empresa conveniada
					$origem = substr($linha, 96, 1); // Origem do pedido
					$codclientepbm = substr($linha, 97, 19); // Codigo do cliente PBM
					$nomeclientepbm = substr($linha, 116, 40); // Nome do cliente PBM

					$hrinicio = substr($hrinicio, 0, 2).":".substr($hrinicio, 2, 2).":".substr($hrinicio, 4, 2);

					if(strlen(trim($numdocto_1, "0")) > 0){
						$numdocto_1 = substr($numdocto_1, -11);
						$numdocto_1 = substr($numdocto_1, 0, 3).".".substr($numdocto_1, 3, 3).".".substr($numdocto_1, 6, 3)."-".substr($numdocto_1, 9, 2);
					}else{
						$numdocto_1 = NULL;
					}

					$pdvvenda = new PdvVenda();
					$pdvvenda->setcaixa($caixa);
					$pdvvenda->setnumeroecf($caixa);
					$pdvvenda->setcodcliente($codclientepbm);
//					$pdvvenda->setcodfunc($codfunc);
					$pdvvenda->setcupom($cupom);
					$pdvvenda->setdata($dtmovto);
					$pdvvenda->sethora($hrinicio);
					$pdvvenda->setcpfcnpj($numdocto_1);
					$pdvvenda->setnomecliente($nomeclientepbm);

					$this->pdvvenda[] = $pdvvenda;
					break;

				case "DV": // Detalhe de venda
					$caixa = substr($linha, 2, 3); // Numero do caixa
					$cupom = substr($linha, 5, 6); // Numero do cupom
					$numitem = substr($linha, 11, 3); // Numero do item no cupom
					$codproduto = substr($linha, 14, 7); // Codigo do produto
					$coddepto = substr($linha, 21, 4); // Codigo do departamento
					$codfunc = substr($linha, 25, 7); // Codigo do vendedor
					$reserva = substr($linha, 32, 12); // Reserva (?)
					$quantidade = substr($linha, 44, 9) / 1000; // Quantidade
					$total = substr($linha, 53, 13) / 100; // Valor do item
					$desconto = substr($linha, 66, 13) / 100; // Desconto
					$codean = substr($linha, 79, 13); // Codigo EAN do item
					$legtributacao = substr($linha, 92, 2); // Legenda da tributacao
					$legreducao = substr($linha, 94, 2); // Legenda da reducao
					$valreducao = substr($linha, 96, 13); // Valor reduzido na base
					$tipoentrega = substr($linha, 109, 1); // Tipo de entrega
					$crm = substr($linha, 110, 9); // CRM
					$modovenda = substr($linha, 119, 1); // Modo de venda
					$piscofins_0 = substr($linha, 120, 1); // PIS/Cofins 0%
					$tipoproduto = substr($linha, 121, 1); // Tipo do produto
					$valorcopay = substr($linha, 122, 13); // Valor do copay
					$catpromocao = substr($linha, 135, 4); // Categoria da promocao
					$abatesublimite = substr($linha, 139, 1); // Abate do sub-limite
					$medicgenerico = substr($linha, 140, 1); // Medicamento generico
					$numboleto = substr($linha, 141, 9); // Numero do boleto
					$tipoitem = substr($linha, 150, 1); // Tipo do item (0 = venda; 1 = cancelamento)
					$codsupervisor = substr($linha, 151, 6); // Codigo do supervisor

					$preco = $total / $quantidade;

					$res = $this->con->query("SELECT tipoicms,aliqicms,redicms FROM icmspdv WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." AND infpdv = '".trim($legtributacao)."' AND tipoicms = 'T' AND redicms = 0");
					$icmspdv = $res->fetch(2);

					$pdvitem = new PdvItem();
					$pdvitem->setcodproduto($codproduto);
					$pdvitem->setdesconto($desconto);
					$pdvitem->setpreco($preco);
					$pdvitem->setquantidade($quantidade);
					$pdvitem->settotal($total - $desconto);
					if(strlen($icmspdv["tipoicms"]) == 0){
						$tptribicms = $legtributacao;
						$aliqicms = 0;
					}else{
						$tptribicms = $icmspdv["tipoicms"];
						$aliqicms = $icmspdv["aliqicms"];
					}
					$pdvitem->settptribicms($tptribicms);
					$pdvitem->setaliqicms($aliqicms);

					foreach(array_reverse($this->pdvvenda) as $j => $pdvvenda){
						if($pdvvenda->getcaixa() == $caixa && $pdvvenda->getcupom() == $cupom){
							switch($tipoitem){
								case "0": // Venda
									$pdvvenda->pdvitem[] = $pdvitem;
									$pdvvenda->setcodsupervisor($codsupervisor);
									break 2;
								case "1": // Cancelamento
									/* 									foreach($pdvvenda->pdvitem as $k => $pdvitem){
									  if($pdvitem->getcodproduto() == $codproduto){
									  unset($this->pdvvenda[$j]->pdvitem[$k]);
									  break 3;
									  }
									  }
									 */ break;
							}
						}
					}
					break;

				case "FV": // Finalizacao de venda (vem antes do registro DF)
					$valorfinaliz = substr($linha, 35, 13) / 100;
					break;

				case "DF": // Detalhe finalizacao (vem depois do registro FV)
					$caixa = substr($linha, 2, 3); // Caixa
					$cupom = substr($linha, 5, 6); // Cupom
					$finalizadora = substr($linha, 11, 2); // Tipo de finalizacao
					$valor = substr($linha, 13, 13) / 100; // Valor da finalizacao
					$status = substr($linha, 26, 1); // Status de indentificacao
					$numdoctoln = substr($linha, 27, 29); // Numero do documento LN
					$numdoctolb = substr($linha, 56, 29); // Numero do documento LB
					$ordemgeracaoparam = substr($linha, 85, 2); // Ordem de geracao da finalizadora pelo cadastro de parametros
					$tipotranstef = substr($linha, 87, 1); // Tipo de transacao de TEF
					$numcartao = substr($linha, 88, 22); // Numero do cartao
					$codsupervisor = substr($linha, 110, 6); // Numero do suppervisor que autorizou a digitacao do numero do cartao na TEF de credito
					$contacorrente = substr($linha, 116, 12); // Conta corrente
					$numparcela = substr($linha, 128, 12); // Conta corrente
					$numtransacao = substr($linha, 140, 16); // Numero da transacao se venda UNIK
					$ordemgeracao = substr($linha, 156, 2); // Ordem de geracao da finalizadora
					$bandeira = substr($linha, 158, 3); // Bandeira
					$redetef = substr($linha, 161, 3); // Rede TEF
					$codticked = substr($linha, 164, 2); // Codigo do ticked
					$valorencargo = substr($linha, 166, 13); // Valor do encargo financeiro
					$codempresa = substr($linha, 179, 4); // Codigo da empresa conveniada
					$qtdeparcelas = substr($linha, 183, 2); // Quantidade de parcelas
					$politicajuros = substr($linha, 185, 1); // Politica de juros

					$pdvfinalizador = new PdvFinalizador();
					$pdvfinalizador->setcaixa($caixa);
					$pdvfinalizador->setcodfinaliz($finalizadora);
					$pdvfinalizador->setcupom($cupom);
					$pdvfinalizador->setdata($dtmovto);

					$pdvfinalizador->setcpfcliente($numdocto_1); // Registro "HC" e "HV"
					$pdvfinalizador->setvalortotal($valorfinaliz); // Registro "FV"

					$this->pdvfinalizador[] = $pdvfinalizador;

					break;
			}
		}
		return TRUE;
	}

	private function importar_venda_bancodados($leituraonline, $dtmovto = NULL, $caixa = NULL, $cupom = NULL){
		setprogress(0, "Consultando vendas", TRUE);

		$estabelecimento = $this->pdvconfig->getestabelecimento();
		$codestabelec = $estabelecimento->getcodestabelec();

		// Limpa os objectos que armazenam os dados das vendas
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
/*
		// Verifica se deve carregar numeros inutilizados
		$query = "SELECT COUNT(*) FROM dbo.sysobjects WHERE id = object_id(N'[dbo].[InteNfNfe]')";
		$res = mssql_query($query, $this->con_siac);
		$row = mssql_fetch_assoc($res);
		$importar_inutilizado = ($row["count"] > 0);
*/
		// Inicia criacao da query
		if(TRUE || $importar_inutilizado){
			$query = implode(" ", array(
				"SELECT DISTINCT ".($leituraonline ? "TOP 50" : ""),
				"  CodTipoCupom, NumTerminal, NumSeqOperacao, IdPrimariaCliente, CodOperador,",
				"  NumIdentClienteImpressa, ChaveCupomFiscalEletronico,",
				"  CodLoja, DataMovto, CONVERT(VARCHAR(20), DtHrFimVenda, 120) AS DtHrFimVenda,",
				"  NumDocCliente, CodSupervisor, DescNomeCliente ",
				"FROM (",
				"	SELECT CodTipoCupom, NumTerminal, NumSeqOperacao, IdPrimariaCliente, CodOperador,",
				"    NumIdentClienteImpressa, ChaveCupomFiscalEletronico, DtHrFimVenda,",
				"    CodLoja, DataMovto, NumDocCliente, CodSupervisor, DescNomeCliente ",
				"  FROM IntCupons",
				"  UNION ALL",
				"  SELECT (CASE WHEN status = 'C' THEN 2 ELSE 3 END) AS CodTipoCupom, serie AS NumTerminal, numNF AS NumSeqOperacao, NULL AS IdPrimariaCliente, NULL AS CodOperador,",
				"    '00000000000000' AS NumIdentClienteImpressa, chaveAcesso AS ChaveCupomFiscalEletronico, DtHrSolicitacao AS DtHrFimVenda,",
				"    CodLoja, DtEmissao AS DataMovto, '00000000000000' AS NumDocCliente, null as CodSupervisor, '' as DescNomeCliente ",
				"  FROM InteNFNfe",
				"  WHERE status IN ('C', 'I')",
				"    AND CodTipoAmbiente = 1",
				") AS IntCupons "
			));
		}else{
			$query = implode(" ", array(
				"SELECT DISTINCT ".($leituraonline ? "TOP 50" : ""),
				"  CodTipoCupom, NumTerminal, NumSeqOperacao, IdPrimariaCliente, CodOperador,",
				"  NumIdentClienteImpressa, ChaveCupomFiscalEletronico,",
				"  CONVERT(VARCHAR(20), DtHrFimVenda, 120) AS DtHrFimVenda, NumDocCliente, CodSupervisor, DescNomeCliente ",
				"FROM IntCupons ",
			));
		}

		// Verifica se a data de movimento foi informada
		$dtmovto = (strlen($dtmovto) === 0 ? $this->pdvconfig->getdtmovto() : $dtmovto);
		if(strlen($dtmovto) > 0){
			$query .= "WHERE CodLoja = {$codestabelec} ";
			$query .= "	AND DataMovto = '".convert_date($dtmovto, "Y-m-d", $this->db_format_date)."' ";
			if(strlen($caixa) > 0){
				$query .= "	AND NumTerminal = {$caixa} ";
			}
			if(strlen($cupom) > 0){
				$query .= "	AND NumSeqOperacao = {$cupom} ";
			}
			$query .= "ORDER BY DtHrFimVenda ";
		}else{
			// Verifica a data do ultimo cupom importado
			$res = $this->con->query("SELECT MAX(dtmovto) AS dtmovto FROM cupom WHERE codestabelec = {$codestabelec}");
			$dtmovto_ult = $res->fetchColumn();

			// Puxa todos os cupons ja importados para fazer a comparacao no SIAC
			$arr_where_comparacao = array();
			if(strlen($dtmovto_ult) > 0){
				$query_ult = "SELECT dtmovto, caixa, cupom, status ";
				$query_ult .= "FROM cupom ";
				$query_ult .= "WHERE codestabelec = {$codestabelec} ";
				$query_ult .= "	AND dtmovto = '{$dtmovto_ult}' ";
				$query_ult .= " AND status NOT IN ('I') ";
				$res = $this->con->query($query_ult);
				$arr = $res->fetchAll();

				foreach($arr as $row){
					$arr_where_comparacao[] = "(DataMovto = '".convert_date($row["dtmovto"], "Y-m-d", $this->db_format_date)."' AND NumTerminal = '".$row["caixa"]."' AND NumSeqOperacao = '".$row["cupom"]."' AND CodTipoCupom = ".($row["status"] === "C" ? "2" : "1").")";
				}
			}

			// Busca os cupons novos
			$query .= "WHERE CodLoja = ".$codestabelec." ";
			if(count($arr_where_comparacao) > 0){
				$query .= "	AND NOT (".implode(" OR ", $arr_where_comparacao).") ";
			}
			$query .= "ORDER BY DtHrFimVenda DESC ";
		}

		$res = mssql_query($query, $this->con_siac);

		$i = 1;
		$n = mssql_num_rows($res);
		$arr_where_cupom = array();
		while($row = mssql_fetch_assoc($res)){
			setprogress(($i / $n * 100), "Importando vendas: ".$i." de ".$n);
			$i++;

			$arr_dthrfimvenda = explode(" ", $row["DtHrFimVenda"]);
			$dtfimvenda = value_date($arr_dthrfimvenda[0]);
			$hrfimvenda = value_time($arr_dthrfimvenda[1]);

			if($row["CodTipoCupom"] == "2"){
				foreach(array_reverse($this->pdvvenda) as $pdvvenda){
					if($pdvvenda->getdata() == $dtfimvenda && $pdvvenda->getcaixa() == $row["NumTerminal"] && $pdvvenda->getcupom() == $row["NumSeqOperacao"]){
						$pdvvenda->setstatus("C");
						continue 2;
					}
				}
			}

			$cupom = $row["NumSeqOperacao"];
			$chavecfe = $row["ChaveCupomFiscalEletronico"];
			if(strlen($chavecfe) === 44){
				if($estabelecimento->getuf() === "SP"){
					$seqecf = substr($chavecfe, 31, 6);
				}else{
					$seqecf = substr($chavecfe, 25, 9);
				}
			}else{
				$seqecf = $cupom;
			}

			$pdvvenda = new PdvVenda;
			$pdvvenda->setcaixa($row["NumTerminal"]);
			$pdvvenda->setcodcliente($row["IdPrimariaCliente"]);
			$pdvvenda->setcodfunc($row["CodOperador"]);
			$pdvvenda->setcpfcnpj(strlen(trim($row["NumDocCliente"], '0')) > 0 ? $row["NumDocCliente"] : $row["NumIdentClienteImpressa"]);
			$pdvvenda->setcupom($cupom);
			$pdvvenda->setdata($dtfimvenda);
			$pdvvenda->sethora($hrfimvenda);
			$pdvvenda->setseqecf($seqecf);
			$pdvvenda->setchavecfe($chavecfe);
			$pdvvenda->setnumeroecf($row["NumTerminal"]);
			$pdvvenda->setoperador($row["CodOperador"]);

			$pdvvenda->setcodsupervisor($row["CodSupervisor"]);
			$pdvvenda->setnomecliente($row["DescNomeCliente"]);
			switch($row["CodTipoCupom"]){
				case "3": $pdvvenda->setstatus("I"); break;
				case "2": $pdvvenda->setstatus("C"); break;
				default : $pdvvenda->setstatus("A"); break;
			}

			if($pdvvenda->getstatus() === "I"){
				$pdvvenda->setcupom("I".$pdvvenda->getcupom());
			}

			$this->pdvvenda[] = $pdvvenda;

			$arr_where_cupom[] = "(CodLoja = ".$row["CodLoja"]." AND DataMovto = '".convert_date($dtfimvenda, "Y-m-d", $this->db_format_date)."' AND NumTerminal = ".$row["NumTerminal"]." AND NumSeqOperacao = ".$row["NumSeqOperacao"].")";
		}

		// Busca os itens dos cupons
		if(count($arr_where_cupom) > 0){
			$arr_where_cupom = array_chunk($arr_where_cupom, 200);

			$arr = array();
			foreach($arr_where_cupom as $where_cupom){
				$query = "SELECT NumSeqItem, CodInternoProduto, QtdeItemVenda, ValorPrecoUnitario, ";
				$query .= "	ValorDesc, ValorVenda, CodLegendaTrib, ValorAliquotaTrib, NumTerminal, ";
				$query .= "	NumSeqOperacao, CodTipoOperacao, ";
				$query .= "	CONVERT(VARCHAR(20), DataMovto, 120) AS DataMovto, ";
				$query .= " CodSupervisor ";
				$query .= "FROM IntItensCupons ";
				$query .= "WHERE ".implode(" OR ", $where_cupom)." ";
				$res = mssql_query($query, $this->con_siac);
				while($row = mssql_fetch_assoc($res)){
					$arr[] = $row;
				}
			}
			$n = count($arr);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / $n * 100), "Importando itens: ".($i + 1)." de ".$n);

				$arr_datamovto = explode(" ", $row["DataMovto"]);
				$datamovto = value_date($arr_datamovto[0]);

				$pdvitem = new PdvItem;
				$pdvitem->setsequencial($row["NumSeqItem"]);
				$pdvitem->setcodproduto($row["CodInternoProduto"]);
				$pdvitem->setquantidade($row["QtdeItemVenda"]);
				$pdvitem->setpreco($row["ValorPrecoUnitario"]);
				$pdvitem->setdesconto($row["ValorDesc"]);
				$pdvitem->settotal($row["ValorVenda"] - $row["ValorDesc"]);
				$pdvitem->settptribicms(substr($row["CodLegendaTrib"], 0, 1));
				$pdvitem->setaliqicms($row["ValorAliquotaTrib"]);
				$pdvitem->setcodsupervisor($row["CodSupervisor"]);

				foreach($this->pdvvenda as $pdvvenda){
					if($pdvvenda->getdata() == $datamovto && $pdvvenda->getcaixa() == $row["NumTerminal"] && $pdvvenda->getcupom() == $row["NumSeqOperacao"]){
						switch($row["CodTipoOperacao"]){
							case "0": // Venda normal
								$pdvvenda->pdvitem[] = $pdvitem;
								break 2;
							case "1": // Cancelamento
								foreach($pdvvenda->pdvitem as $pdvitem2){
									if($pdvitem->getsequencial() == $pdvitem2->getsequencial()){
										$pdvitem2->setstatus("C");
										break 2;
									}
								}
								$pdvitem->setstatus("C");
								$pdvvenda->pdvitem[] = $pdvitem;
								break 2;
						}
						break;
					}
				}
			}

			$arr = array();
			foreach($arr_where_cupom as $where_cupom){
				$query = "SELECT NumTerminal, CodigoCliente, CodFinaliz, NumSeqOperacao, ValorFinaliz, ";
				$query .= "	CONVERT(VARCHAR(20), DtHrOperacao, 120) AS DtHrOperacao ";
				$query .= "FROM IntPagamentos ";
				$query .= "WHERE ".implode(" OR ", $where_cupom)." ";
				$res = mssql_query($query, $this->con_siac);
				$i = 1;
				$n = mssql_num_rows($res);
				while($row = mssql_fetch_assoc($res)){
					$arr[] = $row;
				}
			}
			foreach($arr as $row){
				setprogress(($i / $n * 100), "Importando pagamentos: ".$i." de ".$n);
				$i++;

				$arr_dthroperacao = explode(" ", $row["DtHrOperacao"]);
				$dtoperacao = value_date($arr_dthroperacao[0]);
				$hroperacao = value_time($arr_dthroperacao[1]);

				$pdvfinalizador = new PdvFinalizador;
				$pdvfinalizador->setcaixa($row["NumTerminal"]);
				$pdvfinalizador->setcodcliente($row["CodigoCliente"]);
				$pdvfinalizador->setcodfinaliz($row["CodFinaliz"]);
				$pdvfinalizador->setcupom($row["NumSeqOperacao"]);
				$pdvfinalizador->setdata($dtoperacao);
				$pdvfinalizador->sethora($hroperacao);
				$pdvfinalizador->setvalortotal(abs($row["ValorFinaliz"]));

				if(strlen($pdvfinalizador->getdata()) == 0){
					$pdvfinalizador->setdata(date("Y-m-d"));
					$pdvfinalizador->sethora(date("H:i:s"));
				}

				foreach($this->pdvvenda as $pdvvenda){
					if($pdvvenda->getdata() == $pdvfinalizador->getdata() && $pdvvenda->getcaixa() == $pdvfinalizador->getcaixa() && $pdvvenda->getcupom() == $pdvfinalizador->getcupom()){
						$pdvfinalizador->setcpfcliente($pdvvenda->getcpfcnpj());
						break;
					}
				}

				$this->pdvfinalizador[] = $pdvfinalizador;
			}
		}

		// Ajusta o valor das finalizadoras dinheiro
		$i = 1;
		$n = count($this->pdvvenda);
		foreach($this->pdvvenda as $pdvvenda){
			setprogress(($i / $n * 100), "Ajustando valores das finalizadoras: ".$i." de ".$n);
			if($pdvvenda->getstatus() == "A"){
				$valortotal_venda = 0;
				foreach($pdvvenda->pdvitem as $pdvitem){
					if($pdvitem->getstatus() == "A"){
						$valortotal_venda += $pdvitem->gettotal();
					}
				}
				$valortotal_finalizador = 0;
				$pdvfinalizador_dinheiro = null;
				foreach($this->pdvfinalizador as $pdvfinalizador){
					if($pdvvenda->getdata() == $pdvfinalizador->getdata() && $pdvvenda->getcaixa() == $pdvfinalizador->getcaixa() && $pdvvenda->getcupom() == $pdvfinalizador->getcupom()){
						$valortotal_finalizador += $pdvfinalizador->getvalortotal();
						if(is_null($pdvfinalizador_dinheiro) && $pdvfinalizador->getcodfinaliz() == "1"){
							$pdvfinalizador_dinheiro = $pdvfinalizador;
						}
					}
				}
				if(!is_null($pdvfinalizador_dinheiro)){
					$diferenca = $valortotal_finalizador - $valortotal_venda;
					$pdvfinalizador_dinheiro->setvalortotal($pdvfinalizador_dinheiro->getvalortotal() - $diferenca);
				}
			}
			$i++;
		}

		return TRUE;
	}

	private function valor_data($data){
		$data = value_date($data);
		$data = convert_date($data, "Y-m-d", "dmY");
		return $data;
	}

	private function valor_numerico_siac($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, ".", "");
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto, $tamanho){
		$texto = str_replace(array("\r", "\n", "'", "§", "º"), " ", $texto); //Campo observacao
		$texto = substr($texto, 0, $tamanho);
		$texto = str_pad($texto, $tamanho, " ", STR_PAD_RIGHT);
		return $texto;
	}

	// Retorna:
	// A - Arquivo
	// B - Banco de dados
	private function tipo_exportacao(){
		$dirpdvexp = $this->pdvconfig->getestabelecimento()->getdirpdvexp();
		if(!is_dir($dirpdvexp) && is_file($dirpdvexp)){
			if(!$this->siac_conectar($dirpdvexp)){
				return FALSE;
			}
			return "B";
		}else{
			return "A";
		}
	}

}
