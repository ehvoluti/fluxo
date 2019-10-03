<?php
require_once("websac/require_file.php");
require_file("class/notafiscaleletronica.class.php");
require_file("class/notafiscaleletronicaestabelecimento.class.php");
require_once("../class/log.class.php");

class ManifestoNFe{

	private $con;
	private $log;
	private $error;

	function __construct(Connection $con, Log $log = null){
		$this->con = $con;
		$this->log = $log;
	}

	/*
	 * Metodo vai retornar FALSE quando falhar a consulta e
	 * uma STRING quando consultar com sucesso
	 */
	public function consultarSefaz($codestabelec){
		try{
			$retorno = "";

			$nfeestabelecimento = new NotafiscalEletronicaEstabelecimento();
			$nfeestabelecimento->setconnection($this->con);

			$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
			$notafiscaleletronica = new NotaFiscalEletronica($this->con);
			if(strlen($estabelecimento->getultnsu()) > 0){
				$ultnsu = $estabelecimento->getultnsu();
				$maxnsu = $estabelecimento->getmaxnsu();
				if($maxnsu > 0 && $maxnsu = $ultnsu){
					$maxnsu++;
				}
			}else{
				$ultnsu = -1;
				$maxnsu = 0;
			}
			$notafiscaleletronica->setestabelecimento($estabelecimento);
			$nrdocumento = 0;

			$notafiscaleletronica->carregaconfiguracoes();

			$this->log("Variáveis do estabelecimento: ultnsu = {$ultnsu}; maxnsu = {$maxnsu}");

			$this->con->start_transaction();

			$ncont = 0;
			while($ultnsu < $maxnsu && $ncont < 1000){

				if($ultnsu < 0){
					$ultnsu = 0;
				}

				$this->log("Consultando NSU {$ultnsu} para o estabelecimento {$codestabelec}.");
				Log::fastWrite("manifestonfe", "downloadlistanfesefaz: chamada");
				$arr_nfe = $notafiscaleletronica->downloadlistanfesefaz($ultnsu);
				//Log::fastWrite("manifestonfe", "downloadlistanfesefaz: retorno:". var_export($arr_nfe, TRUE));
				$this->log("Retorno da consulta: ".json_encode($arr_nfe));

				if($arr_nfe["bStat"]){
					if($arr_nfe["cStat"] == "138"){

						$ultnsu = $arr_nfe["ultNSU"];
						$maxnsu = $arr_nfe["maxNSU"];
						$dhresp = substr($arr_nfe["dhResp"], 0, 10);
						$arr_DocZip = $arr_nfe["aDoc"];
						foreach($arr_DocZip as $nfe_xml){
							$ncont++;
							$schema = $nfe_xml["schema"];
							$xmlnfe = $nfe_xml["doc"];
							$obj_xml = simplexml_load_string($xmlnfe);

							$numnotafiscal = "";
							$serie = "";
							$emissao = "";
							$finalidade = "";
							$cnpj = "";
							$nome = "";
							$chave = "";
							$totalnfe = "";
							$StatusNFe = "";
							$tipoevento = "";
							/*
							if(isset($obj_xml->chNFe)){
								$chave = $obj_xml->chNFe;
							}elseif(isset($obj_xml->retEvento->infEvento->chNFe)){
								$chave = $obj_xml->retEvento->infEvento->chNFe;
							}elseif(isset($obj_xml->protNFe->infProt->chNFe)){
								$chave = $obj_xml->protNFe->infProt->chNFe;
							}
							file_put_contents("../temp/downloadxml_{$chave}_{$ncont}.xml", $xmlnfe);
							 *
							 */
							if(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_RESNFE) > 0){
								$nrdocumento++;
								$numnotafiscal = substr((string) $obj_xml->chNFe, 25, 9);
								$serie = substr((string) $obj_xml->chNFe, 22, 3);
								$emissao = (string) $obj_xml->dhEmi;
								$finalidade = "";
								if(isset($obj_xml->CNPJ)){
									$cnpj = (string) $obj_xml->CNPJ;
								}else{
									$cnpj = (string) $obj_xml->CPF;
								}
								$nome = (string) $obj_xml->xNome;
								$chave = (string) $obj_xml->chNFe;
								$totalnfe = (float) $obj_xml->vNF;
								$StatusNFe = (float) $obj_xml->cSitNFe;
								$xmlnfe = "";
							}elseif(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_PROCEVENTONFE) > 0){
								$tipoevento = (string) $obj_xml->retEvento->infEvento->tpEvento;
								$chave = (string) $obj_xml->retEvento->infEvento->chNFe;
							}elseif(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_RESEVENTO) > 0){
								$tipoevento = (string) $obj_xml->tpEvento;
								$chave = (string) $obj_xml->chNFe;
							}elseif(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_PROCNFE) > 0){
								$nrdocumento++;
								$numnotafiscal = (string) $obj_xml->NFe->infNFe->ide->nNF;
								$serie = (string) $obj_xml->NFe->infNFe->ide->serie;
								$emissao = (string) $obj_xml->NFe->infNFe->ide->dhEmi;
								$finalidade = (string) $obj_xml->NFe->infNFe->ide->NFe;
								if(isset($obj_xml->NFe->infNFe->emit->CNPJ)){
									$cnpj = (string) $obj_xml->NFe->infNFe->emit->CNPJ;
								}else{
									$cnpj = (string) $obj_xml->NFe->infNFe->emit->CPF;
								}
								$nome = (string) $obj_xml->NFe->infNFe->emit->xNome;
								$chave = (string) $obj_xml->protNFe->infProt->chNFe;
								$totalnfe = (float) $obj_xml->NFe->infNFe->total->ICMSTot->vNF;
								$StatusNFe = (float) $obj_xml->protNFe->infProt->cStat;
							}

							if(strlen($chave) > 0){
								$nfeestabelecimento = objectbytable("notafiscaleletronicaestabelecimento", null, $this->con);
								$nfeestabelecimento->setchavenfe($chave);
								$arr_nfeestabelecimento = object_array($nfeestabelecimento);
								$nfeExist = count($arr_nfeestabelecimento) > 0;
								if($nfeExist){
									$nfeestabelecimento = array_shift($arr_nfeestabelecimento);
								}

								if(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_PROCNFE) > 0 ||
									(!$nfeExist && substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_RESNFE) > 0)){
									$nfeestabelecimento->setcnpj($cnpj);
									$nfeestabelecimento->setcodestabelec($estabelecimento->getcodestabelec());
									$nfeestabelecimento->setdataemissao(str_replace("T", " ", $emissao));
									$nfeestabelecimento->setnome($nome);
									$nfeestabelecimento->setnumnotafiscal($numnotafiscal);
									$nfeestabelecimento->settotal($totalnfe);
									$nfeestabelecimento->setstatus($StatusNFe);
									$nfeestabelecimento->setxmlnfe($xmlnfe);
									if($StatusNFe == "100"){
										$nfeestabelecimento->setevento("210210");
									}

									if(!$nfeestabelecimento->save()){
										$this->con->rollback();
										$this->error = "Não foi possivel salvar a(s) NF-e(s)<br>".$_SESSION["ERROR"];
										return false;
									}
									if(substr_count($schema, NotafiscalEletronicaEstabelecimento::SCHEMA_XML_NFE_PROCNFE) > 0){
										$path_xml = $estabelecimento->getdirxmlnfe();
										$dtemissao = strtotime(str_replace("/", "-", substr($emissao, 0, 10)));
										$hoje = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
										$dif = (int) floor(($hoje - $dtemissao) / 86400);

										if($dif > 7){
											$path_xml = $path_xml."IMPORTADO".DIRECTORY_SEPARATOR.date("Y-m-d", $dtemissao);
											if(!is_dir($path_xml)){
												mkdir($path_xml, 0777);
											}
											$path_xml .= DIRECTORY_SEPARATOR;
										}
										$file_xml = $chave."-nfe.xml";
										$bytes = file_put_contents($path_xml.$file_xml, $xmlnfe);
										if($bytes > 0){
											chmod($path_xml.$file_xml, 0777);
										}
									}
								}else{
									if($nfeExist && in_array($tipoevento, array("210200", "210210", "210220", "210240"))){
										$nfeestabelecimento->setevento($tipoevento);
										if(!$nfeestabelecimento->save()){
											$this->con->rollback();
											$this->error = "Não foi possivel salvar a(s) NF-e(s) com evento {$tipoevento}<br>".$_SESSION["ERROR"];
											return false;
										}
									}
								}
							}
						}
						$estabelecimento->setultnsu($ultnsu);
						$estabelecimento->setmaxnsu($maxnsu);
						$estabelecimento->setdtultdistmdfe($dhresp);
						if(!$estabelecimento->save()){
							$this->con->rollback();
							$this->error = "Não foi possivel atualizar o estabelecimento.";
							return false;
						}
					}else{
						if($ultnsu < $maxnsu - 1){
							$retorno = "Consulta realizada com sucesso<br>Codigo: ".$arr_nfe["cStat"]."<br> Motivo: ".$arr_nfe["xMotivo"]."<br><br>Ainda existe NF-e(s) a ser consultada. Por favor repita a consulta.";
						}else{
							$retorno = "Consulta realizada com sucesso<br>Codigo: ".$arr_nfe["cStat"]."<br> Motivo: ".$arr_nfe["xMotivo"];
						}
						$this->con->commit();
						return $retorno;
					}
				}else{
					$this->error = "Consulta não realizada. Serviço do <b>SEFAZ</b> indisponivel no momento. <br>Aguarde uns minutos e tente novamente";
					$this->con->rollback();
					return false;
				}
			}

			if($nrdocumento > 0){
				$retorno = "Consulta realizada com sucesso!<br>Codigo: ".$arr_nfe["cStat"]."<br> Motivo: ".$nrdocumento." ".$arr_nfe["xMotivo"];
			}else{
				$retorno = "Consulta realizada com sucesso!<br>Nenhum documento disponível.";
			}
			$this->con->commit();
			return $retorno;
		}catch(Exception $ex){
			$this->error = $ex->getTraceAsString();
			return false;
		}
	}

	public function manifestoSefaz($codestabelec, $evento, $chavenfe = null, $xJust = null, $idnfeestabelec = null, $arr_lotenfe = null){
		try{
			$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
			//Log::fastWrite("manifestonfe", "codestabelec: {} cnpj a ser cosnultado: {$estabelecimento->getcpfcnpj()}");
			$notafiscaleletronica = new NotaFiscalEletronica($this->con);
			$notafiscaleletronica->setestabelecimento($estabelecimento);
			$notafiscaleletronica->carregaconfiguracoes();

			if(!is_array($arr_lotenfe)){
				$nfeestabelecimento = new NotafiscalEletronicaEstabelecimento();
				$nfeestabelecimento->setconnection($this->con);
				$nfeestabelecimento->setidnfeestabelec($idnfeestabelec);
				$nfeestabelecimento->searchbyobject();

				Log::fastWrite("manifestonfe", "manifestonfesefaz: chamada");
				$retorno = $notafiscaleletronica->manifestonfesefaz($evento, $chavenfe, $xJust);
				Log::fastWrite("manifestonfe", "manifestonfesefaz: retorno". var_export($retorno, TRUE));
				if($retorno["bStat"]){
					if($retorno["cStat"] == "128"){
						if($notafiscaleletronica->versaoapi == "4.0.0"){
							$retorno = $retorno["evento"][0];
						}else{
							$retorno = $retorno["retEvento"]["infEvento"];
						}
						if($retorno["cStat"] == "135" || $retorno["cStat"] == "573"){
							$nfeestabelecimento->setevento($evento);
							$nfeestabelecimento->save();
							return true;
						}else{
							if($retorno["cStat"] == "650"){
								$nfeestabelecimento->setstatus("101");
								$nfeestabelecimento->save();
							}
							$this->error = "Não foi possivel realizar o manifesto da NF-e.<br>"."Status:".$retorno["cStat"]."<br>"."Motivo".$retorno["xMotivo"];
							return FALSE;
						}
					}else{
						$this->error = "Não foi possivel realizar o manifesto da NF-e.".var_export($retorno, TRUE);
						return false;
					}
				}else{
					$this->error = "Não foi possivel realizar o manifesto da NF-e.".var_export($retorno, TRUE);
					return false;
				}
			}else{
				foreach($arr_lotenfe as $chavenfe){
					$this->log("Efetuando manifesto da NF-e {$chavenfe}");

					$nfeestabelecimento = new NotafiscalEletronicaEstabelecimento();
					$nfeestabelecimento->setconnection($this->con);
					$nfeestabelecimento->setchavenfe($chavenfe);
					$arr_nfeestabelecimento = object_array($nfeestabelecimento);
					$nfeExist = count($arr_nfeestabelecimento) > 0;
					if($nfeExist){
						$nfeestabelecimento = array_shift($arr_nfeestabelecimento);

						$retorno = $notafiscaleletronica->manifestonfesefaz($evento, $chavenfe, $xJust);
						if($retorno["bStat"]){
							if($retorno["cStat"] == "128"){
								if($notafiscaleletronica->versaoapi == "4.0.0"){
									$retorno = $retorno["evento"][0];
								}else{
									$retorno = $retorno["retEvento"]["infEvento"];
								}
								if($retorno["cStat"] == "135" || $retorno["cStat"] == "573"){
									$nfeestabelecimento->setevento($evento);
									$nfeestabelecimento->save();
								}elseif($retorno["cStat"] == "650"){
									$nfeestabelecimento->setstatus("101");
									$nfeestabelecimento->save();
								}else{
									$this->error = var_export($retorno, TRUE);
									//return FALSE;
								}
							}else{
								$this->error = var_export($retorno, TRUE);
							}
						}
					}
				}
				return true;
			}
		}catch(Exception $ex){
			$this->error = $ex->getMessage();
			return false;
		}
	}

	public function error(){
		return $this->error;
	}

	private function log($text){
		if(is_object($this->log)){
			$this->log->write($text);
		}
	}

}