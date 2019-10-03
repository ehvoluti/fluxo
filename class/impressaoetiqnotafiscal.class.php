<?php

require_once("websac/require_file.php");
require_file("lib/fpdf-1.8.0/fpdf.php");

class ImpressaoEtiqNotaFiscal{

	private $con;
	private $estabelecimento;
	private $etiqnotafiscal;
	private $arr_idnotafiscal;

	function __construct(Connection $con){
		$this->con = $con;
		$this->arr_notafiscal = array();
	}

	function setetiqnotafiscal(EtiqNotaFiscal $etiqnotafiscal){
		$this->etiqnotafiscal = $etiqnotafiscal;
		$this->estabelecimento = objectbytable("estabelecimento", $this->etiqnotafiscal->getcodestabelec(), $this->con);
	}

	function set_arr_idnotafiscal($arr_idnotafiscal){
		$this->arr_idnotafiscal = $arr_idnotafiscal;
	}

	function imprimir($returnText = false){
		// Separa os notafiscals que serao impressos de acordo com a quantidade de etiquetas
		$arr_campos = array(
			"nomeestabelec", "origemendereco", "origembairro", "origemcep", "origemcidadeuf",
			"parceironome", "destinoendereco", "destinobairro", "destinocep", "destinocidadeuf",
			"numnotafis", "numpedido", "transportadoranome", "transpvolume", "transppeso",
			"texto1", "texto2", "texto3", "texto4", "texto5"
		);

		// Impressora a jato de tinta
		if($this->etiqnotafiscal->getcodimpressora() == 3){
			$fator = 10;

			$orientacao = ($this->etiqnotafiscal->getlargurafolha() > $this->etiqnotafiscal->getalturafolha() ? "L" : "P");

			$pdf = new FPDF();
			foreach($this->arr_idnotafiscal as $i => $idnotafiscal){
				$nota = $this->buscanota_paraimpressao($idnotafiscal);

				for($volume = 1; $volume <= $nota["transpvolume"]; $volume++){
					$pdf->SetFont("Arial");
					$pdf->AddPage($orientacao, array($this->etiqnotafiscal->getlargurafolha() / $fator, $this->etiqnotafiscal->getalturafolha() / $fator));
					$x = $this->etiqnotafiscal->getbordahorizontal() / $fator;
					$y = $this->etiqnotafiscal->getbordavertical() / $fator;

					if($i > 0){
						if(($i / $this->etiqnotafiscal->getnumcarreiras()) == floor($i / $this->etiqnotafiscal->getnumcarreiras())){
							$y += $this->etiqnotafiscal->getaltura() / $fator;
							$x = $this->etiqnotafiscal->getbordahorizontal() / $fator;
						}else{
							$x += $this->etiqnotafiscal->getlargura() / $fator;
						}
					}
					if($y + $this->etiqnotafiscal->getaltura() / $fator > $this->etiqnotafiscal->getalturafolha() / $fator){
						$pdf->AddPage("P", array($this->etiqnotafiscal->getlargurafolha() / $fator, $this->etiqnotafiscal->getalturafolha() / $fator));
						$y = $this->etiqnotafiscal->getbordavertical() / $fator;
					}

					foreach($arr_campos as $campo){
						if(call_user_func(array($this->etiqnotafiscal, "getshow_".$campo)) == "S"){
							$_altu = call_user_func(array($this->etiqnotafiscal, "getaltu_".$campo));
							$_posx = call_user_func(array($this->etiqnotafiscal, "getposx_".$campo));
							$_posy = call_user_func(array($this->etiqnotafiscal, "getposy_".$campo));

							$pdf->SetFontSize($_altu / $fator);
							if($campo == "transpvolume"){
								$total_volume = round($nota[$campo]);
								$pdf->Text($x + $_posx / $fator, $y + $_posy / $fator, "$volume/$total_volume");
							}elseif(!in_array($campo, array("texto1", "texto2", "texto3", "texto4", "texto5"))){
								$pdf->Text($x + $_posx / $fator, $y + $_posy / $fator, $nota[$campo]);
							}else{
								$pdf->Text($x + $_posx / $fator, $y + $_posy / $fator, call_user_func(array($this->etiqnotafiscal, "gettext_".$campo)));
							}
						}
					}
				}
			}
			$pdf->Output("Etiquetas.pdf", "I");
			return TRUE;
			// Impressora normal (termica) de etiquetas
		}else{
			switch($this->etiqnotafiscal->getcodimpressora()){
				case 1: $prt_name = "argox";
					break;
				case 2: $prt_name = "zebra";
					break;
				case 4: $prt_name = "zebras4m";
					break;
			}

			$printer = new Printer($this->con, $this->estabelecimento->getcodestabelec());
			
			foreach($this->arr_idnotafiscal as $i => $idnotafiscal){
				$nota = $this->buscanota_paraimpressao($idnotafiscal);

				for($volume = 1; $volume <= $nota["transpvolume"]; $volume++){
					if($i == 0){
						$etiqueta = new Etiqueta($prt_name);
						$etiqueta->setheight($this->etiqnotafiscal->getaltura());
						$etiqueta->setwidth($this->etiqnotafiscal->getlargura());
						$etiqueta->setdensity($this->etiqnotafiscal->gettemperatura());
						$etiqueta->setbackfeed($this->etiqnotafiscal->getrecuo());
					}

					$posX = $i * $this->etiqnotafiscal->getlargura();

					foreach($arr_campos as $campo){
						if(call_user_func(array($this->etiqnotafiscal, "getshow_".$campo)) == "S"){
							$_orie = call_user_func(array($this->etiqnotafiscal, "getorie_".$campo));
							$_font = call_user_func(array($this->etiqnotafiscal, "getfont_".$campo));
							$_larg = call_user_func(array($this->etiqnotafiscal, "getlarg_".$campo));
							$_altu = call_user_func(array($this->etiqnotafiscal, "getaltu_".$campo));
							$_posx = call_user_func(array($this->etiqnotafiscal, "getposx_".$campo));
							$_posy = call_user_func(array($this->etiqnotafiscal, "getposy_".$campo));

							if($campo == "transpvolume"){
								$total_volume = round($nota[$campo]);
								$etiqueta->addtext($_orie, $_font, $_larg, $_altu, $_posx + $posX, $_posy, "$volume/$total_volume");
							}elseif(!in_array($campo, array("texto1", "texto2", "texto3", "texto4", "texto5"))){
								$etiqueta->addtext($_orie, $_font, $_larg, $_altu, $_posx + $posX, $_posy, $nota[$campo]);
							}else{
								$etiqueta->addtext($_orie, $_font, $_larg, $_altu, $_posx + $posX, $_posy, call_user_func(array($this->etiqnotafiscal, "gettext_".$campo)));
							}
						}
					}

					$i++;
					if($i == $this->etiqnotafiscal->getnumcarreiras()){
						$printer->addtext($etiqueta->getall(), TRUE);
						$i = 0;
					}
				}
			}
			if($i > 0){
				$printer->addtext($etiqueta->getall(), TRUE);
			}

			if($returnText){
				return $printer->gettext();
			}else{
				$printer->print_on($etiqueta, $this->etiqnotafiscal->getlocalimpressora(), $this->etiqnotafiscal->gettiposervidor());
				return TRUE;
			}
		}
	}

