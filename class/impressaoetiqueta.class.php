<?php

require_once("websac/require_file.php");
require_file("class/etiqueta.class.php");
require_file("class/printer.class.php");
require_file("class/pdfbarcode.class.php");
require_file("lib/fpdf-1.8.0/fpdf.php");

if(strpos($_SERVER["SCRIPT_FILENAME"], "/mobile/") === FALSE){
	define("FPDF_FONTPATH", "../lib/fpdf-1.8.0/font/");
}else{
	define("FPDF_FONTPATH", "../../lib/fpdf-1.8.0/font/");
}

class ImpressaoEtiqueta{

	private $con;
	private $estabelecimento;
	private $etiqgondola;
	private $arr_produto;
	private $arr_produtoean;
	private $habilitaprecopdv;
	private $mensagemvenda;

	function __construct($con){
		$this->con = $con;
		$this->arr_produto = array();
	}

	function addproduto($produto, $qtdeetiqueta){
		$this->arr_produto[] = array($produto, $qtdeetiqueta);
	}

	function addprodutoean($produtoean, $codproduto){
		$this->arr_produtoean[$codproduto] = $produtoean;
	}

	function setetiqgondola($etiqgondola){
		$this->etiqgondola = $etiqgondola;
		$this->estabelecimento = objectbytable("estabelecimento", $this->etiqgondola->getcodestabelec(), $this->con);
	}

	function setmensagemvenda($mensagem){
		$this->mensagemvenda = $mensagem;
	}

	function imprimir($returnText = FALSE){
		// Grava log de impressao
		$this->con->start_transaction();
		foreach($this->arr_produto as $produto){
			$produtoestab = objectbytable("produtoestab", array($this->estabelecimento->getcodestabelec(), $produto[0]->getcodproduto()), $this->con);
			$logetiqueta = objectbytable("logetiqueta", NULL, $this->con);
			$logetiqueta->setcodestabelec($this->estabelecimento->getcodestabelec());
			$logetiqueta->setcodproduto($produto[0]->getcodproduto());
			$logetiqueta->setdata(date("d/m/Y"));
			$logetiqueta->sethora(date("H:i:s"));
			$logetiqueta->setqtdeetiqueta($produto[1]);
			$logetiqueta->setpreco($this->getpreco($produtoestab));
			if(!$logetiqueta->save()){
				$this->con->rollback();
				$_SESSION["ERROR"] = "Erro ao gravar log de impress&atilde;o.<br><br>".$_SESSION["ERROR"];
				return FALSE;
			}
		}
		$this->con->commit();

		// Separa os produtos que serao impressos de acordo com a quantidade de etiquetas
		$arr_produto = array();
		foreach($this->arr_produto as $produto){
			for($i = 0; $i < $produto[1]; $i++){
				$arr_produto[] = $produto[0];
			}
		}

		if($this->etiqgondola->getcodimpressora() == 3){
			// Impressora a jato de tinta
			return $this->imprimir_jatotinta($arr_produto);
		}else{
			// Impressora normal (termica) de etiquetas
			return $this->imprimir_termica($arr_produto, $returnText);
		}
	}

