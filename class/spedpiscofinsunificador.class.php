<?php
/**
 * Description of unificadorspedcontribuicoes
 *
 * @author jesus
 */
require_once("websac/require_file.php");
require_file("def/require_php.php");

define("PIPE", "|");

class UnificadorSpedContribuicoes {
	private $lista_arquivos;
	private $local_arquivos;
	private $cpfcnpj;
	private $REG_0000 = array();
	private $REG_0001 = array();
	private $REG_0005 = array();
	private $REG_0100 = array();
	private $REG_0110 = array();
	private $REG_0111 = array();
	private $REG_0140 = array();
	private $REG_0150 = array();
	private $REG_0190 = array();
	private $REG_0200 = array();
	private $REG_0220 = array();
	private $REG_0300 = array();
	private $REG_0305 = array();
	private $REG_0400 = array();
	private $REG_0450 = array();
	private $REG_0460 = array();
	private $REG_0500 = array();
	private $REG_0600 = array();
	private $REG_A001 = array();
	private $REG_A010 = array();
	private $REG_A100 = array();
	private $REG_A170 = array();
	private $REG_C001 = array();
	private $REG_C010 = array();
	private $REG_C100 = array();
	private $REG_C140 = array();
	private $REG_C141 = array();
	private $REG_C160 = array();
	private $REG_C170 = array();
	private $REG_C180 = array();
	private $REG_C190 = array();
	private $REG_C195 = array();
	private $REG_C300 = array();
	private $REG_C320 = array();
	private $REG_C321 = array();
	private $REG_C350 = array();
	private $REG_C370 = array();
	private $REG_C380 = array();
	private $REG_C381 = array();
	private $REG_C385 = array();
	private $REG_C390 = array();
	private $REG_C395 = array();
	private $REG_C400 = array();
	private $REG_C405 = array();
	private $REG_C410 = array();
	private $REG_C420 = array();
	private $REG_C460 = array();
	private $REG_C470 = array();
	private $REG_C481 = array();
	private $REG_C485 = array();
	private $REG_C489 = array();
	private $REG_C490 = array();
	private $REG_C500 = array();
	private $REG_C501 = array();
	private $REG_C505 = array();
	private $REG_C800 = array();
	private $REG_C850 = array();
	private $REG_C860 = array();
	private $REG_C870 = array();
	private $REG_C890 = array();
	private $REG_D001 = array();
	private $REG_D010 = array();
	private $REG_D100 = array();
	private $REG_D101 = array();
	private $REG_D105 = array();
	private $REG_D190 = array();
	private $REG_D500 = array();
	private $REG_D590 = array();
	private $REG_E001 = array();
	private $REG_E100 = array();
	private $REG_E110 = array();
	private $REG_E111 = array();
	private $REG_E116 = array();
	private $REG_E200 = array();
	private $REG_E210 = array();
	private $REG_E250 = array();
	private $REG_E300 = array();
	private $REG_E310 = array();
	private $REG_E316 = array();
	private $REG_E500 = array();
	private $REG_E510 = array();
	private $REG_E520 = array();
	private $REG_F001 = array();
	private $REG_F010 = array();
	private $REG_F100 = array();
	private $REG_F120 = array();
	private $REG_F130 = array();
	private $REG_F150 = array();
	private $REG_F200 = array();
	private $REG_F205 = array();
	private $REG_F210 = array();
	private $REG_F600 = array();
	private $REG_F700 = array();
	private $REG_F800 = array();
	private $REG_G001 = array();
	private $REG_G110 = array();
	private $REG_G125 = array();
	private $REG_H001 = array();
	private $REG_H005 = array();
	private $REG_H010 = array();
	private $REG_H020 = array();
	private $REG_K001 = array();
	private $REG_I001 = array();
	private $REG_I010 = array();
	private $REG_M001 = array();
	private $REG_M100 = array();
	private $REG_M105 = array();
	private $REG_M110 = array();
	private $REG_M200 = array();
	private $REG_M205 = array();
	private $REG_M210 = array();
	private $REG_M400 = array();
	private $REG_M410 = array();
	private $REG_M500 = array();
	private $REG_M505 = array();
	private $REG_M510 = array();
	private $REG_M600 = array();
	private $REG_M610 = array();
	private $REG_M800 = array();
	private $REG_M810 = array();
	private $REG_P001 = array();
	private $REG_P010 = array();
	private $REG_1001 = array();
	private $REG_1010 = array();
	private $REG_1100 = array();
	private $REG_1500 = array();

	private $REG_9900 = array();
	private $arr_unificado = array();

	public function setcpfcnpj($value){
		$this->cpfcnpj = $value;
	}

	public function getcpfcnpj(){
		return $this->cpfcnpj;
	}

	public function setlistaarquivos($value){
		$this->lista_arquivos = $value;
	}

	public function setlocalarquivos($value){
		$this->local_arquivos = $value;
	}

	private function getlocalarquivos(){
		return $this->local_arquivos;
	}

