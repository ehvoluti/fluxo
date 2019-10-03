<?php

require_once("../class/interfacebancaria.class.php");

class Sicredi extends InterfaceBancaria{

	public function gerar_remessa_pagamento(){
		if(!parent::gerar_remessa()){
			return FALSE;
		}

		$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
		if(!$this->banco->save()){
			return FALSE;
		}

		// Carrega os cadastros auxiliares
		$arr_lancamento = $this->carregarauxiliares();

		// Pega a cidade do estabelecimento
		$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

		// Array que contem todas a linha do arquivo
		$arr_linha = array();

		$arr_linha[] = $this->gerar_header_arquivo_240($seqremessa,"082", "P");

		$codigolote = 0; // Codigo do lote (inicia do zero, pois cada lote incrementa um antes da geracao)
		foreach($arr_lancamento as $tipo_pagamento => $arr_lancamento_2){
			foreach($arr_lancamento_2 as $forma_pagamento => $arr_lancamento_3){
				$codigolote++;
				$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar
				$arr_linha[] = $this->gerar_header_lote_240($cidade, $codigolote, $tipo_pagamento, $forma_pagamento,"042", $lancamento->getpagrec());

				$total_valorliquido = 0;

				if(in_array($lancamento->getcodtipoaceite(), array(1, 2))){

					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){ //Boletos
						$parceiro = objectbytable("v_parceiro", array($lancamento->gettipoparceiro(), $lancamento->getcodparceiro()), $this->con);
						$arr_linha[] = $this->gerar_segmento_J_240($lancamento, $codigolote, $i);

						$arr_linha[] = $this->gerar_segmento_J52_240($parceiro, $lancamento, $codigolote, $i);
						$total_valorliquido += $lancamento->getvalorliquido();
					}
					$arr_linha[] = $this->gerar_trailer_lote_240($codigolote, (sizeof($arr_lancamento_3) * 2), $total_valorliquido);

				}elseif($lancamento->getcodtipoaceite() == 3 || strlen($lancamento->getcodbarras()) > 0){ //Concessionarias
					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){
						$arr_linha[] = $this->gerar_segmento_O_240($lancamento, $codigolote, $i);
						$total_valorliquido += $lancamento->getvalorliquido();
					}
					$arr_linha[] = $this->gerar_trailer_lote_240($codigolote, sizeof($arr_lancamento_3), $total_valorliquido);
				}else{

					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){ // Tributos sem codigos de barras

						$arr_linha[] = $this->gerar_segmento_N_240($cidade, $lancamento, $codigolote, $i);
						$total_valorliquido += $lancamento->getvalorliquido();
					}
					$arr_linha[] = $this->gerar_trailer_lote_240($this->banco->getcodoficial(), $codigolote, $totalregistroslote, $total_valorliquido);
				}
			}
		}
		$arr_linha[] = $this->gerar_trailer_arquivo_240($codigolote, sizeof($arr_linha));

		$procfina = $this->atualiza_lancamentos_gerados();

		// Gera relatorio de remessa na pasta temp
		$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
		$paramarqremessa = array("numremessa" => $controleprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));

		$arr_m = ["1","2","3","4","5","6","7","8","9","O","N","D"];
		$nomearquivo = substr(trim($this->banco->getcodigoempresa()), 0, 3).date("d").$arr_m[date("n") - 1];
		$dtultremessa = $this->banco->getdtultremessa();
		$now = date("Y-m-d");
		if($dtultremessa == $now){
			$seqremessadia = $this->banco->getseqremessadia();
			$this->banco->setseqremessadia($this->banco->getseqremessadia() + 1);
		}else{
			$this->banco->setdtultremessa(date("d-m-Y"));
			$this->banco->setseqremessadia(0);
		}
		$this->banco->save();
		$nomearquivo .= str_pad($seqremessadia, 2, "0", STR_PAD_LEFT).".REM";
		parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);
		echo write_file($this->estabelecimento->getdirremessabanco().$nomearquivo, $arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);

		return TRUE;
	}

	function processar_retorno_pagamento($arr_arquivo){
		if(!is_array($arr_arquivo)){
			$arr_arquivo = array($arr_arquivo);
		}
		$arr_retorno = array();
		foreach($arr_arquivo as $i => $arquivo){
			setprogress((($i + 1) / sizeof($arr_arquivo) * 100), "Lendo arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
			$arr_linha = read_file($arquivo);

			// Verifica se e um arquivo valido
			$linha = $arr_linha[0];
			if(strlen($linha) != 240 || substr($linha, 0, 3) != "341"){
				continue;
			}

			foreach($arr_linha as $linha){
				// Verifica se e linha detalhe
				if(substr($linha, 7, 1) == "3"){

					$dados_retorno = new stdClass();
					$dados_retorno = $this->getdadosliquidacao($linha);

					if(isset($dados_retorno->codlancto)){
						// Captura as ocorrencias do lancamento
						$arr_ocorrencia = array();
						for($j = 0; $j < 10; ($j = $j + 2)){
							$ocorrencia = trim(substr($dados_retorno->ocorrencias, $j, 2));
							if(strlen($ocorrencia) > 0){
								$arr_ocorrencia[] = array($ocorrencia, $this->ocorrenciapagamento($ocorrencia));
							}
						}
						$liquidado = (substr($dados_retorno->ocorrencias, 0, 2) == "00");
						$arr_retorno[] = array(
							"codlancto" => $dados_retorno->codlancto,
							"valorparcela" => $dados_retorno->valorparcela,
							"valorpago" => ($liquidado ? $dados_retorno->valorparcela : 0),
							"liquidado" => $liquidado,
							"dtliquid" => $dados_retorno->dtliquid,
							"codocorrencia" => $arr_ocorrencia[0][0],
							"ocorrencia" => $arr_ocorrencia[0][1]
						);
					}
				}
			}
		}
		if(sizeof($arr_retorno) > 0){
			$arr_retorno = aasort($arr_retorno, "codocorrencia");
			return parent::processar_retorno($arr_retorno);
		}else{
			return TRUE;
		}
	}
}

?>