	private function imprimir_jatotinta($arr_produto){
		$fator = 10;

		//$orientacao = ($this->etiqgondola->getlargurafolha() > $this->etiqgondola->getalturafolha() ? "L" : "P");
		$orientacao = "P";
		$dimensao = array(
			($this->etiqgondola->getlargurafolha() / $fator),
			($this->etiqgondola->getalturafolha() / $fator)
		);

		$pdf = new FPDF();
		$pdf->SetFont("Arial");

		$pdf->AddPage($orientacao, $dimensao);

		if($this->etiqgondola->getinvertercor() == "S"){
			$pdf->SetDrawColor(0,0,0);
			$pdf->Rect(0,0,1000,1000,"F");
			$pdf->SetFillColor(255,255,255);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetDrawColor(255,255,255);
		}

		$x = $this->etiqgondola->getbordahorizontal() / $fator;
		$y = $this->etiqgondola->getbordavertical() / $fator;
		foreach($arr_produto as $i => $produto){
			$produtoestab = objectbytable("produtoestab", array($this->estabelecimento->getcodestabelec(), $produto->getcodproduto()), $this->con);
			if($i > 0){
				if(($i / $this->etiqgondola->getnumcarreiras()) == floor($i / $this->etiqgondola->getnumcarreiras())){
					$y += $this->etiqgondola->getaltura() / $fator;
					$x = $this->etiqgondola->getbordahorizontal() / $fator;
				}else{
					$x += $this->etiqgondola->getlargura() / $fator;
				}
			}
			if($i > 0 && ($y + $this->etiqgondola->getaltura() / $fator > $this->etiqgondola->getalturafolha() / $fator)){
				$pdf->AddPage($orientacao, $dimensao);
				$y = $this->etiqgondola->getbordavertical() / $fator;
			}

			if($this->etiqgondola->getbox() == "S"){
				$pdf->Rect($x, $y, $this->etiqgondola->getlargura() / $fator, $this->etiqgondola->getaltura() / $fator);
			}

			if($this->etiqgondola->getshow_estabelecimento() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_estabelecimento() / $fator);
				$pdf->Text($x + $this->etiqgondola->getposx_estabelecimento() / $fator, $y + $this->etiqgondola->getposy_estabelecimento(), $this->estabelecimento->getnome());
			}

			if($this->etiqgondola->getshow_infonutricional() == "S"){
				$texto_etiqueta = $this->etiqgondola->getcampos_infonutricional();
				if(strstr($texto_etiqueta, "[receita]") && strlen($produto->getcodreceita()) > 0){
					if(strstr($this->etiqgondola->getcampos_infonutricional(), "[infonutricional]")){
						$texto_etiqueta_aux = explode("[infonutricional]", $this->etiqgondola->getcampos_infonutricional());
						$texto_etiqueta = $texto_etiqueta_aux[0];
					}
					$receita = objectbytable("receita", NULL, $this->con);
					$receita->setcodreceita($produto->getcodreceita());
					$receita = object_array($receita);
					$texto_receita = "";
					if(strlen($receita[0]->getcomponentes()) > 0){
						$texto_receita .= "Componentes: ".trim($receita[0]->getcomponentes());
					}
					if(strlen($receita[0]->getmodopreparo()) > 0){
						$texto_receita .= " Modo de Preparo: ".trim($receita[0]->getmodopreparo());
					}
					$texto_etiqueta = str_replace("[receita]", $texto_receita, $texto_etiqueta);
					$aux = array_reverse(explode("\n", $texto_etiqueta));
					$posy = $this->etiqgondola->getposy_infonutricional();
					$tam_fonte = $this->etiqgondola->getfont_infonutricional() * 10;
					foreach($aux as $a){
						$posy += $tam_fonte;
						$pdf->Text($x + $this->etiqgondola->getposx_infonutricional() / $fator, $y + $this->etiqgondola->getposy_infonutricional() + $posy, $a);
					}
				}

				$texto_etiqueta = $this->etiqgondola->getcampos_infonutricional();
				if(strstr($texto_etiqueta, "[infonutricional]") && strlen($produto->getcodnutricional()) > 0){
					if(strstr($this->etiqgondola->getcampos_infonutricional(), "[infonutricional]")){
						$texto_etiqueta_aux = explode("[infonutricional]", $this->etiqgondola->getcampos_infonutricional());
						$texto_etiqueta = $texto_etiqueta_aux[1];
					}
					$nutricional = objectbytable("nutricional", NULL, $this->con);
					$nutricional->setcodnutricional($produto->getcodnutricional());
					$nutricional = object_array($nutricional);
					$campos = array
						(
						"[qtdcalorico]", "[perccalorico]", "[qtdcolesterol]", "[perccolesterol]", "[qtdcarboidrato]", "[perccarboidrato]",
						"[qtdfibraalimentar]", "[percfibraalimentar]", "[qtdproteina]", "[percproteina]", "[qtdcalcio]", "[perccalcio]",
						"[qtdtotalgordura]", "[perctotalgordura]", "[qtdferro]", "[percferro]", "[qtdgordsaturada]", "[percgordsaturada]",
						"[qtdsodio]", "[percsodio]", "[qtdgorduratrans]", "[percgorduratrans]"
					);
					$valores = array
						(
						$nutricional[0]->getqtdecal(), $nutricional[0]->getperccal(), $nutricional[0]->getqtdecolest(), $nutricional[0]->getperccolest(), $nutricional[0]->getqtdecarbo(), $nutricional[0]->getperccarbo(),
						$nutricional[0]->getqtdefibra(), $nutricional[0]->getpercfibra(), $nutricional[0]->getqtdeprot(), $nutricional[0]->getpercprot(), $nutricional[0]->getqtdecalcio(), $nutricional[0]->getperccalcio(),
						$nutricional[0]->getqtdegord(), $nutricional[0]->getpercgord(), $nutricional[0]->getqtdeferro(), $nutricional[0]->getpercferro(), $nutricional[0]->getqtdegordsat(), $nutricional[0]->getpercgordsat(),
						$nutricional[0]->getqtdesodio(), $nutricional[0]->getpercsodio(), $nutricional[0]->getqtdegordtrans(), $nutricional[0]->getpercgordtrans()
					);
					$texto_etiqueta = str_replace($campos, $valores, $texto_etiqueta);
				}

				//$teste = round($this->etiqgondola->getlargura_tabelainfonutricional() / 3);

				$coluna1 = $this->etiqgondola->getposx_infonutricional();
				$coluna2 = $this->etiqgondola->getposx_tabelainfonutricional() + $this->etiqgondola->getcoluna1_tabelainfonutricional();
				$coluna3 = $this->etiqgondola->getposx_tabelainfonutricional() + $this->etiqgondola->getcoluna1_tabelainfonutricional() + $this->etiqgondola->getcoluna2_tabelainfonutricional() + $this->etiqgondola->getcoluna3_tabelainfonutricional();
				if(strlen($posy) == 0){
					$posy = $this->etiqgondola->getposy_infonutricional();
				}

				$posy += $this->etiqgondola->getfont_infonutricional() * 20;
				$an = $posy;
				$texto_tabela = array_reverse(explode("\n", $texto_etiqueta));
				foreach($texto_tabela as $tabela){
					$aux = explode("|", trim($tabela));
					$cnt = 0;
					$posy += $tam_fonte;
					foreach($aux as $a){
						if($cnt == 0){
							$pdf->Text($x + $coluna1 / $fator, $y + $this->etiqgondola->getposy_infonutricional() + $posy, $a);
						}elseif($cnt == 1){
							$pdf->Text($x + $coluna2 / $fator, $y + $this->etiqgondola->getposy_infonutricional() + $posy, $a);
						}elseif($cnt == 2){
							$pdf->Text($x + $coluna3 / $fator, $y + $this->etiqgondola->getposy_infonutricional() + $posy, $a);
						}
						$cnt++;
					}
				}
				$tam_fonte = $this->etiqgondola->getaltura_tabelainfonutricional() - $this->etiqgondola->getfont_infonutricional() * 10;
				$pdf->Rect($coluna1, $an, $this->etiqgondola->getlargura_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional());
				$pdf->Rect($coluna2, $an, $this->etiqgondola->getlargura_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional());
				$pdf->Rect($coluna3, $an, $this->etiqgondola->getlargura_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional());
			}

			if($this->etiqgondola->getshow_codproduto() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_codproduto() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_codproduto() / $fator, $y + $this->etiqgondola->getposy_codproduto() / $fator, $produto->getcodproduto(), $this->orientacao($this->etiqgondola->getorie_codproduto()));
			}
			if($this->etiqgondola->getshow_codeantexto() == "S"){
				if(sizeof($this->arr_produtoean) > 0){
					$codean = $this->arr_produtoean[$produtoestab->getcodproduto()];
				}else{
					$produtoean = objectbytable("produtoean", NULL, $this->con);
					$produtoean->setcodproduto($produtoestab->getcodproduto());
					$arr_produtoean = object_array($produtoean);
					if(sizeof($arr_produtoean) > 0){
						$codean = $arr_produtoean[0]->getcodean();
					}else{
						$codean = "";
					}
				}
				$pdf->SetFontSize($this->etiqgondola->getaltu_codeantexto() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_codeantexto() / $fator, $y + $this->etiqgondola->getposy_codeantexto() / $fator, $codean, $this->orientacao($this->etiqgondola->getorie_codeantexto()));
			}

			if($this->etiqgondola->getshow_descricao() == "S"){
				unset($simprod);
				if(strlen($produto->getcodsimilar())){
					$simprod = objectbytable("simprod", $produto->getcodsimilar(), $this->con);
				}
				if(is_object($simprod) && $simprod->getimpdescricao() == "S"){
					$descricao = $simprod->getdescricao();
				}else{
					if($this->etiqgondola->gettipodescricao() == "N"){
						$descricao = $produto->getdescricaofiscal();
					}else{
						$descricao = $produto->getdescricao();
					}
				}
				$descricao = removespecial($descricao);
				$pdf->SetFontSize($this->etiqgondola->getaltu_descricao() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_descricao() / $fator, $y + $this->etiqgondola->getposy_descricao() / $fator, $descricao, $this->orientacao($this->etiqgondola->getorie_descricao()));

//					$arr_descricao = explode(" ", $descricao);
//					foreach($arr_descricao AS $descricao){
//						$pdf->RotatedText($x + $this->etiqgondola->getposx_descricao() / $fator, $y + $this->etiqgondola->getposy_descricao() / $fator, $descricao, $this->orientacao($this->etiqgondola->getorie_descricao()));
//						$y += ($this->etiqgondola->getaltu_descricao() * 0.03);
//					}
			}