	private function buscanota_paraimpressao($idnotafiscal){
		$query = "select estab.nome AS nomeestabelec, (estab.endereco::text || ' ' || estab.numero::text || ' ' || coalesce(estab.complemento::text,'')) as origemendereco, estab.bairro as origembairro, estab.cep as origemcep, ";
		$query .= "estabcidade.nome || '-' || estab.uf  AS origemcidadeuf, v_parceiro.nome as parceironome, v_parceiro.endereco || ' ' || v_parceiro.numero || ' ' || coalesce(v_parceiro.complemento::text,'') AS destinoendereco, ";
		$query .= "v_parceiro.bairro AS destinobairro, v_parceiro.cep as destinocep, parceirocidade.nome || '-' || v_parceiro.uf AS destinocidadeuf, ";
		$query .= "notafiscal.numnotafis, notafiscal.numpedido, notafiscal.totalquantidade AS transpvolume, notafiscal.pesobruto AS transppeso, ";
		$query .= "transportadora.nome as transportadoranome ";
		$query .= "from notafiscal ";
		$query .= "INNER join estabelecimento estab on (notafiscal.codestabelec = estab.codestabelec) ";
		$query .= "LEFT JOIN cidade as estabcidade ON (estab.codcidade = estabcidade.codcidade) ";
		$query .= "LEFT join v_parceiro on (notafiscal.tipoparceiro = v_parceiro.tipoparceiro aND notafiscal.codparceiro = v_parceiro.codparceiro) ";
		$query .= "LEFT JOIN cidade as parceirocidade ON (v_parceiro.codcidade = parceirocidade.codcidade) ";
		$query .= "LEFT JOIN transportadora ON (notafiscal.codtransp = transportadora.codtransp) ";
		$query .= "WHERE idnotafiscal = $idnotafiscal ";

		$res = $this->con->query($query);
		return $res->fetch(0);
	}

}