	public function lerarquivospiscofins(){
		$_SESSION["ERROR"] = "";
		$_SESSION["SUCCESS"] = "";
		foreach($this->lista_arquivos["arr_arquivos"] as $i => $arquivo){
			$arquivounificar = $this->local_arquivos.$arquivo;
			if(strlen($arquivo) == 0 || $arquivo == "item"){
				continue;
			}
			if(file_exists($arquivounificar)){
				$arquivo_unificar = file($arquivounificar);
				$arr_campos = explode("|", substr($arquivo_unificar[0],1,  strlen($arquivo_unificar[0]) - 1));
				$cnpj_arquivo = substr($arr_campos[8], 0, 8);
				$cnpj_estabelecimento  = substr($this->getcpfcnpj(), 0, 8);
				if($cnpj_arquivo != $cnpj_estabelecimento){
					$_SESSION["ERROR"] .= "CNPJ encontrado no arquivo {$arquivo} não pertence ao estabelecimento selecionado. ";
					continue;
				}
				foreach($arquivo_unificar as $j => $linhatxt){
					$arr_campos = explode("|", substr($linhatxt,1,  strlen($linhatxt) - 1));
					$registro = $arr_campos[0];
					switch($registro){
						case "0000":
							if($i === 0){
								$this->REG_0000["0000"] = $linhatxt;
							}
							break;
						case "0001":
							if($i === 0){
								$this->REG_0001["0001"] = $linhatxt;
							}
							break;
						case "0100"	:
							if($i === 0){
								$this->REG_0100["0100"] = $linhatxt;
							}
							break;
						case "0110"	:
							if($i === 0){
								$this->REG_0110["0110"] = $linhatxt;
							}
							break;
						case "0111"	:
							if($i === 0){
								$this->REG_0111["0111"] = $linhatxt;
							}
							break;
						case "0140":
							$cnpj_estabelecimento = $arr_campos[3];
							$this->REG_0140[$cnpj_estabelecimento] = $linhatxt;
							break;
						case "0150":
							$this->REG_0150[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "0190":
							$this->REG_0190[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "0200":
							$this->REG_0200[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "0400":
							$this->REG_0400[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "0450":
							$this->REG_0450[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "0500":
							$this->REG_0500[] = $linhatxt;
							break;
						case "A001":
							if($i === 0){
								$this->REG_A001["A001"] = $linhatxt;
							}
							break;
						case "A010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_A010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_A001["A001"] = "|A001|0|\n";
							break;
						case "A100":
							$ind_oper = $arr_campos[1];
							$cod_part = $arr_campos[3];
							$num_doc  = $arr_campos[7];
							$serie    = $arr_campos[6];
							$this->REG_A100[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "A170":
							$this->REG_A170[$cnpj_estabelecimento.$ind_oper.$cod_part.$num_doc.$serie][] = $linhatxt;
							break;
						case "C001":
							$this->REG_C001["C001"] = $linhatxt;
							break;
						case "C010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_C010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_C001["C001"] = "|C001|0|\n";
							break;
						case "C100":
							$ind_oper = $arr_campos[1];
							$cod_part = $arr_campos[3];
							$num_doc  = $arr_campos[7];
							$serie    = $arr_campos[6];
							$dt_doc   = $arr_campos[9];
							$this->REG_C100[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C170":
							$this->REG_C170[$cnpj_estabelecimento.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc][] = $linhatxt;
							break;
						case "C180":
							$this->REG_C180[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C190":
							$this->REG_C190[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C380":
							$this->REG_C380[$cnpj_estabelecimento][] = $linhatxt;
							$dt_doc_ini  = $arr_campos[2];
							$dt_doc_fin  = $arr_campos[3];
							$num_doc_ini = $arr_campos[4];
							$num_doc_fin = $arr_campos[5];
							break;
						case "C381":
							$this->REG_C381[$cnpj_estabelecimento.$dt_doc_ini.$dt_doc_fin.$num_doc_ini.$num_doc_fin][] = $linhatxt;
							break;
						case "C385":
							$this->REG_C385[$cnpj_estabelecimento.$dt_doc_ini.$dt_doc_fin.$num_doc_ini.$num_doc_fin][] = $linhatxt;
							break;
						case "C395":
							$this->REG_C395[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C400":
							$ecf_fab = $arr_campos[3];
							$ecf_cxa = $arr_campos[4];
							$this->REG_C400[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C405":
							$dt_doc = $arr_campos[1];
							$crz    = $arr_campos[3];
							$this->REG_C405[$cnpj_estabelecimento.$ecf_cxa.$ecf_fab][] = $linhatxt;
							break;
						case "C481":
							$this->REG_C481[$cnpj_estabelecimento.$ecf_cxa.$ecf_fab.$dt_doc.$crz][] = $linhatxt;
							break;
						case "C485":
							$this->REG_C485[$cnpj_estabelecimento.$ecf_cxa.$ecf_fab.$dt_doc.$crz][] = $linhatxt;
							break;
						case "C489":
							$this->REG_C489[$cnpj_estabelecimento.$ecf_cxa.$ecf_fab][] = $linhatxt;
							break;
						case "C490":
							$this->REG_C490[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C500":
							$cod_part = $arr_campos[3];
							$num_doc  = $arr_campos[7];
							$serie    = $arr_campos[6];
							$dt_doc   = $arr_campos[9];
							$this->REG_C500[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C501":
							$this->REG_C501[$cnpj_estabelecimento.$cod_part.$num_doc.$serie.$dt_doc][] = $linhatxt;
							break;
						case "C505":
							$this->REG_C505[$cnpj_estabelecimento.$cod_part.$num_doc.$serie.$dt_doc][] = $linhatxt;
							break;
						case "C860":
							$nr_sat = $arr_campos[2];
							$dt_doc = $arr_campos[3];
							$this->REG_C860[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "C870":
							$this->REG_C870[$cnpj_estabelecimento.$nr_sat.$dt_doc][] = $linhatxt;
							break;
						case "D001":
							if($i === 0){
								$this->REG_D001["D001"] = $linhatxt;
							}
							break;
						case "D010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_D010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_D001["D001"] = "|D001|0|\n";
							break;
						case "D100":
							$ind_oper = $arr_campos[1];
							$cod_part = $arr_campos[3];
							$num_doc  = $arr_campos[8];
							$serie    = $arr_campos[6];
							$dt_doc   = $arr_campos[10];
							$this->REG_D100[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "D101":
							$this->REG_D101[$cnpj_estabelecimento.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc][] = $linhatxt;
							break;
						case "D105":
							$this->REG_D105[$cnpj_estabelecimento.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc][] = $linhatxt;
							break;
						case "D500":
							$this->REG_D500[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F001":
							if($i === 0){
								$this->REG_F001["F001"] = $linhatxt;
							}
							break;
						case "F010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_F010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_F001["F001"] = "|F001|0|\n";
							break;
						case "F100":
							$this->REG_F100[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F120":
							$this->REG_F120[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F130":
							$this->REG_F130[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F150":
							$this->REG_F150[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F200":
							$this->REG_F200[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F205":
							$this->REG_F205[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F210":
							$this->REG_F210[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F600":
							$this->REG_F600[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case"F700":
							$this->REG_F700[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "F800":
							$this->REG_F800[$cnpj_estabelecimento][] = $linhatxt;
							break;
						case "I001":
							if($i === 0){
								$this->REG_I001["I001"] = $linhatxt;
							}
							break;
						case "I010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_I010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_I001["I001"] = "|I001|0|\n";
							break;
						case "M001":
							if($i === 0){
								$this->REG_M001["M001"] = $linhatxt;
							}
							break;
						case "M100":
							$tipo_credito = $arr_campos[1];
							$aliq_pis = $arr_campos[4];
							$aliq_pis_quant = $arr_campos[6];
							$this->REG_M100[$tipo_credito.$aliq_pis.$aliq_pis_quant][] = $linhatxt;
							break;
						case "M105":
							$nat_bc_cred = $arr_campos[1];
							$cst_pis = $arr_campos[2];
							$this->REG_M105[$tipo_credito.$aliq_pis.$aliq_pis_quant][$nat_bc_cred.$cst_pis][] = $linhatxt;
							break;
						case "M110":
							$this->REG_M110[$tipo_credito.$aliq_pis.$aliq_pis_quant][] = $linhatxt;
							break;
						case "M200":
							$this->REG_M200[] = $linhatxt;
							break;
						case "M205":
							$num_campo = $arr_campos[1];
							$cod_rec = $arr_campos[2];
							$this->REG_M205[$num_campo.$cod_rec][] = $linhatxt;
							break;
						case "M210":
							$cod_cont = $arr_campos[1];
							$aliq_pis = $arr_campos[4];
							$aliq_pis_quant = $arr_campos[6];
							$this->REG_M210[$cod_cont.$aliq_pis.$aliq_pis_quant][] = $linhatxt;
							break;
						case "M400":
							$cst_pis = $arr_campos[1];
							$this->REG_M400[$cst_pis][] = $linhatxt;
							break;
						case "M410":
							$nat_rec = $arr_campos[1];
							$this->REG_M410[$cst_pis][$nat_rec][] = $linhatxt;
							break;
						case "M500":
							$tipo_credito = $arr_campos[1];
							$aliq_cofins = $arr_campos[4];
							$aliq_cofins_quant = $arr_campos[6];
							$this->REG_M500[$tipo_credito.$aliq_cofins.$aliq_cofins_quant][] = $linhatxt;
							break;
						case "M505":
							$nat_bc_cred = $arr_campos[1];
							$cst_cofins = $arr_campos[2];
							$this->REG_M505[$tipo_credito.$aliq_cofins.$aliq_cofins_quant][$nat_bc_cred.$cst_cofins][] = $linhatxt;
							break;
						case "M510":
							$this->REG_M510[$tipo_credito.$aliq_cofins.$aliq_cofins_quant][] = $linhatxt;
							break;
						case "M600":
							$this->REG_M600[] = $linhatxt;
							break;
						case "M605":
							$num_campo = $arr_campos[1];
							$cod_rec = $arr_campos[2];
							$this->REG_M605[$num_campo.$cod_rec][] = $linhatxt;
							break;
						case "M610":
							$cod_cont = $arr_campos[1];
							$aliq_pis = $arr_campos[4];
							$aliq_pis_quant = $arr_campos[6];
							$this->REG_M610[$cod_cont.$aliq_pis.$aliq_pis_quant][] = $linhatxt;
							break;
						case "M800":
							$cst_pis = $arr_campos[1];
							$this->REG_M800[$cst_pis][] = $linhatxt;
							break;
						case "M810":
							$nat_rec = $arr_campos[1];
							$this->REG_M810[$cst_pis][$nat_rec][] = $linhatxt;
							break;
						case "P001":
							if($i === 0){
								$this->REG_P001["P001"] = $linhatxt;
							}
							break;
						case "P010":
							$cnpj_estabelecimento = $arr_campos[1];
							$this->REG_P010[$cnpj_estabelecimento] = $linhatxt;
							$this->REG_P001["P001"] = "|P001|0|\n";
							break;
						case "1001":
							$this->REG_1001["1001"] = $linhatxt;
							break;
						case "1100":
							$periodo = $arr_campos[1];
							$this->REG_1100[$periodo] = $linhatxt;
							$this->REG_1001["1001"] = "|1001|0|\n";
							break;
						case "1500":
							$periodo = $arr_campos[1];
							$this->REG_1500[$periodo] = $linhatxt;
							break;
					}
				}
			}

			if(isset($arquivo_unificar)){
				unset($arquivo_unificar);
			}
		}
		return (count($this->REG_0000) > 0);
	}

	function unificardadospiscofins(){
		$this->recalcular_100_200();

		$this->arr_unificado[] = $this->REG_0000["0000"];
		$this->arr_unificado[] = $this->REG_0001["0001"];
		$this->arr_unificado[] = $this->REG_0100["0100"];
		$this->arr_unificado[] = $this->REG_0110["0110"];
		if(count($this->REG_0111) > 0){
			$this->arr_unificado[] = $this->REG_0111["0111"];
		}
		$this->REG_9900["0000"]++;
		$this->REG_9900["0001"]++;
		$this->REG_9900["0100"]++;
		$this->REG_9900["0110"]++;
		if(count($this->REG_0111) > 0){
			$this->REG_9900["0111"]++;
		}

		foreach($this->REG_0140 as $index_0140 => $REG_0140){
			$arr_0140 = explode("|", $REG_0140);
			$cnpj_estabelecimento = $arr_0140[3];
			$this->arr_unificado[] = $REG_0140;
			$this->REG_9900["0140"]++;
			foreach($this->REG_0150[$index_0140] as $REG_0150){
				$this->arr_unificado[] = $REG_0150;
				$this->REG_9900["0150"]++;
			}

			foreach($this->REG_0190[$index_0140] as $REG_0190){
				$this->arr_unificado[] = $REG_0190;
				$this->REG_9900["0190"]++;
			}

			foreach($this->REG_0200[$index_0140] as $REG_0200){
				$this->arr_unificado[] = $REG_0200;
				$this->REG_9900["0200"]++;
			}

			foreach($this->REG_0400[$index_0140] as $REG_0400){
				$this->arr_unificado[] = $REG_0400;
				$this->REG_9900["0400"]++;
			}

			foreach($this->REG_0450[$index_0140] as $REG_0450){
				$this->arr_unificado[] = $REG_0450;
				$this->REG_9900["0450"]++;
			}

		}

		if(count($this->REG_0500) > 0){
			foreach($this->REG_0500 as $index_REG_0500 => $REG_0500){
				$this->arr_unificado[] = $REG_0500;
				$this->REG_9900["0500"]++;
			}
		}

		$this->arr_unificado[] = PIPE."0990".PIPE.(count($this->arr_unificado) + 1).PIPE."\n";
		$this->REG_9900["0990"]++;

		if(count($this->REG_A001) > 0){
			$this->arr_unificado[] = $this->REG_A001["A001"];
			$total_registros = 1;
			$this->REG_9900["A001"]++;
			foreach($this->REG_A010 as $index_A010 => $REG_A010){
				$this->arr_unificado[] = $REG_A010;
				$total_registros++;
				$this->REG_9900["A010"]++;
				foreach($this->REG_A100[$index_A010] as $index_A100 => $REG_A100){
					$this->arr_unificado[] = $REG_A100;
					$total_registros++;
					$this->REG_9900["A100"]++;
					$arr_campos = explode("|", substr($REG_A100,1,  strlen($REG_A100) - 1));
					$ind_oper = $arr_campos[1];
					$cod_part = $arr_campos[3];
					$num_doc  = $arr_campos[7];
					$serie    = $arr_campos[6];
					foreach($this->REG_A170[$index_A010.$ind_oper.$cod_part.$num_doc.$serie] as $index_A170 => $REG_A170){
						$this->arr_unificado[] = $REG_A170;
						$total_registros++;
						$this->REG_9900["A170"]++;
					}
				}
			}
			$this->arr_unificado[] = PIPE."A990".PIPE.($total_registros + 1).PIPE."\n";
			$this->REG_9900["A990"]++;
		}

		if(count($this->REG_C001) > 0){
			$this->arr_unificado[] = $this->REG_C001["C001"];
			$total_registros = 1;
			$this->REG_9900["C001"]++;
			foreach($this->REG_C010 as $index_C010 => $REG_C010){
				$this->arr_unificado[] = $REG_C010;
				$total_registros++;
				$this->REG_9900["C010"]++;
				foreach($this->REG_C100[$index_C010] as $index_C100 => $REG_C100){
					$this->arr_unificado[] = $REG_C100;
					$total_registros++;
					$this->REG_9900["C100"]++;
					$arr_campos = explode("|", substr($REG_C100,1,  strlen($REG_C100) - 1));
					$ind_oper = $arr_campos[1];
					$cod_part = $arr_campos[3];
					$num_doc  = $arr_campos[7];
					$serie    = $arr_campos[6];
					$dt_doc   = $arr_campos[9];
					foreach($this->REG_C170[$index_C010.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc] as $index_C170 => $REG_C170){
						$this->arr_unificado[] = $REG_C170;
						$total_registros++;
						$this->REG_9900["C170"]++;
					}
				}

				foreach($this->REG_C180[$index_C010] as $index_C180 => $REG_C180){
					$this->arr_unificado[] = $REG_C180;
					$total_registros++;
					$this->REG_9900["C180"]++;
				}

				foreach($this->REG_C190[$index_C010] as $index_C190 => $REG_C190){
					$this->arr_unificado[] = $REG_C190;
					$total_registros++;
					$this->REG_9900["C190"]++;
				}

				foreach($this->REG_C380[$index_C010] as $index_C380 => $REG_C380){
					$this->arr_unificado[] = $REG_C380;
					$total_registros++;
					$this->REG_9900["C380"]++;
					$arr_campos = explode("|", substr($REG_C380,1,  strlen($REG_C380) - 1));
					$dt_doc_ini  = $arr_campos[2];
					$dt_doc_fin  = $arr_campos[3];
					$num_doc_ini = $arr_campos[4];
					$num_doc_fin = $arr_campos[5];
					foreach($this->REG_C381[$index_C010.$dt_doc_ini.$dt_doc_fin.$num_doc_ini.$num_doc_fin] as $index_C381 => $REG_C381){
						$this->arr_unificado[] = $REG_C381;
						$total_registros++;
						$this->REG_9900["C381"]++;
					}
					foreach($this->REG_C385[$index_C010.$dt_doc_ini.$dt_doc_fin.$num_doc_ini.$num_doc_fin] as $index_C385 => $REG_C385){
						$this->arr_unificado[] = $REG_C385;
						$total_registros++;
						$this->REG_9900["C385"]++;
					}
				}

				foreach($this->REG_C395[$index_C010] as $index_C395 => $REG_C395){
					$this->arr_unificado[] = $REG_C395;
					$total_registros++;
					$this->REG_9900["C395"]++;
				}

				foreach($this->REG_C400[$index_C010] as $index_C400 => $REG_C400){
					$this->arr_unificado[] = $REG_C400;
					$total_registros++;
					$this->REG_9900["C400"]++;
					$arr_campos = explode("|", substr($REG_C400,1,  strlen($REG_C400) - 1));
					$ecf_fab = $arr_campos[3];
					$ecf_cxa = $arr_campos[4];
					foreach($this->REG_C405[$index_C010.$ecf_cxa.$ecf_fab] as $index_C405 => $REG_C405){
						$this->arr_unificado[] = $REG_C405;
						$total_registros++;
						$this->REG_9900["C405"]++;
						$arr_campos = explode("|", substr($REG_C405,1,  strlen($REG_C405) - 1));
						$dt_doc = $arr_campos[1];
						$crz    = $arr_campos[3];
						foreach($this->REG_C481[$index_C405.$dt_doc.$crz] as $index_C481 => $REG_C481){
							$this->arr_unificado[] = $REG_C481;
							$total_registros++;
							$this->REG_9900["C481"]++;
						}
						foreach($this->REG_C485[$index_C405.$dt_doc.$crz] as $index_C485 => $REG_C485){
							$this->arr_unificado[] = $REG_C485;
							$total_registros++;
							$this->REG_9900["C485"]++;
						}
					}
					foreach($this->REG_C489[$index_C010.$ecf_cxa.$ecf_fab] as $index_C489 => $REG_C489){
						$this->arr_unificado[] = $REG_C489;
						$total_registros++;
						$this->REG_9900["C489"]++;
					}
				}

				foreach($this->REG_C500[$index_C010] as $index_C500 => $REG_C500){
					$this->arr_unificado[] = $REG_C500;
					$total_registros++;
					$this->REG_9900["C500"]++;
					$arr_campos = explode("|", substr($REG_C500,1,  strlen($REG_C500) - 1));
					$cod_part = $arr_campos[3];
					$num_doc  = $arr_campos[7];
					$serie    = $arr_campos[6];
					$dt_doc   = $arr_campos[9];
					foreach($this->REG_C501[$index_C010.$cod_part.$num_doc.$serie.$dt_doc] as $index_C501 => $REG_C501){
						$this->arr_unificado[] = $REG_C501;
						$total_registros++;
						$this->REG_9900["C501"]++;
					}
					foreach($this->REG_C505[$index_C010.$cod_part.$num_doc.$serie.$dt_doc] as $index_C505 => $REG_C505){
						$this->arr_unificado[] = $REG_C505;
						$total_registros++;
						$this->REG_9900["C505"]++;
					}
				}

				foreach($this->REG_C860[$index_C010] as $index_C860 => $REG_C860){
					$this->arr_unificado[] = $REG_C860;
					$total_registros++;
					$this->REG_9900["C860"]++;
					$arr_campos = explode("|", substr($REG_C860,1,  strlen($REG_C860) - 1));
					$nr_sat = $arr_campos[2];
					$dt_doc = $arr_campos[3];
					foreach($this->REG_C870[$index_C010.$nr_sat.$dt_doc] as $index_C870 => $REG_C870){
						$this->arr_unificado[] = $REG_C870;
						$total_registros++;
						$this->REG_9900["C870"]++;
					}
				}
			}
			$total_registros++;
			$this->arr_unificado[] = PIPE."C990".PIPE.($total_registros).PIPE."\n";
			$this->REG_9900["C990"]++;
		}

		if(count($this->REG_D001) > 0){
			$this->arr_unificado[] = $this->REG_D001["D001"];
			$total_registros = 1;
			$this->REG_9900["D001"]++;
			foreach($this->REG_D010 as $index_D010 => $REG_D010){
				$this->arr_unificado[] = $REG_D010;
				$total_registros++;
				$this->REG_9900["D010"]++;
				foreach($this->REG_D100[$index_D010] as $index_D100 => $REG_D100){
					$this->arr_unificado[] = $REG_D100;
					$total_registros++;
					$this->REG_9900["D100"]++;
					$arr_campos = explode("|", substr($REG_D100,1,  strlen($REG_D100) - 1));
					$ind_oper = $arr_campos[1];
					$cod_part = $arr_campos[3];
					$num_doc  = $arr_campos[8];
					$serie    = $arr_campos[6];
					$dt_doc   = $arr_campos[10];

					foreach($this->REG_D101[$index_D010.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc] as $index_D101 => $REG_D101){
						$this->arr_unificado[] = $REG_D101;
						$total_registros++;
						$this->REG_9900["D101"]++;
					}
					foreach($this->REG_D105[$index_D010.$ind_oper.$cod_part.$num_doc.$serie.$dt_doc] as $index_D105 => $REG_D105){
						$this->arr_unificado[] = $REG_D105;
						$total_registros++;
						$this->REG_9900["D105"]++;
					}
				}
			}
			$total_registros++;
			$this->arr_unificado[] = PIPE."D990".PIPE.($total_registros).PIPE."\n";
			$this->REG_9900["D990"]++;
		}

		if(count($this->REG_F001) > 0){
			$this->arr_unificado[] = $this->REG_F001["F001"];
			$total_registros = 1;
			$this->REG_9900["F001"]++;
			foreach($this->REG_F010 as $index_F010 => $REG_F010){
				$this->arr_unificado[] = $REG_F010;
				$total_registros++;
				$this->REG_9900["F010"]++;
				foreach($this->REG_F100[$index_F010] as $index_F100 => $REG_F100){
					$this->arr_unificado[] = $REG_F100;
					$total_registros++;
					$this->REG_9900["F100"]++;
				}
				foreach($this->REG_F120[$index_F010] as $index_F120 => $REG_F120){
					$this->arr_unificado[] = $REG_F120;
					$total_registros++;
					$this->REG_9900["F120"]++;
				}
				foreach($this->REG_F130[$index_F010] as $index_F130 => $REG_F130){
					$this->arr_unificado[] = $REG_F130;
					$total_registros++;
					$this->REG_9900["F130"]++;
				}
				foreach($this->REG_F150[$index_F010] as $index_F150 => $REG_F150){
					$this->arr_unificado[] = $REG_F150;
					$total_registros++;
					$this->REG_9900["F150"]++;
				}
				foreach($this->REG_F200[$index_F010] as $index_F200 => $REG_F200){
					$this->arr_unificado[] = $REG_F200;
					$total_registros++;
					$this->REG_9900["F200"]++;
				}
				foreach($this->REG_F205[$index_F010] as $index_F205 => $REG_F205){
					$this->arr_unificado[] = $REG_F205;
					$total_registros++;
					$this->REG_9900["F205"]++;
				}
				foreach($this->REG_F210[$index_F010] as $index_F210 => $REG_F210){
					$this->arr_unificado[] = $REG_F210;
					$total_registros++;
					$this->REG_9900["F210"]++;
				}
				foreach($this->REG_F600[$index_F010] as $index_F600 => $REG_F600){
					$this->arr_unificado[] = $REG_F600;
					$total_registros++;
					$this->REG_9900["F600"]++;
				}
				foreach($this->REG_F700[$index_F010] as $index_F700 => $REG_F700){
					$this->arr_unificado[] = $REG_F700;
					$total_registros++;
					$this->REG_9900["F700"]++;
				}
				foreach($this->REG_F800[$index_F010] as $index_F800 => $REG_F800){
					$this->arr_unificado[] = $REG_F800;
					$total_registros++;
					$this->REG_9900["F800"]++;
				}
			}

			$total_registros++;
			$this->arr_unificado[] = PIPE."F990".PIPE.($total_registros).PIPE."\n";
			$this->REG_9900["F990"]++;
		}

		if(count($this->REG_I001) > 0){
			$this->arr_unificado[] = $this->REG_I001["I001"];
			$total_registros = 1;
			$this->REG_9900["I001"]++;
			foreach($this->REG_I010 as $index_I010 => $REG_I010){
				$this->arr_unificado[] = $REG_I010;
				$total_registros++;
				$this->REG_9900["I010"]++;
			}
			$total_registros++;
			$this->arr_unificado[] = PIPE."I990".PIPE.($total_registros).PIPE."\n";
			$this->REG_9900["I990"]++;
		}

		$gerarbloco100 = TRUE;

		$this->arr_unificado[] = $this->REG_M001["M001"];
		$total_registros = 1;
		$this->REG_9900["M001"]++;
		$VL_CRED_DESC_PIS = 0;
		if($gerarbloco100){
			if(count($this->REG_M100) > 0){
				foreach($this->REG_M100 as $index_M100 => $REG_M100){
					$VL_BC_PIS_04 = 0;
					$QUANT_BC_PIS_06 = 0;
					$VL_CRED_08 = 0;
					$VL_AJUS_ACRES_09 = 0;
					$VL_AJUS_REDUC_10 = 0;
					$VL_CRED_DIF_11 = 0;
					$VL_CRED_DISP_12 = 0;
					$VL_CRED_DESC_14 = 0;
					$SLD_CRED_15 = 0;
					foreach($REG_M100 as $linha_M100){
						$arr_campos = explode("|", substr($linha_M100,1,  strlen($linha_M100) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_BC_PIS_04 += value_numeric($arr_campos[3]);
						$QUANT_BC_PIS_06 += value_numeric($arr_campos[5]);
						$VL_CRED_08 += value_numeric($arr_campos[7]);
						$VL_AJUS_ACRES_09 += value_numeric($arr_campos[8]);
						$VL_AJUS_REDUC_10 += value_numeric($arr_campos[9]);
						$VL_CRED_DIF_11 += value_numeric($arr_campos[10]);
						$VL_CRED_DISP_12 += value_numeric($arr_campos[11]);
						$VL_CRED_DESC_14 += value_numeric($arr_campos[13]);
						$SLD_CRED_15 += value_numeric($arr_campos[14]);
						$this->REG_9900["M100"]++;
					}
					$arr_campos[3] = number_format($VL_BC_PIS_04, 2, ",","");
					$arr_campos[5] = number_format($QUANT_BC_PIS_06, 3, ",","");
					$arr_campos[7] = number_format($VL_CRED_08, 2, ",","");
					$arr_campos[8] = number_format($VL_AJUS_ACRES_09, 2, ",","");
					$arr_campos[9] = number_format($VL_AJUS_REDUC_10, 2, ",","");
					$arr_campos[10] = number_format($VL_CRED_DIF_11, 2, ",","");
					$arr_campos[11] = number_format($VL_CRED_DISP_12, 2, ",","");
					$arr_campos[13] = number_format($VL_CRED_DESC_14, 2, ",","");
					$arr_campos[14] = number_format($SLD_CRED_15, 2, ",","");
					$linha_M100 = PIPE.implode("|", $arr_campos)."\n";
					if(in_array($arr_campos[1], array("101","103"))){
						$VL_CRED_DESC_PIS += $VL_CRED_DESC_14;
					}
					$this->arr_unificado[] = $linha_M100;
					$total_registros++;
					foreach($this->REG_M105[$index_M100] as $index_M105 => $REG_M105){
						$VL_BC_PIS_TOT_04 = 0;
						$VL_BC_PIS_CUM_05 = 0;
						$VL_BC_PIS_NC_06 = 0;
						$VL_BC_PIS_07 = 0;
						$QUANT_BC_PIS_TOT_08 = 0;
						$QUANT_BC_PIS_09 = 0;
						foreach($REG_M105 as $linha_M105){
							$arr_campos = explode("|", substr($linha_M105,1,  strlen($linha_M105) - 1));
							$arr_campos = array_map("trim", $arr_campos);
							$VL_BC_PIS_TOT_04 += value_numeric($arr_campos[3]);
							$VL_BC_PIS_CUM_05 += value_numeric($arr_campos[4]);
							$VL_BC_PIS_NC_06 += value_numeric($arr_campos[5]);
							$VL_BC_PIS_07 += value_numeric($arr_campos[6]);
							$QUANT_BC_PIS_TOT_08 += value_numeric($arr_campos[7]);
							$QUANT_BC_PIS_09 += value_numeric($arr_campos[8]);
						}

						$arr_campos[3] = number_format($VL_BC_PIS_TOT_04, 2, ",","") ;
						$arr_campos[4] = number_format($VL_BC_PIS_CUM_05, 2, ",","") ;
						$arr_campos[5] = number_format($VL_BC_PIS_NC_06, 2, ",","") ;
						$arr_campos[6] = number_format($VL_BC_PIS_07, 2, ",","") ;
						$arr_campos[7] = ($QUANT_BC_PIS_TOT_08 > 0 ? number_format($QUANT_BC_PIS_TOT_08, 3, ",","") : "");
						$arr_campos[8] = ($QUANT_BC_PIS_09 > 0 ? number_format($QUANT_BC_PIS_09, 3, ",","") : "");
						$linha_M105 = PIPE.implode("|", $arr_campos)."\n";
						$this->arr_unificado[] = $linha_M105;
						$total_registros++;
						$this->REG_9900["M105"]++;
					}
					foreach($this->REG_M110[$index_M100] as $index_M110 => $REG_M110){
						$this->arr_unificado[] = $REG_M110;
						$total_registros++;
						$this->REG_9900["M110"]++;
					}
				}
			}

			if(count($this->REG_M200) > 0){
				$VL_TOT_CONT_NC_PER_02 = 0;
				$VL_TOT_CRED_DESC_03 = 0;
				$VL_TOT_CRED_DESC_ANT_04 = 0;
				$VL_TOT_CONT_NC_DEV_05 = 0;
				$VL_RET_NC_06 = 0;
				$VL_OUT_DED_NC_07 = 0;
				$VL_CONT_NC_REC_08 = 0;
				$VL_TOT_CONT_CUM_PER_09 = 0;
				$VL_RET_CUM_10 = 0;
				$VL_OUT_DED_CUM_11 = 0;
				$VL_CONT_CUM_REC_12 = 0;
				$VL_TOT_CONT_REC_13 = 0;
				foreach($this->REG_M200 as $index_M200 => $REG_M200){
					$arr_campos = explode("|", substr($REG_M200,1,  strlen($REG_M200) - 1));
					$arr_campos = array_map("trim", $arr_campos);
					$VL_TOT_CONT_NC_PER_02 += value_numeric($arr_campos[1]);
					$VL_TOT_CRED_DESC_03 += value_numeric($arr_campos[2]);
					$VL_TOT_CRED_DESC_ANT_04 += value_numeric($arr_campos[3]);
					$VL_TOT_CONT_NC_DEV_05 += value_numeric($arr_campos[4]);
					$VL_RET_NC_06 += value_numeric($arr_campos[5]);
					$VL_OUT_DED_NC_07 += value_numeric($arr_campos[6]);
					$VL_CONT_NC_REC_08 += value_numeric($arr_campos[7]);
					$VL_TOT_CONT_CUM_PER_09 += value_numeric($arr_campos[8]);
					$VL_RET_CUM_10 += value_numeric($arr_campos[9]);
					$VL_OUT_DED_CUM_11 += value_numeric($arr_campos[10]);
					$VL_CONT_CUM_REC_12 += value_numeric($arr_campos[11]);
					$VL_TOT_CONT_REC_13 += value_numeric($arr_campos[12]);
				}

				$arr_campos[1] = number_format($VL_TOT_CONT_NC_PER_02, 2, ",","");
				$arr_campos[2] = number_format($VL_TOT_CRED_DESC_03, 2, ",","");
				$arr_campos[3] = number_format($VL_TOT_CRED_DESC_ANT_04, 2, ",","");
				$arr_campos[4] = number_format($VL_TOT_CONT_NC_DEV_05, 2, ",","");
				$arr_campos[5] = number_format($VL_RET_NC_06, 2, ",","");
				$arr_campos[6] = number_format($VL_OUT_DED_NC_07, 2, ",","");
				$arr_campos[7] = number_format($VL_CONT_NC_REC_08, 2, ",","");
				$arr_campos[8] = number_format($VL_TOT_CONT_CUM_PER_09, 2, ",","");
				$arr_campos[9] = number_format($VL_RET_CUM_10, 2, ",","");
				$arr_campos[10] = number_format($VL_OUT_DED_CUM_11, 2, ",","");
				$arr_campos[11] = number_format($VL_CONT_CUM_REC_12, 2, ",","");
				$arr_campos[12] = number_format($VL_TOT_CONT_REC_13, 2, ",","");
				$linha_M200 = PIPE.implode("|", $arr_campos)."\n";
				$this->arr_unificado[] = $linha_M200;
				$total_registros++;
				$this->REG_9900["M200"]++;
			}

			if(count($this->REG_M205) > 0){
				foreach($this->REG_M205 as $index_M205 => $REG_M205){
					$VL_DEBITO_04 = 0;
					foreach($REG_M205 as $linha_M205){
						$arr_campos = explode("|", substr($linha_M205,1,  strlen($linha_M205) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_DEBITO_04 += value_numeric($arr_campos[3]);
					}
					$arr_campos[3] = number_format($VL_DEBITO_04, 2, ",","");
					$linha_M205 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M205;
					$total_registros++;
					$this->REG_9900["M205"]++;
				}
			}

			if(count($this->REG_M210) > 0){
				foreach($this->REG_M210 as $index => $REG_M210){
					$VL_REC_BRT_03 = 0;
					$VL_BC_CONT_04 = 0;
					$QUANT_BC_PIS_06 = 0;
					$VL_CONT_APUR_08 = 0;
					$VL_AJUS_ACRES_09 = 0;
					$VL_AJUS_REDUC_10 = 0;
					$VL_CONT_DIFER_11 = 0;
					$VL_CONT_DIFER_ANT_12 = 0;
					$VL_CONT_PER_13 = 0;
					foreach($REG_M210 as $linha_M210){
						$arr_campos = explode("|", substr($linha_M210,1,  strlen($linha_M210) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_REC_BRT_03 += value_numeric($arr_campos[2]);
						$VL_BC_CONT_04 += value_numeric($arr_campos[3]);
						$QUANT_BC_PIS_06 += value_numeric($arr_campos[5]);
						$VL_CONT_APUR_08 += value_numeric($arr_campos[7]);
						$VL_AJUS_ACRES_09 += value_numeric($arr_campos[8]);
						$VL_AJUS_REDUC_10 += value_numeric($arr_campos[9]);
						$VL_CONT_DIFER_11 += value_numeric($arr_campos[10]);
						$VL_CONT_DIFER_ANT_12 += value_numeric($arr_campos[11]);
						$VL_CONT_PER_13 += value_numeric($arr_campos[12]);
					}

					$arr_campos[2] = number_format($VL_REC_BRT_03, 2, ",","");
					$arr_campos[3] = number_format($VL_BC_CONT_04, 2, ",","");
					$arr_campos[5] = number_format($QUANT_BC_PIS_06, 3, ",","");
					$arr_campos[7] = number_format($VL_CONT_APUR_08, 2, ",","");
					$arr_campos[8] = number_format($VL_AJUS_ACRES_09, 2, ",","");
					$arr_campos[9] = number_format($VL_AJUS_REDUC_10, 2, ",","");
					$arr_campos[10] = number_format( $VL_CONT_DIFER_11, 2, ",","");
					$arr_campos[11] = number_format($VL_CONT_DIFER_ANT_12, 2, ",","");
					$arr_campos[12] = number_format($VL_CONT_PER_13, 2, ",","");
					$linha_M210 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M210;
					$total_registros++;
					$this->REG_9900["M210"]++;
				}
			}

			if(count($this->REG_M400) > 0){
				foreach($this->REG_M400 as $index_M400 => $REG_M400){
					$VL_TOT_REC_03 = 0;
					foreach($REG_M400 as $linha_M400){
						$arr_campos = explode("|", substr($linha_M400, 1,strlen($linha_M400) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_TOT_REC_03 += value_numeric($arr_campos[2]);
					}
					$arr_campos[2] = number_format($VL_TOT_REC_03,2,",","");
					$linha_M400 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M400;
					$total_registros++;
					$this->REG_9900["M400"]++;
					foreach($this->REG_M410[$index_M400] as $index_M410 => $REG_M410){
						$VL_REC_03 = 0;
						foreach($REG_M410 as $linha_M410){
							$arr_campos = explode("|", substr($linha_M410, 1,strlen($linha_M410) - 1));
							$arr_campos = array_map("trim", $arr_campos);
							$VL_REC_03 += value_numeric($arr_campos[2]);
						}
						$arr_campos[2] = number_format($VL_REC_03,2,",","");
						$linha_M410 = PIPE.implode("|", $arr_campos)."\n";
						$this->arr_unificado[] = $linha_M410;
						$total_registros++;
						$this->REG_9900["M410"]++;
					}
				}
			}
			$VL_CRED_DESC_COFINS = 0;
			if(count($this->REG_M500) > 0){
				foreach($this->REG_M500 as $index_M500 => $REG_M500){
					$VL_BC_PIS_04 = 0;
					$QUANT_BC_PIS_06 = 0;
					$VL_CRED_08 = 0;
					$VL_AJUS_ACRES_09 = 0;
					$VL_AJUS_REDUC_10 = 0;
					$VL_CRED_DIF_11 = 0;
					$VL_CRED_DISP_12 = 0;
					$VL_CRED_DESC_14 = 0;
					$SLD_CRED_15 = 0;
					foreach($REG_M500 as $linha_M500){
						$arr_campos = explode("|", substr($linha_M500,1,  strlen($linha_M500) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_BC_PIS_04 += value_numeric($arr_campos[3]);
						$QUANT_BC_PIS_06 += value_numeric($arr_campos[5]);
						$VL_CRED_08 += value_numeric($arr_campos[7]);
						$VL_AJUS_ACRES_09 += value_numeric($arr_campos[8]);
						$VL_AJUS_REDUC_10 += value_numeric($arr_campos[9]);
						$VL_CRED_DIF_11 += value_numeric($arr_campos[10]);
						$VL_CRED_DISP_12 += value_numeric($arr_campos[11]);
						$VL_CRED_DESC_14 += value_numeric($arr_campos[13]);
						$SLD_CRED_15 += value_numeric($arr_campos[14]);
						$this->REG_9900["M500"]++;
					}
					$arr_campos[3] = number_format($VL_BC_PIS_04, 2, ",","");
					$arr_campos[5] = number_format($QUANT_BC_PIS_06, 3, ",","");
					$arr_campos[7] = number_format($VL_CRED_08, 2, ",","");
					$arr_campos[8] = number_format($VL_AJUS_ACRES_09, 2, ",","");
					$arr_campos[9] = number_format($VL_AJUS_REDUC_10, 2, ",","");
					$arr_campos[10] = number_format($VL_CRED_DIF_11, 2, ",","");
					$arr_campos[11] = number_format($VL_CRED_DISP_12, 2, ",","");
					$arr_campos[13] = number_format($VL_CRED_DESC_14, 2, ",","");
					$arr_campos[14] = number_format($SLD_CRED_15, 2, ",","");
					$linha_M500 = PIPE.implode("|", $arr_campos)."\n";
					if(in_array($arr_campos[1], array("101","103"))){
						$VL_CRED_DESC_COFINS += $VL_CRED_DESC_14;
					}
					$this->arr_unificado[] = $linha_M500;
					$total_registros++;
					foreach($this->REG_M505[$index_M500] as $index_M505 => $REG_M505){
						$VL_BC_PIS_TOT_04 = 0;
						$VL_BC_PIS_CUM_05 = 0;
						$VL_BC_PIS_NC_06 = 0;
						$VL_BC_PIS_07 = 0;
						$QUANT_BC_PIS_TOT_08 = 0;
						$QUANT_BC_PIS_09 = 0;
						foreach($REG_M505 as $linha_M505){
							$arr_campos = explode("|", substr($linha_M505,1,  strlen($linha_M505) - 1));
							$arr_campos = array_map("trim", $arr_campos);
							$VL_BC_PIS_TOT_04 += value_numeric($arr_campos[3]);
							$VL_BC_PIS_CUM_05 += value_numeric($arr_campos[4]);
							$VL_BC_PIS_NC_06 += value_numeric($arr_campos[5]);
							$VL_BC_PIS_07 += value_numeric($arr_campos[6]);
							$QUANT_BC_PIS_TOT_08 += value_numeric($arr_campos[7]);
							$QUANT_BC_PIS_09 += value_numeric($arr_campos[8]);
						}

						$arr_campos[3] = number_format($VL_BC_PIS_TOT_04, 2, ",","") ;
						$arr_campos[4] = number_format($VL_BC_PIS_CUM_05, 2, ",","") ;
						$arr_campos[5] = number_format($VL_BC_PIS_NC_06, 2, ",","") ;
						$arr_campos[6] = number_format($VL_BC_PIS_07, 2, ",","") ;
						$arr_campos[7] = ($QUANT_BC_PIS_TOT_08 > 0 ? number_format($QUANT_BC_PIS_TOT_08, 3, ",","") : "") ;
						$arr_campos[8] = ($QUANT_BC_PIS_09 > 0 ? number_format($QUANT_BC_PIS_09, 3, ",","") : "");
						$linha_M505 = PIPE.implode("|", $arr_campos)."\n";
						$this->arr_unificado[] = $linha_M505;
						$total_registros++;
						$this->REG_9900["M505"]++;
					}
					foreach($this->REG_M510[$index_M500] as $index_M510 => $REG_M510){
						$this->arr_unificado[] = $REG_M510;
						$total_registros++;
						$this->REG_9900["M510"]++;
					}

				}
			}

			if(count($this->REG_M600) > 0){
				$VL_TOT_CONT_NC_PER_02 = 0;
				$VL_TOT_CRED_DESC_03 = 0;
				$VL_TOT_CRED_DESC_ANT_04 = 0;
				$VL_TOT_CONT_NC_DEV_05 = 0;
				$VL_RET_NC_06 = 0;
				$VL_OUT_DED_NC_07 = 0;
				$VL_CONT_NC_REC_08 = 0;
				$VL_TOT_CONT_CUM_PER_09 = 0;
				$VL_RET_CUM_10 = 0;
				$VL_OUT_DED_CUM_11 = 0;
				$VL_CONT_CUM_REC_12 = 0;
				$VL_TOT_CONT_REC_13 = 0;
				foreach($this->REG_M600 as $index_M600 => $REG_M600){
					$arr_campos = explode("|", substr($REG_M600,1,  strlen($REG_M600) - 1));
					$arr_campos = array_map("trim", $arr_campos);
					$VL_TOT_CONT_NC_PER_02 += value_numeric($arr_campos[1]);
					$VL_TOT_CRED_DESC_03 += value_numeric($arr_campos[2]);
					$VL_TOT_CRED_DESC_ANT_04 += value_numeric($arr_campos[3]);
					$VL_TOT_CONT_NC_DEV_05 += value_numeric($arr_campos[4]);
					$VL_RET_NC_06 += value_numeric($arr_campos[5]);
					$VL_OUT_DED_NC_07 += value_numeric($arr_campos[6]);
					$VL_CONT_NC_REC_08 += value_numeric($arr_campos[7]);
					$VL_TOT_CONT_CUM_PER_09 += value_numeric($arr_campos[8]);
					$VL_RET_CUM_10 += value_numeric($arr_campos[9]);
					$VL_OUT_DED_CUM_11 += value_numeric($arr_campos[10]);
					$VL_CONT_CUM_REC_12 += value_numeric($arr_campos[11]);
					$VL_TOT_CONT_REC_13 += value_numeric($arr_campos[12]);
				}

				$arr_campos[1] = number_format($VL_TOT_CONT_NC_PER_02, 2, ",","");
				$arr_campos[2] = number_format($VL_TOT_CRED_DESC_03, 2, ",","");
				$arr_campos[3] = number_format($VL_TOT_CRED_DESC_ANT_04, 2, ",","");
				$arr_campos[4] = number_format($VL_TOT_CONT_NC_DEV_05, 2, ",","");
				$arr_campos[5] = number_format($VL_RET_NC_06, 2, ",","");
				$arr_campos[6] = number_format($VL_OUT_DED_NC_07, 2, ",","");
				$arr_campos[7] = number_format($VL_CONT_NC_REC_08, 2, ",","");
				$arr_campos[8] = number_format($VL_TOT_CONT_CUM_PER_09, 2, ",","");
				$arr_campos[9] = number_format($VL_RET_CUM_10, 2, ",","");
				$arr_campos[10] = number_format($VL_OUT_DED_CUM_11, 2, ",","");
				$arr_campos[11] = number_format($VL_CONT_CUM_REC_12, 2, ",","");
				$arr_campos[12] = number_format($VL_TOT_CONT_REC_13, 2, ",","");
				$linha_M600 = PIPE.implode("|", $arr_campos)."\n";
				$this->arr_unificado[] = $linha_M600;
				$total_registros++;
				$this->REG_9900["M600"]++;
			}

			if(count($this->REG_M605) > 0){
				foreach($this->REG_M605 as $index_M605 => $REG_M605){
					$VL_DEBITO_04 = 0;
					foreach($REG_M605 as $linha_M605){
						$arr_campos = explode("|", substr($linha_M605,1,  strlen($linha_M605) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_DEBITO_04 += value_numeric($arr_campos[3]);
					}
					$arr_campos[3] = number_format($VL_DEBITO_04, 2, ",","");
					$linha_M605 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M605;
					$total_registros++;
					$this->REG_9900["M605"]++;
				}
			}

			if(count($this->REG_M610) > 0){
				foreach($this->REG_M610 as $index => $REG_M610){
					$VL_REC_BRT_03 = 0;
					$VL_BC_CONT_04 = 0;
					$QUANT_BC_PIS_06 = 0;
					$VL_CONT_APUR_08 = 0;
					$VL_AJUS_ACRES_09 = 0;
					$VL_AJUS_REDUC_10 = 0;
					$VL_CONT_DIFER_11 = 0;
					$VL_CONT_DIFER_ANT_12 = 0;
					$VL_CONT_PER_13 = 0;
					foreach($REG_M610 as $linha_M610){
						$arr_campos = explode("|", substr($linha_M610,1,  strlen($linha_M610) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_REC_BRT_03 += value_numeric($arr_campos[2]);
						$VL_BC_CONT_04 += value_numeric($arr_campos[3]);
						$QUANT_BC_PIS_06 += value_numeric($arr_campos[5]);
						$VL_CONT_APUR_08 += value_numeric($arr_campos[7]);
						$VL_AJUS_ACRES_09 += value_numeric($arr_campos[8]);
						$VL_AJUS_REDUC_10 += value_numeric($arr_campos[9]);
						$VL_CONT_DIFER_11 += value_numeric($arr_campos[10]);
						$VL_CONT_DIFER_ANT_12 += value_numeric($arr_campos[11]);
						$VL_CONT_PER_13 += value_numeric($arr_campos[12]);
					}

					$arr_campos[2] = number_format($VL_REC_BRT_03, 2, ",","");
					$arr_campos[3] = number_format($VL_BC_CONT_04, 2, ",","");
					$arr_campos[5] = number_format($QUANT_BC_PIS_06, 3, ",","");
					$arr_campos[7] = number_format($VL_CONT_APUR_08, 2, ",","");
					$arr_campos[8] = number_format($VL_AJUS_ACRES_09, 2, ",","");
					$arr_campos[9] = number_format($VL_AJUS_REDUC_10, 2, ",","");
					$arr_campos[10] = number_format( $VL_CONT_DIFER_11, 2, ",","");
					$arr_campos[11] = number_format($VL_CONT_DIFER_ANT_12, 2, ",","");
					$arr_campos[12] = number_format($VL_CONT_PER_13, 2, ",","");
					$linha_M610 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M610;
					$total_registros++;
					$this->REG_9900["M610"]++;
				}
			}

			if(count($this->REG_M800) > 0){
				foreach($this->REG_M800 as $index_M800 => $REG_M800){
					$VL_TOT_REC_03 = 0;
					foreach($REG_M800 as $linha_M800){
						$arr_campos = explode("|", substr($linha_M800, 1,strlen($linha_M800) - 1));
						$arr_campos = array_map("trim", $arr_campos);
						$VL_TOT_REC_03 += value_numeric($arr_campos[2]);
					}
					$arr_campos[2] = number_format($VL_TOT_REC_03,2,",","");
					$linha_M800 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $linha_M800;
					$total_registros++;
					$this->REG_9900["M800"]++;
					foreach($this->REG_M810[$index_M800] as $index_M810 => $REG_M810){
						$VL_REC_03 = 0;
						foreach($REG_M810 as $linha_M810){
							$arr_campos = explode("|", substr($linha_M810, 1,strlen($linha_M810) - 1));
							$arr_campos = array_map("trim", $arr_campos);
							$VL_REC_03 += value_numeric($arr_campos[2]);
						}
						$arr_campos[2] = number_format($VL_REC_03,2,",","");
						$linha_M810 = PIPE.implode("|", $arr_campos)."\n";
						$this->arr_unificado[] = $linha_M810;
						$total_registros++;
						$this->REG_9900["M810"]++;
					}
				}
			}

			$total_registros++;
			$this->arr_unificado[] = PIPE."M990".PIPE.($total_registros).PIPE."\n";
			$this->REG_9900["M990"]++;


			if(count($this->REG_P001) > 0){
				$this->arr_unificado[] = $this->REG_P001["P001"];
				$total_registros = 1;
				$this->REG_9900["P001"]++;
				foreach($this->REG_P010 as $index_P010 => $REG_P010){
					$this->arr_unificado[] = $REG_P010;
					$total_registros++;
					$this->REG_9900["P010"]++;
				}
				$total_registros++;
				$this->arr_unificado[] = PIPE."P990".PIPE.($total_registros).PIPE."\n";
				$this->REG_9900["P990"]++;
			}

			if(count($this->REG_1001) > 0){
				$this->REG_9900["1001"]++;
				$this->arr_unificado[] = $this->REG_1001["1001"];
				$total_registros = 1;
				foreach($this->REG_1100 as $index_1100 => $REG_1100){
					$arr_campos = explode("|", substr($REG_1100, 1,strlen($REG_1100) - 1));
					$arr_campos = array_map("trim", $arr_campos);
					if($VL_CRED_DESC_PIS > 0){
						$arr_campos[12] = number_format($VL_CRED_DESC_PIS,2,",","");
						$arr_campos[17] = number_format(value_numeric($arr_campos[11]) - value_numeric($arr_campos[12]),2,",","");
					}
					$REG_1100 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $REG_1100;
					$total_registros++;
					$this->REG_9900["1100"]++;
				}

				foreach($this->REG_1500 as $index_1500 => $REG_1500){
					$arr_campos = explode("|", substr($REG_1500, 1,strlen($REG_1500) - 1));
					$arr_campos = array_map("trim", $arr_campos);
					if($VL_CRED_DESC_COFINS > 0){
						$arr_campos[12] = number_format($VL_CRED_DESC_COFINS,2,",","");
						$arr_campos[17] = number_format(value_numeric($arr_campos[11]) - value_numeric($arr_campos[12]),2,",","");
					}
					$REG_1500 = PIPE.implode("|", $arr_campos)."\n";
					$this->arr_unificado[] = $REG_1500;
					$total_registros++;
					$this->REG_9900["1500"]++;
				}
				$total_registros++;
				$this->arr_unificado[] = PIPE."1990".PIPE.($total_registros).PIPE."\n";
				$this->REG_9900["1990"]++;
			}
		}
		$this->arr_unificado[] = "|9001|0|\n";
		$this->REG_9900["9001"]++;


		foreach($this->REG_9900 as $index_9900 => $REG_9900){
			$this->arr_unificado[] = "|9900|{$index_9900}|{$REG_9900}|\n";
		}
		$this->REG_9900["9990"]++;
		$this->arr_unificado[] = "|9900|9900|".count($this->REG_9900)."|\n";
		$this->arr_unificado[] = "|9900|9990|".$this->REG_9900["9990"]."|\n";
		$this->arr_unificado[] = "|9900|9999|1|\n";
		$this->arr_unificado[] = "|9990|".(count($this->REG_9900) + 5)."|\n";
		$this->arr_unificado[] = "|9999|".(count($this->arr_unificado) + 1)."|\n";

		//file_put_contents($this->getlocalarquivos()."pis_cofins_unificado.txt", implode("",  $this->arr_unificado));
		file_put_contents("../temp/pis_cofins_unificado.txt", implode("",  $this->arr_unificado));
		$_SESSION["SUCCESS"] = "Unificação gerada com sucesso!";
	}

	function recalcular_100_200(){
		$total_debito  = 0;
		$total_credito = 0;

		foreach($this->REG_M200 as $index_M200 => $REG_M200){
			$arr_campos = explode("|", substr($REG_M200, 1,  strlen($REG_M200) - 1));
			$total_debito += value_numeric($arr_campos[1]);
		}

		$credito_maior_debito = FALSE;
		foreach($this->REG_M100 as $index_M100 => $REG_M100){
			foreach($REG_M100 as $index_REG_M100 => $linha_REG_M100){
				$arr_campos = explode("|", substr($linha_REG_M100, 1,  strlen($linha_REG_M100) - 1));
				$valor_credito_disp = value_numeric($arr_campos[11]);
				$valor_credito_desc = value_numeric($arr_campos[13]);
				$total_credito += $valor_credito_disp;
				if($total_credito > $total_debito){
					if($credito_maior_debito){
						$arr_campos[13] = number_format(0, 2, ",", "");
						$arr_campos[14] = number_format($valor_credito_disp, 2, ",", "");
						$arr_campos[12] = ($valor_credito_disp > 0 ? "1" : "0");
						$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
						$this->REG_M100[$index_M100][$index_REG_M100] = $linhaTxt;
					}else{
						$dif = $total_credito - $total_debito;
						$valor_credito_desc = $valor_credito_disp - $dif;
						$arr_campos[13] = number_format($valor_credito_desc, 2, ",", "");
						$arr_campos[14] = number_format($dif, 2, ",", "");
						$arr_campos[12] = "1";
						$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
						$this->REG_M100[$index_M100][$index_REG_M100] = $linhaTxt;
						$credito_maior_debito = TRUE;
					}
				}else{
					$arr_campos[11] = number_format($valor_credito_disp, 2, ",", "");
					$arr_campos[12] = "0";
					$arr_campos[13] = number_format($valor_credito_disp, 2, ",", "");
					$arr_campos[14] = number_format(0, 2, ",", "");
					$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
					$this->REG_M100[$index_M100][$index_REG_M100] = $linhaTxt;
				}
			}
		}

		$total_debito  = 0;
		$total_credito = 0;

		foreach($this->REG_M600 as $index_M600 => $REG_M600){
			$arr_campos = explode("|", substr($REG_M600, 1,  strlen($REG_M600) - 1));
			$total_debito += value_numeric($arr_campos[1]);
		}

		$credito_maior_debito = FALSE;
		foreach($this->REG_M500 as $index_M500 => $REG_M500){
			foreach($REG_M500 as $index_REG_M500 => $linha_REG_M500){
				$arr_campos = explode("|", substr($linha_REG_M500, 1,  strlen($linha_REG_M500) - 1));
				$valor_credito_disp = value_numeric($arr_campos[11]);
				$valor_credito_desc = value_numeric($arr_campos[13]);
				$total_credito += $valor_credito_disp;
				if($total_credito > $total_debito){
					if($credito_maior_debito){
						$arr_campos[13] = number_format(0, 2, ",", "");
						$arr_campos[14] = number_format($valor_credito_disp, 2, ",", "");
						$arr_campos[12] = "1";
						$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
						$this->REG_M500[$index_M500][$index_REG_M500] = $linhaTxt;
					}else{
						$dif = $total_credito - $total_debito;
						$valor_credito_desc = $valor_credito_disp - $dif;
						$arr_campos[13] = number_format($valor_credito_desc, 2, ",", "");
						$arr_campos[14] = number_format($dif, 2, ",", "");
						$arr_campos[12] = "1";
						$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
						$this->REG_M500[$index_M500][$index_REG_M500] = $linhaTxt;
						$credito_maior_debito = TRUE;
					}
				}else{
					$arr_campos[11] = number_format($valor_credito_disp, 2, ",", "");
					$arr_campos[12] = "0";
					$arr_campos[13] = number_format($valor_credito_disp, 2, ",", "");
					$arr_campos[14] = number_format(0, 2, ",", "");
					$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
					$this->REG_M500[$index_M500][$index_REG_M500] = $linhaTxt;
				}
			}
		}

		if($credito_maior_debito){
			foreach($this->REG_M200 as $index_M200 => $REG_M200){
				$arr_campos = explode("|", substr($REG_M200, 1,  strlen($REG_M200) - 1));
				$total_debito = value_numeric($arr_campos[1]);
				$arr_campos[2] = number_format($total_debito, 2, ",", "");
				$arr_campos[3] = number_format(0, 2, ",", "");
				$arr_campos[4] = number_format(0, 2, ",", "");
				$arr_campos[5] = number_format(0, 2, ",", "");
				$arr_campos[6] = number_format(0, 2, ",", "");
				$arr_campos[7] = number_format(0, 2, ",", "");
				$arr_campos[8] = number_format(0, 2, ",", "");
				$arr_campos[9] = number_format(0, 2, ",", "");
				$arr_campos[10] = number_format(0, 2, ",", "");
				$arr_campos[11] = number_format(0, 2, ",", "");
				$arr_campos[12] = number_format(0, 2, ",", "");

				$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
				$this->REG_M200[$index_M200] = $linhaTxt;
			}

			foreach($this->REG_M600 as $index_M600 => $REG_M600){
				$arr_campos = explode("|", substr($REG_M600, 1,  strlen($REG_M600) - 1));
				$total_debito = value_numeric($arr_campos[1]);
				$arr_campos[2] = number_format($total_debito, 2, ",", "");
				$arr_campos[3] = number_format(0, 2, ",", "");
				$arr_campos[4] = number_format(0, 2, ",", "");
				$arr_campos[5] = number_format(0, 2, ",", "");
				$arr_campos[6] = number_format(0, 2, ",", "");
				$arr_campos[7] = number_format(0, 2, ",", "");
				$arr_campos[8] = number_format(0, 2, ",", "");
				$arr_campos[9] = number_format(0, 2, ",", "");
				$arr_campos[10] = number_format(0, 2, ",", "");
				$arr_campos[11] = number_format(0, 2, ",", "");
				$arr_campos[12] = number_format(0, 2, ",", "");

				$linhaTxt = PIPE.implode("|", $arr_campos)."\n";
				$this->REG_M600[$index_M600] = $linhaTxt;
			}
			$this->REG_M205 = array();
			$this->REG_M605 = array();
		}
	}

	function lerarquivosfiscal(){
		$_SESSION["ERROR"] = "";
		$_SESSION["SUCCESS"] = "";
		foreach($this->lista_arquivos["arr_arquivos"] as $i => $arquivo){
			$arquivounificar = $this->local_arquivos.$arquivo;
			if(strlen($arquivo) == 0 || $arquivo == "item"){
				continue;
			}
			if(file_exists($arquivounificar)){
				$arquivo_unificar = file($arquivounificar);
				$arr_campos = explode("|", substr($arquivo_unificar[0],1,  strlen($arquivo_unificar[0]) - 1));
				/*
				$cnpj_arquivo = substr($arr_campos[8], 0, 8);
				$cnpj_estabelecimento  = substr($this->getcpfcnpj(), 0, 8);
				if($cnpj_arquivo != $cnpj_estabelecimento){
					$_SESSION["ERROR"] .= "CNPJ encontrado no arquivo {$arquivo} não pertence ao estabelecimento selecionado. ";
					continue;
				}
				 *
				 */
				foreach($arquivo_unificar as $j => $linhatxt){
					$arr_campos = explode("|", substr($linhatxt,1,  strlen($linhatxt) - 1));
					$registro = $arr_campos[0];
					switch($registro){
						case "0000":
							if($i === 0){
								$this->REG_0000["0000"] = $linhatxt;
							}
							break;
						case "0001":
							if($i === 0){
								$this->REG_0001["0001"] = $linhatxt;
							}
						case "0005":
							if($i === 0){
								$this->REG_0005["0005"] = $linhatxt;
							}
							break;
						case "0100":
							if($i == 0){
								$this->REG_0100["0100"] = $linhatxt;
							}
							break;
						case "0150":
							$cod_part = $arr_campos[1];
							$this->REG_0150[$cod_part] = $linhatxt;
							break;
						case "0190":
							$un = $arr_campos[1];
							$this->REG_0190[$un] = $linhatxt;
							break;
						case "0200":
							$cod_item = $arr_campos[1];
							$this->REG_0200[$cod_item] = $linhatxt;
							break;
						case "0220":
							$this->REG_0220[$cod_item][] = $linhatxt;
							break;
						case "0300":
							$cod_item_bem = $arr_campos[1];
							$this->REG_0300[$cod_item_bem] = $linhatxt;
							break;
						case "0305":
							$this->REG_0305[$cod_item_bem][] = $linhatxt;
							break;
						case "0400":
							$cfop = $arr_campos[1];
							$this->REG_0400[] = $linhatxt;
							break;
						case "0460":
							$cod_obs = $arr_campos[1];
							$this->REG_0460[$cod_obs] = $linhatxt;
							break;
						case "0500":
							$this->REG_0500[] = $linhatxt;
							break;
						case "0600":
							$this->REG_0600[] = $linhatxt;
							break;
						case "C001":
							if($i === 0){
								$this->REG_C001["C001"] = $linhatxt;
							}
							break;
						case "C100":
							$ind_oper = $arr_campos[1];
							$ind_emit = $arr_campos[2];
							$cod_part = $arr_campos[3];
							$cod_mod  = $arr_campos[4];
							$serie    = $arr_campos[6];
							$num_doc  = $arr_campos[7];
							$this->REG_C100[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc] = $linhatxt;
							break;
						case "C140":
							$this->REG_C140[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][] = $linhatxt;
							break;
						case "C141":
							$num_parc = $arr_campos[1];
							$this->REG_C141[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][$num_parc] = $linhatxt;
							break;
						case "C160":
							$this->REG_C160[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][] = $linhatxt;
							break;
						case "C170":
							$this->REG_C170[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][] = $linhatxt;
							break;
						case "C190":
							$this->REG_C190[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][] = $linhatxt;
							break;
						case "C195":
							$this->REG_C195[$ind_oper.$ind_emit.$cod_part.$cod_mod.$serie.$num_doc][] = $linhatxt;
							break;
						case "C300":
							$cod_mod = $arr_campos[1];
							$serie   = $arr_campos[2];
							$num_ini = $arr_campos[4];
							$num_fin = $arr_campos[5];
							$this->REG_C300[$cod_mod.$serie.$num_ini.$num_fin] = $linhatxt;
							break;
						case "C320":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_C320[$cod_mod.$serie.$num_ini.$num_fin][$cst_icms.$cfop.$aliq_icms][] = $linhatxt;
							break;
						case "C321":
							$cod_item = $arr_campos[1];
							$this->REG_C321[$cod_mod.$serie.$num_ini.$num_fin][$cst_icms.$cfop.$aliq_icms][$cod_item] = $linhatxt;
							break;
						case "C350":
							$serie   = $arr_campos[1];
							$num_doc = $arr_campos[3];
							$dt_doc  = $arr_campos[4];
							$this->REG_C350[$serie.$num_doc.$dt_doc] = $linhatxt;
							break;
						case "C370":
							$num_item = $arr_campos[1];
							$cod_item = $arr_campos[2];
							$this->REG_C370[$serie.$num_doc.$dt_doc][$num_item.$cod_item] = $linhatxt;
							break;
						case "C390":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_C390[$serie.$num_doc.$dt_doc][$cst_icms.$cfop.$aliq_icms] = $linhatxt;
							break;
						case "C400":
							$cod_mod = $arr_campos[1];
							$ecf_mod = $arr_campos[2];
							$ecf_fab = $arr_campos[3];
							$ecf_cxa = $arr_campos[4];
							$this->REG_C400[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa] = $linhatxt;
							break;
						case "C405":
							$dt_doc  = $arr_campos[1];
							$cro     = $arr_campos[2];
							$crz     = $arr_campos[3];
							$num_coo = $arr_campos[4];
							$this->REG_C405[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo] = $linhatxt;
							break;
						case "C410":
							$this->REG_C410[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo][] = $linhatxt;
							break;
						case "C420":
							$this->REG_C420[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo][] = $linhatxt;
							break;
						case "C460":
							$cod_mod = $arr_campos[1];
							$cod_sit = $arr_campos[2];
							$num_doc = $arr_campos[3];
							$dt_doc  = $arr_campos[4];
							$this->REG_C460[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo][$cod_mod.$cod_sit.$num_doc.$dt_doc] = $linhatxt;
							break;
						case "C470":
							$cod_item = $arr_campos[1];
							$this->REG_C470[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo][$cod_mod.$cod_sit.$num_doc.$dt_doc][$cod_item] = $linhatxt;
							break;
						case "C490":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_C490[$cod_mod.$ecf_mod.$ecf_fab.$ecf_cxa][$dt_doc.$cro.$crz.$num_coo][] = $linhatxt;
							break;
						case "C500":
							$ind_oper = $arr_campos[1];
							$ind_emit = $arr_campos[2];
							$cod_part = $arr_campos[3];
							$cod_mod  = $arr_campos[4];
							$cod_sit  = $arr_campos[5];
							$serie    = $arr_campos[6];
							$num_doc  = $arr_campos[9];
							$dt_doc   = $arr_campos[10];
							$this->REG_C500[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc] = $linhatxt;
							break;
						case "C590":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_C590[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc][$cst_icms.$cfop.$aliq_icms] = $linhatxt;
							break;
						case "C800":
							$cod_mod = $arr_campos[1];
							$cod_sit = $arr_campos[2];
							$num_cfe = $arr_campos[3];
							$dt_doc  = $arr_campos[4];
							$num_sat = $arr_campos[9];
							$this->REG_C800[$cod_mod.$cod_sit.$num_cfe.$dt_doc.$num_sat] = $linhatxt;
							break;
						case "C850":
							$this->REG_C850[$cod_mod.$cod_sit.$num_cfe.$dt_doc.$num_sat][] = $linhatxt;
							break;
						case "C860":
							$cod_mod = $arr_campos[1];
							$num_sat = $arr_campos[2];
							$dt_doc  = $arr_campos[3];
							$doc_ini = $arr_campos[4];
							$doc_fin = $arr_campos[5];
							$this->REG_C860[$cod_mod.$num_sat.$dt_doc.$doc_ini.$doc_fin] = $linhatxt;
							break;
						case "C890":
							$this->REG_C890[$cod_mod.$num_sat.$dt_doc.$doc_ini.$doc_fin][] = $linhatxt;
							break;
						case "D001":
							if($i == 0){
								$this->REG_D001["D001"] = $linhatxt;
							}
							break;
						case "D100":
							$ind_oper = $arr_campos[1];
							$ind_emit = $arr_campos[2];
							$cod_part = $arr_campos[3];
							$cod_mod  = $arr_campos[4];
							$cod_sit  = $arr_campos[5];
							$serie    = $arr_campos[6];
							$num_doc  = $arr_campos[8];
							$dt_doc   = $arr_campos[10];
							$this->REG_D100[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc] = $linhatxt;
							break;
						case "D190":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_D190[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc][$cst_icms.$cfop.$aliq_icms] = $linhatxt;
							break;
						case "D500":
							$ind_oper = $arr_campos[1];
							$ind_emit = $arr_campos[2];
							$cod_part = $arr_campos[3];
							$cod_mod  = $arr_campos[4];
							$cod_sit  = $arr_campos[5];
							$serie    = $arr_campos[6];
							$num_doc  = $arr_campos[8];
							$dt_doc   = $arr_campos[9];
							$this->REG_D100[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc] = $linhatxt;
							break;
						case "D590":
							$cst_icms  = $arr_campos[1];
							$cfop      = $arr_campos[2];
							$aliq_icms = $arr_campos[3];
							$this->REG_D190[$ind_oper.$ind_emit.$cod_part.$cod_mod.$cod_sit.$serie.$num_doc.$dt_doc][$cst_icms.$cfop.$aliq_icms] = $linhatxt;
							break;
						case "E001":
							if($i === 0){
								$this->REG_E001["E001"] = $linhatxt;
							}
							break;
						case "E100":
							$dt_ini = $arr_campos[1];
							$dt_fin = $arr_campos[2];
							$this->REG_E100[$dt_ini.$dt_fin] = $linhatxt;
							break;
						case "E110":
							$this->REG_E110[$dt_ini.$dt_fin][] = $linhatxt;
							break;
						case "E111":
							$this->REG_E111[$dt_ini.$dt_fin][] = $linhatxt;
							break;
						case "E116":
							$this->REG_E116[$dt_ini.$dt_fin][] = $linhatxt;
							break;
						case "E200":
							$UF = $arr_campos[1];
							$this->REG_E200[$UF] = $linhatxt;
							break;
						case "E210":
							$this->REG_E210[$UF][] = $linhatxt;
							break;
						case "E250":
							$this->REG_E250[$UF][] = $linhatxt;
							break;
						case "E300":
							$UF = $arr_campos[1];
							$this->REG_E300[$UF] = $linhatxt;
							break;
						case "E310":
							$this->REG_E310[$UF][] =  $linhatxt;
							break;
						case "E316":
							$this->REG_E316[$UF][] = $linhatxt;
							break;
						case "E500":
							$dt_ini = $arr_campos[1];
							$dt_fin = $arr_campos[2];
							$this->REG_E500[$dt_ini.$dt_fin] = $linhatxt;
							break;
						case "E510":
							$this->REG_E510[$dt_ini.$dt_fin][] = $linhatxt;
							break;
						case "E520":
							$this->REG_E520[$dt_ini.$dt_fin][] = $linhatxt;
							break;
						case "G001":
							if($i === 0){
								$this->REG_G001["G001"] = $linhatxt;
							}
							break;
						case "G110":
							$this->REG_G110[] = $linhatxt;
							break;
						case "G125":
							$this->REG_G125[] = $linhatxt;
							break;
						case "H001":
							if($i === 0){
								$this->REG_G001["H001"] = $linhatxt;
							}
							break;
						case "H005":
							$dt_inv = $arr_campos[1];
							$mot_inv = $arr_campos[3];
							$this->REG_H005[$dt_inv.$mot_inv] = $linhatxt;
							break;
						case "H010":
							$cod_item = $arr_campos[1];
							$this->REG_H010[$dt_inv.$mot_inv][] = $linhatxt;
							break;
						case "H020":
							$this->REG_H020[$dt_inv.$mot_inv][$cod_item] = $linhatxt;
							break;
						case "1001":
							if($i === 0){
								$this->REG_1001["1001"] = $linhatxt;
							}
							break;
						case "1010":
							$this->REG_1010[] = $linhatxt;
							break;
					}
				}
			}
		}
		return (count($this->REG_0000) > 0);
	}

	function unificardadosfiscal(){
		$this->arr_unificado[] = $this->REG_0000["0000"];
		$this->arr_unificado[] = $this->REG_0001["0001"];
		$this->arr_unificado[] = $this->REG_0005["0005"];
		$this->arr_unificado[] = $this->REG_0100["0100"];
		$this->REG_9900["0000"]++;
		$this->REG_9900["0001"]++;
		$this->REG_9900["0005"]++;
		$this->REG_9900["0100"]++;
		foreach($this->REG_0150 as $REG_0150){
			$this->arr_unificado[] = $REG_0150;
			$this->REG_9900["0150"]++;
		}

		foreach($this->REG_0190 as $REG_0190){
			$this->arr_unificado[] = $REG_0190;
			$this->REG_9900["0190"]++;
		}

		foreach($this->REG_0200 as $index_0200 => $REG_0200){
			$this->arr_unificado[] = $REG_0200;
			$this->REG_9900["0200"]++;
			$gerou220 = FALSE;
			foreach($this->REG_0220[$index_0200] as $REG_0220){
				if(count($this->REG_0220[$index_0200]) == 1){
					$this->arr_unificado[] = $REG_0220;
					$this->REG_9900["0220"]++;
					$gerou220 = TRUE;
				}
			}

			if(!$gerou220){
				$arr_campos = explode("|", substr($REG_0200,1,  strlen($REG_0200) - 1));
				$unidade = $this->REG_0190[$arr_campos[5]];
				if(!is_null($unidade)){
					$this->arr_unificado[] = PIPE."0220".PIPE.$arr_campos[5].PIPE."1".PIPE."\n";
					$this->REG_9900["0220"]++;
				}
			}
		}

		foreach($this->REG_0300 as $index_0300 => $REG_0300){
			$this->arr_unificado[] = $REG_0300;
			$this->REG_9900["0300"]++;
			foreach($this->REG_0305[$index_0300] as $index_0305 => $REG_0305){
				$this->arr_unificado[] = $REG_0305;
				$this->REG_9900["0305"]++;
			}
		}

		foreach($this->REG_0400 as $REG_0400){
			$this->arr_unificado[] = $REG_0400;
			$this->REG_9900["0400"]++;
		}


		foreach($this->REG_0460 as $REG_0460){
			$this->arr_unificado[] = $REG_0460;
			$this->REG_9900["0460"]++;
		}

		foreach($this->REG_0500 as $REG_0500){
			$this->arr_unificado[] = $REG_0500;
			$this->REG_9900["0500"]++;
		}

		foreach($this->REG_0600 as $REG_0600){
			$this->arr_unificado[] = $REG_0600;
			$this->REG_9900["0600"]++;
		}

		$this->arr_unificado[] = PIPE."0990".PIPE.(count($this->arr_unificado) + 1).PIPE."\n";
		$this->REG_9900["0990"]++;


		$this->arr_unificado[] = $this->REG_C001["C001"];
		$this->REG_9900["C001"]++;
		$total_registros = 1;

		foreach($this->REG_C100 as $index_C100 => $REG_C100){
			$this->arr_unificado[] = $REG_C100;
			$this->REG_9900["C100"]++;
			$total_registros++;
			foreach($this->REG_C140[$index_C100] as $index_C140 => $REG_C140){
				$this->arr_unificado[] = $REG_C140;
				$this->REG_9900["C140"]++;
				$total_registros++;
			}
			foreach($this->REG_C160[$index_C100] as $index_C160 => $REG_C160){
				$this->arr_unificado[] = $REG_C160;
				$this->REG_9900["C160"]++;
				$total_registros++;
			}

			foreach($this->REG_C170[$index_C100] as $index_C170 => $REG_C170){
				$this->arr_unificado[] = $REG_C170;
				$this->REG_9900["C170"]++;
				$total_registros++;
			}

			foreach($this->REG_C190[$index_C100] as $index_C190 => $REG_C190){
				$this->arr_unificado[] = $REG_C190;
				$this->REG_9900["C190"]++;
				$total_registros++;
			}

			foreach($this->REG_C195[$index_C100] as $index_C195 => $REG_C195){
				$this->arr_unificado[] = $REG_C195;
				$this->REG_9900["C195"]++;
				$total_registros++;
			}
		}

		foreach($this->REG_C300 as $index_C300 => $REG_C300){
			$this->arr_unificado[] = $REG_C300;
			$this->REG_9900["C300"]++;
			$total_registros++;
			foreach($this->REG_C320[$index_C300] as $index_C320 => $REG_C320){
				$this->arr_unificado[] = $REG_C320;
				$this->REG_9900["C320"]++;
				$total_registros++;
				foreach($this->REG_C321[$index_C320] as $index_C321 => $REG_C321){
					$this->arr_unificado[] = $REG_C321;
					$this->REG_9900["C321"]++;
					$total_registros++;
				}
			}
		}

		foreach($this->REG_C350 as $index_C350 => $REG_C350){
			$this->arr_unificado[] = $REG_C350;
			$this->REG_9900["C350"]++;
			$total_registros++;

			foreach($this->REG_C370[$index_C350] as $index_C370 => $REG_C370){
				$this->arr_unificado[] = $REG_C370;
				$this->REG_9900["C370"]++;
				$total_registros++;
			}

			foreach($this->REG_C390[$index_C350] as $index_C390 => $REG_C390){
				$this->arr_unificado[] = $REG_C390;
				$this->REG_9900["C390"]++;
				$total_registros++;
			}
		}

		foreach($this->REG_C400 as $index_C400 => $REG_C400){
			$this->arr_unificado[] = $REG_C400;
			$this->REG_9900["C400"]++;
			$total_registros++;

			foreach($this->REG_C405[$index_C400] as $index_C405 => $REG_C405){
				$this->arr_unificado[] = $REG_C405;
				$this->REG_9900["C405"]++;
				$total_registros++;

				foreach($this->REG_C410[$index_C405] as $index_C410 => $REG_C410){
					$this->arr_unificado[] = $REG_C410;
					$this->REG_9900["C410"]++;
					$total_registros++;
				}

				foreach($this->REG_C420[$index_C405] as $index_C420 => $REG_C420){
					$this->arr_unificado[] = $REG_C420;
					$this->REG_9900["C420"]++;
					$total_registros++;
				}

				foreach($this->REG_C460[$index_C405] as $index_C460 => $REG_C460){
					$this->arr_unificado[] = $REG_C460;
					$this->REG_9900["C460"]++;
					$total_registros++;

					foreach($this->REG_C470[$index_C460] as $index_C470 => $REG_C470){
						$this->arr_unificado[] = $REG_C470;
						$this->REG_9900["C470"]++;
						$total_registros++;
					}
				}

				foreach($this->REG_C490[$index_C405] as $index_C490 => $REG_C490){
					$this->arr_unificado[] = $REG_C490;
					$this->REG_9900["C490"]++;
					$total_registros++;
				}
			}
		}

		foreach($this->REG_C500 as $index_C500 => $REG_C500){
			$this->arr_unificado[] = $REG_C500;
			$this->REG_9900["C500"]++;
			$total_registros++;

			foreach($this->REG_C590[$index_C500] as $index_C590 => $REG_C590){
				$this->arr_unificado[] = $REG_C590;
				$this->REG_9900["C590"]++;
				$total_registros++;
			}
		}

		foreach($this->REG_C800 as $index_C800 => $REG_C800){
			$this->arr_unificado[] = $REG_C800;
			$this->REG_9900["C800"]++;
			$total_registros++;

			foreach($this->REG_C850[$index_C800] as $index_C850 => $REG_C850){
				$this->arr_unificado[] = $REG_C850;
				$this->REG_9900["C850"]++;
				$total_registros++;
			}
		}

		foreach($this->REG_C860 as $index_C860 => $REG_C860){
			$this->arr_unificado[] = $REG_C860;
			$this->REG_9900["C860"]++;
			$total_registros++;

			foreach($this->REG_C890[$index_C860] as $index_C890 => $REG_C890){
				$this->arr_unificado[] = $REG_C890;
				$this->REG_9900["C890"]++;
				$total_registros++;
			}
		}

		$total_registros++;
		$this->arr_unificado[] = PIPE."C990".PIPE.($total_registros).PIPE."\n";
		$this->REG_9900["C990"]++;

		$this->arr_unificado[] = $this->REG_D001["D001"];
		$this->REG_9900["D001"]++;
		$total_registros = 1;

		foreach($this->REG_D100 as $index_D100 => $REG_D100){
			$this->arr_unificado[] = $REG_D100;
			$this->REG_9900["D100"]++;
			$total_registros++;

			foreach($this->REG_D190[$index_D100] as $index_D190 => $REG_D190){
				$this->arr_unificado[] = $REG_D190;
				$this->REG_9900["D190"]++;
				$total_registros++;
			}
		}

		foreach($this->REG_D500 as $index_D500 => $REG_D500){
			$this->arr_unificado[] = $REG_D500;
			$this->REG_9900["D500"]++;
			$total_registros++;

			foreach($this->REG_D590[$index_D500] as $index_D590 => $REG_D590){
				$this->arr_unificado[] = $REG_D590;
				$this->REG_9900["D590"]++;
				$total_registros++;
			}
		}

		$total_registros++;
		$this->arr_unificado[] = PIPE."D990".PIPE.($total_registros).PIPE."\n";
		$this->REG_9900["D990"]++;

		file_put_contents($this->getlocalarquivos()."fiscal_unificado.txt", implode("",  $this->arr_unificado));
	}
}
