<?php

require_once("websac/require_file.php");
require_file("class/excelreader.class.php");
require_file("class/saneamento.class.php");

class SaneamentoArquivo{

	private $con;
	private $estabelecimento;
	private $modelosaneamento;
	private $erro_contador;

	function __construct($con){
		$this->con = $con;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setmodelosaneamento($modelosaneamento){
		$this->modelosaneamento = $modelosaneamento;
	}

	function exportar(){
		$arr_coluna = array();
		$arr_campo = $this->modelosaneamento->getcolumnnames();
		foreach($arr_campo as $campo){
			if(substr($campo, 0, 4) == "col_"){
				$n_col = call_user_func(array($this->modelosaneamento, "get".$campo));
				if($n_col > 0){
					$arr_coluna[$n_col] = substr($campo, 4);
				}
			}
		}
		ksort($arr_coluna);
		$j = 1;
		foreach($arr_coluna as $i => $coluna){
			while($j < $i){
				if(!isset($arr_coluna[$j])){
					$arr_coluna[$j] = "";
				}
				$j++;
			}
		}
		ksort($arr_coluna);

		$where = array();

		if(strlen($_REQUEST["codfornec"]) > 0){
			$where[] = "prodfornec.codfornec = ".$_REQUEST["codfornec"];
		}
		if(strlen($_REQUEST["coddepto"]) > 0){
			$where[] = "produto.coddepto = ".$_REQUEST["coddepto"];
		}
		if(strlen($_REQUEST["codgrupo"]) > 0){
			$where[] = "produto.codgrupo = ".$_REQUEST["codgrupo"];
		}
		if(strlen($_REQUEST["codsubgrupo"]) > 0){
			$where[] = "produto.codsubgrupo = ".$_REQUEST["codsubgrupo"];
		}
		if(strlen($_REQUEST["codfamilia"]) > 0){
			$where[] = "produto.codfamilia = ".$_REQUEST["codfamilia"];
		}
		if(strlen($_REQUEST["foralinha"]) > 0){
			$where[] = "produto.foralinha = '".$_REQUEST["foralinha"]."'";
		}
		if(strlen($_REQUEST["marca"]) > 0){
			$where[] = "produto.codmarca = '".$_REQUEST["marca"]."'";
		}
		if(strlen($_REQUEST["datainclusaoini"]) > 0){
			$where[] = "produto.datainclusao >= '".value_date($_REQUEST["datainclusaoini"])."'";
		}
		if(strlen($_REQUEST["datainclusaofim"]) > 0){
			$where[] = "produto.datainclusao <= '".value_date($_REQUEST["datainclusaofim"])."'";
		}

		if(strlen($_REQUEST["dtultimavendaini"]) > 0){
			$where[] = "consvendadia.dtmovto >= '".value_date($_REQUEST["dtultimavendaini"])."'";
		}
		if(strlen($_REQUEST["dtultimavendafim"]) > 0){
			$where[] = "consvendadia.dtmovto <= '".value_date($_REQUEST["dtultimavendafim"])."'";
		}
		if(strlen($_REQUEST["saneado"]) > 0){
			$where[] = "produto.dtsaneamento ".($_REQUEST["saneado"] == "S" ? "IS NOT NULL" : "IS NULL");
		}
		if(strlen($_REQUEST["semmovimento"]) > 0){
			if($_REQUEST["semmovimento"] == "S"){
				$where[] = "produto.codproduto NOT IN (SELECT DISTINCT codproduto FROM movimento) ";
			}else{
				$where[] = "produto.codproduto IN (SELECT DISTINCT codproduto FROM movimento) ";
			}
		}

		$query = "SELECT DISTINCT produto.codproduto, produto.descricaofiscal, ncm.codigoncm, ipi.codcstent AS cstipi, ipi.tptribipi AS tipoipi, ";
		$query .= "	ipi.aliqipi AS valipi, classfiscalnfe.codcst AS csticmsnfe, classfiscalnfe.tptribicms AS tptribicmsnfe, ";
		$query .= "	classfiscalnfe.aliqicms AS aliqicmsnfe, classfiscalnfe.aliqredicms AS redicmsnfe, classfiscalnfe.aliqiva AS aliqivanfe, ";
		$query .= "	classfiscalnfe.valorpauta AS valorpautanfe, classfiscalnfs.codcst AS csticmsnfs, classfiscalnfs.tptribicms AS tptribicmsnfs, ";
		$query .= "	classfiscalnfs.aliqicms AS aliqicmsnfs, classfiscalnfs.aliqredicms AS redicmsnfs, classfiscalnfs.aliqiva AS aliqivanfs, ";
		$query .= "	classfiscalnfs.valorpauta AS valorpautanfs, classfiscalpdv.codcst AS csticmspdv, classfiscalpdv.tptribicms AS tptribicmspdv, ";
		$query .= "	classfiscalpdv.aliqicms AS aliqicmspdv, classfiscalpdv.aliqredicms AS redicmspdv, classfiscalpdv.aliqiva AS aliqivapdv, ";
		$query .= "	classfiscalpdv.valorpauta AS valorpautapdv, piscofinsent.codcst AS cstpiscofinsent, piscofinsent.codccs AS ccspiscofinsent, ";
		$query .= "	piscofinsent.tipo AS tipopiscofinsent, piscofinsent.aliqpis AS aliqpisent, piscofinsent.aliqcofins AS aliqcofinsent, ";
		$query .= "	piscofinsent.redpis AS redpisent, piscofinsent.redcofins AS redcofinsent, piscofinssai.codcst AS cstpiscofinssai, ";
		$query .= "	piscofinssai.codccs AS ccspiscofinssai, piscofinssai.tipo AS tipopiscofinssai, piscofinssai.aliqpis AS aliqpissai, ";
		$query .= "	piscofinssai.aliqcofins AS aliqcofinssai, piscofinssai.redpis AS redpissai, piscofinssai.redcofins AS redcofinssai, ";
		$query .= "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codean, ";
		$query .= " COALESCE(COALESCE(COALESCE(COALESCE((SELECT natreceita FROM natreceita WHERE tabela = 'P' AND codigo = produto.codproduto),(SELECT natreceita FROM natreceita WHERE tabela = 'S' AND codigo = produto.codsubgrupo)),(SELECT natreceita FROM natreceita WHERE tabela = 'G' AND codigo = produto.codgrupo)),(SELECT natreceita FROM natreceita WHERE tabela = 'D' AND codigo = produto.coddepto)),'999') AS natreceita, ";
		$query .= " unidade.sigla AS unidade ";
		$query .= "FROM produto ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "INNER JOIN classfiscal AS classfiscalnfe ON (produto.codcfnfe = classfiscalnfe.codcf) ";
		$query .= "INNER JOIN classfiscal AS classfiscalnfs ON (produto.codcfnfs = classfiscalnfs.codcf) ";
		$query .= "INNER JOIN classfiscal AS classfiscalpdv ON (produto.codcfpdv = classfiscalpdv.codcf) ";
		$query .= "INNER JOIN ipi ON (produto.codipi = ipi.codipi) ";
		$query .= "INNER JOIN piscofins AS piscofinsent ON (produto.codpiscofinsent = piscofinsent.codpiscofins) ";
		$query .= "INNER JOIN piscofins AS piscofinssai ON (produto.codpiscofinssai = piscofinssai.codpiscofins) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (unidade.codunidade = embalagem.codunidade) ";

		if(strlen($_REQUEST["codfornec"]) > 0){
			$query .= "INNER JOIN prodfornec ON (produto.codproduto = prodfornec.codproduto) ";
		}

		if(strlen($_REQUEST["dtultimavendaini"]) > 0 || strlen($_REQUEST["dtultimavendafim"]) > 0){
			$query .= "INNER JOIN consvendadia ON (produto.codproduto = consvendadia.codproduto) ";
		}

		if(sizeof($where) > 0){
			$query .= "WHERE ".implode(" AND ", $where)." ";
		}

		$query .= "ORDER BY produto.descricaofiscal ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$html = "<body><table>";
		for($i = 1; $i < $this->modelosaneamento->getlinhainicial(); $i++){
			$html .= "<tr></tr>";
		}
		foreach($arr as $row){
			$html .= "<tr>";
			foreach($arr_coluna as $i => $coluna){
				$valor = "";
				if(strlen($coluna) > 0){
					$valor = $row[$coluna];
				}
				$html .= "<td style=\"mso-number-format:\@\">".$valor."</td>";
			}
			$html .= "</tr>";
		}
		$html .= "</table></body>";
		header("Content-type: application/vnd.ms-excel");
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=".$this->modelosaneamento->getnomearquivo());
		header("Pragma: no-cache");
		echo $html;
	}

	function erro_contador(){
		return $this->erro_contador;
	}

	function importar(){
		setprogress(0, "Carregando arquivo de saneamento", TRUE);
		$arquivo = $this->estabelecimento->getdirarqfiscal().$this->modelosaneamento->getnomearquivo();
		if(!file_exists($arquivo)){
			$_SESSION["ERROR"] = "O arquivo ".str_replace("\\", "\\\\", $arquivo)." n&atilde;o p&ocirc;de ser encontrado.";
			return FALSE;
		}

		$saneamento = new Saneamento($this->con);
		$saneamento->setregimetributario($this->estabelecimento->getregimetributario());
		$saneamento->setuf($this->estabelecimento->getuf());

		$excelreader = new ExcelReader($arquivo);
		$excelreader->setsheet(0);
		$data = $excelreader->data;
		$arr_coluna = $this->modelosaneamento->getcolumnnames();
		$this->con->start_transaction();
		foreach($data as $i => $row){
			setprogress((($i + 1) / sizeof($data) * 100), "Atualizando produtos: ".($i + 1)." de ".sizeof($data));

			// Pula as linhas para iniciar da linha informada no modelo
			if($i < $this->modelosaneamento->getlinhainicial()){
				continue;
			}

			// Carrega as colunas em variaveis
			foreach($arr_coluna as $coluna){
				if(substr($coluna, 0, 4) == "col_"){
					$n_col = call_user_func(array($this->modelosaneamento, "get".$coluna));
					$coluna_sub = substr($coluna, 4);
					$$coluna_sub = NULL;
					if($n_col > 0){
						$$coluna_sub = trim(iconv("UTF-8", "UTF-8//IGNORE", utf8_encode($row[$n_col])));
					}
				}
			}

			// Localiza o produto no banco de dados
			if((strlen($codproduto) == 0 && strlen($codean) > 0) || strlen($codproduto) >= 8){
				$produtoean = objectbytable("produtoean", $codean, $this->con);
				$codproduto = $produtoean->getcodproduto();
			}
			$produto = objectbytable("produto", $codproduto, $this->con);
			if(!$produto->exists() && $produto->getsanear() == "S"){
				if(strlen($descricaofiscal) == 0){
					continue;
				}
				$produto->setdescricaofiscal($descricaofiscal);
				$arr_produto = object_array($produto);

				$achou = FALSE;
				foreach($arr_produto AS $produto_){
					if($produto_->getdescricaofiscal() == $descricaofiscal){
						$produto = $produto_;
						$achou = TRUE;
						break;
					}
				}

				if(!$achou){
					continue;
				}

				// Por enquanto vai apenas pular os produtos que nao forem encontrados
//				$this->con->rollback();
//				$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel localizar o produto da linha <b>".($i + 1)."</b>.";
//				return FALSE;
			}

			// Corrige CST de PIS/Cofins quando vir faltando o primeiro caracter
			if(strlen($cstpiscofinssai) == 1){
				$cstpiscofinssai = "0".$cstpiscofinssai;
			}

			// Verifica se o tipo de tributacao de ICMS foi informada, caso nao, preenche usando o CST
			if(strlen($tptribicmsnfe) == 0 && strlen($csticmsnfe) > 0){
				$tptribicmsnfe = $this->classfiscal_tptribicms($csticmsnfe);
			}
			if(strlen($tptribicmsnfs) == 0 && strlen($csticmsnfs) > 0){
				$tptribicmsnfs = $this->classfiscal_tptribicms($csticmsnfs);
			}
			if(strlen($tptribicmspdv) == 0 && strlen($csticmspdv) > 0){
				$tptribicmspdv = $this->classfiscal_tptribicms($csticmspdv);
			}

			// Preenche os dados do produto
			$dados = array(
				"codproduto" => $produto->getcodproduto(),
				"natreceita" => $natreceita,
				"ncm" => array(
					"codigoncm" => $codigoncm
				),
				"classfiscalnfe" => array(
					"codcst" => $csticmsnfe,
					"tptribicms" => $tptribicmsnfe,
					"aliqicms" => $aliqicmsnfe,
					"aliqredicms" => $redicmsnfe,
					"aliqiva" => $aliqivanfe,
					"valorpauta" => $valorpautanfe
				),
				"classfiscalnfs" => array(
					"codcst" => $csticmsnfs,
					"tptribicms" => $tptribicmsnfs,
					"aliqicms" => $aliqicmsnfs,
					"aliqredicms" => $redicmsnfs,
					"aliqiva" => $aliqivanfs,
					"valorpauta" => $valorpautanfs
				),
				"classfiscalpdv" => array(
					"codcst" => $csticmspdv,
					"tptribicms" => $tptribicmspdv,
					"aliqicms" => $aliqicmspdv,
					"aliqredicms" => $redicmspdv,
					"aliqiva" => $aliqivapdv,
					"valorpauta" => $valorpautapdv
				),
				"piscofinsent" => array(
					"tipo" => $tipopiscofinsent,
					"codcst" => $cstpiscofinsent,
					"codccs" => $ccspiscofinsent,
					"aliqpis" => $aliqpisent,
					"aliqcofins" => $aliqcofinsent
				),
				"piscofinssai" => array(
					"tipo" => $tipopiscofinssai,
					"codcst" => $cstpiscofinssai,
					"codccs" => $ccspiscofinssai,
					"aliqpis" => $aliqpissai,
					"aliqcofins" => $aliqcofinssai
				),
				"ipi" => array(
					"codcstent" => $cstipient,
					"codcstsai" => $cstipisai,
					"tptribipi" => $tipoipi,
					"aliqipi" => $valipi
				)
			);

			// Verifica se as tributacoes foram informadas no produto
			$arr_verificarinformado = array(
				"classfiscalnfe", "classfiscalnfs", "classfiscalpdv",
				"piscofinsent", "piscofinssai", "ipi"
			);
			foreach($arr_verificarinformado as $verificarinformado){
				$found = false;
				foreach($dados[$verificarinformado] as $value){
					if(strlen($value) > 0){
						$found = true;
						break;
					}
				}
				if(!$found){
					unset($dados[$verificarinformado]);
				}
			}

			// Atualiza o produto
			if(!$saneamento->atualizar_produto($dados)){
				$this->con->rollback();
				return FALSE;
			}
		}

		$this->erro_contador = $saneamento->erro_contador();

		$this->con->commit();
		return TRUE;
	}

	private function classfiscal_tptribicms($codcst){
		switch($codcst){
			case "00": return "T";
			case "10": return "F";
			case "20": return "R";
			case "40": return "I";
			case "41": return "N";
			case "60": return "F";
			case "70": return "F";
			default : return "I";
		}
	}

}