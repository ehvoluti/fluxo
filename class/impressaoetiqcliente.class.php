<?php

require_once("websac/require_file.php");
require_file("lib/fpdf-1.8.0/fpdf.php");

class ImpressaoEtiqCliente{

	private $con;
	private $estabelecimento;
	private $etiqcliente;
	private $arr_cliente;

	function __construct(Connection $con){
		$this->con = $con;
		$this->arr_cliente = array();
	}

	function addcliente(Cliente $cliente, $qtdeetiqueta){
		if(strlen($qtdeetiqueta) === 0){
			$qtdeetiqueta = 1;
		}
		$this->arr_cliente[] = array($cliente, $qtdeetiqueta);
	}

	function setetiqcliente(EtiqCliente $etiqcliente){
		$this->etiqcliente = $etiqcliente;
		$this->estabelecimento = objectbytable("estabelecimento", $this->etiqcliente->getcodestabelec(), $this->con);
	}

	function imprimir($returnText = false){
		// Separa os clientes que serao impressos de acordo com a quantidade de etiquetas
		$arr_cliente = array();
		foreach($this->arr_cliente as $cliente){
			for($i = 0; $i < $cliente[1]; $i++){
				$arr_cliente[] = $cliente[0];
			}
		}

		// Impressora a jato de tinta
		if($this->etiqcliente->getcodimpressora() == 3){
			$fator = 10;

			$pdf = new FPDF();
			$pdf->SetFont("Arial");
			$x = $this->etiqcliente->getbordahorizontal() / $fator;
			$y = $this->etiqcliente->getbordavertical() / $fator;
			foreach($arr_cliente as $i => $cliente){
				$pdf->AddPage("P", array($this->etiqcliente->getlargurafolha() / $fator, $this->etiqcliente->getalturafolha() / $fator));
//				if($i > 0){
//					if(($i / $this->etiqcliente->getnumcarreiras()) == floor($i / $this->etiqcliente->getnumcarreiras())){
//						$y += $this->etiqcliente->getaltura() / $fator;
//						$x = $this->etiqcliente->getbordahorizontal() / $fator;
//					}else{
//						$x += $this->etiqcliente->getlargura() / $fator;
//					}
//				}

//				if($y + $this->etiqcliente->getaltura() / $fator > $this->etiqcliente->getalturafolha() / $fator){
//					$pdf->AddPage("P", array($this->etiqcliente->getlargurafolha() / $fator, $this->etiqcliente->getalturafolha() / $fator));
//					$y = $this->etiqcliente->getbordavertical() / $fator;
//				}

				if($this->etiqcliente->getshow_codcliente() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_codcliente() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_codcliente() / $fator, $y + $this->etiqcliente->getposy_codcliente() / $fator, $cliente->getcodcliente(), $this->orientacao($this->etiqcliente->getorie_codcliente()));
				}

				if($this->etiqcliente->getshow_codconvenio() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_codconvenio() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_codconvenio() / $fator, $y + $this->etiqcliente->getposy_codconvenio() / $fator, $cliente->getcodempresa(), $this->orientacao($this->etiqcliente->getorie_codconvenio()));
				}

				if($this->etiqcliente->getshow_nome() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_nome() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_nome() / $fator, $y + $this->etiqcliente->getposy_nome() / $fator, $cliente->getnome(), $this->orientacao($this->etiqcliente->getorie_nome()));
				}