			if($this->etiqgondola->getshow_logo() == "S"){
				$pdf->Image($this->estabelecimento->getlocallogotipo(), $x + $this->etiqgondola->getposx_logo() / $fator, $y + $this->etiqgondola->getposy_logo() / $fator, $this->etiqgondola->getlarg_logo(), $this->etiqgondola->getaltu_logo());
			}

			if($this->etiqgondola->getshow_preco() == "S"){
				$preco = $this->getpreco($produtoestab);
				$pdf->SetFontSize($this->etiqgondola->getaltu_preco() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_preco() / $fator, $y + $this->etiqgondola->getposy_preco() / $fator, $preco, $this->orientacao($this->etiqgondola->getorie_preco()));
			}
			if($this->etiqgondola->getshow_mensagemvenda() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_mensagemvenda() / $fator);

				if(strlen($this->mensagemvenda) > 0){
					$mensagemvenda = $this->mensagemvenda;
				}else{
					$mensagemvenda = $produto->gettextovenda();
				}
				$arr_mensagemvenda = explode("\n", $mensagemvenda);

				foreach($arr_mensagemvenda AS $mensagemvenda){
					$pdf->RotatedText($x + $this->etiqgondola->getposx_mensagemvenda() / $fator, $y + $this->etiqgondola->getposy_mensagemvenda() / $fator, $mensagemvenda, $this->orientacao($this->etiqgondola->getorie_mensagemvenda()));
					if($this->etiqgondola->getorie_mensagemvenda() == 0){
						$y += ($this->etiqgondola->getaltu_mensagemvenda() * 0.03);
					}else{
						$x += ($this->etiqgondola->getposx_mensagemvenda() * 0.02);
					}
//						$y += 1.8;
				}
			}
			if($this->etiqgondola->getshow_precoatc() == "S"){
				$preco = $produtoestab->getprecoatc(true);
				$pdf->SetFontSize($this->etiqgondola->getaltu_precoatc() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_precoatc() / $fator, $y + $this->etiqgondola->getposy_precoatc() / $fator, $preco, $this->orientacao($this->etiqgondola->getorie_precoatc()));
			}
			if($this->etiqgondola->getshow_precoof() == "S"){
				$precoof = $this->getpreco($produtoestab, TRUE);
				$pdf->SetFontSize($this->etiqgondola->getaltu_precoof() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_precoof() / $fator, $y + $this->etiqgondola->getposy_precoof() / $fator, $precoof, $this->orientacao($this->etiqgondola->getorie_preoof()));
			}
			if($this->etiqgondola->getshow_qtdatacado() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_qtdatacado() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_qtdatacado() / $fator, $y + $this->etiqgondola->getposy_qtdatacado() / $fator, (int) $produtoestab->getqtdatacado(), $this->orientacao($this->etiqgondola->getorie_qtdatacado()));
			}
			if($this->etiqgondola->getshow_dtpreco() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_dtpreco() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_dtpreco() / $fator, $y + $this->etiqgondola->getposy_dtpreco() / $fator, $produtoestab->getdtpreco(TRUE), $this->orientacao($this->etiqgondola->getorie_dtpreco()));
			}
			if($this->etiqgondola->getshow_dtatual() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_dtatual() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_dtatual() / $fator, $y + $this->etiqgondola->getposy_dtatual() / $fator, date("d/m/Y"), $this->orientacao($this->etiqgondola->getorie_dtatual()));
			}
			if($this->etiqgondola->getshow_unidade() == "S" || $this->etiqgondola->getshow_qtdeunidade() == "S"){
				$embalagem = objectbytable("embalagem", $produto->getcodembalvda(), $this->con);
				if($this->etiqgondola->getshow_unidade() == "S"){
					$unidade = objectbytable("unidade", $embalagem->getcodunidade(), $this->con);
					$pdf->SetFontSize($this->etiqgondola->getaltu_unidade() / $fator);
					$pdf->RotatedText($x + $this->etiqgondola->getposx_unidade() / $fator, $y + $this->etiqgondola->getposy_unidade() / $fator, $unidade->getsigla(), $this->orientacao($this->etiqgondola->getorie_unidade()));
				}
				if($this->etiqgondola->getshow_qtdeunidade() == "S"){
					$qtdeunidade = $embalagem->getquantidade();
					if($qtdeunidade == floor($qtdeunidade)){
						$casas_decimais = 0;
					}else{
						$casas_decimais = 2;
					}
					$qtdeunidade = number_format($qtdeunidade, $casas_decimais, ",", "");
					$pdf->SetFontSize($this->etiqgondola->getaltu_qtdeunidade() / $fator);
					$pdf->RotatedText($x + $this->etiqgondola->getposx_qtdeunidade() / $fator, $y + $this->etiqgondola->getposy_qtdeunidade() / $fator, $qtdeunidade, $this->orientacao($this->etiqgondola->getorie_qtdeunidade()));
				}
			}
			if($this->etiqgondola->getshow_moeda() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_moeda() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_moeda() / $fator, $y + $this->etiqgondola->getposy_moeda() / $fator, "R$", $this->orientacao($this->etiqgondola->getorie_moeda()));
			}
			if($this->etiqgondola->getshow_codean() == "S"){
				if(sizeof($this->arr_produtoean) > 0){
					$ean = $this->arr_produtoean[$produtoestab->getcodproduto()];
					$codean = $ean->getcodean();
				}else{
					$produtoean = objectbytable("produtoean", NULL, $this->con);
					$produtoean->setcodproduto($produtoestab->getcodproduto());
					$arr_produtoean = object_array($produtoean);
					if(sizeof($arr_produtoean) > 0){
						$codean = $arr_produtoean[0]->getcodean();
					}else{
						$codean = "";
					}
				}
				$pdfbarcode = new PDFBarCode($pdf);
				$pdfbarcode->EAN13($x + $this->etiqgondola->getposx_codean() / $fator, $y + $this->etiqgondola->getposy_codean() / $fator, $codean, $this->etiqgondola->getaltu_codean() / $fator, ($this->etiqgondola->getlarg_codean() / $fator) / 100);

				$pdf = $pdfbarcode->getpdf();
			}
			if($this->etiqgondola->getshow_fornecedor() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$fornecedor = objectbytable("fornecedor", $prodfornec->getcodfornec(), $this->con);
					$fornecedor = $fornecedor->getnome();
				}else{
					$fornecedor = "";
				}
				$pdf->SetFontSize($this->etiqgondola->getaltu_fornecedor() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_fornecedor() / $fator, $y + $this->etiqgondola->getposy_fornecedor() / $fator, $fornecedor, $this->orientacao($this->etiqgondola->getorie_fornecedor()));
			}
			if($this->etiqgondola->getshow_reffornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$reffornec = $prodfornec->getreffornec();
				}else{
					$reffornec = "";
				}
				$pdf->SetFontSize($this->etiqgondola->getaltu_reffornec() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_reffornec() / $fator, $y + $this->etiqgondola->getposy_reffornec() / $fator, $reffornec, $this->orientacao($this->etiqgondola->getorie_reffornec()));
			}
			if($this->etiqgondola->getshow_codfornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$codfornec = $prodfornec->getcodfornec();
				}else{
					$codfornec = "";
				}
				$pdf->SetFontSize($this->etiqgondola->getaltu_codfornec() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_codfornec() / $fator, $y + $this->etiqgondola->getposy_codfornec() / $fator, $codfornec, $this->orientacao($this->etiqgondola->getorie_codfornec()));
			}
			if($this->etiqgondola->getshow_diasvalidade() == "S"){
				$produto = objectbytable("produto", $produtoestab->getcodproduto(), $this->con);
				$diasvalidade = $produto->getdiasvalidade();
				$pdf->SetFontSize($this->etiqgondola->getaltu_diasvalidade() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_diasvalidade() / $fator, $y + $this->etiqgondola->getposy_diasvalidade() / $fator, date('d/m/Y', strtotime("+".$diasvalidade." days")), $this->orientacao($this->etiqgondola->getorie_diasvalidade()));
			}
			if($this->etiqgondola->getshow_texto1() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_texto1() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_texto1() / $fator, $y + $this->etiqgondola->getposy_texto1() / $fator, $this->etiqgondola->gettext_texto1(), $this->orientacao($this->etiqgondola->getorie_texto1()));
			}
			if($this->etiqgondola->getshow_texto2() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_texto2() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_texto2() / $fator, $y + $this->etiqgondola->getposy_texto2() / $fator, $this->etiqgondola->gettext_texto2(), $this->orientacao($this->etiqgondola->getorie_texto2()));
			}
			if($this->etiqgondola->getshow_texto3() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_texto3() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_texto3() / $fator, $y + $this->etiqgondola->getposy_texto3() / $fator, $this->etiqgondola->gettext_texto3(), $this->orientacao($this->etiqgondola->getorie_texto3()));
			}
			if($this->etiqgondola->getshow_texto4() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_texto4() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_texto4() / $fator, $y + $this->etiqgondola->getposy_texto4() / $fator, $this->etiqgondola->gettext_texto4(), $this->orientacao($this->etiqgondola->getorie_texto4()));
			}
			if($this->etiqgondola->getshow_texto5() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_texto5() / $fator);
				$pdf->RotatedText($x + $this->etiqgondola->getposx_texto5() / $fator, $y + $this->etiqgondola->getposy_texto5() / $fator, $this->etiqgondola->gettext_texto5(), $this->orientacao($this->etiqgondola->getorie_texto5()));
			}
			if($this->etiqgondola->getshow_infofornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$fornecedor = objectbytable("fornecedor", $prodfornec->getcodfornec(), $this->con);
				}
				if(is_object($fornecedor)){
					$pais = objectbytable("pais",$fornecedor->getcodpais(),$this->con);

					$text_infofornec = $this->etiqgondola->gettext_infofornec();
					$search = array("[nomefantasia]","[razaosocial]","[cnpj]","[pais]");
					$replace = array($fornecedor->getnome(),$fornecedor->getrazaosocial(), $fornecedor->getcpfcnpj(),$pais->getnome());

					$text_infofornec = str_replace($search,$replace,$text_infofornec);

					$tam_fonte = $this->etiqgondola->getfont_infofornec();
					$pdf->SetFontSize($this->etiqgondola->getaltu_infofornec() / $fator);

					$posy = $y + $this->etiqgondola->getposy_infofornec() / $fator;

					$arr_texto = (explode("\n", $text_infofornec));
					foreach($arr_texto as $texto){
						$pdf->Text($x + $this->etiqgondola->getposx_infofornec() / $fator, $posy, $texto);
						$posy += 2;
					}

//						$pdf->Text($x + $this->etiqgondola->getposx_infofornec() / $fator, $y + $this->etiqgondola->getposy_infofornec() / $fator, $text_infofornec);
				}
			}
		}
		$pdf->Output("Etiquetas.pdf", "I");
		return true;
	}

	private function imprimir_termica($arr_produto, $returnText){
		switch($this->etiqgondola->getcodimpressora()){
			case 1: $prt_name = "argox";
				break;
			case 2: $prt_name = "zebra";
				break;
			case 4: $prt_name = "zebras4m";
				break;
			case 5: $prt_name = "datamax";
				break;
		}

		$printer = new Printer($this->con, $this->estabelecimento->getcodestabelec());
		$pdf = new FPDF();
		$i = 0;
		foreach($arr_produto as $produto){
			$produtoestab = objectbytable("produtoestab", array($this->estabelecimento->getcodestabelec(), $produto->getcodproduto()), $this->con);

			if($i == 0){
				$etiqueta = new Etiqueta($prt_name);
				$etiqueta->setheight($this->etiqgondola->getaltura());
				$etiqueta->setwidth($this->etiqgondola->getlargura());
				$etiqueta->setdensity($this->etiqgondola->gettemperatura());
				$etiqueta->setbackfeed($this->etiqgondola->getrecuo());
				if($this->etiqgondola->getcodimpressora() == 5){
					$etiqueta->setmargin_left($this->etiqgondola->getbordavertical());
				}
			}

			$posX = $i * $this->etiqgondola->getlargura();

			if($this->etiqgondola->getshow_estabelecimento() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_estabelecimento(), $this->etiqgondola->getfont_estabelecimento(), $this->etiqgondola->getlarg_estabelecimento(), $this->etiqgondola->getaltu_estabelecimento(), $this->etiqgondola->getposx_estabelecimento() + $posX, $this->etiqgondola->getposy_estabelecimento(), $this->estabelecimento->getnome());
			}
			if($this->etiqgondola->getshow_infonutricional() == "S"){
				if($this->etiqgondola->gettipo_infonutricional() == "0"){
					$this->imprimir_termica_infonutricional_texto($etiqueta, $this->etiqgondola, $produto);
				}else{
					$this->imprimir_termica_infonutricional_tabela($etiqueta, $this->etiqgondola, $produto);
				}
			}
			if($this->etiqgondola->getshow_codproduto() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_codproduto(), $this->etiqgondola->getfont_codproduto(), $this->etiqgondola->getlarg_codproduto(), $this->etiqgondola->getaltu_codproduto(), $this->etiqgondola->getposx_codproduto() + $posX, $this->etiqgondola->getposy_codproduto(), $produto->getcodproduto());
			}

			if($this->etiqgondola->getshow_mensagemvenda() == "S"){
				$pdf->SetFontSize($this->etiqgondola->getaltu_mensagemvenda() / $fator);

				if(strlen($this->mensagemvenda) > 0){
					$mensagemvenda = $this->mensagemvenda;
				}else{
					$mensagemvenda = $produto->gettextovenda();
				}
				$arr_mensagemvenda = explode("\n", $mensagemvenda);

				$posY = $this->etiqgondola->getposy_mensagemvenda();
				foreach($arr_mensagemvenda AS $mensagemvenda){
					$etiqueta->addtext($this->etiqgondola->getorie_mensagemvenda(), $this->etiqgondola->getfont_mensagemvenda(), $this->etiqgondola->getlarg_mensagemvenda(), $this->etiqgondola->getaltu_mensagemvenda(), $this->etiqgondola->getposx_mensagemvenda() + $posX, $posY, $mensagemvenda);
					$posY += 10;
				}
			}

			if($this->etiqgondola->getshow_codeantexto() == "S"){
				if(sizeof($this->arr_produtoean) > 0){
					$codean = $this->arr_produtoean[$produtoestab->getcodproduto()];
					if(is_object($codean)){
						$codean = $codean->getcodean();
					}
				}else{
					$produtoean = objectbytable("produtoean", NULL, $this->con);
					$produtoean->setcodproduto($produtoestab->getcodproduto());
					$arr_produtoean = object_array($produtoean);
					if(sizeof($arr_produtoean) > 0){
						$codean = $arr_produtoean[0]->getcodean();
					}else{
						$codean = "";
					}
				}
				$etiqueta->addtext($this->etiqgondola->getorie_codeantexto(), $this->etiqgondola->getfont_codeantexto(), $this->etiqgondola->getlarg_codeantexto(), $this->etiqgondola->getaltu_codeantexto(), $this->etiqgondola->getposx_codeantexto() + $posX, $this->etiqgondola->getposy_codeantexto(), $codean);
			}

			if($this->etiqgondola->getshow_descricao() == "S"){
				unset($simprod);
				if(strlen($produto->getcodsimilar())){
					$simprod = objectbytable("simprod", $produto->getcodsimilar(), $this->con);
				}
				if(is_object($simprod) && $simprod->getimpdescricao() == "S"){
					$descricao = $simprod->getdescricao();
				}else{
					if($this->etiqgondola->gettipodescricao() == "N"){
						$descricao = $produto->getdescricaofiscal();
					}else{
						$descricao = $produto->getdescricao();
					}
				}
				$etiqueta->addtext($this->etiqgondola->getorie_descricao(), $this->etiqgondola->getfont_descricao(), $this->etiqgondola->getlarg_descricao(), $this->etiqgondola->getaltu_descricao(), $this->etiqgondola->getposx_descricao() + $posX, $this->etiqgondola->getposy_descricao(), $descricao);
			}

			if($this->etiqgondola->getshow_preco() == "S"){
				$preco = $this->getpreco($produtoestab);
				$etiqueta->addtext($this->etiqgondola->getorie_preco(), $this->etiqgondola->getfont_preco(), $this->etiqgondola->getlarg_preco(), $this->etiqgondola->getaltu_preco(), $this->etiqgondola->getposx_preco() + $posX, $this->etiqgondola->getposy_preco(), $preco);
			}
			if($this->etiqgondola->getshow_precoatc() == "S"){
				$preco = $produtoestab->getprecoatc(true);
				$etiqueta->addtext($this->etiqgondola->getorie_precoatc(), $this->etiqgondola->getfont_precoatc(), $this->etiqgondola->getlarg_precoatc(), $this->etiqgondola->getaltu_precoatc(), $this->etiqgondola->getposx_precoatc() + $posX, $this->etiqgondola->getposy_precoatc(), $preco);
			}

			if($this->etiqgondola->getshow_precoof() == "S"){
				$precoof = $this->getpreco($produtoestab, TRUE);
				$etiqueta->addtext($this->etiqgondola->getorie_precoof(), $this->etiqgondola->getfont_precoof(), $this->etiqgondola->getlarg_precoof(), $this->etiqgondola->getaltu_precoof(), $this->etiqgondola->getposx_precoof() + $posX, $this->etiqgondola->getposy_precoof(), $precoof);
			}

			if($this->etiqgondola->getshow_qtdatacado() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_qtdatacado(), $this->etiqgondola->getfont_qtdatacado(), $this->etiqgondola->getlarg_qtdatacado(), $this->etiqgondola->getaltu_qtdatacado(), $this->etiqgondola->getposx_qtdatacado() + $posX, $this->etiqgondola->getposy_qtdatacado(), (int) $produtoestab->getqtdatacado());
			}

			if($this->etiqgondola->getshow_dtpreco() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_dtpreco(), $this->etiqgondola->getfont_dtpreco(), $this->etiqgondola->getlarg_dtpreco(), $this->etiqgondola->getaltu_dtpreco(), $this->etiqgondola->getposx_dtpreco() + $posX, $this->etiqgondola->getposy_dtpreco(), $produtoestab->getdtpreco(TRUE));
			}

			if($this->etiqgondola->getshow_dtatual() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_dtatual(), $this->etiqgondola->getfont_dtatual(), $this->etiqgondola->getlarg_dtatual(), $this->etiqgondola->getaltu_dtatual(), $this->etiqgondola->getposx_dtatual() + $posX, $this->etiqgondola->getposy_dtatual(), date("d/m/Y"));
			}

			if($this->etiqgondola->getshow_unidade() == "S" || $this->etiqgondola->getshow_qtdeunidade() == "S"){
				$embalagem = objectbytable("embalagem", $produto->getcodembalvda(), $this->con);
				if($this->etiqgondola->getshow_unidade() == "S"){
					$unidade = objectbytable("unidade", $embalagem->getcodunidade(), $this->con);
					$etiqueta->addtext($this->etiqgondola->getorie_unidade(), $this->etiqgondola->getfont_unidade(), $this->etiqgondola->getlarg_unidade(), $this->etiqgondola->getaltu_unidade(), $this->etiqgondola->getposx_unidade() + $posX, $this->etiqgondola->getposy_unidade(), $unidade->getsigla());
				}
				if($this->etiqgondola->getshow_qtdeunidade() == "S"){
					$qtdeunidade = $embalagem->getquantidade();
					if($qtdeunidade == floor($qtdeunidade)){
						$casas_decimais = 0;
					}else{
						$casas_decimais = 2;
					}
					$qtdeunidade = number_format($qtdeunidade, $casas_decimais, ",", "");
					$etiqueta->addtext($this->etiqgondola->getorie_qtdeunidade(), $this->etiqgondola->getfont_qtdeunidade(), $this->etiqgondola->getlarg_qtdeunidade(), $this->etiqgondola->getaltu_qtdeunidade(), $this->etiqgondola->getposx_qtdeunidade() + $posX, $this->etiqgondola->getposy_qtdeunidade(), $qtdeunidade);
				}
			}

			if($this->etiqgondola->getshow_moeda() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_moeda(), $this->etiqgondola->getfont_moeda(), $this->etiqgondola->getlarg_moeda(), $this->etiqgondola->getaltu_moeda(), $this->etiqgondola->getposx_moeda() + $posX, $this->etiqgondola->getposy_moeda(), "R$");
			}

			if($this->etiqgondola->getshow_codean() == "S"){
				if(sizeof($this->arr_produtoean) > 0){
					$codean = $this->arr_produtoean[$produtoestab->getcodproduto()];
					if(is_object($codean)){
						$codean = $codean->getcodean();
					}
				}else{
					$produtoean = objectbytable("produtoean", NULL, $this->con);
					$produtoean->setcodproduto($produtoestab->getcodproduto());
					$arr_produtoean = object_array($produtoean);
					if(sizeof($arr_produtoean) > 0){
						$codean = $arr_produtoean[0]->getcodean();
					}else{
						$codean = "";
					}
				}
				$etiqueta->addbarcode($this->etiqgondola->getorie_codean(), $this->etiqgondola->getlarg_codean(), $this->etiqgondola->getaltu_codean(), $this->etiqgondola->getposx_codean() + $posX, $this->etiqgondola->getposy_codean(), $codean);
			}

			if($this->etiqgondola->getshow_fornecedor() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$fornecedor = objectbytable("fornecedor", $prodfornec->getcodfornec(), $this->con);
					$fornecedor = $fornecedor->getnome();
				}else{
					$fornecedor = "";
				}
				$etiqueta->addtext($this->etiqgondola->getorie_fornecedor(), $this->etiqgondola->getfont_fornecedor(), $this->etiqgondola->getlarg_fornecedor(), $this->etiqgondola->getaltu_fornecedor(), $this->etiqgondola->getposx_fornecedor() + $posX, $this->etiqgondola->getposy_fornecedor(), $fornecedor);
			}

			if($this->etiqgondola->getshow_reffornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$reffornec = $prodfornec->getreffornec();
				}else{
					$reffornec = "";
				}
				$etiqueta->addtext($this->etiqgondola->getorie_reffornec(), $this->etiqgondola->getfont_reffornec(), $this->etiqgondola->getlarg_reffornec(), $this->etiqgondola->getaltu_reffornec(), $this->etiqgondola->getposx_reffornec() + $posX, $this->etiqgondola->getposy_reffornec(), $reffornec);
			}

			if($this->etiqgondola->getshow_codfornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$codfornec = $prodfornec->getcodfornec();
				}else{
					$codfornec = "";
				}
				$etiqueta->addtext($this->etiqgondola->getorie_codfornec(), $this->etiqgondola->getfont_codfornec(), $this->etiqgondola->getlarg_codfornec(), $this->etiqgondola->getaltu_codfornec(), $this->etiqgondola->getposx_codfornec() + $posX, $this->etiqgondola->getposy_codfornec(), $codfornec);
			}

			if($this->etiqgondola->getshow_diasvalidade() == "S"){
				$produto = objectbytable("produto", $produtoestab->getcodproduto(), $this->con);
				$diasvalidade = $produto->getdiasvalidade();
				$etiqueta->addtext($this->etiqgondola->getorie_diasvalidade(), $this->etiqgondola->getfont_diasvalidade(), $this->etiqgondola->getlarg_diasvalidade(), $this->etiqgondola->getaltu_diasvalidade(), $this->etiqgondola->getposx_diasvalidade() + $posX, $this->etiqgondola->getposy_diasvalidade(), date('d/m/Y', strtotime("+".$diasvalidade." days")));
			}

			if($this->etiqgondola->getshow_texto1() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_texto1(), $this->etiqgondola->getfont_texto1(), $this->etiqgondola->getlarg_texto1(), $this->etiqgondola->getaltu_texto1(), $this->etiqgondola->getposx_texto1() + $posX, $this->etiqgondola->getposy_texto1(), $this->etiqgondola->gettext_texto1());
			}

			if($this->etiqgondola->getshow_texto2() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_texto2(), $this->etiqgondola->getfont_texto2(), $this->etiqgondola->getlarg_texto2(), $this->etiqgondola->getaltu_texto2(), $this->etiqgondola->getposx_texto2() + $posX, $this->etiqgondola->getposy_texto2(), $this->etiqgondola->gettext_texto2());
			}

			if($this->etiqgondola->getshow_texto3() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_texto3(), $this->etiqgondola->getfont_texto3(), $this->etiqgondola->getlarg_texto3(), $this->etiqgondola->getaltu_texto3(), $this->etiqgondola->getposx_texto3() + $posX, $this->etiqgondola->getposy_texto3(), $this->etiqgondola->gettext_texto3());
			}

			if($this->etiqgondola->getshow_texto4() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_texto4(), $this->etiqgondola->getfont_texto4(), $this->etiqgondola->getlarg_texto4(), $this->etiqgondola->getaltu_texto4(), $this->etiqgondola->getposx_texto4() + $posX, $this->etiqgondola->getposy_texto4(), $this->etiqgondola->gettext_texto4());
			}

			if($this->etiqgondola->getshow_texto5() == "S"){
				$etiqueta->addtext($this->etiqgondola->getorie_texto5(), $this->etiqgondola->getfont_texto5(), $this->etiqgondola->getlarg_texto5(), $this->etiqgondola->getaltu_texto5(), $this->etiqgondola->getposx_texto5() + $posX, $this->etiqgondola->getposy_texto5(), $this->etiqgondola->gettext_texto5());
			}

			if($this->etiqgondola->getshow_infofornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produtoestab->getcodproduto());
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$fornecedor = objectbytable("fornecedor", $prodfornec->getcodfornec(), $this->con);
				}
				if(is_object($fornecedor)){
					$pais = objectbytable("pais",$fornecedor->getcodpais(),$this->con);

					$text_infofornec = $this->etiqgondola->gettext_infofornec();
					$search = array("[nomefantasia]","[razaosocial]","[cnpj]","[pais]");
					$replace = array($fornecedor->getnome(),$fornecedor->getrazaosocial(), $fornecedor->getcnpj(),$pais->getnome());

					$text_infofornec = str_replace($search, $replace, $text_infofornec);

					$etiqueta->addtext($this->etiqgondola->getorie_texto5(), $this->etiqgondola->getfont_texto5(), $this->etiqgondola->getlarg_texto5(), $this->etiqgondola->getaltu_texto5(), $this->etiqgondola->getposx_texto5() + $posX, $this->etiqgondola->getposy_texto5(), $infofornec);
				}
			}

			$i++;
			if($i == $this->etiqgondola->getnumcarreiras()){
				$printer->addtext($etiqueta->getall(), TRUE);
				$i = 0;
			}
		}
		if($this->etiqgondola->getnumcarreiras() > 1 && $i >= 1){
			$printer->addtext($etiqueta->getall(), TRUE);
		}

		if($returnText){
			return $printer->gettext();
		}else{
			$printer->print_on($etiqueta, $this->etiqgondola->getlocalimpressora(), $this->etiqgondola->gettiposervidor());
			return TRUE;
		}
	}

	private function imprimir_termica_infonutricional_tabela(Etiqueta $etiqueta, EtiqGondola $etiqgondola, Produto $produto){
		function quadrado($etiqueta, $x, $y, $w, $h){
			$x2 = $x + $w;
			$y2 = $y + $h;
			$etiqueta->addbox($x, $y, $x2, $y2);
		}

		function texto($etiqueta, $x, $y, $font, $text){
			$etiqueta->addtext(0, $font, 1, 1, $x, $y, $text);
		}

		$nutricional = objectbytable("nutricional", $produto->getcodnutricional(), $this->con);

		$font = $etiqgondola->getfont_infonutricional(); // Tamanho da fonte
		$height = $etiqgondola->getaltura_tabelainfonutricional(); // Altura da celula
		$width = $etiqgondola->getlargura_tabelainfonutricional(); // Largura da celula
		$y = $etiqgondola->getposy_infonutricional(); // Posicao vertical
		$margin = round($height / 3);// Margem do texto dentro da celula

		$width1 = round($width * 0.6); // Largura da celula descritiva
		$width2 = round($width * 0.2); // Largura da celula do primeiro percentual
		$width3 = round($width * 0.2); // Largura da celula do segundo percentual

		$x = $etiqgondola->getposx_infonutricional(); // Posicao horizontal
		$x2 = $x + $width1; // Posicao horizontal apartir da segunda celula
		$x3 = $x + $width1 + $width2; // Posicao horizontal apartir da terceira celula

		quadrado($etiqueta, $x, $y, $width, $height);
		texto($etiqueta, ($x + $margin), ($y + $margin), $font, str_repeat(" ", round(($width - 300) / 25))."INFORMACOES NUTRICIONAIS");
		$y += $height;

		$qtdeporcao = round($nutricional->getqtdeporcao());
		switch($nutricional->getunidporcao()){
			case "0": $unidporcao = "gr"; break;
			case "1": $unidporcao = "ml"; break;
			case "2": $unidporcao = "un"; break;
		}

		quadrado($etiqueta, $x, $y, ($width1 + $width2), $height);
		texto($etiqueta, ($x + $margin), ($y + $margin), $font, "Quantidade por porcao: {$qtdeporcao}{$unidporcao}");
		quadrado($etiqueta, $x3, $y, $width3, $height);
		texto($etiqueta, ($x3 + $margin), ($y + $margin), $font, "%VD");
		$y += $height;

		$colunas = array("cal", "carbo", "prot", "gord", "gordsat", "gordtrans", "colest", "fibra", "calcio", "ferro", "sodio");
		$colunas_d = array("Calorias", "Carboidratos", "Proteinas", "Gorduras Totais", "Gorduras Saturadas", "Gorduras Trans", "Colesterol", "Fibra Alimentar", "Calcio", "Ferro", "Sodio");
		foreach($colunas as $i => $coluna){
			$valor1 = call_user_func(array($nutricional, "getqtde{$coluna}"));
			$valor2 = call_user_func(array($nutricional, "getperc{$coluna}"));
			if(!($valor1 > 0 || $valor2 > 0)){
			//	continue;
			}

			if(in_array($coluna, array("cal"))){
				$medida = "KCal";
			}elseif(in_array($coluna, array("colest", "calcio", "ferro", "sodio"))){
				$medida = "mg";
			}else{
				$medida = "g";
			}

			$descricao = $colunas_d[$i];
			$valor1 = rtrim(rtrim(call_user_func(array($nutricional, "getqtde{$coluna}"), true), "0"), ",").$medida;
			$valor2 = rtrim(rtrim(call_user_func(array($nutricional, "getperc{$coluna}"), true), "0"), ",")."%";

			quadrado($etiqueta, $x, $y, $width1, $height);
			texto($etiqueta, ($x + $margin), ($y + $margin), $font, $descricao);
			quadrado($etiqueta, $x2, $y, $width2, $height);
			texto($etiqueta, ($x2 + $margin), ($y + $margin), $font, $valor1);
			quadrado($etiqueta, $x3, $y, $width3, $height);
			texto($etiqueta, ($x3 + $margin), ($y + $margin), $font, $valor2);
			$y += $height;
		}

		$extras = explode("\n", $etiqgondola->getcampos_infonutricional());
		$extra_box_height = round($height * count($extras) - ($margin * count($extras) / 2));
		quadrado($etiqueta, $x, $y, $width, $extra_box_height);
		foreach($extras as $extra){
			texto($etiqueta, ($x + $margin), ($y + $margin), $font, $extra);
			$y += $height - $margin;
		}
	}

	private function imprimir_termica_infonutricional_texto(Etiqueta $etiqueta, EtiqGondola $etiqgondola, Produto $produto){
		$texto_etiqueta = $this->etiqgondola->getcampos_infonutricional();
		if(strstr($texto_etiqueta, "[receita]") && strlen($produto->getcodreceita()) > 0){
			if(strstr($this->etiqgondola->getcampos_infonutricional(), "[infonutricional]")){
				$texto_etiqueta_aux = explode("[infonutricional]", $this->etiqgondola->getcampos_infonutricional());
				$texto_etiqueta = $texto_etiqueta_aux[0];
			}
			$receita = objectbytable("receita", NULL, $this->con);
			$receita->setcodreceita($produto->getcodreceita());
			$receita = object_array($receita);
			$texto_receita = "";
			if(strlen($receita[0]->getcomponentes()) > 0){
				$texto_receita .= "Componentes: ".trim($receita[0]->getcomponentes());
			}
			if(strlen($receita[0]->getmodopreparo()) > 0){
				$texto_receita .= " Modo de Preparo: ".trim($receita[0]->getmodopreparo());
			}
			$texto_etiqueta = str_replace("[receita]", $texto_receita, $texto_etiqueta);
			$aux = explode("\n", $texto_etiqueta);
			$posy = $this->etiqgondola->getposy_infonutricional();
			$tam_fonte = $this->etiqgondola->getfont_infonutricional() * 10;
			foreach($aux as $a){
				$posy += $tam_fonte;
				$etiqueta->addtext($this->etiqgondola->getorie_infonutricional(), $this->etiqgondola->getfont_infonutricional(), $this->etiqgondola->getlarg_infonutricional(), $this->etiqgondola->getaltu_infonutricional(), $this->etiqgondola->getposx_infonutricional(), $posy, $a);
			}
		}

		$texto_etiqueta = $this->etiqgondola->getcampos_infonutricional();
		if(strstr($texto_etiqueta, "[infonutricional]") && strlen($produto->getcodnutricional()) > 0){
			if(strstr($this->etiqgondola->getcampos_infonutricional(), "[infonutricional]")){
				$texto_etiqueta_aux = explode("[infonutricional]", $this->etiqgondola->getcampos_infonutricional());
				$texto_etiqueta = $texto_etiqueta_aux[1];
			}
			$nutricional = objectbytable("nutricional", NULL, $this->con);
			$nutricional->setcodnutricional($produto->getcodnutricional());
			$nutricional = object_array($nutricional);
			if(!strstr($texto_etiqueta, "[receita]")){
				$aux = explode("\n", $texto_etiqueta);
				$posy = $this->etiqgondola->getposy_infonutricional();
				$tam_fonte = $this->etiqgondola->getfont_infonutricional() * 10;
			}
			$campos = array
				(
				"[qtdcalorico]", "[perccalorico]", "[qtdcolesterol]", "[perccolesterol]", "[qtdcarboidrato]", "[perccarboidrato]",
				"[qtdfibraalimentar]", "[percfibraalimentar]", "[qtdproteina]", "[percproteina]", "[qtdcalcio]", "[perccalcio]",
				"[qtdtotalgordura]", "[perctotalgordura]", "[qtdferro]", "[percferro]", "[qtdgordsaturada]", "[percgordsaturada]",
				"[qtdsodio]", "[percsodio]", "[qtdgorduratrans]", "[percgorduratrans]"
			);
			$valores = array
				(
				number_format($nutricional[0]->getqtdecal(), 2, '.', ''), $nutricional[0]->getperccal(), number_format($nutricional[0]->getqtdecolest(), 2, '.', ''), $nutricional[0]->getperccolest(), number_format($nutricional[0]->getqtdecarbo(), 2, '.', ''), $nutricional[0]->getperccarbo(),
				number_format($nutricional[0]->getqtdefibra(), 2, '.', ''), $nutricional[0]->getpercfibra(), number_format($nutricional[0]->getqtdeprot(), 2, '.', ''), $nutricional[0]->getpercprot(), number_format($nutricional[0]->getqtdecalcio(), 2, '.', ''), $nutricional[0]->getperccalcio(),
				number_format($nutricional[0]->getqtdegord(), 2, '.', ''), $nutricional[0]->getpercgord(), number_format($nutricional[0]->getqtdeferro(), 2, '.', ''), $nutricional[0]->getpercferro(), number_format($nutricional[0]->getqtdegordsat(), 2, '.', ''), $nutricional[0]->getpercgordsat(),
				number_format($nutricional[0]->getqtdesodio(), 2, '.', ''), $nutricional[0]->getpercsodio(), number_format($nutricional[0]->getqtdegordtrans(), 2, '.', ''), $nutricional[0]->getpercgordtrans()
			);
			$texto_etiqueta = str_replace($campos, $valores, $texto_etiqueta);

			if($prt_name != "argox"){
				$etiqueta->addtext($this->etiqgondola->getorie_infonutricional(), $this->etiqgondola->getfont_infonutricional(), 20, 20, $this->etiqgondola->getposx_tabelainfonutricional() - 12, $this->etiqgondola->getposy_tabelainfonutricional() - $tam_fonte, removespecial(trim($nutricional[0]->getdescricaoporcao())));
			}
		}

		$coluna1 = $this->etiqgondola->getposx_tabelainfonutricional();
		$coluna2 = $this->etiqgondola->getposx_tabelainfonutricional() + $this->etiqgondola->getcoluna1_tabelainfonutricional();
		$coluna3 = $this->etiqgondola->getposx_tabelainfonutricional() + $this->etiqgondola->getcoluna1_tabelainfonutricional() + $this->etiqgondola->getcoluna2_tabelainfonutricional();

		if(strlen($posy) == 0){
			$posy = $this->etiqgondola->getposy_infonutricional();
		}

		$posy = $this->etiqgondola->getposy_tabelainfonutricional();
		$posy -= $this->etiqgondola->getfont_infonutricional() * 20 - 5;

		$texto_tabela = explode("\n", $texto_etiqueta);
		foreach($texto_tabela as $tabela){
			$aux = explode("|", trim($tabela));
			$cnt = 0;
			$posy += $tam_fonte;
			foreach($aux as $a){
				if($cnt == 0){
					$etiqueta->addtext($this->etiqgondola->getorie_infonutricional(), $this->etiqgondola->getfont_infonutricional(), $this->etiqgondola->getlarg_infonutricional(), $this->etiqgondola->getaltu_infonutricional(), $coluna1 - 12, $posy, removespecial(trim($a)));
				}elseif($cnt == 1){
					$etiqueta->addtext($this->etiqgondola->getorie_infonutricional(), $this->etiqgondola->getfont_infonutricional(), $this->etiqgondola->getlarg_infonutricional(), $this->etiqgondola->getaltu_infonutricional(), $coluna2 - 10, $posy, removespecial(trim($a)));
				}elseif($cnt == 2){
					$etiqueta->addtext($this->etiqgondola->getorie_infonutricional(), $this->etiqgondola->getfont_infonutricional(), $this->etiqgondola->getlarg_infonutricional(), $this->etiqgondola->getaltu_infonutricional(), $coluna3 - 10, $posy, removespecial(trim($a)));
				}
				$cnt++;
			}
		}
		$tam_fonte = $this->etiqgondola->getaltura_tabelainfonutricional() - $this->etiqgondola->getfont_infonutricional() * 10;
		$etiqueta->addbox($this->etiqgondola->getposx_tabelainfonutricional() - 20, $this->etiqgondola->getposy_tabelainfonutricional(), $this->etiqgondola->getcoluna1_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional(), 1, 1);
		$etiqueta->addbox($coluna2 - 20, $this->etiqgondola->getposy_tabelainfonutricional(), $this->etiqgondola->getcoluna2_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional(), 1, 1);
		$etiqueta->addbox($coluna3 - 20, $this->etiqgondola->getposy_tabelainfonutricional(), $this->etiqgondola->getcoluna3_tabelainfonutricional(), $this->etiqgondola->getaltura_tabelainfonutricional(), 1, 1);
	}

	function sethabilitaprecopdv($value){
		$this->habilitaprecopdv = $value;
	}

	function getpreco($produtoestab, $forcar_oferta = FALSE){
		if($this->etiqgondola->gettipopreco() == "A"){
			if($forcar_oferta || ($this->etiqgondola->getprecooferta() == "S" && $produtoestab->getprecoatcof() > 0)){
				$preco = $produtoestab->getprecoatcof(TRUE);
			}else{
				$preco = $produtoestab->getprecoatc(TRUE);
			}
		}else{
			if($forcar_oferta || ($this->etiqgondola->getprecooferta() == "S" && $produtoestab->getprecovrjof() > 0 && $this->habilitaprecopdv != "S")){
				$preco = $produtoestab->getprecovrjof(TRUE);
			}else{
				if($this->habilitaprecopdv == "S"){
					$preco = $produtoestab->getprecopdv(TRUE);
				}else{
					$preco = $produtoestab->getprecovrj(TRUE);
				}
			}
		}
		return $preco;
	}

	function orientacao($orient){
		switch($orient){
			case 0:
				return 0;
			case 1:
				return 90;
			case 2:
				return 180;
			case 3:
				return 270;
		}
		return 0;
	}
}
