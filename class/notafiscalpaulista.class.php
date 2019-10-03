<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class NotaFiscalPaulista{

	private $con;
	private $arr_pdvvenda;
	private $arr_pdvfinalizador;
	private $arr_codean;
	private $arr_maparesumo; // Array de objetos da tabela mapa resumo
	private $arr_arquivos_lidos; // Array com os arquivos lidos
	private $estabelecimento;
	private $balanca;
	private $data_inicial;
	private $data_final;
	private $caixa;
	private $numeroecf;
	private $arquivo;
	private $gerardataarq;
	private $gerarE00;

	function __construct(Connection $con){
		$this->con = $con;
		$this->arr_ecf = array();
		$this->arr_maparesumo = array();
		$this->arr_arquivos_lidos = array();
		$this->limpar_dados();
	}

	function importar_venda(Estabelecimento $estabelecimento, $string_xml = null){
		$this->limpar_dados();
		$this->estabelecimento = $estabelecimento;
		$this->balanca = objectbytable("balanca", $this->estabelecimento->getcodbalanca(), $this->con);

		// Verifica se o diretorio de importacao existe
		$dirname = $estabelecimento->getdirpdvimp();
		if(!is_dir($dirname) && strlen($string_xml) == 0){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>{$dirname}";
			return FALSE;
		}

		// Verifica se a balanca existe
		if(!$this->balanca->exists()){
			$_SESSION["ERROR"] = "Por favor, defina a balança utilizada pelo estabelecimento.";
			return FALSE;
		}

		// Carrega lista de codigo de barras para ajustar o que vem do arquivo da impressora
		$res = $this->con->query("SELECT codean, LTRIM(codean, '0') FROM produtoean");
		$arr = $res->fetchAll(2);
		$this->arr_codean = array();
		foreach($arr as $row){
			$this->arr_codean[$row[1]] = $row[0];
		}

		// Lista arquivos de um diretorio
		$arr_filename = scandir($dirname);
		$n = count($arr_filename);
		foreach($arr_filename as $i => $filename){
			setprogress((($i + 1) / $n * 100), "Processando arquivo ".($i + 1)." de {$n}");
			if(!in_array($filename, $this->arr_arquivos_lidos)){
				if(strtolower(substr($filename, -4)) === ".xml"){
					if($this->processar_arquivo_sat($dirname.$filename)){
						$this->arr_arquivos_lidos[] = $filename;
					}else{
						return FALSE;
					}
				}elseif(strlen($filename) === 12){
					if($this->processar_arquivo_ecf($dirname, $filename)){
						$this->arr_arquivos_lidos[] = $filename;
					}else{
						return FALSE;
					}
				}
			}
		}
		return TRUE;
	}

	public function exportar_venda(){
		setprogress(0, "Carregando Arquivos");

		$this->con = new Connection();

		$query = "SELECT DISTINCT maparesumo.dtmovto, maparesumo.totalcupomcancelado ,maparesumo.totaldescontocupom, maparesumo.codmaparesumo,ecf.numfabricacao, ecf.caixa AS caixa, maparesumo.operacaoini, maparesumo.operacaofim ";
		$query .= " ,maparesumo.numeroreducoes, maparesumo.reiniciofim, maparesumo.totalbruto, maparesumo.gtfinal, maparesumo.numerodescontos,maparesumo.cuponscancelados ";
		$query .= " , estabelecimento.razaosocial, estabelecimento.cpfcnpj, estabelecimento.endereco, maparesumo.reinicioini, maparesumo.reiniciofim, maparesumo.gtfinal ";
		$query .= " , maparesumo.operacaofim, ecf.modelo, ecf.marca, ecf.numeroecf, estabelecimento.rgie ";
		$query .= "FROM maparesumo ";
		$query .= "INNER JOIN ecf ON (maparesumo.codecf = ecf.codecf) ";
		$query .= "INNER JOIN estabelecimento ON (ecf.codestabelec = estabelecimento.codestabelec) ";
		$query .= "WHERE maparesumo.codestabelec = ".$this->estabelecimento->getcodestabelec()." AND maparesumo.dtmovto >='".$this->data_inicial."' ";
		$query .= " AND  maparesumo.dtmovto <= '".$this->data_final."' AND maparesumo.totalbruto > 0 ";
		$query .= " AND ecf.status = 'A' AND ecf.equipamentofiscal = 'ECF' ";
		if(strlen($this->numeroecf) > 0){
			$query .= " AND maparesumo.numeroecf = ".$this->numeroecf;
		}
		$query .= " ORDER BY maparesumo.dtmovto, ecf.numeroecf ";

		$res = $this->con->query($query);
		$arr_maparesumo = $res->fetchAll();

		if(sizeof($arr_maparesumo) == 0){
			echo messagebox("error", "", "Nenhum movimento encontrado para os filtros informados.");
			die;
		}

		$arr_arquivo = array();
		$arquivo_n_gerado = array();
		foreach($arr_maparesumo AS $maparesumo){
			$dialog = convert_date($maparesumo["dtmovto"], "Y-m-d", "d/m/Y")." ".$maparesumo["numeroecf"]." ";

			$_data = explode("-", $maparesumo["dtmovto"]);
			if($this->gerardataarq == "N"){
				$alpha = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
				$ext = $alpha[(int) $_data[2]].$alpha[(int) $_data[1]].$alpha[(int) substr($_data[0], -2)];
				/*
				  if($_data[2] > 15){
				  $alphabet = array("g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
				  //$ext = $alphabet[$_data[2]-14].dechex($_data[1]).dechex(substr($_data[0],-2));
				  $ext = $alphabet[$_data[2]-16].dechex($_data[1]).dechex(substr($_data[0],-2));
				  }else{
				  $ext = dechex($_data[2]).dechex($_data[1]).dechex(substr($_data[0],-2));
				  }
				 */
				$this->arquivo = "../temp/fiscal/".substr($maparesumo["numfabricacao"], 0, 2).substr($maparesumo["numfabricacao"], -6).".".$ext;
				$arr_arquivo[] = substr($maparesumo["numfabricacao"], 0, 2).substr($maparesumo["numfabricacao"], -6).".".$ext;
			}else{
				$this->arquivo = "../temp/fiscal/".substr($maparesumo["numfabricacao"], 0, 2).substr($maparesumo["numfabricacao"], -6)."_".$_data[0].$_data[1].$_data[2].".txt";
				$arr_arquivo[] = substr($maparesumo["numfabricacao"], 0, 2).substr($maparesumo["numfabricacao"], -6)."_".$_data[0].$_data[1].$_data[2].".txt";
			}


			if(!file_exists("../temp/fiscal/")){
				mkdir("../temp/fiscal/", 0777, true);
			}
			if(is_file($this->arquivo)){
				unlink($this->arquivo);
			}

			if(!$_SESSION["geranfp"]){
				return pararprocesso($arr_arquivo);
			}

			/*			 * *********************************
			  E00 Identificação da Software House
			 * ********************************* */


			if($gerarE00 == "S"){
				$E00 = "E00";
				$E00 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Número de fabricação do ECF
				$E00 .= " "; // Letra indicativa de MF adicional
				$E00 .= "01"; // Número de ordem do usuário do ECF
				$E00 .= $this->valor_texto("ECF-IF", 7, false); // Tipo de ECF
				$E00 .= $this->valor_texto($maparesumo["marca"], 20, false); // Marca do ECF
				$E00 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
				$E00 .= $this->valor_numerico($maparesumo["reiniciofim"], 0, 6); // Nº do Contador de Ordem de Operação relativo à troca de Aplicativo
				$E00 .= $this->valor_numerico(0, 0, 2); // Número de Ordem do Aplicativo
				$E00 .= $this->valor_numerico(18147500000116, 0, 14); // CNPJ/CPF da Software House ou Desenvolvedor Autônomo
				$E00 .= $this->valor_numerico(142404831115, 0, 14); // I.E: da Software House
				$E00 .= $this->valor_numerico(0, 0, 14); // I.M: da Software House
				$E00 .= $this->valor_texto("SCANTECH", 40); // Nome comercial (razão social / denominação) do Software House
				$E00 .= $this->valor_texto("", 40); // Nome do Aplicativo
				$E00 .= $this->valor_texto("1", 10); // Versão do Aplicativo
				$E00 .= $this->valor_texto("", 42); // Dados do Programa Aplicativo
				$E00 .= $this->valor_texto("", 42); // Dados do Programa Aplicativo

				$this->escrever_registro($E00);
				unset($E00);
			}
			/*			 * **********************
			  E01 Identificação do ECF
			 * ********************** */

			$E01 = "E01";
			$E01 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Nº de fabricação do ECF
			$E01 .= " "; // Letra indicativa de MF adicional
			$E01 .= $this->valor_texto("ECF-IF", 7, false); // Tipo do ECF
			$E01 .= $this->valor_texto($maparesumo["marca"], 20, false); // Marca do ECF
			$E01 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
			$E01 .= $this->valor_texto("01.00.01", 10, false); // Versão atual do Software Básico do ECF gravada na MF
			$E01 .= $this->valor_texto("20150201", 8); // Data da gravação na MF da versão do SB a que se refere o campo 07
			$E01 .= $this->valor_texto("000000", 6); // Hora da gravação na MF da versão do SB a que se refere o campo 07
			$E01 .= $this->valor_numerico($maparesumo["numeroecf"], 0, 3); // Nº de ordem seqüencial do ECF no estabelecimento usuário
			$E01 .= $this->valor_texto(removeformat($maparesumo["cpfcnpj"]), 14); // CNPJ do estabelecimento usuário do ECF
			$E01 .= $this->valor_texto("ALT", 3); // Código do comando utilizado para gerar o arquivo, conforme tabela abaixo
			$E01 .= $this->valor_numerico($maparesumo["reinicioini"] + 1, 0, 6); // Contador de Reduções Z do início do período a ser capturado
			$E01 .= $this->valor_numerico($maparesumo["reiniciofim"], 0, 6); // Contador de Reduções Z do final do período a ser capturado
			$E01 .= $this->valor_data_export($maparesumo["dtmovto"]);  // Data do Início do período a ser capturado
			$E01 .= $this->valor_data_export($maparesumo["dtmovto"]);  // Data do fim do período a ser capturado
			$E01 .= $this->valor_texto("01.01.01", 8, false); // Versão da biblioteca do fabricante do ECF geradora deste arquivo
			$E01 .= $this->valor_texto("AC1704 01.01.01", 15, false); // Versão do Ato/COTEPE

			$this->escrever_registro($E01);
			unset($E01);

			/*			 * ***************************************************
			  E02 Identificação do atual contribuinte usuário do ECF
			 * *************************************************** */

			$E02 = "E02";
			$E02 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Nº de fabricação do ECF
			$E02 .= " "; // Letra indicativa de MF adicional
			$E02 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
			$E02 .= $this->valor_texto($maparesumo["cpfcnpj"], 14); // CNPJ do estabelecimento usuário do ECF
			$E02 .= $this->valor_texto($maparesumo["rgie"], 14); // Inscrição Estadual do estabelecimento usuário
			$E02 .= $this->valor_texto($maparesumo["razaosocial"], 40, false); // Nome comercial (razão social / denominação) do contribuinte usuário do ECF
			$E02 .= $this->valor_texto($maparesumo["endereco"], 120, false); // Endereço do estabelecimento usuário do ECF
			$E02 .= $this->valor_texto("20150201", 8); // Data do cadastro do usuário no ECF
			$E02 .= $this->valor_texto("000000", 6); // Hora do cadastro do usuário no ECF
			$E02 .= $this->valor_numerico($maparesumo["reiniciofim"], 0, 6); // Valor do CRO relativo ao cadastro do usuário no ECF
			$E02 .= $this->valor_numerico($maparesumo["gtfinal"], 2, 18); // Valor acumulado no GT, com duas casas decimais.
			$E02 .= $this->valor_numerico("1", 0, 2); //  Nº de ordem do usuário do ECF

			$this->escrever_registro($E02);
			unset($E02);

			/*			 * **********************
			  E12 Relação de Reduções Z
			 * ********************** */
			$E12 = "E12";
			$E12 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Nº de fabricação do ECF
			$E12 .= $this->valor_texto(" ", 1); // Letra indicativa de MF adicional
			$E12 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
			$E12 .= $this->valor_numerico(1, 0, 2); // Nº de ordem do usuário do ECF relativo à respectiva Redução Z
			$E12 .= $this->valor_numerico($maparesumo["numeroreducoes"], 0, 6); // Nº do Contador de Redução Z relativo à respectiva redução
			$E12 .= $this->valor_numerico($maparesumo["operacaofim"], 0, 6); // Nº do Contador de Ordem de Operação relativo à respectiva Redução Z
			$E12 .= $this->valor_numerico($maparesumo["reiniciofim"], 0, 6); // Nº do Contador de Reinício de Operação relativo à respectiva Redução Z
			$E12 .= $this->valor_data_export($maparesumo["dtmovto"]); // Data das operações relativas à respectiva Redução Z
			$E12 .= $this->valor_data_export($maparesumo["dtmovto"]); // Data de emissão da Redução Z
			$E12 .= $this->valor_texto("000000", 6); // Hora de emissão da Redução Z
			$E12 .= $this->valor_numerico($maparesumo["totalbruto"], 2, 14); // Valor acumulado neste totalizador relativo à respectiva Redução Z, com duas casas decimais
			$E12 .= $this->valor_texto("N", 1); // Informar "S" ou "N", conforme tenha ocorrido ou não,a parametrização para o desconto em ISSQN

			$this->escrever_registro($E12);
			unset($E12);

			/*			 * *********************
			  E13 Detalhe da Redução Z
			 * ********************* */

			$query = "(SELECT DISTINCT maparesumo.codmaparesumo,ecf.numfabricacao, ecf.caixa AS caixa, maparesumoimposto.totalliquido, maparesumoimposto.tptribicms  ";
			$query .= ", maparesumoimposto.aliqicms, maparesumoimposto.totalicms, ecf.modelo, maparesumo.numeroreducoes, maparesumo.numeroecf ";
			$query .= "FROM maparesumo ";
			$query .= "INNER JOIN ecf ON (maparesumo.numeroecf = ecf.numeroecf AND maparesumo.codestabelec = ecf.codestabelec) ";
			$query .= "INNER JOIN maparesumoimposto ON (maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) ";
			$query .= "WHERE maparesumo.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ecf.status = 'A' ";
			$query .= " AND  maparesumo.dtmovto = '".$maparesumo["dtmovto"]."' AND ecf.numeroecf = ".$maparesumo["numeroecf"];

			$query .= ")UNION ALL (";

			$query .= "SELECT DISTINCT maparesumo.codmaparesumo,ecf.numfabricacao, ecf.caixa AS caixa, maparesumo.totalcupomcancelado AS totalliquido, 'Can-t' AS tptribicms  ";
			$query .= ", 0 AS aliqicms, 0 AS totalicms, ecf.modelo, maparesumo.numeroreducoes, maparesumo.numeroecf ";
			$query .= "FROM maparesumo ";
			$query .= "INNER JOIN ecf ON (maparesumo.numeroecf = ecf.numeroecf AND maparesumo.codestabelec = ecf.codestabelec) ";
			$query .= "WHERE maparesumo.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ecf.status = 'A' ";
			$query .= " AND  maparesumo.dtmovto = '".$maparesumo["dtmovto"]."' AND ecf.numeroecf = ".$maparesumo["numeroecf"];

			$query .= ")UNION ALL (";

			$query .= "SELECT DISTINCT maparesumo.codmaparesumo,ecf.numfabricacao, ecf.caixa AS caixa, maparesumo.totaldescontocupom AS totalliquido, 'DT' AS tptribicms  ";
			$query .= ", 0 AS aliqicms, 0 AS totalicms, ecf.modelo, maparesumo.numeroreducoes, maparesumo.numeroecf ";
			$query .= "FROM maparesumo ";
			$query .= "INNER JOIN ecf ON (maparesumo.numeroecf = ecf.numeroecf AND maparesumo.codestabelec = ecf.codestabelec) ";
			$query .= "WHERE maparesumo.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ecf.status = 'A' ";
			$query .= " AND  maparesumo.dtmovto = '".$maparesumo["dtmovto"]."' AND ecf.numeroecf = ".$maparesumo["numeroecf"].")";

			$res = $this->con->query($query);
			$arr_maparesumo_imposto = $res->fetchAll(2);

			if(sizeof($arr_maparesumo_imposto) == 0){
				if(is_file($this->arquivo)){
					unlink($this->arquivo);
					array_pop($arr_arquivo);
					$arquivo_n_gerado[] = convert_date($maparesumo["dtmovto"], "Y-m-d", "d/m/Y")." ECF: ".$maparesumo["numeroecf"];
				}
				continue;
			}

			$n = sizeof($arr_maparesumo_imposto);
			foreach($arr_maparesumo_imposto AS $k => $row){
				if(!$_SESSION["geranfp"]){
					return pararprocesso($arr_arquivo);
				}

				if($row["totalliquido"] == 0){
					continue;
				}

				$aliq = "";

				if(in_array($row["tptribicms"], array("Can-t", "DT"))){
					$aliq = $row["tptribicms"];
				}else{
					if($row["tptribicms"] == "T"){
						if($row["aliqicms"] == 7){
							$aliq = "01T0700";
						}elseif($row["aliqicms"] == 12){
							$aliq = "02T1200";
						}elseif($row["aliqicms"] == 18){
							$aliq = "03T1800";
						}elseif($row["aliqicms"] == 25){
							$aliq = "04T2500";
						}elseif($row["aliqicms"] == 4){
							$aliq = "05T0400";
						}elseif($row["aliqicms"] == 17){
							$aliq = "06T1700";
						}elseif($row["aliqicms"] == 11){
							$aliq = "07T1100";
						}elseif($row["aliqicms"] == 4.5){
							$aliq = "08T0450";
						}
					}elseif($row["tptribicms"] == "I"){
						$aliq = "I1";
					}elseif($row["tptribicms"] == "N"){
						$aliq = "N1";
					}elseif($row["tptribicms"] == "F"){
						$aliq = "F1";
					}
				}

				setprogress(($k / $n * 100), "{$dialog} Carregando Redução Z: ".$k." de ".$n);
				$E13 = "E13";
				$E13 .= $this->valor_texto($row["numfabricacao"], 20); // Nº de fabricação do ECF
				$E13 .= $this->valor_texto(" ", 1); // Letra indicativa de MF adicional
				$E13 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
				$E13 .= $this->valor_numerico(1, 0, 2); // Nº de ordem do usuário do ECF
				$E13 .= $this->valor_numerico($maparesumo["numeroreducoes"], 0, 6); // Nº do Contador de Redução Z relativo à respectiva redução
				$E13 .= $this->valor_texto($aliq, 7, false); // Código do totalizador conforme tabela abaixo
				$E13 .= $this->valor_numerico($row["totalliquido"], 2, 13); // Valor acumulado no totalizador, relativo à respectiva Redução Z, com duas casas decimais.

				$this->escrever_registro($E13);
			}
			unset($E13);


			/*			 * ***********************************************************************
			  E14 Cupom Fiscal, Nota Fiscal de Venda a Consumidor ou Bilhete de Passagem
			 * *********************************************************************** */

			$query = "SELECT cupom.cupom, cupom.numeroecf, ecf.numfabricacao, ecf.modelo,cupom.cpfcnpj, cupom.totalliquido, cupom.dtmovto ";
			$query .= ", cupom.totalbruto, cupom.cupom, cupom.totalacrescimo, cupom.totaldesconto, cupom.status, cupom.seqecf ";
			$query .= ", (SELECT descricao FROM finalizadora WHERE codfinaliz = (SELECT codfinaliz FROM cupomlancto WHERE cupom = cupom.cupom LIMIT 1) LIMIT 1) AS finalizadora ";
			$query .= ", (CASE WHEN cupom.status = 'C' THEN 'S' ELSE 'N' END) AS cancelado ";
			$query .= "FROM cupom ";
			$query .= "INNER JOIN ecf ON (ecf.numeroecf = cupom.numeroecf AND ecf.codestabelec = cupom.codestabelec) ";
			$query .= "WHERE cupom.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ecf.status = 'A' AND ecf.equipamentofiscal = 'ECF' AND cupom.chavecfe IS NULL ";
			$query .= " AND  cupom.dtmovto = '".$maparesumo["dtmovto"]."' AND ecf.numeroecf = ".$maparesumo["numeroecf"];
			$query .= " ORDER BY cupom.seqecf ";

			$res = $this->con->query($query);
			$arr_cupom = $res->fetchAll(2);

			if(sizeof($arr_cupom) == 0 && $maparesumo["totalbruto"] != 0){
//			if(sizeof($arr_cupom) == 0){
				if(is_file($this->arquivo)){
					unlink($this->arquivo);
					array_pop($arr_arquivo);
					$arquivo_n_gerado[] = convert_date($maparesumo["dtmovto"], "Y-m-d", "d/m/Y")." ECF: ".$maparesumo["numeroecf"];
				}
				continue;
			}

			$n = sizeof($arr_cupom);
			foreach($arr_cupom AS $k => $row){
				if(!$_SESSION["geranfp"]){
					return $this->pararprocesso($arr_arquivo);
				}

				setprogress(($k / $n * 100), "{$dialog} Carregando cupons: ".$k." de ".$n);
				$E14 = "E14";
				$row["totalbruto"] += $row["totalacrescimo"];
				$E14 .= $this->valor_texto($row["numfabricacao"], 20); // Nº de fabricação do ECF
				$E14 .= $this->valor_texto(" ", 1); // Letra indicativa de MF adicional
				$E14 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
				$E14 .= $this->valor_numerico(1, 0, 2); // Nº de ordem do usuário do ECF
				$E14 .= $this->valor_numerico($row["cupom"], 0, 6); // Nº do contador do respectivo documento emitido
				$E14 .= $this->valor_numerico($row["seqecf"], 0, 6); // Nº do COO relativo ao respectivo documento
				$E14 .= $this->valor_data_export($row["dtmovto"]); // Data de início da emissão do documento
				$E14 .= $this->valor_numerico($row["totalbruto"], 2, 14); // Valor total do documento, com duas casas decimais.
				$E14 .= $this->valor_numerico($row["totaldesconto"], 2, 13); // Valor do desconto ou Percentual aplicado sobre o valor do subtotal do documento, com duas casas decimais.
				$E14 .= $this->valor_texto("V", 1); // Informar “V” para valor monetário ou “P” para percentual
				$E14 .= $this->valor_numerico(0, 2, 13); // Valor do acréscimo ou Percentual aplicado sobre o valor do subtotal do documento, com duas casas decimais.
				$E14 .= $this->valor_texto("V", 1); // Informar “V” para valor monetário ou “P” para percentual
				$E14 .= $this->valor_numerico($row["totalliquido"], 2, 14); // Valor total do Cupom Fiscal após desconto/acréscimo, com duas casas decimais.
				$E14 .= $this->valor_texto(($row["cancelado"]), 1); // Informar "S" ou "N", conforme tenha ocorrido ou não, o cancelamento do documento.
				$E14 .= $this->valor_numerico(0, 0, 13); // Valor do cancelamento de acréscimo no subtotal
				$E14 .= $this->valor_texto(" ", 1); // Indicador de ordem de aplicação de desconto/acréscimo em Subtotal. ‘D’ ou ‘A’ caso tenha ocorrido primeiro desconto ou acréscimo, respectivamente
				$E14 .= $this->valor_texto(" ", 40); // Nome do Cliente
				$E14 .= $this->valor_numerico(removeformat($row["cpfcnpj"]), 0, 14); // CPF ou CNPJ do cliente (somente números)
				$this->escrever_registro($E14);
			}
			unset($E14);

			/*			 * **********************************************************************************
			  E15 Detalhe do Cupom Fiscal, Nota Fiscal de Venda a Consumidor ou Bilhete de Passagem
			 * ********************************************************************************** */

			$query = "SELECT DISTINCT produto.descricaofiscal, itcupom.valortotal, itcupom.desconto, itcupom.acrescimo ";
			$query .= ", cupom.cupom, itcupom.quantidade, produto.codproduto, itcupom.preco, cupom.seqecf, itcupom.tptribicms ";
			$query .= ", itcupom.aliqicms, (CASE WHEN itcupom.status = 'C' THEN 'S' ELSE 'N' END) AS cancelado ";
			$query .= "FROM cupom ";
			$query .= "INNER JOIN itcupom ON (cupom.idcupom = itcupom.idcupom) ";
			$query .= "INNER JOIN ecf ON (ecf.numeroecf = cupom.numeroecf) ";
			$query .= "INNER JOIN produto ON (itcupom.codproduto = produto.codproduto) ";
			$query .= "WHERE cupom.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ecf.status = 'A' ";
			$query .= " AND  cupom.dtmovto = '".$maparesumo["dtmovto"]."'  AND cupom.numeroecf = ".$maparesumo["numeroecf"];
			$query .= " AND itcupom.composicao != 'F'  AND itcupom.quantidade > 0 AND cupom.chavecfe is NULL ";
			$query .= " ORDER BY cupom.seqecf ";

			$res = $this->con->query($query);
			$arr_itcupom = $res->fetchAll(2);

			$cupom = 0;
			$i = 1;
			$n = sizeof($arr_itcupom);
			foreach($arr_itcupom as $k => $row){
				if(!$_SESSION["geranfp"]){
					return $this->pararprocesso($arr_arquivo);
				}
				setprogress(($k / $n * 100), "{$dialog} Carregando itens: ".$k." de ".$n);
				if($cupom != $row["cupom"]){
					$i = 1;
					$cupom = $row["cupom"];
				}else{
					$i++;
				}

				$aliq = "";
				if($row["cancelado"] == "S"){
					$aliq = "Can-T";
				}else{
					if($row["tptribicms"] == "T"){
						if($row["aliqicms"] == 7){
							$aliq = "01T0700";
						}elseif($row["aliqicms"] == 12){
							$aliq = "02T1200";
						}elseif($row["aliqicms"] == 18){
							$aliq = "03T1800";
						}elseif($row["aliqicms"] == 25){
							$aliq = "04T2500";
						}elseif($row["aliqicms"] == 4){
							$aliq = "05T0400";
						}elseif($row["aliqicms"] == 17){
							$aliq = "06T1700";
						}elseif($row["aliqicms"] == 11){
							$aliq = "07T1100";
						}elseif($row["aliqicms"] == 4.5){
							$aliq = "08T0450";
						}
					}elseif($row["tptribicms"] == "I"){
						$aliq = "I1";
					}elseif($row["tptribicms"] == "N"){
						$aliq = "N1";
					}elseif($row["tptribicms"] == "F"){
						$aliq = "F1";
					}
				}

				$E15  = "E15"; // 3
				$E15 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Número de fabricação do ECF 23
				$E15 .= $this->valor_texto(" ", 1); // Letra indicativa de MF adicional 24
				$E15 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF 44
				$E15 .= $this->valor_numerico(1, 0, 2); // Número de ordem do usuário do ECF 46
				$E15 .= $this->valor_numerico($row["seqecf"], 0, 6); // Número do COO relativo ao respectivo documento 52
				$E15 .= $this->valor_numerico($row["cupom"], 0, 6); // Número do contador do respectivo documento emitido 58
				$E15 .= $this->valor_numerico($i, 0, 3); // Número do item registrado no documento 61
				$E15 .= $this->valor_texto($row["codproduto"], 14); // Código do produto ou serviço registrado no documento. 75
				$E15 .= $this->valor_texto(removespecial($row["descricaofiscal"]), 100, false); // Descrição do produto ou serviço constante no Cupom Fiscal 175
				$E15 .= $this->valor_numerico($row["quantidade"], 2, 7); // Quantidade comercializada, sem a separação das casas decimais. 182
				$E15 .= $this->valor_texto("UN", 3); // Unidade de medida 185
				$E15 .= $this->valor_numerico($row["preco"], 2, 8); // Valor unitário do produto ou serviço, sem a separação das casas decimais. 193
				$E15 .= $this->valor_numerico(0, 2, 8); // Valor do desconto incidente sobre o valor do item, com duas casas decimais 201
				$E15 .= $this->valor_numerico($row["acrescimo"], 2, 8); // Valor do acréscimo incidente sobre o valor do item, com duas casas decimais. 209
				$E15 .= $this->valor_numerico($row["valortotal"] + $row["desconto"], 2, 14); // Valor total líquido do item, com duas casas decimais. 223
				$E15 .= $this->valor_texto($aliq, 7, false); // Código do totalizador relativo ao produto ou serviço conforme tabela abaixo. 230
				$E15 .= $this->valor_texto($row["cancelado"], 1); // Informar "S" ou "N", conforme tenha ocorrido ou não, o cancelamento total do item no documento. Informar "P" quando ocorrer o cancelamento parcial do item. 231
				$E15 .= $this->valor_numerico(0, 0, 7); // Quantidade cancelada, no caso de cancelamento parcial de item, sem a separação das casas decimais. 238
				$E15 .= $this->valor_numerico(0, 0, 13); // Valor cancelado, no caso de cancelamento parcial de item. 251
				$E15 .= $this->valor_numerico(0, 0, 13); // Valor do cancelamento de acréscimo no item 264
				$E15 .= $this->valor_texto("A", 1); // Indicador de Arredondamento ou Truncamento relativo à regra de cálculo do valor total líquido do item, sendo‘T’ para truncamento ou ‘A’ para arredondamento. 265
				$E15 .= $this->valor_numerico(2, 0, 1); // Parâmetro de número de casas decimais da quantidade  266
				$E15 .= $this->valor_numerico(2, 0, 1); // Parâmetro de número de casas decimais de valor unitário 267
				$this->escrever_registro($E15);
			}
			unset($E15);

			/*			 * ************************************
			  E16 Demais documentos emitidos pelo ECF
			 * ************************************ */

			/*
			  $E16 = "E16";
			  $E16 .= $this->valor_texto("", 20); // Número de fabricação do ECF
			  $E16 .= $this->valor_texto("", 1); // Letra indicativa de MF adicional
			  $E16 .= $this->valor_texto("", 20); // Modelo do ECF
			  $E16 .= $this->valor_numerico(0, 2); // Número de ordem do usuário do ECF
			  $E16 .= $this->valor_numerico(0, 6); // Número do COO relativo ao respectivo documento
			  $E16 .= $this->valor_numerico(0, 6); // Número do GNF relativo ao respectivo documento, quando houver
			  $E16 .= $this->valor_numerico(0, 6); // Número do GRG relativo ao respectivo documento (vide item 6.16.1.4)
			  $E16 .= $this->valor_numerico(0, 4); // Número do CDC relativo ao respectivo documento (vide item 6.16.1.5)
			  $E16 .= $this->valor_numerico(0, 6); // Número do CRZ relativo ao respectivo documento (vide item 6.16.1.6)
			  $E16 .= $this->valor_texto("", 2); // Símbolo referente à denominação do documento fiscal, conforme tabela abaixo
			  $E16 .= $this->valor_data(""); // Data final de emissão
			  $E16 .= valor_hora(""); // Hora final de emissão, no formato hh:mm:ss
			 */

			/*			 * *******************************************************************
			  E21 Detalhe do Cupom Fiscal e Documento Não Fiscal – Meio de Pagamento
			 * ******************************************************************* */

			$n = sizeof($arr_cupom);
			foreach($arr_cupom AS $k => $row){
				if(!$_SESSION["geranfp"]){
					return $this->pararprocesso($arr_arquivo);
				}
				setprogress(($k / $n * 100), "{$dialog} Carregando meio de pagamento: ".$k." de ".$n);
				$E21 = "E21";
				$E21 .= $this->valor_texto($maparesumo["numfabricacao"], 20); // Número de fabricação do ECF
				$E21 .= $this->valor_texto(" ", 1); // Letra indicativa de MF adiciona
				$E21 .= $this->valor_texto($maparesumo["modelo"], 20, false); // Modelo do ECF
				$E21 .= $this->valor_numerico(1, 0, 2); // Número de ordem do usuário do ECF
				$E21 .= $this->valor_numerico($row["seqecf"], 0, 6); // Número do COO relativo ao respectivo Cupom Fiscal ou Comprovante Não Fiscal
				$E21 .= $this->valor_numerico($row["cupom"], 0, 6); //	Número do Contador de Cupom Fiscal relativo ao respectivo Cupom Fiscal emitido
				$E21 .= $this->valor_numerico(0, 0, 6); // Número do Contador Geral Não Fiscal relativo ao respectivo Comprovante Não Fiscal emitido
				$E21 .= $this->valor_texto($row["finalizadora"], 15); // Descrição do totalizador parcial de meio de pagamento
				$E21 .= $this->valor_numerico($row["totalliquido"], 2, 13); // Valor do pagamento efetuado, com duas casas decimais
				$E21 .= $this->valor_texto("N", 1); // Informar "S" ou "N", conforme tenha ocorrido ou não, o estorno do pagamento, ou “P” para estorno parcial do pagamento
				$E21 .= $this->valor_numerico(0, 0, 13); // Valor do estorno efetuado, com duas casas decimais
				$this->escrever_registro($E21);
			}

			unset($E21);

			/*			 * *********************
			  EAD - ASSINATURA DIGITAL
			 * ********************* */
			/*
			  $EAD = "EAD";
			  $EAD .= $this->valor_texto(md5(implode("",$E14)), 259); // Assinatura do Hash

			  //$this->escrever_registro($EAD);
			  unset($EAD); */
		}

		$zip = new ZipArchive();
		$caminho = "../temp/fiscal/";
		$filename = $caminho.str_pad($this->estabelecimento->getcodestabelec(), 3, "0", STR_PAD_LEFT).removeformat($this->data_inicial).removeformat($this->data_final).".zip";
		$linux = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? false : true;

		if(is_file($filename)){
			unlink($filename);
		}
		if(!($zip->open($filename, ZIPARCHIVE::CREATE) == TRUE)){
			echo messagebox("error", "", "Diretorio não encontrado");
			die;
		}

		foreach($arr_arquivo as $arquivo){
			$zip->addFile($caminho.$arquivo, $arquivo);
			if($linux){
				chmod($filename, 0777);
			}
		}
		$zip->close();
		foreach($arr_arquivo as $arquivo){
			unlink($caminho.$arquivo);
		}


		//echo script("window.open('".$caminho."notaxml".date("Ymd").".zip"."')");
		if(sizeof($arquivo_n_gerado) > 0){
			$message = "<br>Nos dias abaixo não foi gerado os arquivos por falta de Mapa Resumo ou Cupom.<br>".implode("<br>", $arquivo_n_gerado);
		}else{
			$message = "";
		}

		echo messagebox("success", "", "Arquivo gerado com sucesso!".$message);
		setprogress(0, "", true);
		echo script("$.download(\"../form/download.php?f={$filename}\")");
	}

	function getarr_ecf(){
		return $this->arr_ecf;
	}

	function getarr_maparesumo(){
		return $this->arr_maparesumo;
	}

	function getarr_pdvfinalizador(){
		return $this->arr_pdvfinalizador;
	}

	function getarr_pdvvenda(){
		return $this->arr_pdvvenda;
	}

	private function limpar_dados(){
		$this->arr_pdvvenda = array();
		$this->arr_pdvfinalizador = array();
	}

	// Captura os dados de ICMS da estrutura XML
	private function capturar_icms(&$tptribicms, &$aliqicms, SimpleXMLElement $icms){
		if(strlen($icms->CSOSN) > 0){
			// Aliquota de ICMS
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : (strlen($icms->pICMSST) > 0 ? $icms->pICMSST : 0));

			// Tipo de tributacao do ICMS do item
			switch($icms->CSOSN){
				case "101": // Tributada pelo Simples Nacional com permissao de credito
					$tptribicms = "T";
					break;
				case "102": // Tributada pelo Simples Nacional sem permissao de credito
					$tptribicms = "N";
					break;
				case "103":
					$tptribicms = "N";
					break;
				case "201": // Tributada pelo Simples Nacional com permissao de credito e com cobranca do ICMS por substituicao tributario
					$tptribicms = "F";
					break;
				case "202": // Tributada pelo Simples Nacional sem permissao de credito e com cobranca do ICMS por substituicao tributario
					$tptribicms = "F";
					break;
				case "203":
					$tptribicms = "F";
					break;
				case "300":
					$tptribicms = "N";
					break;
				case "400": // Nao tributada pelo Simples Nacional
					$tptribicms = "N";
					break;
				case "500": // ICMS cobrado anteriormente por substituicao tributario (substituido) ou por antecipacao
					$tptribicms = "F";
					break;
				case "900":
					$tptribicms = "N";
					break;
			}
		}else{
			// Aliquota de ICMS
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : 0);

			// Tipo de tributacao do ICMS do item
			switch($icms->CST){
				case "00": // Tributada integralmente
					$tptribicms = "T";
					break;
				case "10": // Tributada e com cobranca do ICMS por substituicao tributaria
					$tptribicms = "F";
					break;
				case "20": // Com reducao de base de calculo
					$tptribicms = "R";
					break;
				case "30": // Isenta ou nao tributada e com cobraca do ICMS por substituicao tributaria
					$tptribicms = "I";
					break;
				case "40": // Isenta
					$tptribicms = "I";
					break;
				case "41": // Nao tributada
					$tptribicms = "I";
					break;
				case "50": // Suspensao
					$tptribicms = "N";
					break;
				case "51": // Diferimento (A exigencia do preenchimento das informacoes do ICMS diferido fica a criterio de cada UF)
					$tptribicms = "N";
					break;
				case "60": // ICMS cobrado anteriormente por substituicao
					$tptribicms = "F";
					break;
				case "70": // Com reducao de base de calculo e cobranca do ICMS por substituicao tributaria
					$tptribicms = "F";
					break;
				case "90": // Outros
					$tptribicms = "N";
					break;
			}
		}
	}

	private function escrever_registro($registro){
		if(strlen($registro) > 0){
			$fp = fopen($this->arquivo, "a+");
			fwrite($fp, $registro."\r\n");
			fclose($fp);
		}
	}

	public function setdata_inicial($data_inicial){
		$this->data_inicial = value_date($data_inicial);
	}

	public function setdata_final($data_final){
		$this->data_final = value_date($data_final);
	}

	public function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	private function processar_arquivo_ecf($dirname, $filename){
		$arr_linha = read_file($dirname.$filename);

		$arr_desconto = array();
		$arr_acrescimo = array();
		$totalcupomcancelado = 0;

		foreach($arr_linha as $i => $linha){
			$registro = substr($linha, 0, 3);
			$numfabricacaoecf = substr($linha, 3, 20); // Numero de fabricacao do ECF
			$modeloecf = substr($linha, 24, 20); // Modelo do ECF

			switch($registro){
				case "E00": // Identificacao da software house
					$numusuario = substr($linha, 24, 2); // Numero de ordem do usuario do ECF
					$tipoecf = substr($linha, 26, 7); // Tipo de ECF
					$marcaecf = substr($linha, 33, 20); // Marca do ECF
					$coo = substr($linha, 73, 6); // Numero do contador de ordem de operacao relativo a troca de aplicativo
					break;
				case "E01": // Identificacao do ECF
					$tipoecf = substr($linha, 24, 7); // Tipo de ECF
					$marcaecf = substr($linha, 31, 20); // Marca do ECF
					$modeloecf = substr($linha, 51, 20); // Modelo do ECF
					$numeroecf = substr($linha, 95, 3); // Numero de ordem sequencial do ECF no estabelecimento usuario

					$ecf = $this->verificar_ecf($numfabricacaoecf, $modeloecf, $numeroecf, "ECF");
					if($ecf === FALSE){
						return FALSE;
					}
					break;
				case "E02": // Identificacao do atual contribuinte usuario do ECF
					$cro = substr($linha, 246, 6); // Valor do CRO (contador de reinicio de operacao) relativo ao cadastro do usuario no ECF
					$gtfinal = substr($linha, 252, 18) / 100; // Valor acumulado no GT (grande total), com duas casas decimais
					$numusuario = substr($linha, 270, 2); // Numero de ordem do usuario do ECF
					break;
				case "E12": // Relacao de reducoes Z
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$crz = substr($linha, 46, 6); // Numero do contador de reducoes Z relativo a respectiva reducao Z
					$coo = substr($linha, 52, 6); // Numero do contador de ordem de operacao relativo a respectiva reducao Z
					$cro = substr($linha, 58, 6); // Numero do contador de reinicio de operacao relativo a respectiva reducao Z
					$dtmovto = substr($linha, 64, 8); // Data das operacoes relativas a respectiva reducao Z
					$vendabruta = substr($linha, 86, 14) / 100; // Valor acumulado neste totalizador relativo a respectiva reducao Z, com duas casas decimais

					$dtmovto = $this->valor_data($dtmovto);

					// Percorre as linha do mesmo arquivo para capturar informacoes dos cupons e itens
					$coo_num = 0; // Numero de cupons
					$coo_min = NULL; // Menor cupom (primeiro)
					$coo_max = NULL; // Maior cupom (ultimo)
					$coo_can = 0; // Cupons cancelados
					$ite_can = 0; // Itens cancelados
					foreach($arr_linha as $linha_E12){
						switch(substr($linha_E12, 0, 3)){
							case "E14": // Cupom
								$coo = substr($linha_E12, 52, 6); // Numero do COO
								if(is_null($coo_min) || $coo < $coo_min){
									$coo_min = $coo;
								}
								if(is_null($coo_max) || $coo > $coo_max){
									$coo_max = $coo;
								}
								$coo_num++;
								if(substr($linha_E12, 122, 1) == "S"){ // Verifica se cupom cancelado
									$coo_can++;
								}
								break;
							case "E15": // Item de cupom
								if(substr($linha_E12, 231, 1)){
									$ite_can++;
								}
								break;
						}
					}

					$maparesumo = objectbytable("maparesumo", NULL, $this->con);
					$maparesumo->setcodestabelec($this->estabelecimento->getcodestabelec());
					$maparesumo->setcaixa($ecf->getcaixa());
					$maparesumo->setnumeroecf($ecf->getnumeroecf());
					$maparesumo->setdtmovto($dtmovto);
					$maparesumo->setnumeroreducoes($crz);
					$maparesumo->setoperacaoini($coo_min);
					$maparesumo->setoperacaofim($coo_max);
					$maparesumo->setreiniciofim($cro);
					$maparesumo->setcuponsfiscemit($coo_num);
					$maparesumo->setitenscancelados($ite_can);
					$maparesumo->setcuponscancelados($coo_can);
					$maparesumo->settotalbruto($vendabruta);
					$maparesumo->settotalliquido($vendabruta);
					$maparesumo->setcodecf($ecf->getcodecf());
					$maparesumo->setgtfinal($gtfinal); // Variavel preenchida no registro E02
					$maparesumo->setgtinicial($maparesumo->getgtfinal() - $maparesumo->gettotalbruto());
					$maparesumo->flag_maparesumoimposto(TRUE);
					$this->arr_maparesumo[] = $maparesumo;
					break;
				case "E13": // Detalhe da reducao Z
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$crz = substr($linha, 46, 6); // Numero do contador de reducoes Z relativo a respectiva reducao Z
					$codtotal = substr($linha, 52, 7); // Codigo do totalizador conforme tabela abaixo
					$valoracu = substr($linha, 59, 13) / 100; // Valor acumulado no totalizador, relativo a respectiva reducao Z, com duas casas decimais

					unset($tptribicms, $aliqicms);

					if(in_array(substr($codtotal, 0, 1), array("F", "I", "N"))){
						$tptribicms = substr($codtotal, 0, 1);
						$aliqicms = 0;
					}elseif(in_array(substr($codtotal, 2, 1), array("T", "S"))){
						$tptribicms = substr($codtotal, 2, 1);
						$aliqicms = substr($codtotal, 3, 4) / 100;
					}elseif(substr($codtotal, 0, 3) == "Can" || in_array(trim($codtotal), array("DT"))){
						$maparesumo->settotalliquido($maparesumo->gettotalliquido() - $valoracu);
					}

					if(substr($linha, 52, 3) == "Can" && $valoracu > 0){
						$totalcupomcancelado += $valoracu;
						$maparesumo->settotalcupomcancelado($totalcupomcancelado);
					}elseif(in_array(substr($linha, 52, 2), array("DT", "DS")) && $valoracu > 0){
						$maparesumo->settotaldescontocupom($valoracu);
					}elseif(in_array(substr($linha, 52, 2), array("AT", "AS")) && $valoracu > 0){
						$maparesumo->settotalacrescimocupom($valoracu);
					}

					if(isset($tptribicms) && $valoracu > 0){
						$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
						$maparesumoimposto->settptribicms($tptribicms);
						$maparesumoimposto->setaliqicms($aliqicms);
						$maparesumoimposto->settotalliquido($valoracu);
						$maparesumoimposto->settotalicms($valoracu * ($aliqicms / 100));
						$maparesumo->maparesumoimposto[] = $maparesumoimposto;
					}

					break;
				case "E14": // Cupom fiscal, nota fiscal de venda a consumidor ou bilhete de passagem
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$contdocto = substr($linha, 46, 6); // Numero do contador do respectivo documento emitido (CCF, CVC ou CBP, conforme o documento emitido)
					$coo = substr($linha, 52, 6); // Numero do COO (contador de ordem de operacao) relativo ao respectivo documento
					$dtemissaoini = substr($linha, 58, 8); // Data de inicio da emissao do documento
					$desconto = substr($linha, 80, 13) / 100; // Valor do desconto ou percentual aplicado sobre o valor do subtotal do documento, com dias casas decimais
					$tipodescto = substr($linha, 93, 1); // Indicador do tipo de desconto sobre o subtotal ("V" para valor monetario ou "P" para valor percentutal)
					$acrescimo = substr($linha, 94, 13) / 100; // Valor do acrescimo ou percentual aplicado sobre o valor do subtotal do documento, com dias casas decimais
					$tipoacrescimo = substr($linha, 107, 1); // Indicador do tipo de acrescimo sobre o subtotal ("V" para valor monetario ou "P" para valor percentutal)
					$totalliquido = substr($linha, 108, 14) / 100; // Valor total do cupom fiscal apos desconto/acrescimo, com duas casas decimais
					$indcancel = substr($linha, 122, 1); // Indicador de cancelamento ("S" ou "N", conforme tenha ocorrido ou nao, o cancelamento do documento)
					$cpfcnpjadquirente = substr($linha, 177, 14); // CPF ou  CNPJ do adquirente (cliente) (somente os numeros)

					$dtemissaoini = $this->valor_data($dtemissaoini);

					if($desconto > 0){
						if($tipodescto == "V"){
							$desconto = (1 - $totalliquido / ($totalliquido + $desconto)) * 100;
						}
						$arr_desconto[$coo] = $desconto;
					}

					if($acrescimo > 0){
						$acrescimooriginal = $acrescimo;
						if($tipoacrescimo == "V"){
							$acrescimo = (1 - $totalliquido / ($totalliquido + $acrescimo)) * 100;
						}
						$arr_acrescimo[$coo] = array($acrescimo, $acrescimooriginal);
					}

					$cupom_cancelado = FALSE;
					if($indcancel == "S"){
						foreach($this->arr_pdvvenda as $pdvvenda){
							if($pdvvenda->getnumeroecf() == $numfabricacaoecf && $pdvvenda->getcupom() == $coo){
								$pdvvenda->setstatus("C");
								$cupom_cancelado = TRUE;
							}
						}
					}

					if(!$cupom_cancelado){
						$pdvvenda = new PdvVenda();
						$pdvvenda->setstatus($indcancel == "S" ? "C" : "A");
						$pdvvenda->setcupom($coo);
						$pdvvenda->setseqecf($coo);
						$pdvvenda->setcaixa($ecf->getcaixa());
						$pdvvenda->setnumeroecf($ecf->getnumeroecf()); // Variavel preenchida no registro E01
						$pdvvenda->setdata($dtemissaoini);
						$pdvvenda->setcodecf($ecf->getcodecf());
						if(!valid_cnpj($cpfcnpjadquirente) || (substr($cpfcnpjadquirente, 0, 3) == "000" && valid_cpf(substr($cpfcnpjadquirente, -11)))){
							$cpfcnpjadquirente = substr($cpfcnpjadquirente, -11);
						}
						$pdvvenda->setcpfcnpj($cpfcnpjadquirente);
						array_unshift($this->arr_pdvvenda, $pdvvenda);
					}

					break;
				case "E15": // Detalhe do cupom fiscal, nota fiscal a consumidor ou bilhete de passagem
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$coo = substr($linha, 46, 6); // Numero do COO (contador de ordem de operacao) relativo ao respectivo documento
					$contdocto = substr($linha, 52, 6); // Numero do contador do respectivo documento emitido (CCF, CVC ou CBP, conforme o documento emitido)
					$numitem = substr($linha, 58, 3); // Numero do item registrado no documento
					$codproduto = substr($linha, 61, 14); // Codigo do produto ou servico registrado no documento
					$descricao = substr($linha, 75, 100); // Descricao do produto ou servico constante no cupom fiscal
					$quantidade = substr($linha, 175, 7); // Quantidade comercializada, sem a separacao das casas decimais
					$vlrunitario = substr($linha, 185, 8); // Valor unitario do produto ou servico, sem a separacao das casas decimais
					$desconto = substr($linha, 193, 8) / 100; // Valor do desconto incidente sobre o valor do item, com duas casas decimais
					$acrescimo = substr($linha, 201, 8) / 100; // Valor do acrescimo incidente sobre o valor do item, com duas casas decimais
					$totalliquido = substr($linha, 209, 14) / 100; // Valor total do item, com duas casas decimais
					$codtotal = substr($linha, 223, 7); // Codigo do totalizador conforme tabela do registro E13
					$indcancel = substr($linha, 230, 1); // Indicador de cancelamento ("S" ou "N", conforme tenha ocorrido ou nao, o cancelamento total do item. "P" quando ocorrer o cancelamento parcial do item)
					$decquantidade = substr($linha, 265, 1); // Parametro de numero de casas decimais da quantidade
					$decvlrunitario = substr($linha, 266, 1); // Parametro de numero de casas decimais de valor unitario

					$quantidade = $quantidade / pow(10, $decquantidade); // Aplica o numero de casas decimais na quantidade
					$vlrunitario = $vlrunitario / pow(10, $decvlrunitario); // Aplica o numero de casas decimais no valor unitario do item

					$codproduto = trim(ltrim($codproduto, "0"));
					if(strlen($codproduto) < 8){
						$codproduto = str_pad($codproduto, 8, "0", STR_PAD_LEFT);
					}else{
						if(strlen($codproduto) == 13 && substr($codproduto, 0, 1) == 2){
							$codproduto = substr($codproduto, 1, $this->balanca->gettamcodnfp());
						}else{
							$codean = $this->arr_codean[$codproduto];
							if(strlen($codean) > 0){
								$codproduto = $codean;
							}elseif(substr($codproduto, -3) == "000"){
								$codproduto = substr($codproduto, 0, (strlen($codproduto) - 9));
							}
						}
					}

					if($codproduto == 0 || strlen($codproduto) == 0){
						echo messagebox("error", "", " Erro na linha $i");
						die();
					}

					unset($tptribicms, $aliqicms);
					if(in_array(substr($codtotal, 0, 1), array("F", "I", "N"))){
						$tptribicms = substr($codtotal, 0, 1);
						$aliqicms = 0;
					}elseif(in_array(substr($codtotal, 2, 1), array("T", "S"))){
						$tptribicms = substr($codtotal, 2, 1);
						$aliqicms = substr($codtotal, 3, 4) / 100;
					}

					foreach($this->arr_pdvvenda as $pdvvenda){
						if($pdvvenda->getnumeroecf() == $ecf->getnumeroecf() && $pdvvenda->getcupom() == $coo){
							$item_cancelado = FALSE;
							if($indcancel == "S"){
								foreach($pdvvenda->pdvitem as $pdvitem){
									if($pdvitem->getsequencial() == $numitem){
										if($pdvitem->getsequencial() == $numitem){
											$pdvitem->setstatus("C");
											$item_cancelado = TRUE;
											break;
										}
									}
								}
							}
							if(!$item_cancelado){
								$pdvitem = new PdvItem();
								$pdvitem->setstatus($indcancel === "S" ? "C" : "A");
								$pdvitem->setsequencial($numitem);
								$pdvitem->setcodproduto($codproduto);
								$pdvitem->setdescricao($descricao);
								$pdvitem->setquantidade($quantidade);
								$pdvitem->setpreco($vlrunitario);
								$pdvitem->setdesconto($desconto);
								$pdvitem->setacrescimo($acrescimo);
								$pdvitem->settotal($totalliquido);
								$pdvitem->settptribicms($tptribicms);
								$pdvitem->setaliqicms($aliqicms);

								$pdvvenda->pdvitem[] = $pdvitem;
							}
							break;
						}
					}

					break;
				case "E16": // Demais documentos emitidos pelo ECF
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$coo = substr($linha, 46, 6); // Numero do COO (contador de ordem de operacao) relativo ao respectivo documento
					$gnf = substr($linha, 52, 6); // Numero do GNF (contador geral de operacao nao fiscal) relativo ao respectivo documento, quando houver
					break;
				case "E21": // Detalhe do cupom fiscal e documento nao fiscal - meio de pagamento
					$numusuario = substr($linha, 44, 2); // Numero de ordem do usuario do ECF relativo a respectiva reducao Z
					$coo = substr($linha, 46, 6); // Numero do COO (contador de ordem de operacao) relativo ao respectivo cupom fiscal ou comprovante nao fiscal
					$gnf = substr($linha, 58, 6); // Numero do GNF (contador geral de operacao nao fiscal) relativo ao respectivo cupom fiscal ou comprovante nao fiscal emitido
					break;
			}
		}

		// Aplica o desconto dos cupons nos itens
		foreach($arr_desconto as $cupom => $desconto){
			foreach($this->arr_pdvvenda as $pdvvenda){
				if($pdvvenda->getcupom() == $cupom){
					foreach($pdvvenda->pdvitem as $pdvitem){
						$desconto_item = $pdvitem->gettotal() * $desconto / 100;
						$pdvitem->setdesconto($pdvitem->getdesconto() + $desconto_item);
						$pdvitem->settotal($pdvitem->gettotal() - $desconto_item);
					}
					break;
				}
			}
		}

		// Aplica o acrescimo dos cupons nos itens
		foreach($arr_acrescimo as $cupom => $acrescimo){
			foreach($this->arr_pdvvenda as $pdvvenda){
				if($pdvvenda->getcupom() == $cupom){
					$acrescimo_total = 0;
					foreach($pdvvenda->pdvitem as $pdvitem){
						$acrescimo_item = $pdvitem->gettotal() * $acrescimo[0] / 100;
						$acrescimo_item = round($acrescimo_item, 2);
						$acrescimo_total += $acrescimo_item;
						$pdvitem->setacrescimo($pdvitem->getacrescimo() + $acrescimo_item);
						$pdvitem->settotal($pdvitem->gettotal() + $acrescimo_item);

						if($pdvitem == end($pdvvenda->pdvitem)){
							if($acrescimo_total > $acrescimo[1]){
								$dif = $acrescimo_total - $acrescimo[1];
								$pdvitem->settotal($pdvitem->gettotal() - $dif);
							}
							if($acrescimo_total < $acrescimo[1]){
								$dif = $acrescimo[1] - $acrescimo_total;
								$pdvitem->settotal($pdvitem->gettotal() + $dif);
							}
						}
					}
					break;
				}
			}
		}


		if(!is_dir($dirname."IMPORTADO")){
			mkdir($dirname."IMPORTADO");
		}

		copy($dirname.$filename, $dirname."IMPORTADO/".$filename);
		unlink($dirname.$filename);

		return TRUE;
	}

	private function processar_arquivo_sat($filename, $string_xml){
		if(strlen($filename) > 0){
			// Carrega o arquivo XML do CF-e
			$xml = simplexml_load_file($filename);
		}else{
			// Puxa a string do xml
			$xml = simplexml_load_string($string_xml);
		}
		// Verifica se o XML foi carregado corretamente
		if($xml === FALSE){
			$_SESSION["ERROR"] = "O arquivo <b>{$filename}</b> não é um arquivo XML válido.";
			return FALSE;
		}

		// Localiza a tag infCFe
		if(strlen($filename) > 0){
			$infCFe = $xml->infCFe;
		}else{
			$infCFe = $xml->infNFe;
		}

		// Verifica se existe itens no cupom
		if(!isset($infCFe->det)){
			return TRUE;
		}

		// Carrega os dados do cupom
		$chavecfe = substr($infCFe["Id"], -44);
		$seqecf = (string) $infCFe->ide->nCFe;
		$cupom = $seqecf;
		$caixa = (int) $infCFe->ide->numeroCaixa;
		$numfabricacao = (string) $infCFe->ide->nserieSAT;
		$data = (string) $infCFe->ide->dEmi;
		$hora = (string) $infCFe->ide->hEmi;
		$cpfcnpj = (string) $infCFe->dest->CPF;

		// Formata data e hora
		$data = substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);
		$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":".substr($hora, 4, 2);

		// Localiza a ECF (no caso o SAT) a partir do numero de serie
		$ecf = $this->verificar_ecf($numfabricacao, "DESCONHECIDO", $caixa, "SAT");
		if($ecf === FALSE){
			return FALSE;
		}

		// Captura os dados do ECF
		$codecf = $ecf->getcodecf();
		$numeroecf = $ecf->getnumeroecf();

		// Cria o cupom
		$pdvvenda = new PdvVenda();
		$pdvvenda->setstatus("A");
		$pdvvenda->setcaixa($caixa);
		$pdvvenda->setchavecfe($chavecfe);
		$pdvvenda->setcodecf($codecf);
		$pdvvenda->setnumeroecf($numeroecf);
		$pdvvenda->setseqecf($seqecf);
		$pdvvenda->setcupom($cupom);
		$pdvvenda->setdata($data);
		$pdvvenda->sethora($hora);
		$pdvvenda->setcpfcnpj($cpfcnpj);

		// Percorre todos os itens do XML
		foreach($infCFe->det as $det){

			// Captura os dados de cada item
			$sequencial = $det["nItem"];
			$codproduto = (string) $det->prod->cProd;
			$quantidade = (float) $det->prod->qCom;
			$preco = (float) $det->prod->vUnCom;
			$desconto = (float) $det->prod->vDesc;
			$total = (float) $det->prod->vItem;

			// Captura as informacoes de ICMS
			$icms = reset($det->imposto->ICMS);
			$this->capturar_icms($tptribicms, $aliqicms, $icms);

			// Cria o item
			$pdvitem = new PdvItem();
			$pdvitem->setstatus("A");
			$pdvitem->setsequencial($sequencial);
			$pdvitem->setcodproduto($codproduto);
			$pdvitem->setquantidade($quantidade);
			$pdvitem->setpreco($preco);
			$pdvitem->setdesconto($desconto);
			$pdvitem->settotal($total);
			$pdvitem->settptribicms($tptribicms);
			$pdvitem->setaliqicms($aliqicms);

			// Inclui o item na venda
			$pdvvenda->pdvitem[] = $pdvitem;
		}

		// Le as finalizadoras de venda
		foreach($infCFe->pgto->MP as $mp){
			$pdvfinalizador = new PdvFinalizador();
			$pdvfinalizador->setdata($pdvvenda->getdata());
			$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
			$pdvfinalizador->setcupom($pdvvenda->getcupom());
			$pdvfinalizador->setcodcliente($pdvvenda->getcodcliente());
			$pdvfinalizador->setcpfcliente($pdvvenda->getcpfcnpj());
			$pdvfinalizador->setcodfinaliz((string) $mp->cMP);
			$pago = (float) $mp->vMP;
			$troco = (float) $infCFe->pgto->vTroco;
			$total = $pago - $troco;
			$pdvfinalizador->setvalortotal($total);

			// Armazena a finalizadora
			$this->arr_pdvfinalizador[] = $pdvfinalizador;
		}

		// Armazena a venda
		$this->arr_pdvvenda[] = $pdvvenda;

		// Retorna TRUE para identificar que foi processado com sucesso
		return TRUE;
	}

	public function setcaixa($caixa){
		$this->caixa = $caixa;
	}

	public function setnumeroecf($numeroecf){
		$this->numeroecf = $numeroecf;
	}

	public function setgerardataarq($gerardataarq){
		$this->gerardataarq = $gerardataarq;
	}

	public function setgerarE00($gerarE00){
		$this->gerarE00 = $gerarE00;
	}

	public function pararprocesso($arr_arquivo){
		$caminho = "../temp/fiscal/";
		foreach($arr_arquivo as $arquivo){
			if(file_exists($caminho.$arquivo)){
				unlink($caminho.$arquivo);
			}
		}
		echo messagebox("info", "", "O processo atual foi interrompido.");

		return true;
	}

	// Formata a data de "yyyymmdd" para "yyyy-mm-dd"
	private function valor_data($data){
		$data = substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);
		return $data;
	}

	private function valor_data_export($data){
		$data = substr($data, 0, 4).substr($data, 5, 2).substr($data, 8, 2);
		return $data;
	}

	private function valor_hora($data){
		return substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);
	}

	private function valor_numerico($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, "", "");
		$numero = str_replace(array("."), "", $numero);
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto, $tamanho, $format = true){
		if($format){
			$texto = removeformat($texto);
		}

		$texto = (substr(rtrim($texto), 0, $tamanho));
		$texto = str_pad($texto, $tamanho, " ", STR_PAD_RIGHT);
		return $texto;
	}

	private function verificar_ecf($numfabricacao, $modelo, $caixa, $equipamentofiscal){
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setnumfabricacao($numfabricacao);
		$arr_ecf = object_array($ecf);
		if(count($arr_ecf) > 0){
			$ecf = array_shift($arr_ecf);
		}else{
			$ecf = objectbytable("ecf", NULL, $this->con);
			$ecf->setcodestabelec($this->estabelecimento->getcodestabelec());
			$ecf->setnumfabricacao($numfabricacao);
			$ecf->setmodelo($modelo);
			$ecf->setcaixa($caixa);
			$ecf->setequipamentofiscal($equipamentofiscal);
			if(!$ecf->save()){
				return FALSE;
			}
		}
		return $ecf;
	}

}