				if($this->etiqcliente->getshow_cpfcnpj() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_cpfcnpj() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_cpfcnpj() / $fator, $y + $this->etiqcliente->getposy_cpfcnpj() / $fator, $cliente->getcpfcnpj(), $this->orientacao($this->etiqcliente->getorie_cpfcnpj()));
				}

				if($this->etiqcliente->getshow_codbarracpfcnpj() == "S"){
					$pdfbarcode = new PDFBarCode($pdf);
					$pdfbarcode->UPC_A($x + $this->etiqcliente->getposx_codbarracpfcnpj() / $fator, $y + $this->etiqcliente->getposy_codbarracpfcnpj() / $fator, ($cliente->getcpfcnpj()), $this->etiqcliente->getaltu_codbarracpfcnpj() / $fator, ($this->etiqcliente->getlarg_codbarracpfcnpj() / $fator) / 100);
					$pdf = $pdfbarcode->getpdf();
				}

				if($this->etiqcliente->getshow_codbarramatricula() == "S"){
					$pdfbarcode = new PDFBarCode($pdf);
					$pdfbarcode->UPC_A($x + $this->etiqcliente->getposx_codbarramatricula() / $fator, $y + $this->etiqcliente->getposy_codbarramatricula() / $fator, ($cliente->getnummatricula()), $this->etiqcliente->getaltu_codbarramatricula() / $fator, ($this->etiqcliente->getlarg_codbarramatricula() / $fator) / 100);
					$pdf = $pdfbarcode->getpdf();
				}

				if($this->etiqcliente->getshow_uf() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_uf() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_uf() / $fator, $y + $this->etiqcliente->getposy_uf() / $fator, $cliente->getufent(), $this->orientacao($this->etiqcliente->getorie_uf()));
				}

				if($this->etiqcliente->getshow_cidade() == "S"){
					$cidade = objectbytable("cidade", $cliente->getcodcidadeent(), $this->con);
					$pdf->SetFontSize($this->etiqcliente->getaltu_cidade() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_cidade() / $fator, $y + $this->etiqcliente->getposy_cidade() / $fator, $cidade->getnome(), $this->orientacao($this->etiqcliente->getorie_cidade()));
				}
				if($this->etiqcliente->getshow_cepent() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_cepent() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_cepent() / $fator, $y + $this->etiqcliente->getposy_cepent() / $fator, $cliente->getcepent(), $this->orientacao($this->etiqcliente->getorie_cepent()));
				}
				if($this->etiqcliente->getshow_enderecoent() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_enderecoent() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_enderecoent() / $fator, $y + $this->etiqcliente->getposy_enderecoent() / $fator, $cliente->getenderent().", ".$cliente->getnumeroent(), $this->orientacao($this->etiqcliente->getorie_enderecoent()));
				}
				if($this->etiqcliente->getshow_bairroent() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_bairroent() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_bairroent() / $fator, $y + $this->etiqcliente->getposy_bairroent() / $fator, $cliente->getbairroent(), $this->orientacao($this->etiqcliente->getorie_bairroent()));
				}
				if($this->etiqcliente->getshow_texto1() == "S"){
					$pdf->SetFontSize($this->etiqcliente->getaltu_texto1() / $fator);
					$pdf->RotatedText($x + $this->etiqcliente->getposx_texto1() / $fator, $y + $this->etiqcliente->getposy_texto1() / $fator, $this->etiqcliente->gettext_texto1(), $this->orientacao($this->etiqcliente->getorie_texto1()));
				}
			}
			$pdf->Output("Etiquetas.pdf", "I");
			return TRUE;
			// Impressora normal (termica) de etiquetas
		}else{
			switch($this->etiqcliente->getcodimpressora()){
				case 1: $prt_name = "argox";
					break;
				case 2: $prt_name = "zebra";
					break;
				case 4: $prt_name = "zebras4m";
					break;
			}

			$printer = new Printer($this->con, $this->estabelecimento->getcodestabelec());
			$pdf = new FPDF();
			$i = 0;
			foreach($arr_cliente as $cliente){
				if($i == 0){
					$etiqueta = new Etiqueta($prt_name);
					$etiqueta->setheight($this->etiqcliente->getaltura());
					$etiqueta->setwidth($this->etiqcliente->getlargura());
					$etiqueta->setdensity($this->etiqcliente->gettemperatura());
					$etiqueta->setbackfeed($this->etiqcliente->getrecuo());
				}

				$posX = $i * $this->etiqcliente->getlargura();

				if($this->etiqcliente->getshow_codcliente() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_codcliente(), $this->etiqcliente->getfont_codcliente(), $this->etiqcliente->getlarg_codcliente(), $this->etiqcliente->getaltu_codcliente(), $this->etiqcliente->getposx_codcliente() + $posX, $this->etiqcliente->getposy_codcliente(), $cliente->getcodcliente());
				}

				if($this->etiqcliente->getshow_codconvenio() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_codconvenio(), $this->etiqcliente->getfont_codconvenio(), $this->etiqcliente->getlarg_codconvenio(), $this->etiqcliente->getaltu_codconvenio(), $this->etiqcliente->getposx_codconvenio() + $posX, $this->etiqcliente->getposy_codconvenio(), (strlen($cliente->getcodempresa()) > 0 ? $cliente->getcodempresa() : ""));
				}

				if($this->etiqcliente->getshow_nome() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_nome(), $this->etiqcliente->getfont_nome(), $this->etiqcliente->getlarg_nome(), $this->etiqcliente->getaltu_nome(), $this->etiqcliente->getposx_nome() + $posX, $this->etiqcliente->getposy_nome(), $cliente->getnome());
				}

				if($this->etiqcliente->getshow_cpfcnpj() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_cpfcnpj(), $this->etiqcliente->getfont_cpfcnpj(), $this->etiqcliente->getlarg_cpfcnpj(), $this->etiqcliente->getaltu_cpfcnpj(), $this->etiqcliente->getposx_cpfcnpj() + $posX, $this->etiqcliente->getposy_cpfcnpj(), $cliente->getcpfcnpj());
				}

				if(strlen($cliente->getcodempresa()) > 0){
					$cliente_empresa = objectbytable("cliente", $cliente->getcodempresa(), $con);
					$empresa = $cliente_empresa->getnome();
				}else{
					$empresa = "";
				}

				if($this->etiqcliente->getshow_convenio() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_convenio(), $this->etiqcliente->getfont_convenio(), $this->etiqcliente->getlarg_convenio(), $this->etiqcliente->getaltu_convenio(), $this->etiqcliente->getposx_convenio() + $posX, $this->etiqcliente->getposy_convenio(), $cliente->getcodempresa());
				}

				if($this->etiqcliente->getshow_codbarracpfcnpj() == "S" && strlen($cliente->getcpfcnpj()) > 0){
					$etiqueta->addbarcode($this->etiqcliente->getorie_codbarracpfcnpj(), $this->etiqcliente->getlarg_codbarracpfcnpj(), $this->etiqcliente->getaltu_codbarracpfcnpj(), $this->etiqcliente->getposx_codbarracpfcnpj() + $posX, $this->etiqcliente->getposy_codbarracpfcnpj(), ($cliente->getcpfcnpj()), ($prt_name == "zebras4m" ? "S" : "cpfcnpj"), 1);
				}

				if($this->etiqcliente->getshow_codbarramatricula() == "S" && strlen($cliente->getnummatricula()) > 0){
					$etiqueta->addbarcode($this->etiqcliente->getorie_codbarramatricula(), $this->etiqcliente->getlarg_codbarramatricula(), $this->etiqcliente->getaltu_codbarramatricula(), $this->etiqcliente->getposx_codbarramatricula() + $posX, $this->etiqcliente->getposy_codbarramatricula(), (($cliente->getnummatricula())), FALSE, 1);
				}

				if($this->etiqcliente->getshow_uf() == "S" && strlen($cliente->getufent()) > 0){
					$etiqueta->addtext($this->etiqcliente->getorie_uf(), $this->etiqcliente->getfont_uf(), $this->etiqcliente->getlarg_uf(), $this->etiqcliente->getaltu_uf(), $this->etiqcliente->getposx_uf() + $posX, $this->etiqcliente->getposy_uf(), (($cliente->getufent())));
				}

				if($this->etiqcliente->getshow_cidade() == "S" && strlen($cliente->getcodcidadeent()) > 0){
					$cidade = objectbytable("cidade", $cliente->getcodcidadeent(), $this->con);
					$etiqueta->addtext($this->etiqcliente->getorie_cidade(), $this->etiqcliente->getfont_cidade(), $this->etiqcliente->getlarg_cidade(), $this->etiqcliente->getaltu_cidade(), $this->etiqcliente->getposx_cidade() + $posX, $this->etiqcliente->getposy_cidade(), (($cidade->getnome())));
				}

				if($this->etiqcliente->getshow_cepent() == "S" && strlen($cliente->getcepent()) > 0){
					$etiqueta->addtext($this->etiqcliente->getorie_cepent(), $this->etiqcliente->getfont_cepent(), $this->etiqcliente->getlarg_cepent(), $this->etiqcliente->getaltu_cepent(), $this->etiqcliente->getposx_cepent() + $posX, $this->etiqcliente->getposy_cepent(), (($cliente->getcepent())));
				}

				if($this->etiqcliente->getshow_enderecoent() == "S" && strlen($cliente->getenderent()) > 0){
					$etiqueta->addtext($this->etiqcliente->getorie_enderecoent(), $this->etiqcliente->getfont_enderecoent(), $this->etiqcliente->getlarg_enderecoent(), $this->etiqcliente->getaltu_enderecoent(), $this->etiqcliente->getposx_enderecoent() + $posX, $this->etiqcliente->getposy_enderecoent(), (($cliente->getenderent()." , ".$cliente->getnumeroent())));
				}

				if($this->etiqcliente->getshow_bairroent() == "S" && strlen($cliente->getbairroent()) > 0){
					$etiqueta->addtext($this->etiqcliente->getorie_bairroent(), $this->etiqcliente->getfont_bairroent(), $this->etiqcliente->getlarg_bairroent(), $this->etiqcliente->getaltu_bairroent(), $this->etiqcliente->getposx_bairroent() + $posX, $this->etiqcliente->getposy_bairroent(), (($cliente->getbairroent())));
				}

				if($this->etiqcliente->getshow_texto1() == "S"){
					$etiqueta->addtext($this->etiqcliente->getorie_texto1(), $this->etiqcliente->getfont_texto1(), $this->etiqcliente->getlarg_texto1(), $this->etiqcliente->getaltu_texto1(), $this->etiqcliente->getposx_texto1() + $posX, $this->etiqcliente->getposy_texto1(), $this->etiqcliente->gettext_texto1());
				}

				$i++;
				if($i == $this->etiqcliente->getnumcarreiras()){
					$printer->addtext($etiqueta->getall(), TRUE);
					$i = 0;
				}
			}
			if($i > 0){
				$printer->addtext($etiqueta->getall(), TRUE);
			}

			if($returnText){
				return $printer->gettext();
			}else{
				$printer->print_on($etiqueta, $this->etiqcliente->getlocalimpressora(), $this->etiqcliente->gettiposervidor());
				return TRUE;
			}
		}
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