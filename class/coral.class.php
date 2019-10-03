<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class Coral{

	private $con;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	private $limitar_arquivos = FALSE; // Se deve limitar a leitura para 50 arquivos
	private $arr_arquivo; // Armazena o nome dos arquivos da ultima importacao
	private $param_leituraonline;

	function __construct(){
		$this->limpar_dados();
		$processo = objectbytable("processo", "LEITURAONLINE", $this->con);
		$this->param_leituraonline = parse_ini_string($processo->getparametro());
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

	function armazenar_arquivos(){
		foreach($this->arr_arquivo as $i => $nome_arquivo){
			setprogress(($i / sizeof($this->arr_arquivo) * 100), "Movendo arquivos importados: ".($i + 1)." de ".sizeof($this->arr_arquivo));
			if(!file_exists($nome_arquivo)){
				continue;
			}
			$diretorio = dirname($nome_arquivo)."/";
			$arquivo = fopen($nome_arquivo, "r");
			$linha = fgets($arquivo, 4096);
			fclose($arquivo);
			$data = implode(" ", array_reverse(explode("/", $this->formatar_data(substr($linha, 36, 8))))); // Data da venda
			if(!is_dir($diretorio."IMPORTADO")){
				mkdir($diretorio."IMPORTADO");
			}
			if(!is_dir($diretorio."IMPORTADO/".$data)){
				mkdir($diretorio."IMPORTADO/".$data);
			}
			copy($nome_arquivo, $diretorio."IMPORTADO/".$data."/".basename($nome_arquivo));
			unlink($nome_arquivo);
		}
	}

	function exportar_produto($return = FALSE){
		$arr_linha = array();
		// Busca os produtos
		setprogress(0, "Buscando produtos", TRUE);
		$query = "SELECT DISTINCT CASE WHEN frentecaixa.balancaean = 'N' AND produto.pesounid = 'P' THEN produto.codproduto::text ELSE produtoean.codean END AS codean, ";
		$query .= "	produto.coddepto, ".$this->pdvconfig->sql_descricao().", produtoestab.codproduto, ";
		$query .= "	classfiscal.aliqredicms, classfiscal.aliqicms, classfiscal.tptribicms, ";
		$query .= "	".$this->pdvconfig->sql_tipopreco().", produto.pesado, icmspdv.infpdv, produto.alcoolico, ";
		$query .= "	COALESCE(ibptestabelec.aliqnacionalfederal, produto.aliqmedia, ncm.aliqmedia) AS aliqmedianacional, ";
		$query .= "	COALESCE(ibptestabelec.aliqestadual, classfiscal.aliqicms) AS aliqmediaestadual, ";
		$query .= "	classfiscal.codcst AS csticms, piscofins.codcst AS cstpiscofins, ncm.codigoncm, ";
		$query .= "	piscofins.aliqpis, piscofins.aliqcofins, (CASE WHEN estabelecimento.regimetributario='1' THEN classfiscal.csosn ELSE NULL END) AS csosn, COALESCE(produto.cest, cest.cest) AS cest  ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		$query .= "	INNER JOIN estabelecimento ON (produtoestab.codestabelec = estabelecimento.codestabelec) ";
		$query .= "	INNER JOIN frentecaixa ON (estabelecimento.codfrentecaixa = frentecaixa.codfrentecaixa) ";
		$query .= "INNER JOIN ( ";
		$query .= "	SELECT produtoestab.codproduto, produtoestab.codestabelec, classfiscal.* ";
		$query .= "	FROM produtoestab ";
		$query .= "	INNER JOIN estabelecimento ON (produtoestab.codestabelec = estabelecimento.codestabelec) ";
		$query .= "	INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "	LEFT JOIN estadotributo ON (estabelecimento.uf = estadotributo.uf AND estabelecimento.regimetributario = estadotributo.regimetributario AND produto.codproduto = estadotributo.codproduto) ";
		$query .= "	INNER JOIN classfiscal ON (COALESCE(estadotributo.codcfpdv,produto.codcfpdv) = classfiscal.codcf) ";
		$query .= ") AS classfiscal ON (produtoestab.codestabelec = classfiscal.codestabelec AND produto.codproduto = classfiscal.codproduto) ";
		$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "LEFT JOIN icmspdv ON (produtoestab.codestabelec = icmspdv.codestabelec AND classfiscal.tptribicms = icmspdv.tipoicms AND classfiscal.aliqicms = icmspdv.aliqicms AND classfiscal.aliqredicms = icmspdv.redicms) ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "LEFT JOIN cest ON (ncm.idcest = cest.idcest) ";
		$query .= "LEFT JOIN ibptestabelec ON (replace(ncm.codigoncm,'.','') = replace(ibptestabelec.codigoncm,'.','') AND produtoestab.codestabelec = ibptestabelec.codestabelec) ";
		$query .= "WHERE produtoestab.codestabelec = '".$this->pdvconfig->getestabelecimento()->getcodestabelec()."' AND produto.gerapdv = 'S' ";
		if(param("ESTOQUE", "CARGAITEMCOMESTOQ", $this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		$query .= "	AND produtoestab.disponivel = 'S' ";
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$query .= "ORDER BY 3";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$query  = "select codproduto ";
		$query .= "from composicao ";

		$res = $this->con->query($query);
		$arr_codproduto_composicaopai = $res->fetchAll(PDO::FETCH_COLUMN, 0);

		$query  = "select codproduto ";
		$query .= "from itcomposicao ";

		$res = $this->con->query($query);
		$arr_codproduto_composicaofilho = $res->fetchAll(PDO::FETCH_COLUMN, 0);

		if($this->pdvconfig->getestabelecimento()->getequipamentofiscal() == "SAT"){
			$param_frentecaixa_coralcfopsat = param('FRENTECAIXA','CORALCFOPSAT', $this->con);
			if(strlen($param_frentecaixa_coralcfopsat) > 0){
				$param_frentecaixa_coralcfopsat = explode("|", $param_frentecaixa_coralcfopsat);
				$coralcfopsat_principal = $param_frentecaixa_coralcfopsat[0];
				$coralcfopsat_substituicao = $param_frentecaixa_coralcfopsat[1];
			}else{
				$coralcfopsat_principal = "5102";
				$coralcfopsat_substituicao = "5405";
			}
		}

		$arr_codproduto = array();
		foreach($arr as $i => $row){
			// Verifica a tributacao no cadastro de PDV
			if(strlen($row["infpdv"]) == 0){
				echo messagebox("error", "", "N&atilde;o encontrado informa&ccedil;&otilde;es tributarias para o PDV do produto <b>".$row["codproduto"]."</b>: \n\n <b>Tipo de Tributa&ccedil;&atilde;o</b> = ".$row["tptribicms"]."\n<b>Aliquota </b> = ".$row["aliqicms"]."\n <b>Aliquota de Redu&ccedil;&atilde;o</b> = ".$row["aliqredicms"]."\n\n <a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributa&ccedil;&atilde;o do PDV.");
				die();
			}

			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando produtos: ".($i + 1)." de ".sizeof($arr));
			$linha = str_pad($row["codean"], 14, "0", STR_PAD_LEFT);
			$linha .= str_pad(substr($row["coddepto"], 0, 2), 2, "0", STR_PAD_LEFT);
			$linha .= str_pad(substr(removespecial(utf8_decode($row["descricao"])), 0, 24), 24, " ", STR_PAD_RIGHT);
			$linha .= str_pad(number_format($row["preco"], 2, "", ""), 10, "0", STR_PAD_LEFT);
			$linha .= str_pad(substr($row["infpdv"], 0, 3), 3, " ", STR_PAD_RIGHT);
			$linha .= str_pad(substr($row["pesado"], 0, 1), 1, "N", STR_PAD_LEFT);
			$linha .= str_pad(substr($row["alcoolico"], 0, 1), 1, "N", STR_PAD_LEFT);
			$linha .= str_pad(number_format($row["aliqmedianacional"], 2, "", ""), 6, "0", STR_PAD_LEFT);
			$linha .= str_pad(number_format($row["aliqmediaestadual"], 2, "", ""), 6, "0", STR_PAD_LEFT);
			if($this->pdvconfig->getestabelecimento()->getequipamentofiscal() == "SAT"){
				//$csosn = csosn($row["tptribicms"], $this->pdvconfig->getestabelecimento());
				$linha .= str_pad(substr($row["csticms"], 0, 1), 1, "0", STR_PAD_LEFT); // Origem
				$linha .= str_pad((strlen($row["csosn"]) > 0 ? $row["csosn"] : substr($row["csticms"], 1, 2)), 3, "0", STR_PAD_RIGHT); // CST/CSOSN
				$linha .= str_pad(number_format($row["aliqicms"], 2, "", ""), 5, "0", STR_PAD_LEFT); // Aliquota de ICMS
				$linha .= str_pad(removeformat($row["codigoncm"]), 8, "0", STR_PAD_LEFT); // NCM
				$linha .= ($row["tptribicms"] == "F" ? $coralcfopsat_substituicao : $coralcfopsat_principal); // CFOP
				if($this->pdvconfig->getestabelecimento()->getregimetributario() != "1"){
					$linha .= str_pad($row["cstpiscofins"], 2, "0", STR_PAD_LEFT); // CST PIS
					$linha .= str_pad(number_format($row["aliqpis"], 4, "", ""), 5, "0", STR_PAD_LEFT); // Aliquota de PIS
					$linha .= str_pad($row["cstpiscofins"], 2, "0", STR_PAD_LEFT); // CST Cofins
					$linha .= str_pad(number_format($row["aliqcofins"], 4, "", ""), 5, "0", STR_PAD_LEFT); // Aliquota de Cofins
				}else{
					$linha .= str_pad("49", 2, "0", STR_PAD_LEFT); // CST PIS
					$linha .= str_pad("0", 5, "0", STR_PAD_LEFT); // Aliquota de PIS
					$linha .= str_pad("49", 2, "0", STR_PAD_LEFT); // CST Cofins
					$linha .= str_pad("0", 5, "0", STR_PAD_LEFT); // Aliquota de Cofins
				}

			}
			$comple = true;

			if(in_array($row["codproduto"], $arr_codproduto_composicaopai)){
				$linha .= "M"; // tipo M = Mãe
				$linha .= str_pad(substr($row["codean"],-10), 10, "0", STR_PAD_LEFT); //  EAN
				$linha .= "00"; // quantidade
				$linha .= str_pad(substr($row["codean"],-14), 14, "0", STR_PAD_LEFT); //  EAN Pai
				$comple = false;
			}

			if(in_array($row["codproduto"], $arr_codproduto_composicaofilho)){
				$query  = "select produtoean.codean, itcomposicao.quantidade ";
				$query .= "from composicao ";
				$query .= "INNER JOIN itcomposicao ON (composicao.codcomposicao = itcomposicao.codcomposicao) ";
				$query .= "LEFT JOIN produtoean ON (composicao.codproduto = produtoean.codproduto) ";
				$query .= "WHERE itcomposicao.codproduto = {$row["codproduto"]} ";
				$query .= "LIMIT 1 ";


				$res = $this->con->query($query);
				$codeanpai = $res->fetch(PDO::FETCH_ASSOC);

				$linha .= "F"; // tipo M = Mãe
				$linha .= str_pad(substr($codeanpai["codean"],-10), 10, "0", STR_PAD_LEFT); //  EAN Pai
				$linha .= str_pad(substr(round($codeanpai["quantidade"]),-2),2,"0"); // quantidade
				$linha .= str_pad(substr($row["codean"],-14), 14, "0", STR_PAD_LEFT); //  EAN filho
				$comple = false;
			}

			if($comple){
				$linha .= str_pad("", 27,"0");
			}

			$linha .= str_pad(substr(removeformat($row["cest"]),0,7),7,"0",STR_PAD_LEFT);
			$arr_linha[] = $linha;
			$arr_codproduto[] = $row["codproduto"];
		}

		$this->pdvconfig->atualizar_precopdv($arr_codproduto);

		if($return){
			if($this->pdvconfig->produto_parcial()){
				return array($this->pdvconfig->file_create("PRGALT.TXT", $arr_linha, "w+", TRUE));
			}else{
				return array($this->pdvconfig->file_create("PRGPLU.TXT", $arr_linha, "w+", TRUE));
			}
		}else{
			if($this->pdvconfig->produto_parcial()){
				$this->pdvconfig->file_create("PRGALT.TXT", $arr_linha);
			}else{
				$this->pdvconfig->file_create("PRGPLU.TXT", $arr_linha);
			}
		}
	}

	function diretorio_venda($dir_name, $arquivar = FALSE, $leituraonline = FALSE){
		$param_frentecaixa_coraltratatrib = param("FRENTECAIXA", "CORALTRATATRIB", $this->con);
		$this->limpar_dados();

		// Acha todos os arquivos gerados pelo coral
		if(!is_dir($dir_name)){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->path;
			return FALSE;
		}
		$files = scandir($dir_name);
		foreach($files as $i => $file){
			if(!in_array(substr($file, 0, 2), array("RV", "LZ", "ZL"))){
				unset($files[$i]);

				if(is_file($dir_name.$file)){
					if(!is_dir($dir_name."IMPORTADO")){
						mkdir($dir_name."IMPORTADO");
					}
					if(!is_dir($dir_name."IMPORTADO/LIXO/")){
						mkdir($dir_name."IMPORTADO/LIXO/");
					}
					copy($dir_name.$file, $dir_name."IMPORTADO/LIXO/".$file);
					unlink($dir_name.$file);
				}
				continue;
			}
			// Verifica se o arquivo esta completo
			if(substr($file, 0, 2) == "RV" && strlen($file) == 11){
				$achou = FALSE;
				$linhas = file($dir_name.$file);

				if(in_array(substr($linhas[0], 0, 1) , array("E","A","X","7","Z"))){
					if(!is_dir($dir_name."IMPORTADO/RELATORIO/")){
						mkdir($dir_name."IMPORTADO/RELATORIO/");
					}
					copy($dir_name.$file, $dir_name."IMPORTADO/RELATORIO/".$file);
					unlink($dir_name.$file);
					unset($files[$i]);
					continue;
				}else{
					foreach($linhas as $linha){
						if(substr($linha, 0, 1) == "5"){
							$achou = TRUE;
							break;
						}
					}
					if(!$achou){
						unset($files[$i]);
						continue;
					}
				}
			}
			if(substr($file, 0, 2) == "LZ"){
				$this->importmaparesumo($file);
				unset($files[$i]);
			}
		}
		sort($files);

		if($leituraonline){
			if(strlen($this->param_leituraonline[quantidadeimportado]) > 0){
				$quantidadeimportado = $this->param_leituraonline[quantidadeimportado];
			}else{
				$quantidadeimportado = 50;
			}

			$files = array_slice($files, 0, $quantidadeimportado);
		}

		// Carrega as tributacoes do PDV
		$arr_icmspdv = array();
		$icmspdv = objectbytable("icmspdv", NULL, $this->con);
		$icmspdv->setcodestabelec($this->pdvconfig->getestabelecimento()->getcodestabelec());
		$arr_icmspdv_aux = object_array($icmspdv);
		foreach($arr_icmspdv_aux as $icmspdv){
			$arr_icmspdv[$icmspdv->getinfpdv()] = $icmspdv;
		}

		// Percorre todos os arquivos
		foreach($files as $i => $file_name){
			setprogress($i / sizeof($files) * 100, "Processando arquivos de venda: ".($i + 1)." de ".sizeof($files));

			$linhas = array();
			$file = fopen($dir_name.$file_name, "r");
			while(!feof($file)){
				$linha = fgets($file, 4096);
				$linhas[] = $linha;
			}
			fclose($file);

			if(!(count($linhas) > 1 && !end($linhas))){
				continue;
			}

			$this->arr_arquivo[] = $dir_name.$file_name;

			// Percorre todas as linhas
			foreach($linhas as $i => $linha){
				$tipo = substr($linha, 0, 1); // Tipo
				$cupom = substr($linha, 1, 6); // Numero do cupom
				$caixa = substr($linha, 7, 3); // Numero do caixa
				$codestabelec = substr($linha, 10, 4); // Numero da loja
				$resposta = substr($linha, 14, 8); // Resposta da impressora (deferente de zero quando o comando nao foi executado)
				// Tratamento para nao continuar quando houver um erro da impressora
				if((int) $resposta != 0){
					continue;
				}

				// Verifica se nao e a ultima linha do arquivo
				if(strlen($tipo) == 0){
					continue;
				}
				switch($tipo){
					case "0": // Abertura de venda
						$operador = substr($linha, 22, 14); // Operador
						$data = substr($linha, 36, 8); // Data da venda
						$hora = substr($linha, 44, 6); // Hora da venda
						$cpfcnpj = substr($linha, 50, 14); // Hora da venda
						$chavecfe = substr($linha, 97, 44); // Chave CFE
						$chavecfe = trim($chavecfe);
						$seqecf = trim(substr($chavecfe, 31, 6)); // Sequencial de ECF

						$data = $this->formatar_data($data);
						$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":".substr($hora, 4, 2);

						$pdvvenda = new PdvVenda();
						$pdvvenda->setcupom($cupom);
						$pdvvenda->setcaixa($caixa);
						$pdvvenda->setdata($data);
						$pdvvenda->sethora($hora);
						$pdvvenda->setnumeroecf($caixa);
						$pdvvenda->setcpfcnpj($cpfcnpj);
						if(strlen($chavecfe) == 44){
							$pdvvenda->setchavecfe($chavecfe);
							$pdvvenda->setseqecf($seqecf);
						}else{
							$pdvvenda->setseqecf($cupom);
						}

						$this->pdvvenda[] = $pdvvenda;

						// Limpa o codigo do cliente
						$codcliente = NULL;
						break;

					case "1": // Venda de item
						$itemimp = substr($linha, 22, 6); // Numero do item (retornado pela impressora)
						$codproduto = substr($linha, 48, 14); // Codigo do produto (ean)
						$quantidade = substr($linha, 62, 9) / 1000; // Quantidade (*1000)
						$preco = substr($linha, 71, 12) / 100; // Preco (*100)
						$tributacao = substr($linha, 83, 3); // Tributacao do produto
						$descricao = substr($linha, 86, 40); // Descricao do produto
						$desconto = substr($linha, 126, 12) / 100; // Desconto (*100) (em valor ou percentual, verificar proximo campo ($tipodesconto)) (*100)
						$tipodesconto = substr($linha, 138, 1); // Tipo de desconto ('$' ou '%')
						$totalitem = substr($linha, 139, 12) / 100; // Valor total do item (*100)
						$totaldesconto = substr($linha, 151, 12) / 100; // Valor total do desconto (*100)
						$pesado = substr($linha, 196, 1); // Pesado

						$codproduto = ltrim($codproduto, "0");
						if(strlen($codproduto) <= 8){
							$codproduto = str_pad($codproduto, 8, "0", STR_PAD_LEFT);
						}else{
							$codproduto = str_pad($codproduto, 13, "0", STR_PAD_LEFT);
						}

						if($param_frentecaixa_coraltratatrib == "1"){
							$icmspdv = $arr_icmspdv[trim($tributacao)];
							if(is_object($icmspdv)){
								$tptribicms = ($icmspdv->gettipoicms() == "R" ? "T" : $icmspdv->gettipoicms());
								if($icmspdv->getaliqicms() == 4.5){
									$aliqicms = $icmspdv->getaliqicms();
								}else{
									$aliqicms = round($icmspdv->getaliqicms() * (1 - $icmspdv->getredicms() / 100));
								}
							}else{
								$_SESSION["ERROR"] = "Tributa&ccedil;&atilde;o n&atilde;o cadastrada: ".$tributacao;
								return FALSE;
							}
						}elseif($param_frentecaixa_coraltratatrib == "2"){
							$tptribicms = substr($tributacao, 0, 1);
							if($tptribicms == "T"){
								if(substr($tributacao, 1, 2) == "01"){
									$aliqicms = "18.00";
								}elseif(substr($tributacao, 1, 2) == "02"){
									$aliqicms = "07.00";
								}elseif(substr($tributacao, 1, 2) == "03"){
									$aliqicms = "12.00";
								}elseif(substr($tributacao, 1, 2) == "04"){
									$aliqicms = "25.00";
								}elseif(substr($tributacao, 1, 2) == "05"){
									$aliqicms = "11.00";
								}elseif(substr($tributacao, 1, 2) == "07"){
									$aliqicms = "4.50";
								}else{
									echo "Tributa&ccedil;&atilde;o <b>".$tributacao."</b> n&atilde;o esta cadastrada.";
									die(messagebox("error", "", "Tributa&ccedil;&atilde;o <b>".$tributacao."</b> n&atilde;o esta cadastrada."));
								}
							}else{
								if(!in_array($tptribicms, array("F", "I", "R", "N"))){
									echo "Tributa&ccedil;&atilde;o <b>".$tributacao."</b> n&atilde;o esta cadastrada.";
									die(messagebox("error", "", "Tributa&ccedil;&atilde;o <b>".$tributacao."</b> n&atilde;o esta cadastrada."));
								}
								$aliqicms = "00.00";
							}
						}

						$pdvitem = new PdvItem();
						$pdvitem->setsequencial($itemimp);
						$pdvitem->setcodproduto($codproduto);
						$pdvitem->setquantidade($quantidade);
						$pdvitem->setpreco($preco);
						$pdvitem->setdesconto($totaldesconto);
						$pdvitem->settotal($totalitem - $totaldesconto);
						$pdvitem->settptribicms($tptribicms);
						$pdvitem->setaliqicms($aliqicms);

						foreach(array_reverse($this->pdvvenda) as $i => $pdvvenda){
							if($pdvvenda->getcaixa() == $caixa && $pdvvenda->getcupom() == $cupom){
								if($pdvvenda->getstatus() == "A"){
									$pdvvenda->pdvitem[] = $pdvitem;
								}
								break;
							}
						}
						break;

					case "2": // Cancelamento de item
						$itemimp = substr($linha, 22, 6); // Numero do item (retornado pela impressora)
						$codproduto = substr($linha, 48, 14); // Codigo do produto (ean)
						$quantidade = substr($linha, 62, 9) / 1000; // Quantidade (*1000)
						$preco = substr($linha, 71, 12) / 100; // Preco (*100)

						$codproduto = ltrim($codproduto, "0");
						if(strlen($codproduto) <= 8){
							$codproduto = str_pad($codproduto, 8, "0", STR_PAD_LEFT);
						}else{
							$codproduto = str_pad($codproduto, 13, "0", STR_PAD_LEFT);
						}

						foreach(array_reverse($this->pdvvenda) as $i => $pdvvenda){
							if($pdvvenda->getcaixa() == $caixa && $pdvvenda->getcupom() == $cupom){
								foreach(array_reverse($pdvvenda->pdvitem) as $j => $pdvitem){
									if($pdvitem->getcodproduto() == $codproduto && $pdvitem->getsequencial() == $itemimp){
										$pdvitem->setstatus("C");
										break 2;
									}
								}
							}
						}
						break;

					case "3": // Finalizador de venda
						$codfinaliz = substr($linha, 22, 2); // Codigo da finalizadora
						$descricao = substr($linha, 24, 20); // Descricao da finalizadora
						$legenda = substr($linha, 44, 20); // Legenda da finalizadora
						$valorbruto = substr($linha, 64, 12) / 100; // Valor bruto da venda (*100)
						$troco = substr($linha, 76, 12) / 100; // Valor do troco (*100)
						$valorliquido = substr($linha, 88, 12) / 100; // Valor liquido da venda (*100)
						$desconto = substr($linha, 100, 12) / 100; // Desconto (em valor ou percentual, verificar proximo campo ($tipodesconto)) (*100)
						$totaldesconto = substr($linha, 112, 12) / 100; // Valor total do desconto (*100)
						$tipodesconto = substr($linha, 124, 1); // Tipo de desconto ('$' ou '%')
						$contravale = substr($linha, 125, 12); // Valor do contra-vale

						$pdvvenda = $this->pdvvenda[sizeof($this->pdvvenda) - 1];

						if(is_object($pdvvenda)){
							if($pdvvenda->getcupom() != $cupom){
								continue;
							}
							$pdvfinalizador = new PdvFinalizador();
							$pdvfinalizador->setcupom($pdvvenda->getcupom());
							$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
							$pdvfinalizador->setdata($pdvvenda->getdata());
							$pdvfinalizador->sethora($pdvvenda->gethora());
							$pdvfinalizador->setcodfinaliz($codfinaliz);
							//$pdvfinalizador->setvalortotal($valorbruto - $desconto);
							$pdvfinalizador->setvalortotal($valorbruto - $troco);
							$pdvfinalizador->setcodcliente($codcliente); // Essa variavel e preenchida no registro D
							$this->pdvfinalizador[] = $pdvfinalizador;

							// Aplica desconto do cupom nos itens
							if($totaldesconto > 0){
								$arr_pdvitem = array();

								foreach($pdvvenda->pdvitem as $pdvitem){
									if($pdvitem->getstatus() == "A"){
										$arr_pdvitem[] = $pdvitem;
									}
								}

								$totalvenda = 0;
								foreach($arr_pdvitem as $pdvitem){
									$totalvenda += $pdvitem->gettotal();
								}

								$totaldesconto = ($totalvenda > 0 ? $totaldesconto / $totalvenda : 0);
								$soma_desconto = 0;
								foreach($arr_pdvitem as $pdvitem){
									$pdvitem->setdesconto(number_format(($pdvitem->gettotal() * $totaldesconto), 2, ".", ""));
									$pdvitem->settotal($pdvitem->gettotal() - $pdvitem->getdesconto());
									$soma_desconto += $pdvitem->getdesconto();
								}
								$desconto_old = $pdvitem->getdesconto();
								$desconto_new = number_format(($pdvitem->getdesconto() - ($soma_desconto - ($totaldesconto * $totalvenda))), 2, ".", "");
								$pdvitem->setdesconto($desconto_new);
								$pdvitem->settotal($pdvitem->gettotal() + $desconto_old - $desconto_new);
							}
						}
						break;

					case "6": // Cancelamento de venda
						$operador = substr($linha, 22, 14); // Operador
						$data = substr($linha, 36, 8); // Data da venda
						$hora = substr($linha, 44, 6); // Hora da venda
						$cupomcanc = substr($linha, 50, 6); // Sequencial do cupom cancelado

						$data = $this->formatar_data($data);
						$hora = substr($data, 0, 2).":".substr($data, 2, 2).":".substr($data, 4, 2);

						if(strlen(trim($cupomcanc, "0")) > 0){
							$cupom = $cupomcanc;
						}

						$found = FALSE;
						foreach(array_reverse($this->pdvvenda) as $i => $pdvvenda){
							if($pdvvenda->getcaixa() == $caixa && $pdvvenda->getcupom() == $cupom){
								$pdvvenda->setstatus("C");
								$found = TRUE;
								break;
							}
						}
						if(!$found){
							$pdvvenda = new PdvVenda();
							$pdvvenda->setcupom($cupom);
							$pdvvenda->setcaixa($caixa);
							$pdvvenda->setdata($data);
							$pdvvenda->sethora($hora);
							$pdvvenda->setnumeroecf($caixa);
							$pdvvenda->setstatus("C");
							$this->pdvvenda[] = $pdvvenda;
						}
						break;
					case "D": // Dados diversos (para informacoes complementares)
						$tipo = substr($linha, 22, 1); // Tipo
						$subtipo = substr($linha, 23, 1); // Subtipo
						// Verifica se e convenio
						if($tipo == "W" && $subtipo == "C"){
							$codcliente = substr($linha, 35, 10); // Codigo do cliente
							$pdvvenda->setcodcliente($codcliente);
						}

						break;
				}
			}
		}

		$this->armazenar_arquivos();
		// Recalcula a quantidade dos produtos baseado no total e no preco
		// Esta comentado pois em venda com desconto estava rateando na quantidade sendo que a mesma é unitaria
//		foreach($this->pdvvenda as $pdvvenda){
//			foreach($pdvvenda->pdvitem as $pdvitem){
//				$quantidade = ($pdvitem->gettotal() - $pdvitem->getdesconto() + $pdvitem->getacrescimo()) / $pdvitem->getpreco();
//				$pdvitem->setquantidade($quantidade);
//			}
//		}

		return TRUE;
	}

	function getpdvvenda(){
		return $this->pdvvenda;
	}

	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}

	function limitar_arquivos($bool){
		if(is_bool($bool)){
			$this->limitar_arquivos = $bool;
		}
	}

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
		$this->arr_arquivo = array();
	}

	private function codproduto($codproduto){
		$codproduto = trim($codproduto);
		if(strlen($codproduto) == 14 && substr($codproduto, 0, 1) == "0"){
			$codproduto = substr($codproduto, 1);
		}
		if(strlen(ltrim($codproduto, "0")) == 8){
			$codproduto = ltrim($codproduto, "0");
		}
		if(strlen(ltrim($codproduto)) > 7){
			$produtoean = objectbytable("produtoean", null);
			$produtoean->setcodean(ltrim($codproduto));
			$obj_produtoean = object_array($produtoean);
			$_produtoean = array_shift($obj_produtoean);
			if(is_object($_produtoean)){
				$codproduto = $_produtoean->getcodproduto();
			}elseif(strlen($codproduto) == 13){
				$produtoean = objectbytable("produtoean", null);
				$produtoean->setcodean(substr($codproduto, 0, 8));
				$obj_produtoean = object_array($produtoean);
				$_produtoean = array_shift($obj_produtoean);
				if(is_object($_produtoean)){
					$codproduto = $_produtoean->getcodproduto();
				}
			}
		}
		return $codproduto;
	}

	private function formatar_data($data){
		if(strlen($data) == 8){
			return substr($data, 0, 2)."/".substr($data, 2, 2)."/".substr($data, 4);
		}else{
			return $data;
		}
	}

	public function importmaparesumo($find_file = TRUE){
		$paramfiscal = objectbytable("paramfiscal", $this->pdvconfig->getestabelecimento()->getcodestabelec(), $this->con);

		// Tenta abrir o diretorio dos arquivos
		if(!($dir = @opendir($this->pdvconfig->getestabelecimento()->getdirpdvimp()))){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->pdvconfig->getestabelecimento()->getdirpdvimp();
			return FALSE;
		}
		if($find_file === TRUE){
			// Acha todos os arquivos gerados pelo coral
			$files = array();
			while($file = readdir($dir)){
				if(strlen($file) == 12 && substr($file, 0, 2) == "LZ"){
					$files[] = $file;
				}
			}

			if(sizeof($files) == 0){
				return TRUE;
			}
		}else{
			$files = array($find_file);
		}
		$this->con->start_transaction();

		// Percorre todos os arquivos
		foreach($files as $i => $file){
			$nome_arquivo = $file;
			setprogress(($i + 1), "Processando mapa resumo: ".($i + 1)." de ".sizeof($files));
			$dtmovto = substr($file, 2, 6);
			$dtmovto = date("d/m/Y", mktime(0, 0, 0, substr($dtmovto, 2, 2), substr($dtmovto, 0, 2), substr($dtmovto, 4, 2)));
			$dtmovto_query = convert_date($dtmovto, "d/m/Y", "Y-m-d");
			$caixa = substr($file, 9, 3);

			if($caixa == "TXT"){
				continue;
			}

			$res = $this->con->query("SELECT equipamentofiscal FROM ecf WHERE caixa = '$caixa' AND codestabelec = {$this->pdvconfig->getestabelecimento()->getcodestabelec()} ");
			$equipamentofiscal = $res->fetchColumn();

			if($equipamentofiscal == "SAT"){
				continue;
			}

			// Busca as datas que nao deverao ser importadas
			$res = $this->con->query("SELECT DISTINCT dtmovto,caixa FROM maparesumo WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." AND dtmovto = '".$dtmovto_query."' ");
			$arr = $res->fetchAll(2);
			$arr_dtmovto = array();
			$arr_caixa = array();
			foreach($arr as $row){
				$arr_dtmovto[] = convert_date($row["dtmovto"], "Y-m-d", "d/m/Y");
				$arr_caixa[] = str_pad($row["caixa"], 3, "0", STR_PAD_LEFT);
			}


			if(in_array($dtmovto, $arr_dtmovto) && in_array($caixa, $arr_caixa)){
				$data = substr($dtmovto, 6, 4)." ".substr($dtmovto, 3, 2)." ".substr($dtmovto, 0, 2);
				if(!is_dir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO")){
					mkdir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO");
				}
				if(!is_dir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data)){
					mkdir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data);
				}

				copy($this->pdvconfig->getestabelecimento()->getdirpdvimp().$nome_arquivo, $this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data."/".basename($nome_arquivo));
				unlink($this->pdvconfig->getestabelecimento()->getdirpdvimp().$nome_arquivo);
				continue;
			}
			$caixa = substr($file, 9, 3);
			$linhas = array();
			$file = fopen($this->pdvconfig->getestabelecimento()->getdirpdvimp().$file, "r");
			while(!feof($file)){
				$linhas[] = fgets($file, 1024);
			}

			$maparesumo = objectbytable("maparesumo", NULL, $this->con);
			$maparesumo->setcodestabelec($this->pdvconfig->getestabelecimento()->getcodestabelec());
			$maparesumo->setcaixa($caixa);
			$maparesumo->setnumeroecf($caixa);
			$maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
			$maparesumo->setdtmovto($dtmovto_query);

			$arr_maparesumoimposto = array();
			foreach($linhas as $linha){
				$arr = explode("=", trim($linha));
				$campo = $arr[0];
				$valor = $arr[1];
				if($valor <= 0 && $campo != "NSERIE"){
					continue;
				}
				switch($campo){
					case "GTI":
						$maparesumo->setgtinicial($valor / 100);
						break;
					case "GTF":
						$maparesumo->setgtfinal($valor / 100);
						break;
					case "NCANCITEM":
						$maparesumo->setitenscancelados($valor);
						break;
					case "VCANCITEM":
						$maparesumo->settotalitemcancelado($valor / 100);
						break;
					case "NCANCCUPOM":
						$maparesumo->setcuponscancelados($valor);
						break;
					case "VCANCCUPOM":
						$maparesumo->settotalcupomcancelado($valor / 100);
						break;
					case "NDESCONTOS":
						$maparesumo->setnumerodescontos($valor);
						break;
					case "VDESCONTOS":
						$maparesumo->settotaldescontocupom($valor / 100);
						break;
					case "VBRUTA":
						$maparesumo->settotalbruto($valor / 100);
						break;
					case "VLIQUIDA":
						$maparesumo->settotalliquido($valor / 100);
						break;
					case "REDUCOES":
						$maparesumo->setnumeroreducoes($valor);
						break;
					case "REINICIOS":
						$maparesumo->setreinicioini($valor - 1);
						$maparesumo->setreiniciofim($valor);
						break;
					case "NSERIE":
						$valor = strtoupper($valor);
						$ecf = objectbytable("ecf", NULL, $this->con);
						$ecf->setnumfabricacao($valor);
						$arr_ecf = object_array($ecf);
						if(sizeof($arr_ecf) > 0){
							$ecf = array_shift($arr_ecf);
						}else{
							$ecf->setcodestabelec($this->pdvconfig->getestabelecimento()->getcodestabelec());
							$ecf->setcaixa($caixa);
							$ecf->setequipamentofiscal("ECF");
							if(!$ecf->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
						$maparesumo->setcodecf($ecf->getcodecf());
						$maparesumo->setnumseriefabecf($ecf->getnumfabricacao());
						break;
					case "COOI":
						$maparesumo->setoperacaoini($valor);
						break;
					case "COOF":
						$maparesumo->setoperacaofim($valor);
						break;
					case "I":
					case "N":
					case "F":
					case "T0700":
					case "T1100":
					case "T1800":
					case "T1200":
					case "T2500":
					case "T0400":
					case "T0450":
						$tptribicms = substr($campo, 0, 1);
						$aliqicms = substr($campo, 1) / 100;
						$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
						$maparesumoimposto->settptribicms($tptribicms);
						$maparesumoimposto->setaliqicms($aliqicms);
						$maparesumoimposto->settotalliquido($valor / 100);
						$maparesumoimposto->settotalicms(($valor / 100) * ($aliqicms / 100));
						$arr_maparesumoimposto[] = $maparesumoimposto;
						break;
				}
			}
			$data = substr($dtmovto, 6, 4)." ".substr($dtmovto, 3, 2)." ".substr($dtmovto, 0, 2);
			fclose($file);
			if(!is_dir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO")){
				mkdir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO");
			}
			if(!is_dir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data)){
				mkdir($this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data);
			}

			copy($this->pdvconfig->getestabelecimento()->getdirpdvimp().$nome_arquivo, $this->pdvconfig->getestabelecimento()->getdirpdvimp()."IMPORTADO/".$data."/".basename($nome_arquivo));
			unlink($this->pdvconfig->getestabelecimento()->getdirpdvimp().$nome_arquivo);
			closedir($dir);

			if(!is_object($ecf)){
				continue;
			}

			// Verifica se ja existe o mapa resumo
			$count_maparesumo = $this->con->query("SELECT * FROM maparesumo WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." AND caixa = ".$caixa." AND dtmovto = '".convert_date($dtmovto, "d/m/Y", "Y-m-d")."' AND codecf = ".$ecf->getcodecf());
			if($count_maparesumo->rowCount() > 0){
				continue;
			}

			if(!$maparesumo->save()){
				$this->con->rollback();
				return FALSE;
			}
			foreach($arr_maparesumoimposto as $maparesumoimposto){
				$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
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

}