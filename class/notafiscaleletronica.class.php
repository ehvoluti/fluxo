<?php
require_once("../class/itemcalculo.class.php");
require_once("../class/notafiscaleletronicamonitor.class.php");
require_once("../class/email.class.php");
include_once("../class/log.class.php");
include_once("../class/ncmunidade.class.php");

define("TCP", "0");
define("TXT", "1");
define("NFEPHP", "2");
define("ENTTXT", "ent.txt");
define("SAITXT", "sai.txt");
define("LITERALEVENTO", "-procEventoNFe.xml");
define("LITERALEVENTOPDF", "-procEventoNFe.pdf");
define("LITERALCANCELAMENTO", "-CancNFe-procEvento.xml");
define("LITERALCCE", "-CCe-procEvento.xml");
define("LITERALNFECANCELADA", "-canc-nfe.xml");
define("LITERALNFE", "-nfe.xml");
define("LITERALPDF", ".pdf");
//define("LITERALLOGO", "logo.jpg");
define("LITERALPROTOCOLORECIBO", "-retConsReciNFe.xml");
define("LITERALPROTOCOLOCHAVE", "-retConsSitNFe.xml");
define("EVENTOCANCELAMENTO", "110111");
define("EVENTOCCE", "110110");
define("EVENTOMANIFESTACAO", "210200");
define("TRANSMITIRNFE", "0");
define("CONSULTARNFE", "1");
define("CANCELARNFE", "2");
define("DS", DIRECTORY_SEPARATOR);

class NotaFiscalEletronica{
	const VERSAO_NFE4 = "4.00";
	const VERSAO_NFE3 = "3.10";
	const VERSAO_API4 = "4.0.0";
	const VERSAO_API5 = "5.0.0";

	private $con;
	private $notacomplemento;
	private $notafiscal;
	private $descricaoproduto;
	private $nfemonitor;
	private $tipocomunicacao;
	private $estabelecimento;
	private $modoimpressao;
	private $nomepdfevento;
	private $nomepdfnfe;
	private $ambiente;
	private $timeoutconsulta; // tempo a aguardar apos envio da NF-e para consulta do lote enviado
	private $pathlog;  //pasta raiz onde sera armazenados os arquivos xml criados
	private $pathnfe;  //pasta raiz onde sera armazenados as NF-e autorizadas
	private $pathcanc;  //pasta raiz onde sera armazenda as NF-e canceladas
	private $pathinut;  //pasta raiz onde sera armasenada as NF-e inutilizada
	private $pathden;  //pasta raiz onde sera armazenada as NF-e denegadas
	private $pathpdf;  //pasta raiz onde sera armazenado os pdf's das NF-e
	private $pathcce;  //pasta raiz onde sera armazenada as cartas de correcoes
	private $pathlogo;  //pasta onde devera ser localizado o logo a ser impresso no DANFE
	private $pathprotocolos; //pasta raiz onde sera armazenado os protocolos da(s) NF-e(s)
	private $xsdArquivo; //nome do arquivo de validaÃ§Ãµes locais
	private $nfeTools;  //obejto que contem metodos de validaÃ§Ã£o assinatura e consulta da nfe
	private $classToolsNFe; //contem a definiÃ§Ã£o da classe ToolsNFe para criaÃ§Ã£o de objetos
	private $classMakeNFe; //contem a definiÃ§Ã£o da classe MakeNfe para criaÃ§Ã£o de objetos
	private $classDanfe; //contem definiÃ§Ã£o da classe Danfe para criaÃ§Ã£o de objetos
	private $classDacce; //contem definiÃ§Ã£o da classe Dacce para criaÃ§Ã£o de objetos
	private $nfe;   //objeto que contem as informaÃ§oes da nf-e
	private $aConfig; //contem definicoes de configuracoes para conexao com o SEFAZ
	private $certificado;
	public $versaoapi = VERSAO_API5;
	public $versaonfe;

	function __construct($con){
		$this->con = $con;
		$this->versaonfe = param("NOTAFISCAL", "VERSAONFE", $this->con);
		$this->tipocomunicacao = param("NOTAFISCAL", "TIPOCOMNFE", $this->con);

		$this->modoimpressao = param("NOTAFISCAL", "MODOIMPRESSAO", $this->con);
		if($this->versaonfe == "3"){
			$this->versaonfe = self::VERSAO_NFE3;
		}
		if($this->versaonfe == "4"){
			$this->versaonfe = self::VERSAO_NFE4;
		}
	}

	function configurarconexao($host, $port){
		$this->nfemonitor = new NFeMonitor($host, $port);
	}

	function gettipocomunicacao(){
		return $this->tipocomunicacao;
	}

	function setnotacomplemento($notacomplemento){
		$this->notacomplemento = $notacomplemento;
		if(strlen($this->notacomplemento->getidnotafiscal()) == 0){
			$this->notafiscal = $notacomplemento;
		}else{
			$this->notafiscal = objectbytable("notafiscal", $this->notacomplemento->getidnotafiscal(), $this->con);
		}

		$this->estabelecimento = objectbytable("estabelecimento", $this->notafiscal->getcodestabelec(), $this->con);
		$this->versaoapi = $this->estabelecimento->getversaoapi();
		if(is_null($this->versaoapi) || strlen($this->versaoapi) == 0){
			$this->versaoapi = self::VERSAO_API4;
		}
		$this->ambiente = $this->estabelecimento->getambientenfe();

		$this->carregaconfiguracoes();

		if($this->tipocomunicacao == TCP){
			$this->configurarconexao($this->estabelecimento->gethost(), $this->estabelecimento->getport());
		}elseif($this->tipocomunicacao == NFEPHP){
			$paramfiscal = objectbytable("paramfiscal", $this->notafiscal->getcodestabelec(), $this->con);
			$this->configuranfephp();
		}
	}

	function setnotafiscal($notafiscal){
		$this->notacomplemento = NULL;
		$this->notafiscal = $notafiscal;

		$this->estabelecimento = objectbytable("estabelecimento", $this->notafiscal->getcodestabelec(), $this->con);
		$this->versaoapi = $this->estabelecimento->getversaoapi();
		if(is_null($this->versaoapi) || strlen($this->versaoapi) == 0){
			$this->versaoapi = self::VERSAO_API4;
		}
		$this->ambiente = $this->estabelecimento->getambientenfe();

		$this->carregaconfiguracoes();

		if($this->tipocomunicacao == TCP){
			$this->configurarconexao($this->estabelecimento->gethost(), $this->estabelecimento->getport());
		}elseif($this->tipocomunicacao == NFEPHP){
			$paramfiscal = objectbytable("paramfiscal", $this->notafiscal->getcodestabelec(), $this->con);
			$this->configuranfephp();
		}
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
		$this->versaoapi = $this->estabelecimento->getversaoapi();
		$this->ambiente = $this->estabelecimento->getambientenfe();
		if(is_null($this->versaoapi) || strlen($this->versaoapi) == 0){
			$this->versaoapi = self::VERSAO_API4;
		}
	}

	function carregaconfiguracoes(){
		switch($this->versaoapi){
			case self::VERSAO_API4:
				include_once "../lib/nfephp-4.0.0/bootstrap.php";
				$this->classToolsNFe = "\NFePHP\NFe\ToolsNFe";
				$this->classMakeNFe = "\NFePHP\NFe\MakeNFe";
				$this->classDanfe = "\NFePHP\Extras\Danfe";
				$this->classDacce = "\NFePHP\Extras\Dacce";
				$filefolderconfig = "..".DS."lib".DS."nfephp-4.0.0".DS."config".DS.str_pad(removeformat($this->estabelecimento->getcpfcnpj()), 5, "0", STR_PAD_LEFT)."_config.json";
				if(!is_file($filefolderconfig)){
					throw new Exception("NÃ£o encontrado o arquivo de configuraÃ§Ãµes da NF-e! Favor entrar em contato com o suporte tÃ©cnico.");
				}
				$this->aConfig = $filefolderconfig;
				if(!isset($this->nfeTools)){
					$this->nfeTools = new $this->classToolsNFe($this->aConfig);
					$this->nfeTools->setModelo("55");
				}
				if(!isset($this->nfe)){
					$this->nfe = new \NFePHP\NFe\MakeNFe();
				}
				break;
			case self::VERSAO_API5:
				require_once("../lib/nfephp-5.0.0/bootstrap.php");
				$this->classToolsNFe = "\NFePHP\NFe\Tools";
				$this->classMakeNFe = "\NFePHP\NFe\Make";
				$this->classDanfe = "\NFePHP\DA\NFe\Danfe";
				$this->classDacce = "\NFePHP\DA\NFe\Dacce";

				$this->aConfig = json_encode([
					"atualizacao" => date("Y-m-d")." ".date("h:i:s")."-02:00",
					"tpAmb" => $this->ambiente,
					"razaosocial" => $this->estabelecimento->getrazaosocial(),
					"cnpj" => removeformat($this->estabelecimento->getcpfcnpj()),
					"siglaUF" => $this->estabelecimento->getuf(),
					"schemes" => $this->estabelecimento->getnomeschema(),
					"versao" => $this->versaonfe,
					"mailSmtp" => $this->estabelecimento->getservidorsmtp(),
					"mailPort" => $this->estabelecimento->getporta(),
					"mailProtocol" => $this->estabelecimento->gettipoautenticacao(),
					"mailPass" => $this->estabelecimento->getsenhaemail(),
					"mailUser" => $this->estabelecimento->getusuarioemail()
				]);
				$this->certificado = \NFePHP\Common\Certificate::readPfx($this->carregacertificado(), $this->estabelecimento->getsenhachaveprivada());
				if(!isset($this->nfeTools)){
					$this->nfeTools = new $this->classToolsNFe($this->aConfig, $this->certificado);
					$this->nfeTools->Model("55");
				}
				if(!isset($this->nfe)){
					switch($this->versaonfe){
						case self::VERSAO_NFE3 :
							//$this->nfe = \NFePHP\NFe\Make::v310();
							$this->nfe = new \NFePHP\NFe\Make();
							break;
						case self::VERSAO_NFE4 :
							//$this->nfe = \NFePHP\NFe\Make::v400();
							$this->nfe = new \NFePHP\NFe\Make();
							break;
					}
				}
				break;
		}
	}

	function carregacertificado(){
		try{
			$arquivocertificado = $this->estabelecimento->getdircertificados();
			$arquivocertificado .= $this->estabelecimento->getnomecertificado();
			$senhacertificado = $this->estabelecimento->getsenhachaveprivada();

			$certificado = file_get_contents($arquivocertificado);
		}catch(Exception $ex){
			throw new Exception($ex->getMessage(), $ex->getCode(), "");
		}
		return $certificado;
	}

	function configuranfephp(){
		$dirname = $this->estabelecimento->getdirimportnfe()."nfe";
		if(!is_dir($dirname)){
			mkdir($dirname, 0777);
		}

		$this->timeoutconsulta = $this->estabelecimento->gettimeoutconsulta();

		$this->pathlog = $this->estabelecimento->getdirimportnfe()."nfe/log/";
		$dirName = $this->estabelecimento->getdirimportnfe()."nfe";
		$dirnametrab = $dirName."/log";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathnfe = $this->estabelecimento->getdirimportnfe()."nfe/autorizadas/";
		$dirnametrab = $dirName."/autorizadas";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathcanc = $this->estabelecimento->getdirimportnfe()."nfe/canceladas/";
		$dirnametrab = $dirName."/canceladas";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathinut = $this->estabelecimento->getdirimportnfe()."nfe/inutilizadas/";
		$dirnametrab = $dirName."/inutilizadas";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathden = $this->estabelecimento->getdirimportnfe()."nfe/denegadas/";
		$dirnametrab = $dirName."/denegadas";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathpdf = "../temp/pdfnfe/";
		$dirnametrab = "../temp/pdfnfe";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathcce = $this->estabelecimento->getdirimportnfe()."nfe/cce/";
		$dirnametrab = $dirName."/cce";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}

		$this->pathprotocolos = $this->estabelecimento->getdirimportnfe()."nfe/protocolo/";
		$dirnametrab = $dirName."/protocolo";
		if(!is_dir($dirnametrab)){
			mkdir($dirnametrab, 0777);
		}


		$this->pathlogo = $this->estabelecimento->getlocallogotipo();
		$this->xsdArquivo = "../nfephp/schemes/".$this->estabelecimento->getarquivoxsd();
	}

	function setdescricaoproduto($descricao){
		$this->descricaoproduto = $descricao;
	}

	function getdescricaoproduto(){
		return $this->descricaoproduto;
	}

	function nfemonitorok($excluirresp){
		$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
		if(file_exists($fileresp)){
			$resp = explode("\r\n", file_get_contents($fileresp));
			if($excluirresp){
				sleep(2);
				unlink($fileresp);
				sleep(2);
			}
			if(substr($resp[0], 0, 3) == "OK:"){
				return TRUE;
			}else{
				if(file_exists($fileresp)){
					sleep(2);
					unlink($fileresp);
					sleep(2);
				}
				return $resp[0];
			}
		}else{
			return FALSE;
		}
	}

	function ativo(){
		if($this->tipocomunicacao == TCP){
			$result = $this->nfemonitor->ativo();
		}else{
			$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
			if(file_exists($fileresp)){
				unlink($fileresp);
				sleep(1);
			}
			$pathnfe = $this->estabelecimento->getdirimportnfe();
			echo write_file($pathnfe.ENTTXT, "NFe.Ativo", (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$result = $this->nfemonitorok(TRUE);
				if($result){
					break;
				}
			}
		}
		if((!$result) || (is_string($result))){
			$filename = $this->estabelecimento->getdirimportnfe().ENTTXT;
			if(file_exists($filename)){
				unlink($filename);
			}
			return FALSE;
		}else{
			return TRUE;
		}
	}

	function downloadnfesefaz($chavenfe, $codestabelec, $verificarACBr = TRUE){
		if($verificarACBr){
			$this->estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
			$this->configurarconexao($this->estabelecimento->gethost(), $this->estabelecimento->getport());
		}

		if($verificarACBr && !$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel fazer o download da NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}

		$_SESSION["ERROR"] = "";
		$_SESSION["DOWNLOADNFE"] = "";
		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.CONSULTANFEDEST("'.removerformat($this->estabelecimento->getcpfcnpj()).'",'.$chavenfe.'"', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			$retorno = FALSE;
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				unlink($fileresp);
			}else{
				if(is_bool($ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. Tente o download da NF-e novamente.";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel fazer o downalod da NF-e no SEFAZ.\n".utf8_encode($ok);
				}
				return FALSE;
			}
		}else{

			$retorno = $this->nfemonitor->downloadNFe(removeformat($this->estabelecimento->getcpfcnpj()), $chavenfe);

			if(is_array($retorno)){
				$arr_retorno = $retorno["OK"]["DOWNLOADNFE"];
				if(is_array($arr_retorno)){
					$nStat = $arr_retorno["cStat"];
					if($nStat == 139){
						$arr_retorno = $retorno["OK"]["NFE001"];
						$nStat = $arr_retorno["cStat"];
						switch($nStat){
							case 633:
								$retorno = $this->enviareventosefaz($chavenfe, removeformat($this->estabelecimento->getcpfcnpj()), "CONFIRMACAO DA OPERACAO", EVENTOMANIFESTACAO, "91", "", "", 1);
								if(is_array($retorno)){
									$arr_retorno = $retorno["OK"]["EVENTO001"];
									if(is_array($arr_retorno)){
										$nStat = $arr_retorno["cStat"];
										$xMotivo = $arr_retorno["+"];
										if($verificarACBr && $nStat == 135){
											if($this->downloadnfesefaz($chavenfe, $codestabelec, FALSE)){
												return TRUE;
											}else{
												$_SESSION["ERROR"] .= "N&atilde;o foi poss&iacute;vel fazer o download da NF-e. Tente novamente.";
											}
										}else{
											$_SESSION["ERROR"] = "N&atilde;o foi possivel fazer o download da NF-e.<br>".$nStat.":".$xMotivo;
										}
									}else{
										$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. Tente o download da NF-e novamente.";
										return FALSE;
									}
								}else{
									$retorno = str_replace(array("'", "\r", "\n"), "", utf8_encode($retorno));
									$_SESSION["ERROR"] = $retorno;
									return FALSE;
								}
								break;
							case 217:
								$_SESSION["ERROR"] = "N&atilde;o foi possivel fazer o download da NF-e.<br>".utf8_encode($arr_retorno["xMotivo"]);
								return FALSE;
								break;
							case 140:
								if(is_dir($this->estabelecimento->getdirxmlnfe())){
									$nomexml = $chavenfe.LITERALNFE;
									$xml = $this->nfemonitor->loadfromfile($nomexml);
									if(strlen($xml) > 0){
										$nomexml = $this->estabelecimento->getdirxmlnfe().$chavenfe.LITERALNFE;
										$bytes = file_put_contents($nomexml, $xml);
										return TRUE;
									}else{
										$_SESSION["ERROR"] = "N&atilde;o foi possivel carregar o xml da NF-e. Tente novamente";
									}
								}else{
									$_SESSION["ERROR"] = "O local de importa&ccedil;&atilde;o da NF-e informado no estabelecimento &eacute inv&aacute;lido.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para verificar o local informado";
									return FALSE;
								}
							default:
								$_SESSION["ERROR"] = utf8_encode($arr_retorno["xMotivo"]);
								return FALSE;
								break;
						}
					}
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do sefaz. Por tente o download da NF-e novamente.";
				}
			}else{
				$_SESSION["ERROR"] = utf8_encode($retorno);
				return FALSE;
			}
		}
	}

	function manifestonfesefaz($evento, $chavenfe, $xJust = ""){
		switch($this->versaoapi){
			case self::VERSAO_API4:
				$arr_retorno = array();
				$retorno = $this->nfeTools->sefazManifesta($chavenfe, $this->ambiente, $xJust, $evento, $arr_retorno);
				break;
			case self::VERSAO_API5:
				//Log::fastWrite("manisfestonfe", "sefazManifesta: chamada");
				$retorno = $this->nfeTools->sefazManifesta($chavenfe, $evento, $xJust);
				$std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
				$arr_retorno = $std_retorno->toArray();
				//Log::fastWrite("manisfestonfe", "sefazManifesta: retorno:". var_export($arr_retorno, TRUE));
				$arr_retorno["bStat"] = TRUE;
				break;
		}
		return $arr_retorno;
	}

	function downloadlistanfesefaz($ultimo_nsu = 0){
		try{
			switch($this->versaoapi){
				case self::VERSAO_API4:
					$arr_retorno = array();
					//Log::fastWrite("manisfestonfe", "sefazDistDFe: chamada");
					$retorno = $this->nfeTools->sefazDistDFe("AN", $this->ambiente, removeformat($this->estabelecimento->getcpfcnpj()), $ultimo_nsu, 0, $arr_retorno);
					//Log::fastWrite("manisfestonfe", "sefazDistDFe: retorno:". var_export($retorno, TRUE));
					//Log::fastWrite("manisfestonfe", "sefazDistDFe: retorno:". var_export($arr_retorno, TRUE));
					break;
				case self::VERSAO_API5 :
					$retorno = $this->nfeTools->sefazDistDFe($ultimo_nsu);

					$std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
					$arr_retorno = $std_retorno->toArray();
					unset($arr_retorno["loteDistDFeInt"]);
					$aDocZip = Array();
					if($this->formatarretornosefaz($retorno, $aDocZip)){
						$arr_retorno["bStat"] = TRUE;
					}else{
						$arr_retorno["bStat"] = FALSE;
					}
					$arr_retorno["aDoc"] = $aDocZip;
					break;
			}

			//$arr_retorno["bStat"] = TRUE;
		}catch(Exception $ex){
			$arr_retorno["ERRO"] = $ex->getMessage();
		}
		return $arr_retorno;
	}

	function formatarretornosefaz($retornoDist, &$aDocs = array()){
		try{
			$dom = DOMDocument::loadXML($retornoDist, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
			$tagDist = $dom->getElementsByTagName("retDistDFeInt")->item(0);

			if(!isset($tagDist)){
				return FALSE;
			}else{
				$docs = $tagDist->getElementsByTagName('docZip');
				foreach($docs as $doc){
					$xml = gzdecode(base64_decode($doc->nodeValue));
					$aDocs[] = array(
						"NSU" => $doc->getAttribute("NSU"),
						'schema' => $doc->getAttribute('schema'),
						"doc" => $xml
					);
				}
			}
		}catch(Exception $ex){
			return FALSE;
		}
		return TRUE;
	}

	function downloadnfesefazphp($chavenfe, $codestabelec){
		$_SESSION["ERROR"] = "";
		$this->estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
		try{
			if(is_null($this->aConfig) || strlen($this->aConfig) == 0){
				$this->versaoapi = $this->estabelecimento->getversaoapi();
			}
			if(is_null($this->ambiente)){
				$this->ambiente = $this->estabelecimento->getambientenfe();
			}
			$arr_retorno = array();
			$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
			$chavenfe = removeformat($chavenfe);
			switch($this->versaoapi){
				case self::VERSAO_API4:
					if(!is_object($this->classToolsNFe)){
						$this->carregaconfiguracoes();
						$this->nfeTools = new $this->classToolsNFe($this->aConfig);
						$this->nfeTools->setModelo("55");
					}
					$xml = $this->nfeTools->sefazDownload($chavenfe, $this->ambiente, $cnpj, $arr_retorno);
					if($arr_retorno["bStat"]){
						if(isset($arr_retorno["aRetNFe"]) && count($arr_retorno["aRetNFe"]) > 0){
							$xMotivo = $arr_retorno["aRetNFe"]["xMotivo"];
							$cStat = $arr_retorno["aRetNFe"]["cStat"];
						}else{
							$xMotivo = $arr_retorno["xMotivo"];
							$cStat = $arr_retorno["cStat"];
						}
						switch($cStat){
							case 140:
								if(is_dir($this->estabelecimento->getdirxmlnfe())){
									if(strlen($xml) > 0){
										$nomexml = $this->estabelecimento->getdirxmlnfe().$chavenfe.LITERALNFE;
										$bytes = file_put_contents($nomexml, $xml);
										chmod($nomexml, 0777);
										if($bytes == 0){
											$_SESSION["ERROR"] = "N&atilde;o foi possivel salvar o xml da NF-e. Tente novamente";
											return FALSE;
										}else{
											return TRUE;
										}
									}else{
										$_SESSION["ERROR"] = "N&atilde;o foi possivel carregar o xml da NF-e. Tente novamente";
									}
								}else{
									$_SESSION["ERROR"] = "O local de importa&ccedil;&atilde;o da NF-e informado no estabelecimento &eacute inv&aacute;lido.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para verificar o local informado";
									return FALSE;
								}
								break;
							case 633:
								$arr_retorno = array();
								$resp = $this->nfeTools->sefazManifesta($chavenfe, $this->ambiente, "", "210200", $arr_retorno);
								if($arr_retorno["bStat"]){
									$xMotivo = $arr_retorno["xMotivo"];
									$cStat = $arr_retorno["cStat"];
									if($cStat == "135"){
										sleep(5);
										if($this->downloadnfesefazphp($chavenfe, $codestabelec)){
											return TRUE;
										}else{
											return FALSE;
										}
									}else{
										$_SESSION["ERROR"] = "N&atilde;o foi possivel realizar o manifesto de confirma&ccedil;&atilde;o da opera&ccedil;&atilde;o. Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
										return FALSE;
									}
								}else{
									$_SESSION["ERROR"] = "N&atilde;o foi possivel realizar o manifesto de confirma&ccedil;&atilde;o da opera&ccedil;&atilde;o. Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
								}
								break;
							default: $_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
								break;
						}
					}else{
						if(isset($arr_retorno["retNFe"])){
							$xMotivo = $arr_retorno["retNFe"]["xMotivo"];
							$cStat = $arr_retorno["retNFe"]["cStat"];
						}else{
							$xMotivo = $arr_retorno["xMotivo"];
							$cStat = $arr_retorno["cStat"];
						}
						if(!empty($xMotivo)){
							$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
						}else{
							$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
						}
					}
					break;
				case self::VERSAO_API5:
					if(!is_object($this->classToolsNFe)){
						$this->carregaconfiguracoes();
						$this->nfeTools = new $this->classToolsNFe($this->aConfig, \NFePHP\Common\Certificate::readPfx($this->carregacertificado(), $this->estabelecimento->getsenhachaveprivada()));
						$this->nfeTools->model("55");
					}

					$retorno = $this->nfeTools->sefazDownload($chavenfe);

					$std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
					$arr_retorno = $std_retorno->toArray();
					if($arr_retorno["cStat"] == "138"){
						$zip = $arr_retorno["loteDistDFeInt"]["docZip"];
						$zipdata = base64_decode($zip);
						$unzip = pGunzip1($zipdata);
						if(!$unzip){
							$_SESSION["ERROR"] = "Download de NF-e"."<br>Houve um erro na descompactaÃ§Ã£o da NF-e";
							return FALSE;
						}else{
							$nomexml = $this->estabelecimento->getdirxmlnfe().$chavenfe."-nfe.xml";
							$bytes = file_put_contents($nomexml, $unzip);
							if($bytes == 0){
								$_SESSION["ERROR"] = "N&atilde;o foi possivel salvar o xml da NF-e. Tente novamente";
								return FALSE;
							}else{
								chmod($nomexml, 0777);
							}
							$tpEvento = "210200";
							$retorno = $this->nfeTools->sefazManifesta($chavenfe, $tpEvento, "");
							$std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
							$arr_retorno = $std_retorno->toArray();
							if($arr_retorno["cStat"] == "128" && isset($arr_retorno["retEvento"]["infEvento"])){
								if(in_array($arr_retorno["retEvento"]["infEvento"]["cStat"], array("573", "135"))){
									$_SESSION["EVENTO"] = $arr_retorno["retEvento"]["infEvento"]["tpEvento"];
								}
							}
							return TRUE;
						}
					}else{
						$_SESSION["ERROR"] = "Download de NF-e"."<br>Status:".$arr_retorno["cStat"]."<br>Motivo:".$arr_retorno["xMotivo"];
						return FALSE;
					}
					break;
			}
		}catch(Exception $ex){
			$msgerr = $ex->getMessage();
			if(!empty($msgerr)){
				$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$ex->getMessage()}";
			}else{
				$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
			}
		}
		return FALSE;
	}

	function downnfesefazphp($chavenfe, $codestabelec){
		$_SESSION["ERROR"] = "";
		$this->estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
		try{
			if(!is_object($this->nfe)){
				$this->nfeTools = new ToolsNFePHP;
			}
			if(is_null($this->ambiente)){
				$paramfiscal = objectbytable("paramfiscal", $codestabelec, $this->con);
				//$this->ambiente = $paramfiscal->getambientenfe();
				$this->ambiente = $this->estabelecimento->getambientenfe();
			}
			$arr_retorno = array();
			$xml = $this->nfeTools->getNFe(TRUE, $chavenfe, $this->ambiente, $arr_retorno);

			if($arr_retorno["bStat"]){
				if(isset($arr_retorno["retNFe"])){
					$xMotivo = $arr_retorno["retNFe"]["xMotivo"];
					$cStat = $arr_retorno["retNFe"]["cStat"];
				}else{
					$xMotivo = $arr_retorno["xMotivo"];
					$cStat = $arr_retorno["cStat"];
				}
				switch($cStat){
					case 140:
						if(is_dir($this->estabelecimento->getdirxmlnfe())){
							if(strlen($xml) > 0){
								$nomexml = $this->estabelecimento->getdirxmlnfe().$chavenfe.LITERALNFE;
								$bytes = file_put_contents($nomexml, $xml);
								if($bytes == 0){
									$_SESSION["ERROR"] = "N&atilde;o foi possivel salvar o xml da NF-e. Tente novamente";
									return FALSE;
								}else{
									return TRUE;
								}
							}else{
								$_SESSION["ERROR"] = "N&atilde;o foi possivel carregar o xml da NF-e. Tente novamente";
							}
						}else{
							$_SESSION["ERROR"] = "O local de importa&ccedil;&atilde;o da NF-e informado no estabelecimento &eacute inv&aacute;lido.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para verificar o local informado";
							return FALSE;
						}
						break;
					case 633:
						$arr_retorno = array();
						$resp = $this->nfeTools->manifDest($chavenfe, "00", "", $this->ambiente, $arr_retorno);
						if($arr_retorno["bStat"]){
							$xMotivo = $arr_retorno["xMotivo"];
							$cStat = $arr_retorno["cStat"];
							if($cStat == "135"){
								if($this->downloadnfesefazphp($chavenfe, $codestabelec)){
									return TRUE;
								}else{
									return FALSE;
								}
							}else{
								$_SESSION["ERROR"] = "N&atilde;o foi possivel realizar o manifesto de confirma&ccedil;&atilde;o da opera&ccedil;&atilde;o. Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
								return FALSE;
							}
						}else{
							$_SESSION["ERROR"] = "N&atilde;o foi possivel realizar o manifesto de confirma&ccedil;&atilde;o da opera&ccedil;&atilde;o. Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
						}
						break;
					default: $_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
						break;
				}
			}else{
				if(isset($arr_retorno["retNFe"])){
					$xMotivo = $arr_retorno["retNFe"]["xMotivo"];
					$cStat = $arr_retorno["retNFe"]["cStat"];
				}else{
					$xMotivo = $arr_retorno["xMotivo"];
					$cStat = $arr_retorno["cStat"];
				}
				if(!empty($xMotivo)){
					$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$cStat}-{$xMotivo}";
				}else{
					$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
				}
			}
		}catch(Exception $ex){
			$msgerr = $ex->getMessage();
			if(!empty($msgerr)){
				$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$ex->getMessage()}";
			}else{
				$_SESSION["ERROR"] = "Download n&atilde;o efetuado\n{$this->nfeTools->soapDebug}";
			}
		}
		return FALSE;
	}

	function enviaremailnfe($copiasemails = ""){
		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		/* if(!$this->ativo()){
		  $_SESSION["ERROR"] = "N&atilde;o foi possivel enviar email da NF-e. Verifique se o aplicativo transmissor esta ativo!";
		  return FALSE;
		  } */

		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				break;
		}

		if($this->notafiscal->gettipoparceiro() == "C"){
			if(strlen(trim($parceiro->getemailnfe())) > 0){
				$email = $parceiro->getemailnfe();
			}else{
				$email = $parceiro->getemail();
			}
		}else{
			$email = $parceiro->getemail();
		}
		if(strlen(trim($email)) == 0){
			$_SESSION["EMAILNFE"] = "O destinatÃ¡rio nÃ£o possui email informado";
			return FALSE;
		}
		$chavenfe = $this->notafiscal->getchavenfe();

		if($this->tipocomunicacao == TXT){
			$dirnfe = $this->estabelecimento->getdirimportnfe();
			echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAIL("'.$email.'","'.$chavenfe.LITERALNFE.'",1,"","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			if((is_bool($ok)) && ($ok)){
				$_SESSION["EMAILNFE"] = "Email enviado para o destinat&aacute;rio com sucesso!";
				return TRUE;
			}else{
				if((is_bool($ok)) && (!$ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar o email ao destinat&aacute;rio. Verifique se o aplicativo transmissor esta ativo!";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar o email ao destinat&aacute;rio. <br>Erro ocorrido: ".$ok;
				}
				return FALSE;
			}
		}else{
			$retorno = $this->nfemonitor->enviarNFeEmail($email, $chavenfe.LITERALNFE, 1, "", $copiasemails);
			if(is_bool($retorno) && $retorno){
				$_SESSION["EMAILNFE"] = "Email enviado para o destinat&aacute;rio com sucesso!";
				return TRUE;
			}else{
				if(is_bool($retorno) && !$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar o email. Verifique se o aplicativo transmissor esta ativo!";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar o email!<br>Erro ocorrido:".utf8_encode($retorno);
				}
				return FALSE;
			}
		}
	}

	function enviaremailevento($documento, $copiasemails = ""){
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar email ".($this->notafiscal->gettipoevento() == EVENTOCANCELAMENTO ? "do cancelamento" : " da CC-e").". Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				break;
		}

		if($this->notafiscal->gettipoevento() == EVENTOCANCELAMENTO){
			$msgerror = "N&atilde;o foi possivel enviar o cancelamento para o cliente";
			if($documento == "A"){
				$msgok = "NF-e e Cancelamento enviado para o cliente com sucesso!";
			}else{
				$msgok = "Cancelamento enviado para o cliente com sucesso!";
			}
		}else{
			$msgerror = "N&atilde;o foi possivel enviar a CC-e para o cliente";
			if($documento == "A"){
				$msgok = "NF-e e CC-e enviada para o cliente com sucesso!";
			}else{
				$msgok = "CC-e enviada para o cliente com sucesso!";
			}
		}

		$email = $parceiro->getemail();
		$chavenfe = $this->notafiscal->getchavenfe();
		$nevento = $this->notafiscal->getsequenciaevento();
		$numnotafis = $this->notafiscal->getnumnotafis();
		$AnoMesEvento = "20".substr($chavenfe, 2, 4);
		if($this->tipocomunicacao == TXT){
			$dirnfe = $this->estabelecimento->getdirimportnfe();
			if($documento == "A"){
				if($this->notafiscal->gettipoevento() == EVENTOCCE){
					//echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.$chavenfe.EVENTOCCE.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$chavenfe.LITERALNFE.'",1,"Informamos que a NF-e '.$numnotafis.' juntamente com a CC-e segue anexado neste email","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
					echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.EVENTOCCE.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$chavenfe.LITERALNFE.'",1,"Informamos que a NF-e '.$numnotafis.' juntamente com a CC-e segue anexado neste email","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				}else{
					//echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.$chavenfe.EVENTOCANCELAMENTO.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$chavenfe.LITERALNFE.'",1,"Informamos que a NF-e '.$numnotafis.' juntamente com o protocolo de cancelamento segue anexado neste email","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
					echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.EVENTOCANCELAMENTO.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$chavenfe.LITERALNFE.'",1,"Informamos que a NF-e '.$numnotafis.' juntamente com o protocolo de cancelamento segue anexado neste email","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				}
			}else{
				if($this->notafiscal->gettipoevento() == EVENTOCCE){
					//echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.$chavenfe.EVENTOCCE.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","",1,"Informamos que a CCE-e referente a NF-e'.$numnotafis.' segue anexada neste email","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
					echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.EVENTOCCE.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","",1,"Informamos que a CCE-e referente a NF-e'.$numnotafis.' segue anexada neste email","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				}else{
					//echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.$chavenfe.EVENTOCANCELAMENTO.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","",1,"Informamos que a NF-e '.$numnotafis.' foi cancelada conforme protocolo anexado neste email","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
					echo write_file($dirnfe.ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$email.'","'.EVENTOCANCELAMENTO.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","",1,"Informamos que a NF-e '.$numnotafis.' foi cancelada conforme protocolo anexado neste email","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				}
			}
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			if((is_bool($ok)) && ($ok)){
				$_SESSION["EMAILEVENTO"] = $msgok;
				return TRUE;
			}else{
				$_SESSION["ERROR"] = $msgerror;
				return FALSE;
			}
		}else{
			if($documento == "A"){
				if($this->notafiscal->gettipoevento() == EVENTOCCE){
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, $chavenfe.EVENTOCCE.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, $chavenfe.LITERALNFE, 1, "Informamos que a NF-e ".$numnotafis." juntamente com a CC-e segue anexado neste email", $copiasemails);
					$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCCE.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, $chavenfe.LITERALNFE, 1, "Informamos que a NF-e ".$numnotafis." juntamente com a CC-e segue anexado neste email", $copiasemails, $AnoMesEvento);
				}else{
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, $chavenfe.EVENTOCANCELAMENTO.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, $chavenfe.LITERALNFE, 1, "Informamos que a NF-e ".$numnotafis." juntamente com o protocolo de cancelamento segue anexado neste email", $copiasemails);
					$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCANCELAMENTO.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, $chavenfe.LITERALNFE, "", 1, "Informamos que a NF-e ".$numnotafis." juntamente com o protocolo de cancelamento segue anexado neste email", $copiasemails, $AnoMesEvento);
				}
			}else{
				if($this->notafiscal->gettipoevento() == EVENTOCCE){
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, $chavenfe.EVENTOCCE.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a CC-e referente NF-e".$numnotafis." segue anexado neste email", $copiasemails);
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCCE.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a CC-e referente NF-e".$numnotafis." segue anexado neste email", $copiasemails);
					$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCCE.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a CC-e referente NF-e".$numnotafis." segue anexado neste email", $copiasemails, $AnoMesEvento);
				}else{
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, $chavenfe.EVENTOCANCELAMENTO.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a NF-e ".$numnotafis." foi cancelada conforme protocolo anexado neste email", $copiasemails);
					//$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCANCELAMENTO.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a NF-e ".$numnotafis." foi cancelada conforme protocolo anexado neste email", $copiasemails);
					$retorno = $this->nfemonitor->enviarEmailEvento($email, EVENTOCANCELAMENTO.$chavenfe.str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", 1, "Informamos que a NF-e ".$numnotafis." foi cancelada conforme protocolo anexado neste email", $copiasemails, $AnoMesEvento);
				}
			}
			if(is_bool($retorno) && $retorno){
				$_SESSION["EMAILEVENTO"] = $msgok;
				return TRUE;
			}else{
				$_SESSION["ERROR"] = $msgerror;
				return FALSE;
			}
		}
	}

	function imprimirevento(){
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}

		if($this->notafiscal->gettipoevento() == EVENTOCANCELAMENTO){
			$msgok = "Cancelamento enviado para impressora com sucesso!";
			$msgerror = "N&atilde;o foi possivel imprimir o cabcelamento. Verifique se o aplicativo transmissor esta ativo!";
		}else{
			$msgok = "CC-e enviada para impressora com sucesso!";
			$msgerror = "N&atilde;o foi possivel imprimir a CC-e. Verifique se o aplicativo transmissor esta ativo!";
		}
		$AnoMesEvento = "20".substr($this->notafiscal->getchavenfe(), 2, 4);
		if($this->tipocomunicacao == TXT){
			//echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT,'NFE.IMPRIMIREVENTO("'.$chavenfe.$tipoevento.str_pad($sequencialevento, 2,"0", STR_PAD_LEFT).LITERALEVENTO.'")',(param("SISTEMA","TIPOSERVIDOR",$this->con) == "0"));
			if($this->modoimpressao == "1"){
				//echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIREVENTO("'.$this->notafiscal->getchavenfe().$this->notafiscal->gettipoevento().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIREVENTO("'.$this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","","","","","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				for($i = 0; $i < 6; $i++){
					sleep(10);
					$ok = $this->nfemonitorok(FALSE);
					if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
						break;
					}
				}
				if((is_bool($ok)) && ($ok)){
					$_SESSION["IMPRIMIREVENTO"] = $msgok;
					return TRUE;
				}else{
					if((is_bool($ok)) && (!$ok)){
						$_SESSION["ERROR"] = $msgerror;
					}else{
						if($tipoevento == EVENTOCANCELAMENTO){
							$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir cancelamento.<br>Erro ocorrido: ".$ok;
						}else{
							$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a CC-e.<br>Erro ocorrido: ".$ok;
						}
					}
					return FALSE;
				}
			}else{
				if($this->imprimireventopdf("E")){
					$arquivopdf = "window.open('../temp/pdfnfe/".$this->nomepdfevento."');";
					echo script($arquivopdf);
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}else{
			if($this->modoimpressao == "1"){
				$retorno = $this->nfemonitor->imprimirEventoNFe($this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", "", "", "", $AnoMesEvento0);
				if(is_bool($retorno) && $retorno){
					$_SESSION["IMPRIMIREVENTO"] = $msgok;
					return TRUE;
				}else{
					if(is_bool($retorno) && !$retorno){
						$_SESSION["ERROR"] = $msg;
					}else{
						if($tipoevento == EVENTOCANCELAMENTO){
							$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir o cancelamento.<br>Erro ocorrido: ".utf8_encode($retorno);
						}else{
							$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a CC-e.<br>Erro ocorrido: ".utf8_encode($retorno);
						}
					}
					return FALSE;
				}
			}else{
				if($this->imprimireventopdf("E")){
					$arquivopdf = "window.open('../temp/pdfnfe/".$this->nomepdfevento."');";
					echo script($arquivopdf);
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}
	}

	function imprimirdanfepdf($atualizarsession = TRUE){
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF da NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIRDANFEPDF("'.$this->notafiscal->getchavenfe().LITERALNFE.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				$ok = substr($arr_resp[0], 3, strlen($arr_resp[0]) - 3);
				if($atualizarsession){
					$_SESSION["PDFNFE"] = "PDF da NF-e gerado com sucesso!<br>".str_replace("\\", "\\\\", $ok);
				}
				return TRUE;
			}else{
				if($atualizarsession){
					if((is_bool($ok)) && (!$ok)){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF da NF-e. Verifique se o aplicativo transmissor esta ativo!";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF da NF-e.<br>".$ok;
					}
				}
				return FALSE;
			}
		}else{
			$retorno = $this->nfemonitor->imprimirDanfePDF($this->notafiscal->getchavenfe().LITERALNFE);
			if(is_array($retorno)){
				$retorno = str_replace("\\", "\\\\", $retorno["OK"]);
				if($atualizarsession){
					$_SESSION["PDFNFE"] = "PDF da NF-e gerado com sucesso!<br>".$retorno;
				}
				return TRUE;
			}else{
				if($atualizarsession){
					if(is_bool($retorno) && !$retorno){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF da NF-e. Verifique se o aplicativo transmissor esta ativo!";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF da NF-e.<br>".htmlentities($retorno);
					}
				}
				return FALSE;
			}
		}
	}

	function imprimireventopdf($documento){
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF do evento. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		$AnoMesevento = "20".substr($this->notafiscal->getchavenfe(), 2, 4);
		if($this->tipocomunicacao == TXT){
			if($documento == "A"){
				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIREVENTOPDF("'.$this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$this->notafiscal->getchavenfe().LITERALNFE.'","","","","'.$AnoMesevento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			}else{
				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIREVENTOPDF("'.$this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","","","","","'.$AnoMesevento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			}
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				$ok = substr($arr_resp[2], 13, strlen($arr_resp[2]) - 13);
				$ok .= '<br>'.substr($arr_resp[4], 10, strlen($arr_resp[4]) - 10);
				$this->nomepdfevento = substr($arr_resp[3], 15, strlen($arr_resp[3]) - 15);
				$this->nomepdfnfe = substr($arr_resp[5], 12, strlen($arr_resp[5]) - 12);
				if($documento == "A"){
					$_SESSION["PDFNFE"] = "PDF do danfe e do evento  gerado com sucesso!<br>".str_replace("\\", "\\\\", $ok);
				}else{
					$_SESSION["PDFNFE"] = "PDF de evento gerado com sucesso!<br>".str_replace("\\", "\\\\", $ok);
				}
				return TRUE;
			}else{
				if((is_bool($ok)) && (!$ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF do evento. Verifique se o aplicativo transmissor esta ativo!";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF do evento.<br>".utf8_encode($ok);
				}
				return FALSE;
			}
		}else{
			if($documento == "A"){
				$retorno = $this->nfemonitor->imprimirEventoPDF($this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO, $this->notafiscal->getchavenfe().LITERALNFE, $AnoMesevento);
			}else{
				$retorno = $this->nfemonitor->imprimirEventoPDF($this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO, "", $AnoMesevento);
			}
			if(is_array($retorno)){
				$this->nomepdfevento = $this->notafiscal->gettipoevento().$this->notafiscal->getchavenfe().str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTOPDF;
				//$this->nomepdfnfe = $retorno["OK"]["EVENTOPDF"]["xArquivoNFe"];
				if($documento == "A"){
					$_SESSION["PDFNFE"] = "PDF do danfe e do evento gerado com sucesso!<br>".$localarquivo;
				}else{
					$_SESSION["PDFNFE"] = "PDF do evento gerado com sucesso!<br>".$localarquivo;
				}
				return TRUE;
			}else{
				if(is_bool($retorno) && !$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF do evento. Verifique se o aplicativo transmissor esta ativo!";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar PDF do evento.<br>".utf8_encode($retorno);
				}
				return FALSE;
			}
		}
	}

	function imprimirnfe(){
		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		if($this->tipocomunicacao == TXT){
			if($this->modoimpressao == "1"){
				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.IMPRIMIRDANFE("'.$this->notafiscal->getchavenfe().LITERALNFE.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				$ok = FALSE;
				for($i = 0; $i < 6; $i++){
					sleep(10);
					$ok = $this->nfemonitorok(FALSE);
					if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
						break;
					}
				}
				if((is_bool($ok)) && ($ok)){
					$_SESSION["IMPRIMIRNFE"] = "NF-e enviada para impressora com sucesso!";
					return TRUE;
				}else{
					if((is_bool($ok)) && (!$ok)){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e. Verifique se o aplicativo transmissor esta ativo!";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e.<br>Erro ocorrido: ".$ok;
					}
					return FALSE;
				}
			}else{
				if($this->imprimirdanfepdf(FALSE)){
					if(file_exists("../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf")){
						$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf');";
					}else{
						$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe()."-nfe.pdf');";
					}
					echo script($arquivopdf);
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}else{
			if($this->modoimpressao == "1"){
				$retorno = $this->nfemonitor->imprimirDanfe($this->notafiscal->getchavenfe().LITERALNFE);
				if(is_bool($retorno) && $retorno){
					$_SESSION["IMPRIMIRNFE"] = "NF-e enviada para impressora com sucesso!";
					return TRUE;
				}else{
					if(is_bool($retorno) && !$retorno){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e. Verifique se o aplicativo transmissor esta ativo!";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel imprimir a NF-e.<br>Erro ocorrido: ".htmlentities($retorno);
					}
					return FALSE;
				}
			}else{
				if($this->imprimirdanfepdf(FALSE)){
					if(file_exists("../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf")){
						$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf');";
					}else{
						$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe()."-nfe.pdf');";
					}
					echo script($arquivopdf);
					return TRUE;
				}else{
					return FALSE;
				}
			}
		}
	}

	function inutilizarnfe($justificativa){
		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o f possivel inutilizar o numero da NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		$anonfe = date("y");
		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.INUTILIZARNFE("'.removeformat($this->estabelecimento->getcpfcnpj()).'","'.$justificativa.'",'.$anonfe.',55,'.$this->notafiscal->getserie().','.$this->notafiscal->getnumnotafis().','.$this->notafiscal->getnumnotafis().')', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));

			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			$retorno = FALSE;
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				unlink($fileresp);
				$nStat = 0;
				foreach($arr_resp as $linha_resp){
					if(!$retorno){
						$retorno = strtoupper($linha_resp) == "[INUTILIZACAO]";
					}
					if($retorno){
						if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
							$nStat = substr($linha_resp, 6);
						}
						if($nStat == 102){
							$_SESSION["INUTILIZACAO"] = "Inutliza&ccedil;&atilde;o do numero ".$numeronf." homologado com sucesso.";
							$this->notafiscal->setstatus("I");
							$this->notafiscal->save();
							return TRUE;
						}
					}
				}
				if(!$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a inutilizac&ccedil;&atilde;ao. FaÃƒÂ§a a inutilizac&ccedil;&atilde;o novamente.";
					return FALSE;
				}
			}else{
				if(is_bool($ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a a consulta da NF-e novamente.";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel inutilizar o numero no SEFAZ.\n".htmlentities($ok);
				}
				return FALSE;
			}
		}else{
			$retorno = $this->nfemonitor->inutilizarNFe(removeformat($this->estabelecimento->getcpfcnpj()), $justificativa, $this->notafiscal->getserie(), $this->notafiscal->getnumnotafis(), $this->notafiscal->getnumnotafis());
			if(is_array($retorno)){
				$arr_retorno = $retorno["OK"]["INUTILIZACAO"];
				if(is_array($arr_retorno)){
					$nStat = $arr_retorno["CStat"];
					if($nStat == 102){
						$_SESSION["INUTILIZACAO"] = "Inutliza&ccedil;&atilde;o do numero ".$numeronf." homologado com sucesso.";
						$this->notafiscal->setstatus("I");
						$this->notafiscal->save();
						return TRUE;
					}
				}else{

				}
			}else{
				$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), htmlentities($retorno));
				$_SESSION["ERROR"] = $retorno;
				return FALSE;
			}
		}
	}

	function cancelarnfe($chavenfe, $justificativa, $copiasemails){
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel consultar a NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}

		$lancamento = objectbytable("lancamento", null, $this->con);
		$lancamento->setidnotafiscal($this->notafiscal->getidnotafiscal());
		$arr_lancamento = object_array($lancamento);
		foreach($arr_lancamento as $lancamento){
			if($lancamento->getstatus() == "L" || $lancamento->getstatus() == "R"){
				$_SESSION["ERROR"] = "N&atilde;o &eacute possivel cancelar uma NF-e com lan&ccedil;amentos liquidados><br>Fa&ccedil;a a libera&ccedil;&atilde;o dos mesmos e tente novamente.";
				return FALSE;
			}
		}

		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				break;
		}

		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.CANCELARNFE("'.$chavenfe.'","'.$justificativa.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));

			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			$retorno = FALSE;
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				unlink($fileresp);
				$nStat = 0;
				$xmotivo = "";
				$nfecancelada = FALSE;
				foreach($arr_resp as $linha_resp){
					if(!$retorno){
						$retorno = strtoupper($linha_resp) == "[CANCELAMENTO]";
					}
					if($retorno){
						if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
							$nStat = substr($linha_resp, 6);
							$this->notafiscal->setcodigostatus($nStat);
						}
						if(substr(strtoupper($linha_resp), 0, 7) == "XMOTIVO"){
							$xmotivo = substr($linha_resp, 8);
							$this->notafiscal->setxmotivo($xmotivo);
						}
						if(substr(strtoupper($linha_resp), 0, 5) == "CHNFE"){
							$this->notafiscal->setchavenfe(substr($linha_resp, 6));
						}
						if(substr(strtoupper($linha_resp), 0, 5) == "NPROT"){
							$this->notafiscal->setprotocolonfe(substr($linha_resp, 6));
						}
					}
				}

				switch($nStat){
					case 101:
					case 135:
					case 151:
					case 155:
						$seqevento = $this->notafiscal->getsequenciaevento() + 1;
						$tpevento = EVENTOCANCELAMENTO;
						$this->notafiscal->setsequenciaevento($seqevento);
						$this->notafiscal->settipoevento($tpevento);
						$this->notafiscal->setcodigostatus("101");
						$this->notafiscal->setstatus("C"); //cancelada
						$this->notafiscal->setdatacancelamento(date("Y-m-d"));
						$nfecancelada = TRUE;
						$_SESSION["CANCELARNFE"] = "Cancelamento da NF-e homologado com sucesso no SEFAZ"."<br>".$nStat." - ".$xmotivo;
						break;
					default:
						$_SESSION["ERROR"] = "N&atilde;o foi possivel homologar o cancelamento da NF-e no SEFAZ"."<br>".$nStat." - ".$xmotivo;
				}
				$AnoMesEvento = "20".substr($chavenfe, 2, 4);
				if(!$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer cancelamento novamente.";
					return FALSE;
				}else{
					if(($retorno) && ($nfecancelada) && ($this->notafiscal->save())){
						echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$parceiro->getemail().'","'.$this->notafiscal->gettipoevento().$chavenfe.str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$chavenfe.LITERALNFE.'",1,"Informamos que a NF-e '.$this->notafiscal->getnumnotafis().' foi cancelada conforme protocolo anexado neste email","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
						$ok = FALSE;
						for($i = 0; $i < 6; $i++){
							sleep(10);
							$ok = $this->nfemonitorok(FALSE);
							if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
								break;
							}
						}
						if((is_bool($ok)) && ($ok)){
							$_SESSION["CANCELARNFE"] = $_SESSION["CANCELARNFE"]."<br>Cancelamento enviado para o cliente com sucesso!";
							return TRUE;
						}else{
							$_SESSION["CANCELARNFE"] = $_SESSION["CANCELARNFE"]."<br>N&atilde;o foi possivel enviar o cancelamento para o cliente";
						}
						return TRUE;
					}else{
						return FALSE;
					}
				}
			}else{
				if(is_bool($ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. Fa&ccedil;a a consulta da NF-e ou tente enviar o cancelamento novamente.";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar o cancelamento da NF-e ao SEFAZ.\n".htmlentities($ok);
				}
				return FALSE;
			}
		}else{
			$retorno = $this->nfemonitor->cancelarNFe($chavenfe, $justificativa);
			$nfecancelada = FALSE;
			if(is_array($retorno)){
				$arr_retorno = $retorno["OK"]["CANCELAMENTO"];
				if(is_array($arr_retorno)){
					$this->notafiscal->setxmotivo("Cancelamento de NF-e homologado");
					$this->notafiscal->setchavenfe($arr_retorno["ChNFe"]);
					$this->notafiscal->setprotocolonfe($arr_retorno["NProt"]);
					$nStat = $arr_retorno["CStat"];
					switch($nStat){
						case 101:
						case 135:
						case 151:
						case 155:
							$this->notafiscal->setsequenciaevento($this->notafiscal->getsequenciaevento() + 1);
							$this->notafiscal->settipoevento(EVENTOCANCELAMENTO);
							$this->notafiscal->setcodigostatus("101");
							$this->notafiscal->setstatus("C"); //cancelada
							$this->notafiscal->setdatacancelamento(date("Y-m-d"));
							$nfecancelada = TRUE;
							$_SESSION["CANCELARNFE"] = "Cancelamento da NF-e homologado com sucesso no SEFAZ"."<br>".$nStat." - Cancelamento de NF-e homologado";
							break;
						default:
							$_SESSION["ERROR"] = "N&atilde;o foi possivel homologar o cancelamento da NF-e no SEFAZ"."<br>".$nStat." - ".$arr_retorno["XMotivo"];
					}
					if(($nfecancelada) && ($this->notafiscal->save())){
						$retorno = $this->nfemonitor->enviarEmailEvento($parceiro->getemail(), $this->notafiscal->gettipoevento().$chavenfe.str_pad($this->notafiscal->getsequenciaevento(), 2, "0", STR_PAD_LEFT).LITERALEVENTO, $chavenfe.LITERALNFE, 1, "Informamos que a NF-e ".$this->notafiscal->getnumnotafis()." foi cancelada conforme protocolo anexado neste email", $copiasemails, $AnoMesEvento);
						if(is_bool($retorno) && $retorno){
							$_SESSION["CANCELARNFE"] = $_SESSION["CANCELARNFE"]."<br>Cancelamento enviado para cliente com sucesso";
						}else{
							$_SESSION["CANCELARNFE"] = $_SESSION["CANCELARNFE"]."<br>N&atilde;o foi possivel enviar o cancelamento para cliente";
						}
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
					return FALSE;
				}
			}else{
				$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), htmlentities($retorno));
				$_SESSION["ERROR"] = $retorno;
				return FALSE;
			}
		}
	}

	function consultarnfe($chavenfe){
		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel consultar a NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		if(strlen($chavenfe) == 0){
			$this->notafiscal->calcular_chavenfe();
			$chavenfe = $this->notafiscal->getchavenfe();
		}
		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.CONSULTARNFE("'.$chavenfe.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));

			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			$retorno = FALSE;
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				unlink($fileresp);
				$nStat = 0;
				$xmotivo = "";
				foreach($arr_resp as $linha_resp){
					if(!$retorno){
						$retorno = strtoupper($linha_resp) == "[CONSULTA]";
					}
					if($retorno){
						if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
							$nStat = substr($linha_resp, 6);
							$this->notafiscal->setcodigostatus($nStat);
						}
						if(substr(strtoupper($linha_resp), 0, 7) == "XMOTIVO"){
							$xmotivo = substr($linha_resp, 8);
							$this->notafiscal->setxmotivo($xmotivo);
						}
						if(substr(strtoupper($linha_resp), 0, 5) == "CHNFE"){
							$this->notafiscal->setchavenfe(substr($linha_resp, 6));
						}
						if(substr(strtoupper($linha_resp), 0, 5) == "NPROT"){
							$this->notafiscal->setprotocolonfe(substr($linha_resp, 6));
						}
					}
				}
				switch($nStat){
					case 100:
					case 150:
						$this->notafiscal->setdataautorizacao(date("Y-m-d"));
						$this->notafiscal->setstatus("A"); //autorizada
						break;
					case 101:
					case 151:
					case 155:
						$this->notafiscal->setstatus("C"); //cancelada
						if($this->notafiscal->getsequenciaevento() == 0){
							$this->notafiscal->setsequenciaevento($this->notafiscal->getsequenciaevento() + 1);
							$this->notafiscal->settipoevento(EVENTOCANCELAMENTO);
							$this->notafiscal->setdatacancelamento(date("Y-m-d"));
						}
						break;
					case 102:
					case 302:
						$this->notafiscal->setstatus("D"); //inutilizada
						break;
					case 110:
						$this->notafiscal->setstatus("D"); //denegada
						break;
					default:
						//$this->notafiscal->setstatus("P"); //pendente
						break;
				}

				$_SESSION["CONSULTARNFE"] = "NF-e consultada com sucesso no SEFAZ"."<br>".$nStat." - ".$xmotivo;
				if(!$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. F&ccedil;a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
					return FALSE;
				}else{
					if(($retorno) && ($this->notafiscal->save())){
						return TRUE;
					}else{
						return FALSE;
					}
				}
			}else{
				if(is_bool($ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. F&ccedil;a a consulta da NF-e novamente.";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel consultar a NF-e ao SEFAZ.\n".htmlentities($ok);
				}
				return FALSE;
			}
		}else{
			$retorno = $this->nfemonitor->consultarNFe($chavenfe);
			if(is_array($retorno)){
				$arr_retorno = $retorno["OK"]["CONSULTA"];
				if(is_array($arr_retorno)){
					$this->notafiscal->setcodigostatus($arr_retorno["CStat"]);
					$this->notafiscal->setxmotivo($arr_retorno["XMotivo"]);
					$this->notafiscal->setchavenfe($arr_retorno["ChNFe"]);
					$this->notafiscal->setprotocolonfe($arr_retorno["NProt"]);
					$nStat = $arr_retorno["CStat"];
					switch($nStat){
						case 100:
						case 150:
							$this->notafiscal->setdataautorizacao(date("Y-m-d"));
							$this->notafiscal->setstatus("A"); //autorizada
							break;
						case 110:
						case 302:
							$this->notafiscal->setstatus("D"); //denegada
							break;
						case 101:
						case 151:
						case 155:
							$this->notafiscal->setstatus("C"); //cancelada
							if($this->notafiscal->getsequenciaevento() == 0){
								$this->notafiscal->setsequenciaevento($this->notafiscal->getsequenciaevento() + 1);
								$this->notafiscal->settipoevento(EVENTOCANCELAMENTO);
								$this->notafiscal->setdatacancelamento(date("Y-m-d"));
							}
							break;
						case 102:

							break;
						default:
							//$this->notafiscal->setstatus("P"); //pendente
							break;
					}
					$_SESSION["CONSULTARNFE"] = "NF-e consultada com sucesso ao SEFAZ"."<br>".$arr_retorno["CStat"]." - ".$arr_retorno["XMotivo"];
					if($this->notafiscal->save()){
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
					return FALSE;
				}
			}else{
				$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), htmlentities($retorno));
				$_SESSION["ERROR"] = $retorno;
				return FALSE;
			}
		}
	}

	function emitirccenfe($textocce, $copiasemails){
		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel transmitir a CC-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}
		$_SESSION["CCE"] = "";
		$_SESSION["ERROR"] = "";

		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				break;
		}

		$ufestabelcimento = objectbytable("estado", $this->estabelecimento->getuf(), $this->con);
		$codigooficial = $ufestabelcimento->getcodoficial();
		$nevento = $this->notafiscal->getsequenciaevento() + 1;
		$cnpjdest = $this->estabelecimento->getcpfcnpj();
		$chavenfe = $this->notafiscal->getchavenfe();
		$AnoMesEvento = "20".substr($chavenfe, 2, 4);
		if($this->tipocomunicacao == TXT){
			$this->enviareventosefaz($chavenfe, $cnpjdest, EVENTOCCE, $codigooficial, "CARTA DE CORRECAO", $textocce, "", $nevento);
			$ok = FALSE;
			for($i = 0; $i < 6; $i++){
				sleep(10);
				$ok = $this->nfemonitorok(FALSE);
				if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
					break;
				}
			}
			$retorno = FALSE;
			if((is_bool($ok)) && ($ok)){
				$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
				$arr_resp = explode("\r\n", file_get_contents($fileresp));
				unlink($fileresp);
				$nStat = 0;
				$xmotivo = "";

				foreach($arr_resp as $linha_resp){
					if(!$retorno){
						$retorno = strtoupper($linha_resp) == "[EVENTO001]";
					}
					if($retorno){
						if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
							$nStat = substr($linha_resp, 6);
						}
						if(substr(strtoupper($linha_resp), 0, 7) == "XMOTIVO"){
							$xmotivo = substr($linha_resp, 8);
						}
					}
				}
				if(!$retorno){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a CC-e. F&ccedil;a o envio da CC-e novamente.";
					return FALSE;
				}

				$retorno = FALSE;
				switch($nStat){
					case 135:
						$this->notafiscal->setsequenciaevento($nevento);
						$this->notafiscal->settipoevento(EVENTOCCE);
						$this->notafiscal->setcce($textocce);
						$_SESSION["CCE"] = "CC-e Enviada com sucesso ao SEFAZ"."<br>".$nStat." - ".$xmotivo;
						$retorno = TRUE;
						break;
					case 136:
						$_SESSION["CCE"] = "CC-e Enviada com sucesso ao SEFAZ porem n&atilde;o foi possivel anexa-lo a NF-e"."<br>".$nStat." - ".$xmotivo;
						$this->notafiscal->setsequenciaevento($nevento);
						$this->notafiscal->settipoevento(EVENTOCCE);
						$this->notafiscal->setcce($textocce);
						$retorno = TRUE;
						break;
					case 573:
						$this->notafiscal->setsequenciaevento($nevento);
						$this->notafiscal->settipoevento(EVENTOCCE);
						$this->notafiscal->setcce($textocce);
						$retorno = TRUE;
						$_SESSION["ERROR"] = $nStat." - ".$xmotivo;
						break;
					default:
						$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar a CC-e no SEFAZ"."<br>".$xmotivo;
						break;
				}
				if($retorno && $this->notafiscal->save() && $nStat == 135){
					echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.ENVIAREMAILEVENTO("'.$parceiro->getemail().'","'."110110".$this->notafiscal->getchavenfe().str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO.'","'.$this->notafiscal->getchavenfe().LITERALNFE.'",1,"","'.$copiasemails.'","'.$AnoMesEvento.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
					$ok = FALSE;
					for($i = 0; $i < 6; $i++){
						sleep(10);
						$ok = $this->nfemonitorok(FALSE);
						if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
							break;
						}
					}
					if((is_bool($ok)) && ($ok)){
						$_SESSION["CCE"] = $_SESSION["CCE"]."<br>Carta de corre&ccedil;&atilde;o enviada para o cliente com sucesso!";
						return TRUE;
					}else{
						$_SESSION["CCE"] = $_SESSION["CCE"]."<br>N&atilde;o foi possivel enviar a Carta de corre&ccedil;&atilde;o para o cliente";
					}
					return TRUE;
				}else{
					return FALSE;
				}
			}else{
				if(is_bool($ok)){
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a CC-e. Fa&ccedil;a o envio da CC-e novamente.";
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar a CC-e ao SEFAZ.\n".htmlentities($ok);
				}
				return FALSE;
			}
		}else{
			$retorno = $this->enviareventosefaz($chavenfe, $cnpjdest, EVENTOCCE, $codigooficial, "CARTA DE CORRECAO", $textocce, "", $nevento);
			if(is_array($retorno)){
				$arr_retorno = $retorno["OK"]["EVENTO001"];
				if(is_array($arr_retorno)){
					$nStat = $arr_retorno['cStat'];
					$xmotivo = $arr_retorno['xMotivo'];
					$retorno = FALSE;
					switch($nStat){
						case 135:
							$this->notafiscal->setsequenciaevento($nevento);
							$this->notafiscal->settipoevento(EVENTOCCE);
							$this->notafiscal->setcce($textocce);
							$_SESSION["CCE"] = "CC-e Enviada com sucesso ao SEFAZ"."<br>".$nStat." - ".$xmotivo;
							$retorno = TRUE;
							break;
						case 136:
							$_SESSION["CCE"] = "CC-e Enviada com sucesso ao SEFAZ porem n&atilde;o foi possivel anexa-lo a NF-e"."<br>".$nStat." - ".$xmotivo;
							$this->notafiscal->setsequenciaevento($nevento);
							$this->notafiscal->settipoevento(EVENTOCCE);
							$this->notafiscal->setcce($textocce);
							$retorno = TRUE;
							break;
						case 573:
							$this->notafiscal->setsequenciaevento($nevento);
							$this->notafiscal->settipoevento(EVENTOCCE);
							$this->notafiscal->setcce($textocce);
							$_SESSION["ERROR"] = $nStat." - ".$xmotivo;
							$retorno = TRUE;
							break;
						default:
							$_SESSION["ERROR"] = "N&atilde;o foi possivel enviar a CC-e no SEFAZ"."<br>".$xmotivo;
							break;
					}
					if($retorno && $this->notafiscal->save() && $nStat == 135){
						$retorno = $this->nfemonitor->enviarEmailEvento($parceiro->getemail(), "110110".$this->notafiscal->getchavenfe().str_pad($nevento, 2, "0", STR_PAD_LEFT).LITERALEVENTO, $this->notafiscal->getchavenfe().LITERALNFE, 1, "", $copiasemails, $AnoMesEvento);
						if(is_bool($retorno) && $retorno){
							$_SESSION["CCE"] = $_SESSION["CCE"]."<br>CC-e enviada para cliente com sucesso";
						}else{
							$_SESSION["CCE"] = $_SESSION["CCE"]."<br>N&atilde;o foi possivel enviar a CC-e para cliente";
						}
						return TRUE;
					}else{
						return FALSE;
					}
				}else{
					$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a a transmiss&atilde;o da CC-e novamente.";
					return FALSE;
				}
			}else{
				$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), utf8_encode($retorno));
				$_SESSION["ERROR"] = $retorno;
				return FALSE;
			}
		}
	}

	function enviareventosefaz($chavenfe, $cnpjdestinatario, $descricaoevento, $tipoevento, $CodigoOrgao, $correcao = "", $justificativa = "", $nevento = 1){
		$cce = array();
		if($this->tipocomunicacao == TXT){
			$cce[] = 'NFe.EnviarEvento("';
		}
		$cce[] = '[Evento]';
		$cce[] = 'idLote='.$nevento;
		$cce[] = '[Evento001]';
		$cce[] = 'chNFe='.$chavenfe;
		$cce[] = 'cOrgao='.$CodigoOrgao;
		$cce[] = 'CNPJ='.removeformat($cnpjdestinatario);
//		$cce[] = 'dhEvento='.date("d/m/y H:i:s");
		$cce[] = 'dhEvento='.date("d/m/y H:i:s", strtotime("-1 hour"));
		$cce[] = 'tpEvento='.$tipoevento;
		$cce[] = 'nSeqEvento='.$nevento;
		$cce[] = 'versaoEvento=1.00';
		$cce[] = 'descEvento='.$descricaoevento;
		$cce[] = 'xCorrecao='.$correcao;
		$cce[] = 'xCondUso=';
		$cce[] = 'nProt=';
		$cce[] = 'xJust="'.$justificativa.')';
		if($this->tipocomunicacao == TXT){
			echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, $cce, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
			return TRUE;
		}else{
			$retorno = $this->nfemonitor->enviarEvento(implode("\n", $cce));
			return $retorno;
		}
	}

	function criarenviarnfe($imprimirnfe, $copiasemails = ""){
		$param_notafiscal_nfedescoucomp = param("NOTAFISCAL", "NFEDESCOUCOMP", $this->con);
		$param_notafiscal_descricaocomplmento = param("NOTAFISCAL", "DESCRICAOCOMPLEMENTO", $this->con);

		// Carrega os itens da nota fiscal
		if(!is_object($this->notacomplemento)){
			if($this->notafiscal->flag_itnotafiscal){
				$arr_itnotafiscal_aux = $this->notafiscal->itnotafiscal;
			}else{
				$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
				$itnotafiscal->setidnotafiscal($this->notafiscal->getidnotafiscal());
				$arr_itnotafiscal_aux = object_array($itnotafiscal);
			}
			$arr_itnotafiscal = array();
			foreach($arr_itnotafiscal_aux as $itnotafiscal){
				$arr_itnotafiscal[$itnotafiscal->getiditnotafiscal()] = $itnotafiscal;
			}

			// Busca as composicoes
			$arr_composicao = array();
			$composicao = objectbytable("composicao", NULL, $this->con);
			$arr_composicao_aux = object_array($composicao);
			foreach($arr_composicao_aux as $composicao){
				$arr_composicao[$composicao->getcodproduto()] = $composicao;
			}

			// Remove os itens pais/filhos de composicao
			$arr_itnotafiscal_aux = $arr_itnotafiscal;
			foreach($arr_itnotafiscal as $i => $itnotafiscal){
				if($itnotafiscal->getcomposicao() == "P"){
					$composicao = $arr_composicao[$itnotafiscal->getcodproduto()];
					if(is_object($composicao)){
						if($composicao->getexplosaoauto() == "S"){
							unset($arr_itnotafiscal[$i]);
						}
					}
				}elseif($itnotafiscal->getcomposicao() == "F"){
					$composicao = $arr_composicao[$arr_itnotafiscal_aux[$itnotafiscal->getiditnotafiscalpai()]->getcodproduto()];
					if(is_object($composicao)){
						if($composicao->getexplosaoauto() == "N"){
							unset($arr_itnotafiscal[$i]);
						}
					}
				}
			}

			// Remove os itens que estao com quantidade zero
			foreach($arr_itnotafiscal as $i => $itnotafiscal){
				if($itnotafiscal->getquantidade() * $itnotafiscal->getqtdeunidade() == 0 && $itnotafiscal->getoperacao() != "AN"){
					unset($arr_itnotafiscal[$i]);
				}
			}
		}

		//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
		$paramfiscal = objectbytable("paramfiscal", $this->notafiscal->getcodestabelec(), $this->con);
		$cidade_estabelecimento = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);
		$estado_estabelecimento = objectbytable("estado", $cidade_estabelecimento->getuf(), $this->con);
		$pais_estabelecimento = objectbytable("pais", "01058", $this->con);
		$natoperacao = objectbytable("natoperacao", (is_object($this->notacomplemento) ? $this->notacomplemento->getnatoperacao() : $this->notafiscal->getnatoperacao()), $this->con);
		$condpagto = objectbytable("condpagto", $this->notafiscal->getcodcondpagto(), $this->con);
		$operacaonota = objectbytable("operacaonota", $this->notafiscal->getoperacao(), $this->con);
		$transportadora = objectbytable("transportadora", $this->notafiscal->getcodtransp(), $this->con);
		$contingencianfe = objectbytable("contingencianfe", NULL, $this->con);
		$contingencianfe->setcodestabelec($this->estabelecimento->getcodestabelec());
		$contingencianfe->setstatus("A");
		$contingencianfe->searchbyobject();
		$param_notafiscal_tipoeannota = param("NOTAFISCAL", "TIPOEANNOTA", $this->con);
		$param_notafiscal_gerarlanctonfe = param("NOTAFISCAL", "GERARLANCTONFE", $this->con);
		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidaderes(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpaisres(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", "01058", $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpais(), $this->con);
				break;
			default:
				$_SESSION["ERROR"] = "Tipo de parceiro (".$this->notafiscal->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
				return FALSE;
		}
		$estado_parceiro = objectbytable("estado", $cidade_parceiro->getuf(), $this->con);

		if(!$this->ativo()){
			$_SESSION["ERROR"] = "N&atilde;o foi possivel transmitir a NF-e. Verifique se o aplicativo transmissor esta ativo!";
			return FALSE;
		}

		$nfe = array();
		if($this->tipocomunicacao == TXT){
			$nfe[] = 'NFE.CRIARENVIARNFE("';
		}
		$nfe[] = "[Identificacao]";
		$nfe[] = "cUF=".$estado_estabelecimento->getcodoficial(); // Codigo do estado do fato gerador (tabela IBGE)
		$nfe[] = "cMunFG=".$cidade_estabelecimento->getcodoficial(); // Codigo do municipio do emitente (tabela IBGE)
		$nfe[] = "nNF=".(is_object($this->notacomplemento) ? $this->notacomplemento->getnumnotafis() : $this->notafiscal->getnumnotafis()); // Numero do documento fiscal
		$nfe[] = "cNF=".(is_object($this->notacomplemento) ? $this->notacomplemento->getnumnotafis() : $this->notafiscal->getnumnotafis()); // Codigo numerico que compoe a chave de acesso
		$nfe[] = "natOp=".$natoperacao->getdescricao(); //descriÃƒÂ§ÃƒÂ£o da natureza da operaÃƒÂ§ÃƒÂ£o
		if($param_notafiscal_gerarlanctonfe == 2){
			$nfe[] = "indPag=0";
		}else{
			$nfe[] = "indPag=".($condpagto->getdia1() == 0 && $condpagto->getpercdia1() == 100 ? "0" : "1"); //forma de pagamento
		}
		$nfe[] = "mod=55"; //modelo do documento fiscal
		$nfe[] = "serie=".(is_object($this->notacomplemento) ? $this->notacomplemento->getserie() : $this->notafiscal->getserie()); // Serie do documento fiscal
		$nfe[] = "dEmi=".(is_object($this->notacomplemento) ? $this->notacomplemento->getdtemissao(TRUE) : $this->notafiscal->getdtemissao(TRUE)); // Data da emissao do documento fiscal (formato AAAA-MM-DD)
		$nfe[] = "dSaiEnt=".($natoperacao->getgeradtentrega() == "S" ? (is_object($this->notacomplemento) ? $this->notacomplemento->getdtemissao(TRUE) : $this->notafiscal->getdtentrega(TRUE)) : ""); // Data de entrada ou saida da mercadoria (formato DD-MM-AAAA)
		// Tipo de documento fiscal (0 = entrada; 1 = saida)
		if(is_object($this->notafiscal) && $this->notafiscal->getoperacao() == "AN"){
			if($this->notafiscal->gettipoajuste() == "0"){
				$tpNF = "0";
			}elseif($this->notafiscal->gettipoajuste() == "1"){
				$tpNF = "1";
			}
			$nfe[] = "tpNF=".$tpNF;
		}else{
			$nfe[] = "tpNF=".($operacaonota->gettipo() == "E" ? 0 : "1");
		}

		$idDest = "";
		$nfe[] = "finNFe=".(is_object($this->notacomplemento) ? $this->notacomplemento->getfinalidade() : $this->notafiscal->getfinalidade()); // Finalidade da emissao da nota fiscal
		if($estado_estabelecimento != $estado_parceiro){
			if(!in_array($operacaonota->getoperacao(), array("IM", "EX"))){
				$nfe[] = "idDest=2"; // operaÃƒÂ§ÃƒÂ£o interestadual
				$idDest = "2";
			}else{
				if($operacaonota->getoperacao() == "IM"){
					$nfe[] = "idDest=3"; // operacao com exterior - importacao
					$idDest = "3";
				}else{
					$nfe[] = "idDest=1"; // operacao com exterior - exportaÃ§Ã£o
					$idDest = "1";
				}
			}
		}else{
			$nfe[] = "idDest=1";  //operaÃƒÂ§ÃƒÂ£o interna
			$idDest = "1";
		}

		if($operacaonota->getoperacao() == "EX"){
			$nfe[] = "indFinal=1"; //0=NÃƒÂ£o; 1=Consumidor final;
		}else{
			if($parceiro->gettppessoa() == "F" || strlen(trim(removeformat($parceiro->getrgie()), "0")) == 0){
				$nfe[] = "indFinal=1"; //0=NÃƒÂ£o; 1=Consumidor final;
			}else{
				$nfe[] = "indFinal=0"; //0=NÃƒÂ£o; 1=Consumidor final;
			}
		}

		if(strlen($this->notafiscal->getidnotafiscalref()) > 0){
			$notafiscalref = objectbytable("notafiscal", $this->notafiscal->getidnotafiscalref(), $this->con);
		}

		if(is_object($this->notacomplemento)){ // Se estiver uma chave de NFe referenciada informada, deve gerar a informacao
			$nfe[] = "[NFRef001]";
			$nfe[] = "Tipo=NFe";
			$nfe[] = "refNfe=".$this->notafiscal->getchavenfe(); // Chave da NFe referenciada
		}elseif(($this->notafiscal->getoperacao() == "DF" || $this->notafiscal->getoperacao() == "DC") && (strlen($this->notafiscal->getidnotafiscalref()) > 0 && strlen($notafiscalref->getchavenfe()) > 0)){
			$nfe[] = "[NFRef001]";
			$nfe[] = "Tipo=NFe";
			$nfe[] = "refNfe=".$notafiscalref->getchavenfe(); // Chave da NFe referenciada
		}elseif($this->notafiscal->getoperacao() == "DF" && (strlen($this->notafiscal->getidnotafiscalref()) > 0 && strlen($notafiscalref->getchavenfe()) == 0)){
			switch($notafiscalref->gettipoparceiro()){
				case "C": $parceiroref = objectbytable("cliente", $notafiscalref->getcodparceiro(), $this->con);
					break;
				case "E": $parceiroref = objectbytable("estabelecimento", $notafiscalref->getcodparceiro(), $this->con);
					break;
				case "F": $parceiroref = objectbytable("fornecedor", $notafiscalref->getcodparceiro(), $this->con);
					break;
				default: $_SESSION["ERROR"] = "Tipo de parceiro (".$notafiscalref->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
					return FALSE;
			}
			$estadoref = objectbytable("estado", $parceiroref->getuf(), $this->con);
			$nfe[] = "[NFRef001]";
			$nfe[] = "Tipo=NF";
			$nfe[] = "cUF=".$estadoref->getcodoficial();
			$nfe[] = "AAMM=".convert_date($notafiscalref->getdtemissao(), "Y-m-d", "ym");
			$nfe[] = "CNPJ=".removeformat($parceiroref->getcpfcnpj());
			$nfe[] = "mod=01";
			$nfe[] = "serie=".$notafiscalref->getserie();
			$nfe[] = "nNF=".$notafiscalref->getnumnotafis();
		}
		if(!is_object($this->notacomplemento)){
			if(strlen($this->notafiscal->getcupom()) > 0){ // Se estiver um cupom informado, gerar informacoes do ecf
				$nfe[] = "[NFRef001]";
				$nfe[] = "Tipo=ECF";
				$nfe[] = "mod=2C"; // Modelo do documento
				$nfe[] = "nECF=".$this->notafiscal->getnumeroecf(); // Numero do ECF
				$nfe[] = "nCOO=".$this->notafiscal->getcupom(); // Numero do cupom
			}
		}
		if($contingencianfe->exists()){ // Verifica se existe alguma contingencia em aberto
			$nfe[] = "dhCont=".$contingencianfe->getdataini(TRUE)." ".substr($contingencianfe->gethoraini(), 0, 8); // Data e hora da entrada em contingencia (formato AAAA-MM-DD HH:MM:SS)
			$nfe[] = "xJust=".$contingencianfe->getobservacao(); // Justificativa da contingencia
		}

		$nfe[] = "[Emitente]";
		$nfe[] = "CNPJCPF=".removeformat($this->estabelecimento->getcpfcnpj()); // CPF do emitente
		$nfe[] = "IE=".(strlen(trim(removeformat($this->estabelecimento->getrgie()), "0")) > 0 ? removeformat($this->estabelecimento->getrgie()) : ""); // Inscricao estadual do emitente
		$nfe[] = "xNome=".$this->estabelecimento->getrazaosocial(); // Razao social do emitente
		$nfe[] = "xFant=".$this->estabelecimento->getnome(); // Nome fantasia do emitente
		$nfe[] = "xLgr=".$this->estabelecimento->getendereco(); // Endereco do emitente
		$nfe[] = "nro=".$this->estabelecimento->getnumero(); // Numero do endereco do emitente
		$nfe[] = "xCpl=".$this->estabelecimento->getcomplemento(); // Complemento do endereco do emitente
		$nfe[] = "xBairro=".$this->estabelecimento->getbairro(); // Bairro do emitente
		$nfe[] = "cMun=".$cidade_estabelecimento->getcodoficial(); // Codigo da cidade do emitente (Tabela IBGE)
		$nfe[] = "xMun=".utf8_encode($cidade_estabelecimento->getnome()); // Nome da cidade do emitente
		$nfe[] = "UF=".$estado_estabelecimento->getuf(); // Sigla do estado do emitente
		$nfe[] = "CEP=".removeformat($this->estabelecimento->getcep()); // CEP do emitente
		$nfe[] = "cPais=".$pais_estabelecimento->getcodpais(); // Codigo do pais do emitente (1058 = Brasil)
		$nfe[] = "xPais=".$pais_estabelecimento->getnome(); // Nome do pais do emitente
		$nfe[] = "Fone=".(strlen($this->estabelecimento->getfone1()) > 0 ? removeformat($this->estabelecimento->getfone1()) : ""); // Telefone do emitente (DD + telefone)
		if($operacaonota->getoperacao() == "IM"){
			$nfe[] = "CRT=3";
		}else{
			if($this->estabelecimento->getregimetributario() == 2){
				$nfe[] = "CRT=3";
			}else{
				$nfe[] = "CRT=".$this->estabelecimento->getregimetributario();
			}
		}
		$nfe[] = "[Destinatario]";
		$nfe[] = "CNPJCPF=".removeformat($parceiro->getcpfcnpj()); // CNPJ do destinatario
		$nfe[] = "IE=".(strlen(trim(removeformat($parceiro->getrgie()), "0")) > 0 && $parceiro->gettppessoa() == "J" ? removeformat($parceiro->getrgie()) : ""); // Inscricao estadual do destinatario
//		if($operacaonota->getoperacao() == "EX" || $estado_parceiro->getuf() == "EX"){
		if($operacaonota->getoperacao() == "EX"){
			$nfe[] = "idEstrangeiro=".$parceiro->getidestrangeiro(); //identificaÃ¯Â¿Â½Ã¯Â¿Â½o de estrangeiro / passaporte
		}elseif($operacaonota->getoperacao() == "IM"){
			$nfe[] = "idEstrangeiro=00000";
		}
		$nfe[] = "xNome=".$parceiro->getrazaosocial(); // Nome do destinatario
		if($this->notafiscal->gettipoparceiro() == "C"){ // Verifica se o destinatario e um cliente
			$nfe[] = "xLgr=".$parceiro->getenderres(); // Endereco do destinatario
			$nfe[] = "nro=".$parceiro->getnumerores(); // Numero do endereco do destinatario
			$nfe[] = "xCpl=".$parceiro->getcomplementores(); // Complemento do endereco do destinatario
			$nfe[] = "xBairro=".$parceiro->getbairrores(); // Bairro do destinatario
			$nfe[] = "CEP=".removeformat($parceiro->getcepres()); // CEP do destinatario
			$nfe[] = "fone=".(strlen($parceiro->getfoneres()) > 0 ? removeformat($parceiro->getfoneres()) : ""); // Telefone do destinatario (DDD + telefone)
		}else{
			$nfe[] = "xLgr=".$parceiro->getendereco(); // Endereco do destinatario
			$nfe[] = "nro=".$parceiro->getnumero(); // Numero do endereco do destinatario
			$nfe[] = "xCpl=".$parceiro->getcomplemento(); // Complemento do endereco do destinatario
			$nfe[] = "xBairro=".$parceiro->getbairro(); // Bairro do destinatario
			$nfe[] = "CEP=".removeformat($parceiro->getcep()); // CEP do destinatario
			$nfe[] = "fone=".(strlen($parceiro->getfone1()) > 0 ? "0".removeformat($parceiro->getfone1()) : ""); // Telefone do destinatario (DDD + telefone)
		}
		$indIEDest = ($parceiro->gettppessoa() == "F" || strlen(trim(removeformat($parceiro->getrgie()), "0")) == 0 ? "9" : "1"); // Inscricao estadual do destinatario
		$nfe[] = "indIEDest=".($parceiro->gettppessoa() == "F" || strlen(trim(removeformat($parceiro->getrgie()), "0")) == 0 ? "9" : "1"); // Inscricao estadual do destinatario
		$nfe[] = "cMun=".$cidade_parceiro->getcodoficial(); // Codigo oficial da cidade do destinatario
		$nfe[] = "xMun=".utf8_encode($cidade_parceiro->getnome()); // Nome da cidade do destinatario
		$nfe[] = "UF=".$estado_parceiro->getuf(); // Sigla do estado do destinatario
		$nfe[] = "cPais=".$pais_parceiro->getcodpais(); // Codigo do pais do destinatario (1058 = Brasil)
		$nfe[] = "xPais=".$pais_parceiro->getnome(); // Nome do pais do destinatario
		if(method_exists($parceiro, "getsuframa") && strlen($parceiro->getsuframa()) > 0){
			$nfe[] = "ISUF=".$parceiro->getsuframa();
		}
		$nfe[] = "Email=".$parceiro->getemail();

		if($operacaonota->getparceiro() == "C" && ($parceiro->getcepres() != $parceiro->getcepent() || $parceiro->getnumerores() != $parceiro->getnumeroent())){
			$nfe[] = "[Entrega]";
			$cidade_parceiro_ent = objectbytable("cidade", $parceiro->getcodcidadeent(), $this->con);
			$nfe[] = "CNPJ=".removeformat($parceiro->getcpfcnpj());
			$nfe[] = "xLgr=".$parceiro->getenderent();
			$nfe[] = "nro=".$parceiro->getnumeroent();
			$nfe[] = "xCpl=".$parceiro->getcomplementoent();
			$nfe[] = "xBairro=".$parceiro->getbairroent();
			$nfe[] = "cMun=".$cidade_parceiro_ent->getcodoficial();
			$nfe[] = "xMun=".utf8_encode($cidade_parceiro_ent->getnome());
			$nfe[] = "UF=".$cidade_parceiro_ent->getuf();
		}

		if(is_object($this->notacomplemento)){
			$nfe[] = "[Produto001]"; // Inclui um novo item na nota
			$nfe[] = "Codigo=0"; // Codigo do produto
			$nfe[] = "cEAN="; // EAN do produto
			$nfe[] = "xProd=".(strlen($this->notacomplemento->gettextonota()) > 0 ? $this->notacomplemento->gettextonota() : "COMPLEMENTO DE NOTA FISCAL"); // Descricao do produto
			$nfe[] = "NCM=00000000"; // Codigo NCM do produto (com 2 ou 8 digitos)
			$nfe[] = "CFOP=".substr(removeformat($this->notacomplemento->getnatoperacao()), 0, 4); // Natureza de operacao
			$nfe[] = "uCom=UN"; // Unidade comercial
			$nfe[] = "qCom=0"; // Quantidade comercial
			$nfe[] = "vUnCom=0"; // Preco comercial
			$nfe[] = "vProd=0"; // Total bruto
			$nfe[] = "cEANTrib="; // EAN tributavel
			$nfe[] = "uTrib=UN"; // Unidade tributavel
			$nfe[] = "qTrib=0"; // Quantidade tributavel
			$nfe[] = "vUnTrib=0"; // Preco tributavel
			$nfe[] = "indTot=1"; //Indica se o valor do item(vprod) soma no total da NF (vprod)
			$nfe[] = "[ICMS001]";
			$nfe[] = "orig=0"; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
			$nfe[] = "CST=90"; // CST do produto
			$nfe[] = "modBC=3"; // Modo de calculo da base do icms (0 = margem valor agregado (%); 1 = pauta (valor); 2 = preco tabelado max (valor); 3 = valor da operacao)
			$nfe[] = "vBC=".number_format($this->notacomplemento->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
			$nfe[] = "pRedBC=0"; // Percentual da reducao na base de calculo de icms
			$nfe[] = "pICMS=0"; // Aliquota de icms
			$nfe[] = "vICMS=".number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms
			$nfe[] = "modBCST=4"; // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
			$nfe[] = "pMVAST=0"; // Aliquota do iva
			$nfe[] = "pRedBCST=0"; // Reducao na base de calculo
			$nfe[] = "vBCST=".number_format($this->notacomplemento->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
			$nfe[] = "pICMSST=0"; // Aliquota do imposto do icms substituto
			$nfe[] = "vICMSST=".number_format($this->notacomplemento->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
			$nfe[] = "[Total]";
			$nfe[] = "vBC=".number_format($this->notacomplemento->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
			$nfe[] = "vICMS=".number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms
			$nfe[] = "vBCST=".number_format($this->notacomplemento->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de calculo do icms substituto
			$nfe[] = "vST=".number_format($this->notacomplemento->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
			$nfe[] = "vProd=0"; // Total bruto
			$nfe[] = "vFrete=0"; // Total frete
			$nfe[] = "vSeg=0"; // Total seguro
			$nfe[] = "vDesc=0"; // Total desconto
			$nfe[] = "vII=0"; // Total do II
			$nfe[] = "vIPI=".number_format($this->notacomplemento->gettotalipi(), 2, ".", ""); // Total ipi
			$nfe[] = "vPIS=0"; // Total de pis
			$nfe[] = "vCOFINS=0"; // Total de cofins
			$nfe[] = "vOutro=0"; // Total outras tributacoes
			$nfe[] = "vNF=".number_format($this->notacomplemento->gettotalliquido(), 2, ".", ""); // Total da nota fiscal
			$nfe[] = "[Transportador]";
			$nfe[] = "modFrete=0"; // Modalidade do frete (0 = por conta do emitente; 1 = por conta do destinatario; 2 = por conta de terceiro; 9 = sem frete)
			$nfe[] = "Volume001";
			$nfe[] = "qVol=1"; // Quantidade de volumes transportados
			$nfe[] = "esp=VOLUMES"; // Especie dos volumes transportados
			$nfe[] = "marca="; // Marca dos volumes transportados
			$nfe[] = "nVol="; // Numeracao dos volumes transportados
			$nfe[] = "pesoL=0"; // Peso liquido (em Kg)
			$nfe[] = "pesoB=0";   // Peso liquido (em Kg)

			$nfe[] = "infCpl=".trim(removespecial(str_replace("\n", " ", $this->notacomplemento->getobservacao()))); // Observacao
			$this->notacomplemento = objectbytable("notacomplemento", $this->notacomplemento->getidnotacomplemento(), $this->con);
			if($this->tipocomunicacao == TXT){
				$nlote = (is_object($this->notacomplemento) ? $this->notacomplemento->getnumnotafis() : $this->notafiscal->getnumnotafis());
				$nfe[] = '",'.$nlote.','.($imprimirnfe ? '1' : '0').',0)';

				$this->notacomplemento->calcular_chavenfe();
				if(!$this->notacomplemento->save()){
					return FALSE;
				}
				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, $nfe, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
				$ok = FALSE;
				for($i = 0; $i < 6; $i++){
					sleep(10);
					$ok = $this->nfemonitorok(FALSE);
					if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
						break;
					}
				}
				if((is_bool($ok)) && ($ok)){
					$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
					$arr_resp = explode("\r\n", file_get_contents($fileresp));
					unlink($fileresp);
					$nStat = 0;
					$xmotivo = "";
					$retorno = FALSE;
					$nStat = 0;
					foreach($arr_resp as $linha_resp){
						if(!$retorno){
							$retorno = strtoupper($linha_resp) == "[NFE".$this->notafiscal->getnumnotafis()."]";
						}
						if($retorno){
							if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
								$nStat = substr($linha_resp, 6);
								$this->notafiscal->setcodigostatus($nStat);
							}
							if(substr(strtoupper($linha_resp), 0, 7) == "XMOTIVO"){
								$xmotivo = substr($linha_resp, 8);
								$this->notafiscal->setxmotivo($xmotivo);
							}
							if(substr(strtoupper($linha_resp), 0, 5) == "CHNFE"){
								$this->notafiscal->setchavenfe(substr($linha_resp, 6));
							}
							if(substr(strtoupper($linha_resp), 0, 5) == "NPROT"){
								$this->notafiscal->setprotocolonfe(substr($linha_resp, 6));
							}
							if(($nStat == 100) || ($nStat == 110) || ($nStat == 150)){
								$this->notafiscal->setdataautorizacao(date("Y-m-d"));
								if(($nStat == 100) || ($nStat == 150)){
									$this->notafiscal->setstatus("A"); //autorizada
								}else{
									$this->notafiscal->setstatus("D"); //denegada
								}
							}
							if($nStat == "302"){
								$this->notafiscal->setstatus("D"); //denegada
							}
						}
					}
					$_SESSION["ENVIARNFE"] = "NF-e transmitida com sucesso ao SEFAZ"."<br>".$nStat." - ".$xmotivo;
					if(!$retorno){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
						return FALSE;
					}else{
						if(!$this->notafiscal->save()){
							return FALSE;
						}else{
							return TRUE;
						}
					}
				}else{
					if(is_bool($ok)){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel transmitir a NF-e ao SEFAZ.\nErro ocorrido: ".htmlentities($ok);
					}
					return FALSE;
				}
			}else{
				$retorno = $this->nfemonitor->criarEnviarNfe(implode("\n", $nfe), $this->notacomplemento->getnumnotafis(), 1);
				if(is_array($retorno)){
					$arr_retorno = $retorno["OK"]["RETORNO"];
					if(is_array($arr_retorno)){
						$nfenf = "NFE".$this->notacomplemento->getnumnotafis();
						$arr_retorno = $retorno["OK"][$nfenf];
						if(is_array($arr_retorno)){
							$this->notacomplemento->setcodigostatus($arr_retorno["CStat"]);
							$this->notacomplemento->setxmotivo($arr_retorno["XMotivo"]);
							$this->notacomplemento->setchavenfe($arr_retorno["ChNFe"]);
							$this->notacomplemento->setprotocolonfe($arr_retorno["NProt"]);
							if(($arr_retorno["CStat"] == 100) || ($arr_retorno["CStat"] == 110)){
								$this->notacomplemento->setdataautorizacao(date("Y-m-d"));
								if($arr_retorno["CStat"] == 100){
									$this->notacomplemento->setstatus("A"); //autorizada
								}else{
									$this->notacomplemento->setstatus("D"); //denegada
								}
							}
							if($arr_retorno["CStat"] == "302"){
								$this->notafiscal->setstatus("D"); //denegada
							}
							$_SESSION["ENVIARNFE"] = "NF-e transmitida com sucesso ao SEFAZ"."<br>".$arr_retorno["CStat"]." - ".$arr_retorno["XMotivo"];
						}else{
							$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
							return FALSE;
						}
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
						return FALSE;
					}
					if(!$this->notacomplemento->save()){
						return FALSE;
					}else{
						return TRUE;
					}
				}else{
					$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), htmlentities($retorno));
					$_SESSION["ERROR"] = $retorno;
					return FALSE;
				}
			}
		}else{
			$n = 1;
			$total_produto = 0;
			$total_produtos = 0;
			$tbasep_simples = 0;
			$tvicms_simples = 0;
			$totalbaseicms = 0;
			$baseicms = 0;
			$arr_natoperacao = array();
			foreach($arr_itnotafiscal as $itnotafiscal){
				if($itnotafiscal->gettotalicmssubst() > 0){
					$aliqicmssubst = number_format((($itnotafiscal->gettotalicmssubst() + ((($itnotafiscal->gettotalbruto() - $itnotafiscal->gettotaldesconto() + $itnotafiscal->gettotalacrescimo() + ($natoperacao->getcalcfretebaseicms() == "S" ? $itnotafiscal->gettotalfrete() : 0)) * (1 - $itnotafiscal->getredicms() / 100)) * $itnotafiscal->getaliqicms() / 100)) / $itnotafiscal->gettotalbaseicmssubst()) * 100, 0);
				}else{
					$aliqicmssubst = 0;
				}

				$arr_natoperacao[] = $itnotafiscal->getnatoperacao();
				$produto = objectbytable("produto", $itnotafiscal->getcodproduto(), $this->con);
				$operacao = objectbytable("operacaonota", $this->notafiscal->getoperacao(), $this->con);
				$classfiscal = objectbytable("classfiscal", ($operacao->gettipo() == "E" && !in_array($operacao->getoperacao(), array("DC", "IM", "PR")) ? $produto->getcodcfnfe() : $produto->getcodcfnfs()), $this->con);
				if(strlen($natoperacao->getcodcf()) > 0 && !($classfiscal->gettptribicms() == "I" && $natoperacao->getalteracfisento() == "S") && !($classfiscal->gettptribicms() == "F" && $natoperacao->getalteracficmssubst() == "S")){
					$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
				}
				$itnatoperacao = objectbytable("natoperacao", $itnotafiscal->getnatoperacao(), $this->con);
				$unidade = objectbytable("unidade", $itnotafiscal->getcodunidade(), $this->con);
				$ipi = objectbytable("ipi", $produto->getcodipi(), $this->con);
				$piscofins = objectbytable("piscofins", ($operacaonota->gettipo() == "E" && !in_array($operacaonota->getoperacao(), array("DC", "IM", "PR")) ? $produto->getcodpiscofinsent() : $produto->getcodpiscofinssai()), $this->con);
				$produtoean = objectbytable("produtoean", NULL, $this->con);
				$produtoean->setcodproduto($produto->getcodproduto());
				$arr_produtoean = object_array($produtoean);
				$codean = (sizeof($arr_produtoean) > 0 ? reset($arr_produtoean)->getcodean() : "");
				if(strlen($codean) <= 0){
					$codean = plutoean13($itnotafiscal->getcodproduto());
				}
				if($param_notafiscal_tipoeannota == 2 || ($param_notafiscal_tipoeannota == 1 && (!valid_ean($codean) || strlen(ltrim($codean, "0")) < 8))){
					$codean = "";
				}
				$basecalculo = $itnotafiscal->gettotalbruto() - $itnotafiscal->gettotaldesconto() + $itnotafiscal->gettotalacrescimo() + $itnotafiscal->gettotalii() + $itnotafiscal->gettotalfrete();

				// Calcula preco unitario do produto
				$precounitario = $itnotafiscal->getprecopolitica() + ($itnotafiscal->gettotalacrescimo() / $itnotafiscal->getquantidade());
				if($natoperacao->getoperacao() == "IM" && ($itnatoperacao->gettotnfigualbcicms() == "S" || $itnatoperacao->getsumicmstotalnf() == "S")){
					$precounitario += $itnotafiscal->gettotalii() / $itnotafiscal->getquantidade();
				}
				$precounitario = number_format($precounitario, 4, ".", "");

				if(strlen($produto->getidncm()) > 0){
					$ncm = objectbytable("ncm", $produto->getidncm(), $this->con);
					$codigoncm = $ncm->getcodigoncm();
				}else{
					$codigoncm = "";
				}

				$numeroserie = objectbytable("numeroserie", NULL, $this->con);
				$numeroserie->setidnotafiscal($this->notafiscal->getidnotafiscal());
				$numeroserie->setcodproduto($itnotafiscal->getcodproduto());
				$arr_numeroserie_ob = object_array($numeroserie);
				$arr_numeroserie = array();
				foreach($arr_numeroserie_ob as $numeroserie){
					$arr_numeroserie[] = $numeroserie->getnumeroserie();
				}

				$nfe[] = "[Produto".str_pad($n, 3, "0", STR_PAD_LEFT)."]"; // Inclui um novo item na nota
				$nfe[] = "Codigo=".$itnotafiscal->getcodproduto(); // Codigo do produto
				//$nfe[] = "cProd=".$itnotafiscal->getcodproduto(); // Codigo do produto
				$nfe[] = "cEAN=".$codean; // EAN do produto

				if(strlen($itnotafiscal->getpedcliente()) > 0){
					$nfe[] = "xPed=".$itnotafiscal->getpedcliente(); // Numero do pedido de compra do cliente
				}
				if(strlen($itnotafiscal->getseqitemcliente()) > 0){
					$nfe[] = "nItemPed=".$itnotafiscal->getseqitemcliente(); // Item do pedido de compra do cliente
				}
				if($this->descricaoproduto == "D"){
					$desc = $produto->getdescricaofiscal();
				}else{
					if($this->descricaoproduto == "2"){
						if(strlen($produto->getdescricaofiscal2()) == 0){
							$desc = $produto->getdescricaofiscal();
						}else{
							$desc = $produto->getdescricaofiscal2();
						}
					}else{
						if(strlen(trim($itnotafiscal->getcomplemento())) == 0){
							$desc = $produto->getdescricaofiscal();
						}else{
							$desc = $itnotafiscal->getcomplemento();
						}
					}
				}
				$nfe[] = "xProd=".$desc; // Descricao do produto
				$nfe[] = "NCM=".removeformat($codigoncm); // Codigo NCM do produto (com 2 ou 8 digitos)
				$nfe[] = "CFOP=".substr(removeformat($itnatoperacao->getnatoperacao()), 0, 4); // Natureza de operacao do produto
				$nfe[] = "uCom=".$unidade->getsigla(); // Unidade comercial
				$nfe[] = "qCom=".$itnotafiscal->getquantidade(); // Quantidade comercial
				$nfe[] = "vUnCom=".$precounitario; // Preco comercial
				$total_produto = number_format($itnotafiscal->getquantidade() * $precounitario, 2, ".", ""); // $itnotafiscal->gettotalliquido() - $itnotafiscal->gettotaldesconto(); //
				$total_produtos += $total_produto;
				$nfe[] = "vProd=".number_format($total_produto, 2, ".", ""); // Total bruto
				$nfe[] = "cEANTrib=".$codean; // EAN tributavel
				$nfe[] = "uTrib=".$unidade->getsigla(); // Unidade tributavel
				$nfe[] = "qTrib=".$itnotafiscal->getquantidade(); // Quantidade tributavel
				$nfe[] = "vUnTrib=".$precounitario; // Preco tributavel
				if(compare_date(date("Y-m-d"), "2016-01-01", "Y-m-d", ">=")){
					$codigocest = "";
					if(strlen($produto->getcest()) > 0){
						$codigocest = removeformat($produto->getcest());
						$nfe[] = "CEST=".$codigocest; // Cest
					}else{
						if(is_object($ncm) && $itnotafiscal->gettptribicms() == "F" && !is_null($ncm->getidcest())){
							$cest = objectbytable("cest", $ncm->getidcest(), $this->con);
							$codigocest = removeformat($cest->getcest());
							$nfe[] = "CEST=".$codigocest; // Cest
						}
					}
				}
				if($itnotafiscal->gettotalfrete() > 0){
					$nfe[] = "vFrete=".number_format($itnotafiscal->gettotalfrete(), 2, ".", ""); // Total frete
				}
				if($itnotafiscal->gettotalseguro() > 0){
					$nfe[] = "vSeguro=".number_format($itnotafiscal->gettotalseguro(), 2, ".", ""); //Total do seguro
				}
				if(number_format($itnotafiscal->gettotaldesconto(), 2, ".", "") > 0){
					$nfe[] = "vDesc=".number_format($itnotafiscal->gettotaldesconto(), 2, ".", ""); // Total desconto
				}
				if($natoperacao->getoperacao() == "IM" && $itnotafiscal->getvalsiscomex() > 0){
					$nfe[] = "vOutro=".number_format($itnotafiscal->getvalsiscomex() + $itnotafiscal->gettotalpis() + $itnotafiscal->gettotalcofins(), 2, ".", ""); //Total de outras
				}elseif(in_array($natoperacao->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N"){
					$nfe[] = "vOutro=".number_format($itnotafiscal->gettotalicmssubst() + $itnotafiscal->gettotalipi(), 2, ".", ""); //Total de outras
				}

				//Indica se o valor do item(vprod) soma no total da NF (vprod)
				if($this->notafiscal->getfinalidade() == "3" && $this->notafiscal->gettipoajuste() != "0"){
					$nfe[] = "indTot=0";
				}else{
					$nfe[] = "indTot=1";
				}

				// Numero de serie do produto
				if(sizeof($arr_numeroserie) > 0){
					$nfe[] = "infAdProd=N/S: ".implode(", ", $arr_numeroserie);
				}

				if($operacaonota->getoperacao() == "IM"){
					$orig = "1";
				}else{
					$orig = substr($classfiscal->getcodcst(), 0, 1);
				}

				$complemento = array();
				if(strlen($itnotafiscal->getcomplemento()) > 0){
					$complemento[] = $itnotafiscal->getcomplemento();
				}
				if(strlen($itnotafiscal->getnumerolote()) > 0){
					$complemento[] = "Lote=".$itnotafiscal->getnumerolote();
				}
				if(strlen($itnotafiscal->getdtvalidade()) > 0){
					$complemento[] = "Val=".$itnotafiscal->getdtvalidade(TRUE);
				}

				switch($param_notafiscal_nfedescoucomp){
					case("0"):
						if(sizeof($complemento) > 0 && $operacaonota->getoperacao() != "IM"){
							$nfe[] = "InfAdProd=".implode(" ", $complemento);
						}
						break;
					case("1"):
						if($this->descricaoproduto != "C"){
							$nfe[] = "InfAdProd=".$itnotafiscal->getcomplemento();
						}
					case("2"):
						break;
				}

				$cst = substr($itnotafiscal->getcsticms(), 1, 2);
				$c_cst = substr($cst, 0, 1);
				$nfe[] = "[ICMS".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
				if($this->estabelecimento->getregimetributario() == "1" && $operacaonota->getoperacao() != "IM"){
					if($itnotafiscal->gettptribicms() != "F"){
						//if(in_array($classfiscal->getcsosn(), array("101", "900")) && substr($itnotafiscal->getnatoperacao(), 0, 1) != "7"){
						if(in_array($itnotafiscal->getcsticms(), array("101", "900")) && substr($itnotafiscal->getnatoperacao(), 0, 1) != "7"){
							$nfe[] = "orig=".$orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
							$nfe[] = "CSOSN=".$itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional
							$basep_simples = $itnotafiscal->gettotalbaseicms();
							$icms_simples = $itnotafiscal->getaliqicms();
							$vicms_simples = $basep_simples * $icms_simples / 100;
							$tbasep_simples += $basecalculo;
							$tvicms_simples += $vicms_simples;
							if(in_array($operacaonota->getoperacao(), array("DF", "RF"))){
								$nfe[] = "pCredSN=".number_format(0, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
								$nfe[] = "vCredICMSSN=".number_format(0, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
								$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
								$nfe[] = "pRedBC=".number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
								$nfe[] = "pICMS=".number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
								$nfe[] = "vICMS=".number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
							}else{
								$nfe[] = "pCredSN=".number_format($icms_simples, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
								$nfe[] = "vCredICMSSN=".number_format($vicms_simples, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
							}
						}else{
							$nfe[] = "orig=".$orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
							$nfe[] = "CSOSN=".$itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional
							//$nfe[] = "CSOSN=900"; // Codigo da situacao da operacao - simples nacional
						}
					}else{
						if(($operacaonota->getoperacao() == "VD" || ($operacaonota->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S")) && $itnotafiscal->gettotalicmssubst() > 0){
							$nfe[] = "orig=".$orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
							$nfe[] = "CSOSN=".$itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional
							if(in_array($itnotafiscal->getcsticms(), array("900"))){
								$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
								$nfe[] = "pRedBC=".number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
								$nfe[] = "pICMS=".number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
								$nfe[] = "vICMS=".number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
							}
							$nfe[] = "modBCST=".($itnotafiscal->getaliqiva() > 0 ? "4" : "5"); // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
							$nfe[] = "pMVAST=".number_format($itnotafiscal->getaliqiva(), 2, ".", ""); // Aliquota do iva
							if($itnotafiscal->getredicms() > 0){
								$nfe[] = "pRedBCST=".number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
							}
							$nfe[] = "vBCST=".number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
							$nfe[] = "pICMSST=".number_format($aliqicmssubst, 2, ".", ""); // Aliquota do imposto do icms substituto
							$nfe[] = "vICMSST=".number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
							if(in_array($classfiscal->getcsosn(), array("201"))){
								$basep_simples = $itnotafiscal->gettotalbaseicmssubst() / (1 + $itnotafiscal->getaliqiva() / 100);
								$icms_simples = $classfiscal->getaliqicms();
								$vicms_simples = $basep_simples * $icms_simples / 100;
								$tbasep_simples += $basecalculo;
								$tvicms_simples += $vicms_simples;
								$nfe[] = "pCredSN=".number_format($classfiscal->getaliqicms(), 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
								$nfe[] = "vCredICMSSN=".number_format($vicms_simples, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
								if($operacaonota->getoperacao() != "DF"){
									$nfe[] = "pCredSN=".number_format($classfiscal->getaliqicms(), 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
									$nfe[] = "vCredICMSSN=".number_format($vicms_simples, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
								}else{
									$nfe[] = "pCredSN=".number_format(0, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
									$nfe[] = "vCredICMSSN=".number_format(0, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
								}
							}
						}else{
							$nfe[] = "orig=".$orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
							$nfe[] = "CSOSN=".$itnotafiscal->getcsticms();

							$it_totalbaseicms_st += $itnotafiscal->gettotalbaseicms();
							$it_totalicms_st += $itnotafiscal->gettotalicms();
							if(in_array($natoperacao->getoperacao(), array("DF", "RF")) && $itnotafiscal->gettotalicmssubst() > 0){
								$nfe[] = "vBCSTRet=".number_format(0, 2, ".", ""); // Valor da base de calculo do ICSM ST retido
								$nfe[] = "vICMSSTRet=".number_format(0, 2, ".", ""); // Valor do ICSM ST retido
							}else{
								$nfe[] = "vBCSTRet=".number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Valor da base de calculo do ICSM ST retido
								$nfe[] = "vICMSSTRet=".number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Valor do ICSM ST retido
							}
						}
					}
				}else{
					if(in_array($c_cst, array("0", "1", "2", "5", "4", "6", "7", "9"))){
						$nfe[] = "orig=".$orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
						$nfe[] = "CST=".$cst; // CST do produto
					}
					if(in_array($c_cst, array("0", "1", "2", "7", "9"))){
						$nfe[] = "modBC=3"; // Modo de calculo da base do icms (0 = margem valor agregado (%); 1 = pauta (valor); 2 = preco tabelado max (valor); 3 = valor da operacao)
						if($this->estabelecimento->regime_simples() && $operacaonota->getoperacao() != "IM"){ // Verifica se o estabelecimento esta no simples
							if(in_array($operacaonota->getoperacao(), array("DF", "RF"))){
								$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
								$nfe[] = "pICMS=".number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
								$nfe[] = "vICMS=".number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
							}else{
								$nfe[] = "vBC=0.00"; // Total da base do icms
								$nfe[] = "pICMS=0.00"; // Aliquota de icms
								$nfe[] = "vICMS=0.00"; // Total do icms
							}
						}else{
							$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
							$nfe[] = "pICMS=".number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
							$nfe[] = "vICMS=".number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
						}
					}
					if(in_array($c_cst, array("1", "7")) && ((($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S") || $natoperacao->getoperacao() != "DF"))){
						if(in_array($c_cst, array("2", "7"))){
							$nfe[] = "pRedBC=".number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
						}
						if(in_array($c_cst, array("1", "7"))){
							$nfe[] = "modBCST=".($itnotafiscal->getaliqiva() > 0 ? "4" : "5"); // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
							$nfe[] = "pMVAST=".number_format($itnotafiscal->getaliqiva(), 2, ".", ""); // Aliquota do iva
							if($itnotafiscal->getredicms() > 0){
								$nfe[] = "pRedBCST=".number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
							}
							$nfe[] = "vBCST=".number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
							$nfe[] = "pICMSST=".number_format($aliqicmssubst, 2, ".", ""); // Aliquota do imposto do icms substituto
							$nfe[] = "vICMSST=".number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
						}
					}
					if(in_array($c_cst, array("6"))){
						$nfe[] = "vBCST=0.00"; // Total da base de icms substituto
						$nfe[] = "vICMSST=0.00"; // Aliquota do imposto do icms substituto
					}
				}

				if($indIEDest == "9" && $idDest == "2" && in_array(substr(removeformat($itnatoperacao->getnatoperacao()), 0, 4), array("6108", "6107")) && $natoperacao->getoperacao() == "VD"){
					$vBCUFDest = "0";
					$pFCPUFDest = "0";
					$pICMSUFDest = "0";
					$pICMSInter = "0";
					$pICMSInterPart = "0";
					$vFCPUFDest = "0";
					$vICMSUFDest = "0";
					$vICMSUFRemet = "0";

					if($itnotafiscal->getbasecalcufdest() > 0){
						$vBCUFDest = number_format($itnotafiscal->getbasecalcufdest(), 2, ".", "");
					}
					if($itnotafiscal->getaliqfcpufdest() > 0){
						$pFCPUFDest = number_format($itnotafiscal->getaliqfcpufdest(), 2, ".", "");
					}
					if($itnotafiscal->getaliqicmsufdest() > 0){
						$pICMSUFDest = number_format($itnotafiscal->getaliqicmsufdest(), 2, ".", "");
					}
					if($itnotafiscal->getaliqicmsinter() > 0){
						$pICMSInter = number_format($itnotafiscal->getaliqicmsinter(), 2, ".", "");
					}
					if($itnotafiscal->getaliqicminterpart() > 0){
						$pICMSInterPart = number_format($itnotafiscal->getaliqicminterpart(), 2, ".", "");
					}
					if($itnotafiscal->getvalorfcpufdest() > 0){
						$vFCPUFDest = number_format($itnotafiscal->getvalorfcpufdest(), 2, ".", "");
					}
					if($itnotafiscal->getvaloricmsufdest() > 0){
						$vICMSUFDest = number_format($itnotafiscal->getvaloricmsufdest(), 2, ".", "");
					}
					if($itnotafiscal->getvaloricmsufremet() > 0){
						$vICMSUFRemet = number_format($itnotafiscal->getvaloricmsufremet(), 2, ".", "");
					}

					$nfe[] = "[ICMSUFDEST".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "vBCUFDest={$vBCUFDest}";
					$nfe[] = "pICMSUFDest={$pICMSUFDest}";
					$nfe[] = "pICMSInter={$pICMSInter}";
					$nfe[] = "pICMSInterPart={$pICMSInterPart}";
					$nfe[] = "vICMSUFDest={$vICMSUFDest}";
					$nfe[] = "vICMSUFRemet={$vICMSUFRemet}";
					$nfe[] = "pFCPUFDest={$pFCPUFDest}";
					$nfe[] = "vFCPUFDest={$vFCPUFDest}";
				}


				if(((($itnotafiscal->getvalipi() > 0 || $itnotafiscal->getpercipi() > 0) && (($natoperacao->getoperacao() != "DF") || (in_array($natoperacao->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "S"))))){
					$nfe[] = "[IPI".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "cEnq=999"; // Codigo de enquadramento legal do ipi
					if($itnotafiscal->gettotalipi() > 0 && $operacao->gettipo() == "S"){
						$nfe[] = "CST=".$ipi->getcodcstsai();
					}else{
						$nfe[] = "CST=".$ipi->getcodcstent(); // CST do ipi do produto
					}
					$nfe[] = "vIPI=".number_format($itnotafiscal->gettotalipi(), 2, ".", ""); // Total do ipi
					if(in_array(array($ipi->getcodcstsai(), $ipi->getcodcstent()), array("00", "49", "50", "99"))){
						if($itnotafiscal->gettipoipi() == "F"){
							$nfe[] = "qUnid=".$itnotafiscal->getquantidade(); // Quantidade do produto
							$nfe[] = "vUnid=".number_format($itnotafiscal->getvalipi(), 4, ".", ""); // Valor do ipi
						}else{
							$nfe[] = "vBC=".number_format($basecalculo, 2, ".", ""); // Base de calculo do ipi
							$nfe[] = "pIPI=".number_format($itnotafiscal->getpercipi(), 2, ".", ""); // Percetual do ipi
						}
					}
				}elseif($this->notafiscal->getoperacao() == "IM"){
					$nfe[] = "[IPI".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "cEnq=999"; // Codigo de enquadramento legal do ipi
					$nfe[] = "CST=".$ipi->getcodcstent(); // CST do ipi do produto
				}

				// Se importacao, gerar informacoes de imposto de importacao
				if($this->notafiscal->getoperacao() == "IM"){
					$nfe[] = "[II".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbaseii(), 2, ".", ""); // Total base do II
					$nfe[] = "vDespAdu=".number_format($itnotafiscal->getdespaduaneira(), 2, ".", ""); // Despesa aduaneira
					$nfe[] = "vII=".number_format($itnotafiscal->gettotalii(), 2, ".", ""); // Total do II
					$nfe[] = "vIOF=".number_format($itnotafiscal->getvaliof(), 2, ".", ""); // Total do IOF
				}

				$nfe[] = "[PIS".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
				$nfe[] = "CST=".$piscofins->getcodcst();
				if(in_array($piscofins->getcodcst(), array("01", "02", "50", "51", "52", "53", "54", "55", "56"))){
					$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbasepis(), 2, ".", ""); // Base de calculo do PIS
					$nfe[] = "pPIS=".number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
					$nfe[] = "vPIS=".number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
				}elseif(in_array($piscofins->getcodcst(), array("03"))){
					$nfe[] = "vPIS=".number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
				}elseif(in_array($piscofins->getcodcst(), array("05"))){
					$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbasepis(), 2, ".", ""); // Base de calculo do PIS
					$nfe[] = "pPIS=".number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
					$nfe[] = "vPIS=".number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
				}elseif(in_array($piscofins->getcodcst(), array("99"))){
					$nfe[] = "vPIS=".number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
					$nfe[] = "qBCProd=".$itnotafiscal->getquantidade(); // Quantidade
					$nfe[] = "vAliqProd=".number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
				}

				$nfe[] = "[COFINS".str_pad($n, 3, "0", STR_PAD_LEFT)."]";
				$nfe[] = "CST=".$piscofins->getcodcst();
				if(in_array($piscofins->getcodcst(), array("01", "02", "50", "51", "52", "53", "54", "55", "56"))){
					$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbasecofins(), 2, ".", ""); // Base de calculo do Cofins
					$nfe[] = "pCOFINS=".number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
					$nfe[] = "vCOFINS=".number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
				}elseif(in_array($piscofins->getcodcst(), array("03"))){
					$nfe[] = "vCOFINS=".number_format(($itnotafiscal->getquantidade() * $itnotafiscal->getaliqcofins() / 100), 2); // Valor total do Cofins
				}elseif(in_array($piscofins->getcodcst(), array("05"))){
					$nfe[] = "vBC=".number_format($itnotafiscal->gettotalbasecofins(), 2, ".", ""); // Base de calculo do Cofins
					$nfe[] = "pCOFINS=".number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
					$nfe[] = "vCOFINS=".number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
				}elseif(in_array($piscofins->getcodcst(), array("99"))){
					$nfe[] = "qBCProd=".$itnotafiscal->getquantidade(); // Quantidade
					$nfe[] = "vAliqProd=".number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
					$nfe[] = "vCOFINS=".number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
				}

				// Se importacao, gerar as informacoes da declaracao de importacao
				if($this->notafiscal->getoperacao() == "IM"){
					$nfe[] = "[DI".str_pad($n, 3, "0", STR_PAD_LEFT).str_pad(1, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "NumeroDI=".$this->notafiscal->getnumerodi(); // Numero da DI
					$nfe[] = "dDi=".$this->notafiscal->getdtregistrodi(TRUE); // Data d eregistro da DI
					$nfe[] = "xLocDesemb=".$this->notafiscal->getlocaldesembaraco(); // Local do desembaraco
					$nfe[] = "UFDesemb=".$this->notafiscal->getufdesembaraco(); // UF do local do desembaraco
					$nfe[] = "dDesemb=".$this->notafiscal->getdtdesembaraco(TRUE); // Data do desembaraco
					$nfe[] = "cExportador=".$this->notafiscal->getcodparceiro(); // Codigo do exportador
					$nfe[] = "tpViaTransp=".$this->notafiscal->getviatransporte(); // Via de transporte internacional
					if($this->notafiscal->gettotalvalorafrmm() > 0){
						$nfe[] = "vAFRMM=".$this->notafiscal->gettotalvalorafrmm(); // Valor da AFRMM - Adicional ao Frete para RenovaÃ¯Â¿Â½Ã¯Â¿Â½o da Marinha Mercante
					}
					$nfe[] = "tpIntermedio=".$this->notafiscal->gettipoimportacao(); // Forma de importaÃ¯Â¿Â½Ã¯Â¿Â½o quanto a intermediaÃ¯Â¿Â½Ã¯Â¿Â½o
					$nfe[] = "CNPJ=".$this->notafiscal->getcnpjadquirente(); // CNPJ do adquirente ou do encomendante
					$nfe[] = "UFTerceiro=".$this->notafiscal->getufterceiro(); // Sigla da UF do adquirente ou do encomendante
					$nfe[] = "[LADI".str_pad($n, 3, "0", STR_PAD_LEFT).str_pad(1, 3, "0", STR_PAD_LEFT).str_pad(1, 3, "0", STR_PAD_LEFT)."]";
					$nfe[] = "nAdicao=".$itnotafiscal->getnumadicao(); // Numero da adicao
					$nfe[] = "nSeqAdic=".$n; // Sequencial do item na adicao ???????????
					$nfe[] = "cFabricante=".$this->notafiscal->getcodparceiro(); // Codigo do fabricante estrangeiro
					$nfe[] = "vDescDI=".number_format($itnotafiscal->getvaldesctodi(), 2); // Valor de desconto do item na DI
				}

				if($itnotafiscal->gettptribicms() != "F"){
					$totalbaseicms += $itnotafiscal->gettotalbaseicms();
					$totalicms += $itnotafiscal->gettotalicms();
				}
				$n++;
			}

			$arr_natoperacao = array_unique($arr_natoperacao);
			foreach($arr_natoperacao as $i => $natoperacao){
				$arr_natoperacao[$i] = objectbytable("natoperacao", $natoperacao, $this->con);
			}
			$nfe[] = "[Total]";
			if($this->estabelecimento->regime_simples() && $operacaonota->getoperacao() != "IM"){ // Verifica se o estabelecimento esta no simples
				if($operacaonota->getoperacao() == "DF"){
					$nfe[] = "vBC=".number_format($this->notafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
					$nfe[] = "vICMS=".number_format($this->notafiscal->gettotalicms(), 2, ".", ""); // Total do icms
				}else{
					$nfe[] = "vBC=0.00"; // Total da base de calculo do icms
					$nfe[] = "vICMS=0.00"; // Total do icm
				}
			}else{
				if($this->notafiscal->gettipoparceiro() == "F"){
					$nfe[] = "vBC=".number_format($this->notafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
					$nfe[] = "vICMS=".number_format($this->notafiscal->gettotalicms(), 2, ".", ""); // Total do icms
				}else{
					$nfe[] = "vBC=".number_format($this->notafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
					$nfe[] = "vICMS=".number_format($this->notafiscal->gettotalicms(), 2, ".", ""); // Total do icms
				}
			}

			$nfe[] = "vICMSUFDest=".number_format($this->notafiscal->gettotalicmsufdest(), 2, ".", "");
			$nfe[] = "vICMSUFRemet=".number_format($this->notafiscal->gettotalicmsufremet(), 2, ".", "");
			$nfe[] = "vFCPUFDest=".number_format($this->notafiscal->gettotalfcpufdest(), 2, ".", "");

			if(($operacaonota->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "N" && $this->notafiscal->gettotalbaseicmssubst() > 0)){
				$nfe[] = "vBCST=0";
				$nfe[] = "vBC=0.00"; // Total da base de calculo do icms
			}else{
				$nfe[] = "vBCST=".number_format($this->notafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de calculo do icms substituto
			}
			if($operacaonota->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "N"){
				$nfe[] = "vST=0.00"; // Total do icms substituto
				$nfe[] = "vICMS=0.00"; // Total do icm
			}else{
				$nfe[] = "vST=".number_format($this->notafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
			}

			$nfe[] = "vProd=".number_format($total_produtos, 2, ".", ""); // Total bruto
			$nfe[] = "vFrete=".number_format($this->notafiscal->gettotalfrete(), 2, ".", ""); // Total frete
			$nfe[] = "vSeg=0"; // Total seguro
			$nfe[] = "vDesc=".number_format($this->notafiscal->gettotaldesconto(), 2, ".", ""); // Total desconto
			$nfe[] = "vII=".number_format($this->notafiscal->gettotalii(), 2, ".", ""); // Total do II
			if((in_array($operacaonota->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N" && ($this->notafiscal->gettotalbaseicmssubst() > 0 || $this->notafiscal->gettotalipi() > 0))){
				$nfe[] = "vIPI=0"; // Total ipi
			}else{
				$nfe[] = "vIPI=".number_format($this->notafiscal->gettotalipi(), 2, ".", ""); // Total ipi
			}


			$nfe[] = "vPIS=".number_format($this->notafiscal->gettotalpis(), 2, ".", ""); // Total de pis
			$nfe[] = "vCOFINS=".number_format($this->notafiscal->gettotalcofins(), 2, ".", ""); // Total de cofins
			if((in_array($operacaonota->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N" && ($this->notafiscal->gettotalbaseicmssubst() > 0 || $this->notafiscal->gettotalipi() > 0))){
				$nfe[] = "vOutro=".(number_format($this->notafiscal->gettotalipi() + $this->notafiscal->gettotalicmssubst(), 2, ".", "")); // Total outras tributacoes
			}else{
				$nfe[] = "vOutro=".($this->notafiscal->getoperacao() == "IM" ? number_format($this->notafiscal->gettotalsiscomex() + $this->notafiscal->gettotalpis() + $this->notafiscal->gettotalcofins(), 2, ".", "") : "0"); // Total outras tributacoes
			}

			$nfe[] = "vNF=".number_format($this->notafiscal->gettotalliquido(), 2, ".", ""); // Total da nota fiscal
			$nfe[] = "[Transportador]";
			$nfe[] = "modFrete=".$this->notafiscal->getmodfrete(); // Modalidade do frete (0 = por conta do emitente; 1 = por conta do destinatario; 2 = por conta de terceiro; 9 = sem frete)
			if($transportadora->exists()){
				$cidade_transp = objectbytable("cidade", $transportadora->getcodcidade(), $this->con);
				$nfe[] = "CNPJCPF=".removeformat($transportadora->getcpfcnpj()); // CNPJ da transportadora
				$nfe[] = "xNome=".$transportadora->getnome(); // Nome ou razao social da transportadora
				if($transportadora->gettppessoa() == "J"){
					$nfe[] = "IE=".removeformat($transportadora->getrgie()); // IE da transportadora
				}
				$nfe[] = "xEnder=".$transportadora->getendereco().", ".$transportadora->getnumero(); // Endereco da transportadora
				$nfe[] = "xMun=".utf8_encode($cidade_transp->getnome()); // Cidade da transportadora
				$nfe[] = "UF=".$cidade_transp->getuf(); // UF da transportadora
				$nfe[] = "Placa=".removeformat($this->notafiscal->gettranspplacavei()); // Placa do veiculo
				$nfe[] = "UFPlaca=".$this->notafiscal->gettranspufvei(); // UF da placa do veiculo
				$nfe[] = "RNTC=".$this->notafiscal->gettransprntc(); // Registro nacional de transportador de carga (ANTT)
			}
			$nfe[] = "[Volume001]";
			$nfe[] = "qVol=".ceil($this->notafiscal->gettotalquantidade()); // Quantidade de volumes transportados
			if(strlen($this->notafiscal->getespecie()) > 0){
				$nfe[] = "esp=".$this->notafiscal->getespecie(); // Especie dos volumes transportados
			}
			if(strlen($this->notafiscal->getmarca()) > 0){
				$nfe[] = "marca=".$this->notafiscal->getmarca(); // Marca dos volumes transportados
			}
			if(strlen($this->notafiscal->getnumeracao()) > 0){
				$nfe[] = "nVol=".$this->notafiscal->getnumeracao(); // Numeracao dos volumes transportados
			}
			$nfe[] = "pesoL=".number_format($this->notafiscal->getpesoliquido(), 3, ".", ""); // Peso liquido (em Kg)
			$nfe[] = "pesoB=".number_format($this->notafiscal->getpesobruto(), 3, ".", "");   // Peso liquido (em Kg)

			if($this->notafiscal->getoperacao() == "EX" || substr($this->notafiscal->getnatoperacao(), 0, 1) == "7"){ //se exportacao
				$nfe[] = "[Exporta]";
				$nfe[] = "UFEmbarque=".$this->notafiscal->getufdesembaraco(); //UF do estado onde ocorrera o embarque dos produtos
				$nfe[] = "xLocEmbarque=".$this->notafiscal->getlocaldesembaraco();  //Local onde ocorrera o embarque dos produtos
				if(substr($this->notafiscal->getnatoperacao(), 0, 1) == "7"){
					$nfe[] = 'UFSaidaPais='.$this->notafiscal->getufdesembaraco(); //UF do estado onde ocorrera o embarque dos produtos
					$nfe[] = 'xLocExporta='.$this->notafiscal->getlocaldesembaraco();  //Local onde ocorrera o embarque dos produtos
					$nfe[] = 'xLocDespacho='.$this->notafiscal->getlocaldesembaraco();  //Local onde ocorrera o embarque dos produtos
				}
			}

			$param_notafiscal_gerarlanctonfe = param("NOTAFISCAL", "GERARLANCTONFE", $this->con);
			if($param_notafiscal_gerarlanctonfe != 2){
				$lancamento = objectbytable("lancamento", NULL, $this->con);
				$lancamento->setidnotafiscal($this->notafiscal->getidnotafiscal());
				$lancamento->setorder("parcela");
				$arr_lancamento = object_array($lancamento);
				$lancamento = reset($arr_lancamento);

				if($param_notafiscal_gerarlanctonfe == 1 && $lancamento->getdtvencto() == $this->notafiscal->getdtemissao()){
					array_shift($arr_lancamento);
				}
				$cnt = 1;
				if(sizeof($arr_lancamento) > 0){
					$total_bruto = 0;
					$total_desconto = 0;
					$total_acrescimo = 0;
					$total_liquido = 0;
					foreach($arr_lancamento as $lancamento){
						$total_bruto += $lancamento->getvalorparcela();
						$total_desconto += $lancamento->getvalordescto();
						$total_acrescimo += $lancamento->getvaloracresc();
						$total_liquido += $lancamento->getvalorliquido();
					}
					$nfe[] = "[Fatura]";
					$nfe[] = "nFat=".$lancamento->getcodlancto();
					$nfe[] = "vOrig=".number_format($total_bruto, 2, ".", "");
					$nfe[] = "vDesc=".number_format($total_desconto, 2, ".", "");
					$nfe[] = "vLiq=".number_format($total_bruto - $total_desconto + $total_acrescimo, 2, ".", "");
					foreach($arr_lancamento as $lancamento){
						$nfe[] = "[Duplicata".str_pad($cnt, 3, "0", STR_PAD_LEFT)."]";
						$vencimento = $lancamento->getdtvencto(TRUE);
						$nfe[] = "nDup=".$lancamento->getparcela();
						$nfe[] = "dVenc=".$vencimento;
						$nfe[] = "vDup=".number_format($lancamento->getvalorliquido(), 2, ".", "");
						++$cnt;
					}
				}
			}

			$arr_observacao = array();
			if(in_array($this->estabelecimento->getregimetributario(), array("1"))){
				foreach($arr_natoperacao as $natoperacao){
					$arr_observacao[] = $natoperacao->getobservacaosimples();
				}
			}else{
				foreach($arr_natoperacao as $natoperacao){
					$arr_observacao[] = $natoperacao->getobservacao();
				}
			}
			if(sizeof($arr_observacao) > 0){
				$observacao = implode(" ", array_unique($arr_observacao))." ";
			}else{
				$observacao = "";
			}
			if(strlen($this->notafiscal->getcodfunc()) > 0){
				$funcionario = objectbytable("funcionario", $this->notafiscal->getcodfunc(), $this->con);
				$vendedor = $funcionario->getcodfunc()." - ".$funcionario->getnome();
			}

			if(strlen($this->notafiscal->getnumpedido()) > 0){
				$pedido = objectbytable("pedido", array($this->notafiscal->getcodestabelec(), $this->notafiscal->getnumpedido()), $this->con);
			}else{
				$pedido = objectbytable("pedido", null, $this->con);
			}

			$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
			$observacao = str_replace(
					array("[numnotafis]", "[serie]", "[totaldesconto]", "[totalacrescimo]", "[totalfrete]", "[totalicms]", "[totalbaseicms]", "[totalicmssubst]", "[totalbaseicmssubst]", "[totalipi]", "[totalbruto]", "[totalliquido]", "[aliqicms]", "[transpfone]", "[cupom]", "[numeroecf]", "[tbasep_simples]", "[icms_simples]", "[tvicms_simples]", "[vendedor]", "[refpedido]"), array($this->notafiscal->getnumnotafis(), $this->notafiscal->getserie(), $this->notafiscal->gettotaldesconto(TRUE), $this->notafiscal->gettotalacrescimo(TRUE), $this->notafiscal->gettotalfrete(TRUE), $this->notafiscal->gettotalicms(TRUE), $this->notafiscal->gettotalbaseicms(TRUE), $this->notafiscal->gettotalicmssubst(TRUE), $this->notafiscal->gettotalbaseicmssubst(TRUE), $this->notafiscal->gettotalipi(TRUE), $this->notafiscal->gettotalbruto(TRUE), $this->notafiscal->gettotalliquido(TRUE), $classfiscal->getaliqicms(TRUE), $transportadora->getfone(), $this->notafiscal->getcupom(), $this->notafiscal->getnumeroecf(), number_format($tbasep_simples, 2, ",", "."), number_format($icms_simples, 2, ",", ""), number_format($tvicms_simples, 2, ",", "."), $vendedor, $pedido->getrefpedido()), $observacao);

			$observacao .= " ".$this->notafiscal->getobservacaofiscal();
			$observacao = trim(removespecial(str_replace("\n", " ", $observacao)));
			$nfe[] = "[DadosAdicionais]";

			$query = "SELECT (SUM(CASE WHEN ncm.aliqmedia > 0 THEN (itnotafiscal.totalliquido * (ncm.aliqmedia/100)) ELSE (itnotafiscal.totalliquido * (produto.aliqmedia/100)) END)) AS aliqmedia ";
			$query .= "FROM itnotafiscal ";
			$query .= "INNER JOIN produto USING (codproduto) ";
			$query .= "INNER JOIN ncm USING (idncm) ";
			$query .= "WHERE itnotafiscal.idnotafiscal = ".$this->notafiscal->getidnotafiscal();

			$res = $this->con->query($query);
			$totalaliqmedia = $res->fetchColumn();

			$porcentaliqmedia = ($totalaliqmedia / $this->notafiscal->gettotalliquido()) * 100;
			$observacao_compl = "";
			if($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "N"){
				if($this->notafiscal->gettotalicmssubst() > 0 || $this->notafiscal->gettotalipi() > 0){
					$observacao_compl = "Informacoes adicionais de interesse do Fisco: DEVOLUCAO DE MERCADORIA VALOR OUTRAS DESPESAS ";
				}
				if($this->notafiscal->gettotalicmssubst() > 0){
					$observacao_compl .= " ICMS ST R$ ".$this->notafiscal->gettotalicmssubst(TRUE);
				}
				if($this->notafiscal->gettotalipi() > 0){
					$observacao_compl .= " IPI R$".$this->notafiscal->gettotalipi(TRUE);
				}
				if($natoperacao->getimprimpostoibpt() == "S"){
					if($totalaliqmedia > 0){
						$nfe[] = "infCpl=".$observacao.$observacao_compl.($operacaonota->getoperacao() == "VD" ? " Valor aproximado dos tributos R$ ".number_format($totalaliqmedia, 2, ",", ".")." (".number_format($porcentaliqmedia, 2, ",", ".")."%) Fonte IBPT" : "").$observacao_compl; // Observacao (natureza de operacao)
					}else{
						$nfe[] = "infCpl=".$observacao.$observacao_compl;
					}
				}else{
					$nfe[] = "infCpl=".$observacao.$observacao_compl;
				}
			}else{
				if($natoperacao->getimprimpostoibpt() == "S"){
					if($totalaliqmedia > 0){
						$nfe[] = "infCpl=".$observacao.$observacao_compl.($operacaonota->getoperacao() == "VD" ? " Valor aproximado dos tributos R$ ".number_format($totalaliqmedia, 2, ",", ".")." (".number_format($porcentaliqmedia, 2, ",", ".")."%) Fonte IBPT" : ""); // Observacao (natureza de operacao)
					}else{
						$nfe[] = "infCpl=".$observacao;
					}
				}else{
					$nfe[] = "infCpl=".$observacao;
				}
			}

			// Substitui os caracteres necessarios no arquivo
			foreach($nfe as $i => $linha){
				$linha = utf8_decode(removespecial($linha));
				//$linha = str_replace(array("<",">","&","\"","'"),array("&lt;","&gt;","&amp;","&quot;","&#39;"),$linha);
				$nfe[$i] = $linha;
			}

			$this->notafiscal = objectbytable("notafiscal", $this->notafiscal->getidnotafiscal(), $this->con);

			if($this->tipocomunicacao == TXT){
				$nlote = (is_object($this->notacomplemento) ? $this->notacomplemento->getnumnotafis() : $this->notafiscal->getnumnotafis());
				$nfe[] = '",'.$nlote.','.($imprimirnfe && $this->modoimpressao == "1" ? '1' : '0').',0)';

				echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, $nfe, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));


				$ok = FALSE;
				for($i = 0; $i < 6; $i++){
					sleep(10);
					$ok = $this->nfemonitorok(FALSE);
					if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
						break;
					}
				}
				$chavenfe = "";
				if((is_bool($ok)) && ($ok)){
					$enviaremail = FALSE;
					$fileresp = $this->estabelecimento->getdirimportnfe().SAITXT;
					$arr_resp = explode("\r\n", file_get_contents($fileresp));
					unlink($fileresp);
					$nStat = 0;
					$xmotivo = "";
					$retorno = FALSE;
					$nStat = 0;
					foreach($arr_resp as $linha_resp){
						if(!$retorno){
							$retorno = strtoupper($linha_resp) == "[NFE".$this->notafiscal->getnumnotafis()."]";
						}
						if($retorno){
							if(substr(strtoupper($linha_resp), 0, 5) == "CSTAT"){
								$nStat = (int) substr($linha_resp, 6);
								$this->notafiscal->setcodigostatus($nStat);
							}

							if(substr(strtoupper($linha_resp), 0, 7) == "XMOTIVO"){
								$xmotivo = substr($linha_resp, 8);
								$this->notafiscal->setxmotivo($xmotivo);
							}
							if(substr(strtoupper($linha_resp), 0, 5) == "CHNFE"){
								$chavenfe = substr($linha_resp, 6);
								$this->notafiscal->setchavenfe($chavenfe);
							}
							if(substr(strtoupper($linha_resp), 0, 5) == "NPROT"){
								$this->notafiscal->setprotocolonfe(substr($linha_resp, 6));
							}
							if(($nStat == 100) || ($nStat == 110) || ($nStat == 150)){
								$enviaremail = TRUE;
								$this->notafiscal->setdataautorizacao(date("Y-m-d"));
								$this->notafiscal->setsequenciaevento("0");
								if(($nStat == 100) || ($nStat == 150)){
									$this->notafiscal->setstatus("A"); //autorizada
								}else{
									$this->notafiscal->setstatus("D"); //denegada
								}
							}
							if($nStat == "302"){
								$this->notafiscal->setstatus("D"); //denegada
							}
						}
					}
					$_SESSION["ENVIARNFE"] = "NF-e transmitida com sucesso ao SEFAZ"."<br>".$nStat." - ".$xmotivo;
					if(!$retorno){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
						return FALSE;
					}else{
						if(!$this->notafiscal->save()){
							return FALSE;
						}else{
							if($enviaremail){
								if(strlen($chavenfe) > 0){
									if($this->imprimirdanfepdf()){
										if(file_exists("../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf")){
											$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf');";
										}else{
											$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe()."-nfe.pdf');";
										}
										echo script($arquivopdf);
									}
								}
								echo write_file($this->estabelecimento->getdirimportnfe().ENTTXT, 'NFE.ENVIAREMAIL("'.$parceiro->getemail().'","'.$this->notafiscal->getchavenfe().LITERALNFE.'",1,"","'.$copiasemails.'")', (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"));
								$ok = FALSE;
								for($i = 0; $i < 6; $i++){
									sleep(10);
									$ok = $this->nfemonitorok(FALSE);
									if(((is_bool($ok)) && ($ok)) || (is_string($ok))){
										break;
									}
								}
								if((is_bool($ok)) && ($ok)){
									$_SESSION["ENVIARNFE"] = $_SESSION["ENVIARNFE"]."<br>Email enviado para o destinat&aacute;rio com sucesso!";
								}else{
									$_SESSION["ENVIARNFE"] = $_SESSION["ENVIARNFE"]."<br>N&atilde;o foi possivel enviar email com a NF-e para o destinat&aacute;rio com sucesso!";
								}
							}
							return TRUE;
						}
					}
				}else{
					if(is_bool($ok)){
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel transmitir a NF-e ao SEFAZ.\n".htmlentities($ok);
					}
					return FALSE;
				}
			}else{
				$enviaremail = FALSE;

				$retorno = $this->nfemonitor->criarEnviarNfe(implode("\n", $nfe), $this->notafiscal->getnumnotafis(), ($imprimirnfe && $this->modoimpressao == "1" ? 1 : 0));
				if(is_array($retorno)){
					$chavenfe = "";
					$arr_retorno = $retorno["OK"]["RETORNO"];
					if(is_array($arr_retorno)){
						$nfenf = "NFE".$this->notafiscal->getnumnotafis();

						$arr_retorno = $retorno["OK"][$nfenf];
						if(is_array($arr_retorno)){
							$chavenfe = $arr_retorno["ChNFe"];
							$this->notafiscal->setcodigostatus($arr_retorno["CStat"]);
							$this->notafiscal->setxmotivo($arr_retorno["XMotivo"]);
							$this->notafiscal->setchavenfe($chavenfe);
							$this->notafiscal->setprotocolonfe($arr_retorno["NProt"]);
							if(($arr_retorno["CStat"] == 100) || ($arr_retorno["CStat"] == 110) || ($arr_retorno["CStat"] == 150)){
								$enviaremail = TRUE;
								$this->notafiscal->setsequenciaevento("0");
								$this->notafiscal->setdataautorizacao(date("Y-m-d"));
								if(($arr_retorno["CStat"] == 100) || ($arr_retorno["CStat"] == 150)){
									$this->notafiscal->setstatus("A"); //autorizada
								}else{
									$this->notafiscal->setstatus("D"); //denegada
								}
							}
							if($arr_retorno["CStat"] == "302"){
								$this->notafiscal->setstatus("D"); //denegada
							}
							$_SESSION["ENVIARNFE"] = "NF-e transmitida com sucesso ao SEFAZ"."<br>".$arr_retorno["CStat"]." - ".$arr_retorno["XMotivo"];
						}else{
							$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
							return FALSE;
						}
					}else{
						$_SESSION["ERROR"] = "N&atilde;o foi possivel identificar o retorno do SEFAZ sobre a NF-e. FaÃƒÂ§a uma consulta da NF-e ou tente fazer a transmiss&atilde;o novamente.";
						return FALSE;
					}
					if(!$this->notafiscal->save()){
						return FALSE;
					}else{
						if($enviaremail){
							if(strlen($chavenfe) > 0){
								if($this->imprimirdanfepdf()){
									if(file_exists("../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf")){
										$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe().".pdf');";
									}else{
										$arquivopdf = "window.open('../temp/pdfnfe/".$this->notafiscal->getchavenfe()."-nfe.pdf');";
									}
									echo script($arquivopdf);
								}
							}
							$retorno = $this->nfemonitor->enviarNFeEmail($parceiro->getemail(), $this->notafiscal->getchavenfe().LITERALNFE, 1, "", $copiasemails);
							if(is_bool($retorno) && $retorno){
								$_SESSION["ENVIARNFE"] = $_SESSION["ENVIARNFE"]."<br>Email enviado para o destinat&aacute;rio com sucesso!";
								return TRUE;
							}else{
								$_SESSION["ENVIARNFE"] = $_SESSION["ENVIARNFE"]."<br>N&aatilde;o foi possivel enviar o email para o destinat&aacute;rio!";
							}
						}
						return TRUE;
					}
				}else{
					//$retorno = str_replace(array("\n","\r"),array("\r\n",""), tmlentities($retorno));
					$retorno = str_replace(array("\n", "\r"), array("\r\n", ""), utf8_encode($retorno));
					$_SESSION["ERROR"] = $retorno;
					return FALSE;
				}
			}
		}
	}

	private function montachavenfe($xmlnfe){
		$ide = $xmlnfe->getElementsByTagName("ide")->item(0);
		$emit = $xmlnfe->getElementsByTagName("emit")->item(0);
		$cUF = $ide->getElementsByTagName("cUF")->item(0)->nodeValue;
		$dEmi = $ide->getElementsByTagName("dEmi")->item(0)->nodeValue;
		$CNPJ = $emit->getElementsByTagName("CNPJ")->item(0)->nodeValue;
		$mod = $ide->getElementsByTagName("mod")->item(0)->nodeValue;
		$serie = $ide->getElementsByTagName("serie")->item(0)->nodeValue;
		$nNF = $ide->getElementsByTagName("nNF")->item(0)->nodeValue;
		$tpEmis = $ide->getElementsByTagName("tpEmis")->item(0)->nodeValue;
		$cNF = $ide->getElementsByTagName("cNF")->item(0)->nodeValue;
		if(strlen($cNF) != 8){
			$cNF = $ide->getElementsByTagName("cNF")->item(0)->nodeValue = rand(10000001, 99999999);
		}
		$tempData = $dt = explode("-", $dEmi);
		$forma = "%02d%02d%02d%s%02d%03d%09d%01d%08d"; //%01d";
		$tempChave = sprintf($forma, $cUF, $tempData[0] - 2000, $tempData[1], $CNPJ, $mod, $serie, $nNF, $tpEmis, $cNF);

		$cDV = $ide->getElementsByTagName("cDV")->item(0)->nodeValue = $this->__calculaDV($tempChave);
		$chave = $tempChave .= $cDV;
		$this->chave = $chave;
		$infNFe = $xmlnfe->getElementsByTagName("infNFe")->item(0);
		$infNFe->setAttribute("Id", "NFe".$chave);
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Autor.......: Jesus
	  Objetivo....: formatar retornar um numero inteiro com zeros a esquerda
	  Parametros..:
	  $numero.: o valor inteiro a ser formatado
	  $tamanho: tamnaho total dos dados apos a formatcao
	  Retorno: numero interiro formatado
	  --------------------------------------------------------------------------------------------------------- */

	function formatainteiro($numero, $tamanho){
		return str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
	}

	function processaretorno($retornoacbr){
		$result = array();

		return $result;
	}

	function gerarnfephp($transmitir = TRUE){
		$_SESSION["ERROR"] = "";
		$_SESSION["TRANSMITIRNFE"] = "";
		$arr_ini = array(1, 2, 3, 4, 5, 6);


		$param_notafiscal_nfedescoucomp = param("NOTAFISCAL", "NFEDESCOUCOMP", $this->con);

		try{
			if(!is_object($this->notacomplemento)){
				if($this->notafiscal->flag_itnotafiscal){
					$arr_itnotafiscal_aux = $this->notafiscal->itnotafiscal;
				}else{
					$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
					$itnotafiscal->setorder("seqitem");
					$itnotafiscal->setidnotafiscal($this->notafiscal->getidnotafiscal());
					$arr_itnotafiscal_aux = object_array($itnotafiscal);
				}
				$arr_itnotafiscal = array();
				foreach($arr_itnotafiscal_aux as $itnotafiscal){
					$arr_itnotafiscal[$itnotafiscal->getiditnotafiscal()] = $itnotafiscal;
				}

				// Busca as composicoes
				$arr_composicao = array();
				$composicao = objectbytable("composicao", NULL, $this->con);
				$arr_composicao_aux = object_array($composicao);
				foreach($arr_composicao_aux as $composicao){
					$arr_composicao[$composicao->getcodproduto()] = $composicao;
				}

				// Remove os itens pais/filhos de composicao
				$arr_itnotafiscal_aux = $arr_itnotafiscal;
				foreach($arr_itnotafiscal as $i => $itnotafiscal){
					if($itnotafiscal->getcomposicao() == "P"){
						$composicao = $arr_composicao[$itnotafiscal->getcodproduto()];
						if(is_object($composicao)){
							if($composicao->getexplosaoauto() == "S"){
								unset($arr_itnotafiscal[$i]);
							}
						}
					}elseif($itnotafiscal->getcomposicao() == "F"){
						$composicao = $arr_composicao[$arr_itnotafiscal_aux[$itnotafiscal->getiditnotafiscalpai()]->getcodproduto()];
						if(is_object($composicao)){
							if($composicao->getexplosaoauto() == "N"){
								unset($arr_itnotafiscal[$i]);
							}
						}
					}
				}

				// Remove os itens que estao com quantidade zero
				foreach($arr_itnotafiscal as $i => $itnotafiscal){
					if($itnotafiscal->getquantidade() * $itnotafiscal->getqtdeunidade() == 0 && $itnotafiscal->getoperacao() != "AN"){
						unset($arr_itnotafiscal[$i]);
					}
				}
			}

			//$this->estabelecimento = objectbytable("estabelecimento",$this->notafiscal->getcodestabelec(),$this->con);
			$cidade_estabelecimento = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);
			$estado_estabelecimento = objectbytable("estado", $cidade_estabelecimento->getuf(), $this->con);
			$pais_estabelecimento = objectbytable("pais", "01058", $this->con);
			$natoperacao = objectbytable("natoperacao", (is_object($this->notacomplemento) ? $this->notacomplemento->getnatoperacao() : $this->notafiscal->getnatoperacao()), $this->con);
			if(!is_object($this->notacomplemento)){
				$condpagto = objectbytable("condpagto", $this->notafiscal->getcodcondpagto(), $this->con);
			}
			$operacaonota = objectbytable("operacaonota", $this->notafiscal->getoperacao(), $this->con);
			$transportadora = objectbytable("transportadora", $this->notafiscal->getcodtransp(), $this->con);
			$forma_pagamento = objectbytable("especie", $this->notafiscal->getcodespecie(), $this->con);
			//$forma_pagamento = objectbytable("especie", (is_object($this->notacomplemento) ? $this->notacomplemento->getcodespecie() : $this->notafiscal->getcodespecie()), $this->con);
			$contabilidade = objectbytable("contabilidade", $this->estabelecimento->getcodestabelec(), $this->con);
			$arr_notafiscalref = NULL;
			if(!is_object($this->notacomplemento)){
				$notafiscalref = objectbytable("notafiscalreferenciada", NULL, $this->con);
				$notafiscalref->setidnotafiscal($this->notafiscal->getidnotafiscal());
				$arr_notafiscalref = object_array($notafiscalref);
				$notafiscalref = NULL;
			}
			$contingencianfe = objectbytable("contingencianfe", NULL, $this->con);
			$contingencianfe->setcodestabelec($this->estabelecimento->getcodestabelec());
			$contingencianfe->setstatus("A");
			$contingencianfe->searchbyobject();
			$param_notafiscal_tipoeannota = param("NOTAFISCAL", "TIPOEANNOTA", $this->con);
			$param_notafiscal_gerarlanctonfe = param("NOTAFISCAL", "GERARLANCTONFE", $this->con);

			switch($this->notafiscal->gettipoparceiro()){
				case "C": // Cliente
					$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
					$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidaderes(), $this->con);
					$pais_parceiro = objectbytable("pais", $parceiro->getcodpaisres(), $this->con);
					break;
				case "E": // Estabelecimento
					$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
					$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
					$pais_parceiro = objectbytable("pais", "01058", $this->con);
					break;
				case "F": // Fornecedor
					$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
					$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
					$pais_parceiro = objectbytable("pais", $parceiro->getcodpais(), $this->con);
					break;
				default:
					$_SESSION["ERROR"] = "Tipo de parceiro (".$this->notafiscal->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
					return FALSE;
			}

			if(strlen($this->notafiscal->getidnotafiscalref()) > 0){
				$notafiscalref = objectbytable("notafiscal", $this->notafiscal->getidnotafiscalref(), $this->con);
			}

			$estado_parceiro = objectbytable("estado", $cidade_parceiro->getuf(), $this->con);

			$iesubsttributario = objectbytable("estabelecimentoiest", NULL, $this->con);
			$iesubsttributario->setcodestabelec($this->estabelecimento->getcodestabelec());
			$iesubsttributario->setuf($estado_parceiro->getuf());
			$iesubsttributario->searchbyobject();

			//Identificacao da NF-e
			$cNFAuxiliar = (is_object($this->notacomplemento) ? md5($this->notacomplemento->getnumnotafis()) : md5($this->notafiscal->getnumnotafis()));
			$cNFAuxiliar = sprintf("%.0f", hexdec($cNFAuxiliar));
			//$posIni = $arr_ini[$this->weekOfMonth($this->notafiscal->getdtemissao()) - 1];
			$posIni = $arr_ini[$this->weekOfMonth((is_object($this->notacomplemento) ? $this->notacomplemento->getdtemissao() : $this->notafiscal->getdtemissao())) - 1];
			$cNFAuxiliar = str_pad(substr($cNFAuxiliar, $posIni * 5, 8), 8, "0", STR_PAD_LEFT);
			if(!is_object($this->notacomplemento)){
				$chavenfe = $this->notafiscal->calcular_chavenfe();
				if(!$this->notafiscal->save()){
					$_SESSION["ERROR"] = "NÃ£o foi possivel salvar informaÃ§Ãµes da NF-e. Tente novamente";
					return FALSE;
				}
			}else{
				$chavenfe = $this->notacomplemento->calcular_chavenfe();
				if(!$this->notacomplemento->save()){
					$_SESSION["ERROR"] = "NÃ£o foi possivel salvar informaÃ§Ãµes da NF-e. Tente novamente";
					return FALSE;
				}
			}

			$versao = $this->versaonfe;


			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->versao = $this->versaonfe; //versÃ£o do layout
				$std->Id = $chavenfe; //se o Id de 44 digitos nÃ£o for passado serÃ¡ gerado automaticamente
				$std->pk_nItem = null; //deixe essa variavel sempre como NULL
				$resp = $this->nfe->taginfNFe($std);
			}else{
				$resp = $this->nfe->taginfNFe($chavenfe, $versao);
			}

			//Dados da NFe (ide)
			$cUF = $estado_estabelecimento->getcodoficial(); //codigo numerico do estado
			//$cNF = (is_object($this->notacomplemento) ? str_pad($this->notacomplemento->getnumnotafis(), 8, "0", STR_PAD_LEFT) : str_pad($this->notafiscal->getnumnotafis(), 8, "0", STR_PAD_LEFT)); //numero aleatÃƒÂ³rio da NF
			$cNF = $cNFAuxiliar; //numero aleatÃƒÂ³rio da NF
			$natOp = $natoperacao->getdescricao(); //natureza da operaÃƒÂ§ÃƒÂ£o
			if($param_notafiscal_gerarlanctonfe == 2 || is_object($this->notacomplemento)){
				$indPag = "0";
			}else{
				$indPag = ($condpagto->getdia1() == 0 && $condpagto->getpercdia1() == 100 ? "0" : "1"); //0=Pagamento ÃƒÂ  vista; 1=Pagamento a prazo; 2=Outros
			}
			$mod = '55'; //modelo da NFe 55 ou 65 essa ÃƒÂºltima NFCe
			$serie = (is_object($this->notacomplemento) ? $this->notacomplemento->getserie() : $this->notafiscal->getserie()); //serie da NFe
			$nNF = (is_object($this->notacomplemento) ? $this->notacomplemento->getnumnotafis() : $this->notafiscal->getnumnotafis()); // numero da NFe
			$dhEmi = (is_object($this->notacomplemento) ? $this->notacomplemento->getdtemissao()."T".date("h:i:s")."-02:00" : $this->notafiscal->getdtemissao()."T".date("h:i:s")."-02:00");  //para versÃƒÂ£o 3.00 '2014-02-03T13:22:42-3.00' nÃƒÂ£o informar para NFCe
			$dhSaiEnt = $natoperacao->getgeradtentrega() == "S" ? ((is_object($this->notacomplemento) ? $this->notacomplemento->getdtemissao()."T".date("h:i:s")."-02:00" : $this->notafiscal->getdtentrega()."T".date("h:i:s")."-02:00")) : "";  //para versÃƒÂ£o 3.00 '2014-02-03T13:22:42-3.00' nÃƒÂ£o informar para NFCe : ""; //versÃƒÂ£o 2.00, 3.00 e 3.10
			if(is_object($this->notacomplemento)){
				$cfopnf = substr(removeformat($this->notacomplemento->getnatoperacao()), 0, 1);
				if(in_array($cfopnf, array("1", "2", "3"))){
					$tpNF = "0";
				}else{
					$tpNF = "1";
				}
			}else{
				$tpNF = ($operacaonota->gettipo() == "E" ? 0 : "1"); // Tipo de documento fiscal (0 = entrada; 1 = saida)
			}
			if(is_object($this->notafiscal) && $this->notafiscal->getoperacao() == "AN"){
				if($this->notafiscal->gettipoajuste() == "0"){
					$tpNF = "0";
				}elseif($this->notafiscal->gettipoajuste() == "1"){
					$tpNF = "1";
				}
			}

			if($estado_estabelecimento != $estado_parceiro){
				if(!in_array($operacaonota->getoperacao(), array("IM", "EX"))){
					$idDest = "2"; // operaÃƒÂ§ÃƒÂ£o interestadual
				}else{
					if($operacaonota->getoperacao() == "IM" || (is_object($this->notafiscal) && substr($this->notafiscal->getnatoperacao(), 0, 1) == "7")){
						$idDest = "3"; // operacao com exterior - importacao
					}else{
						$idDest = "1"; // operacao com exterior - exportacao
					}
				}
			}else{
				$idDest = "1";  //operaÃƒÂ§ÃƒÂ£o interna
			}
			$cMunFG = $cidade_estabelecimento->getcodoficial(); // Codigo do municipio do emitente (tabela IBGE)
			$tpImp = "1"; //0=Sem geraÃƒÂ§ÃƒÂ£o de DANFE; 1=DANFE normal, Retrato; 2=DANFE normal, Paisagem;
			//3=DANFE Simplificado; 4=DANFE NFC-e; 5=DANFE NFC-e em mensagem eletrÃƒÂ´nica
			//(o envio de mensagem eletrÃƒÂ´nica pode ser feita de forma simultÃƒÂ¢nea com a impressÃƒÂ£o do DANFE;
			//usar o tpImp=5 quando esta for a ÃƒÂºnica forma de disponibilizaÃƒÂ§ÃƒÂ£o do DANFE).
			$tpEmis = $this->notafiscal->gettipoemissao(); //1=EmissÃƒÂ£o normal (nÃƒÂ£o em contingÃƒÂªncia);
			//2=ContingÃƒÂªncia FS-IA, com impressÃƒÂ£o do DANFE em formulÃƒÂ¡rio de seguranÃƒÂ§a;
			//3=ContingÃƒÂªncia SCAN (Sistema de ContingÃƒÂªncia do Ambiente Nacional);
			//4=ContingÃƒÂªncia DPEC (DeclaraÃƒÂ§ÃƒÂ£o PrÃƒÂ©via da EmissÃƒÂ£o em ContingÃƒÂªncia);
			//5=ContingÃƒÂªncia FS-DA, com impressÃƒÂ£o do DANFE em formulÃƒÂ¡rio de seguranÃƒÂ§a;
			//6=ContingÃƒÂªncia SVC-AN (SEFAZ Virtual de ContingÃƒÂªncia do AN);
			//7=ContingÃƒÂªncia SVC-RS (SEFAZ Virtual de ContingÃƒÂªncia do RS);
			//9=ContingÃƒÂªncia off-line da NFC-e (as demais opÃƒÂ§ÃƒÂµes de contingÃƒÂªncia sÃƒÂ£o vÃƒÂ¡lidas tambÃƒÂ©m para a NFC-e);
			//Nota: Para a NFC-e somente estÃƒÂ£o disponÃƒÂ­veis e sÃƒÂ£o vÃƒÂ¡lidas as opÃƒÂ§ÃƒÂµes de contingÃƒÂªncia 5 e 9.
			$cDV = substr($chavenfe, -1); //digito verificador
			$tpAmb = $this->ambiente; //1=ProduÃƒÂ§ÃƒÂ£o; 2=HomologaÃƒÂ§ÃƒÂ£o
			//1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=DevoluÃƒÂ§ÃƒÂ£o/Retorno.
			$finNFe = (is_object($this->notacomplemento) ? $this->notacomplemento->getfinalidade() : $this->notafiscal->getfinalidade());
			if($operacaonota->getoperacao() == "EX"){
				$indFinal = "1"; //0=Nao; 1=Consumidor final;
			}else{
				$indFinal = "0"; //0=Nao; 1=Consumidor final;
			}

//			$indIEDest = ($parceiro->gettppessoa() == "F" || strlen(trim(removeformat($parceiro->getrgie()), "0")) == 0 ? "9" : (strtoupper($parceiro->getrgie()) != "ISENTO" ? "1" : "2")); // Inscricao estadual do destinatario
			if($parceiro->gettppessoa() == "F" || strlen(trim(removeformat($parceiro->getrgie()), "0")) == 0){
				$indIEDest = "9";
			}elseif(in_array($this->notafiscal->getoperacao(), array("VD", "DC")) && $parceiro->gettppessoa() == "J" && $parceiro->getcontribuinteicms() == "N"){
				$indIEDest = "9";
			}else{
				if(strtoupper($parceiro->getrgie()) != "ISENTO"){
					$indIEDest = "1";
				}else{
					$indIEDest = "2";
				}
			}



			if($indIEDest == "9"){
				$indFinal = "1";
			}

			$indPres = (is_object($this->notacomplemento) ? "0" : $this->notafiscal->getindpres()); //0=NÃƒÂ£o se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
			//1=OperaÃƒÂ§ÃƒÂ£o presencial;
			//2=OperaÃƒÂ§ÃƒÂ£o nÃƒÂ£o presencial, pela Internet;
			//3=OperaÃƒÂ§ÃƒÂ£o nÃƒÂ£o presencial, Teleatendimento;
			//4=NFC-e em operaÃƒÂ§ÃƒÂ£o com entrega a domicÃƒÂ­lio;
			//9=OperaÃƒÂ§ÃƒÂ£o nÃƒÂ£o presencial, outros.
			$procEmi = "0"; //0=EmissÃƒÂ£o de NF-e com aplicativo do contribuinte;
			//1=EmissÃƒÂ£o de NF-e avulsa pelo Fisco;
			//2=EmissÃƒÂ£o de NF-e avulsa, pelo contribuinte com seu certificado digital, atravÃƒÂ©s do site do Fisco;
			//3=EmissÃƒÂ£o NF-e pelo contribuinte com aplicativo fornecido pelo Fisco.
			$verProc = "3.22.8"; //versÃƒÂ£o do aplicativo emissor
			if($contingencianfe->exists()){ // Verifica se existe alguma contingencia em aberto
				//$serie = "900";
				$dhCont = $contingencianfe->getdataini()."T".substr($contingencianfe->gethoraini(), 0, 8)."-02:00"; // Data e hora da entrada em contingencia (formato AAAA-MM-DD HH:MM:SS)
				$xJust = $contingencianfe->getobservacao(); // Justificativa da contingencia
				$this->nfeTools->ativaContingencia($estado_estabelecimento->getuf());
			}
			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->cUF = $cUF;
				$std->cNF = $cNF;
				$std->natOp = $natOp;
				if($this->versaonfe == self::VERSAO_NFE3){
					$std->indPag = $indPag; //NÃƒO EXISTE MAIS NA VERSÃƒO 4.00
				}
				$std->mod = $mod;
				$std->serie = $serie;
				$std->nNF = $nNF;
				$std->dhEmi = $dhEmi;
				$std->dhSaiEnt = $dhSaiEnt;
				$std->tpNF = $tpNF;
				$std->idDest = $idDest;
				$std->cMunFG = $cMunFG;
				$std->tpImp = $tpImp;
				$std->tpEmis = $tpEmis;
				$std->cDV = $cDV;
				$std->tpAmb = $tpAmb;
				$std->finNFe = $finNFe;
				$std->indFinal = $indFinal;
				$std->indPres = $indPres;
				$std->procEmi = $procEmi;
				$std->verProc = $verProc;
				$std->dhCont = $dhCont;
				$std->xJust = $xJust;
				$resp = $this->nfe->tagide($std);
			}else{
				$resp = $this->nfe->tagide($cUF, $cNF, $natOp, $indPag, $mod, $serie, $nNF, $dhEmi, $dhSaiEnt, $tpNF, $idDest, $cMunFG, $tpImp, $tpEmis, $cDV, $tpAmb, $finNFe, $indFinal, $indPres, $procEmi, $verProc, $dhCont, $xJust);
			}

			//Documento refrenciado
			if(is_object($this->notacomplemento)){ // Se estiver uma chave de NFe referenciada informada, deve gerar a informacao
				$refNfe = $this->notafiscal->getchavenfe(); // Chave da NFe referenciada
				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->refNFe = $refNfe;
					$resp = $this->nfe->tagrefNFe($std);
				}else{
					$resp = $this->nfe->tagrefNFe($refNfe);
				}
			}elseif(in_array($this->notafiscal->getoperacao(), array("DF", "DC", "PR")) && strlen($this->notafiscal->getchavenferef()) > 0){
				$refNfe = $this->notafiscal->getchavenferef(); // Chave da NFe referenciada
				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->refNFe = $refNfe;
					$resp = $this->nfe->tagrefNFe($std);
				}else{
					$resp = $this->nfe->tagrefNFe($refNfe);
				}
			}elseif(in_array($this->notafiscal->getoperacao(), array("DF", "DC", "PR")) || (in_array($this->notafiscal->getoperacao(), array("VD")) && (strlen($this->notafiscal->getcupom()) > 0) || (strlen($this->notafiscal->getcupomnotafiscal()) > 0))){
				if(!is_null($arr_notafiscalref) && count($arr_notafiscalref) > 0 && $this->notafiscal->getoperacao() !== "PR"){
					foreach($arr_notafiscalref as $notafiscalref){
						$notafiscalreferenciada = objectbytable("notafiscal", $notafiscalref->getidnotafiscalref(), $this->con);
						if($notafiscalreferenciada->exists()){
							if(strlen($notafiscalreferenciada->getchavenfe()) > 0){
								$refNfe = $notafiscalreferenciada->getchavenfe(); // Chave da NFe referenciada
								if($this->versaoapi == self::VERSAO_API5){
									$std = new stdClass();
									$std->refNFe = $refNfe;
									$resp = $this->nfe->tagrefNFe($std);
								}else{
									$resp = $this->nfe->tagrefNFe($refNfe);
								}
							}else{
								switch($notafiscalreferenciada->gettipoparceiro()){
									case "C": $parceiroref = objectbytable("cliente", $notafiscalreferenciada->getcodparceiro(), $this->con);
										break;
									case "E": $parceiroref = objectbytable("estabelecimento", $notafiscalreferenciada->getcodparceiro(), $this->con);
										break;
									case "F": $parceiroref = objectbytable("fornecedor", $notafiscalreferenciada->getcodparceiro(), $this->con);
										break;
									default: $_SESSION["ERROR"] = "Tipo de parceiro (".$notafiscalreferenciada->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
										return FALSE;
								}
								if($notafiscalreferenciada->gettipoparceiro() == "C"){
									$estadoref = objectbytable("estado", $parceiroref->getufent(), $this->con);
								}else{
									$estadoref = objectbytable("estado", $parceiroref->getuf(), $this->con);
								}

								$cUF = $estadoref->getcodoficial();
								$AAMM = convert_date($notafiscalreferenciada->getdtemissao(), "Y-m-d", "ym");
								$CNPJ = removeformat($parceiroref->getcpfcnpj());
								$mod = "01";
								$serie = $notafiscalreferenciada->getserie();
								$nNFRef = $notafiscalreferenciada->getnumnotafis();
								if($this->versaoapi == self::VERSAO_API5){
									$std = new stdClass();
									$std->cUF = $cUF;
									$std->AAMM = $AAMM;
									$std->CNPJ = $CNPJ;
									$std->mod = $mod;
									$std->serie = $serie;
									$std->nNF = $nNFRef;
									$resp = $this->nfe->tagrefNF($std);
								}else{
									$resp = $this->nfe->tagrefNF($cUF, $AAMM, $CNPJ, $mod, $serie, $nNFRef);
								}
							}
						}
					}
				}elseif((strlen($this->notafiscal->getidnotafiscalref()) > 0 || (isset($notafiscalref) && strlen($notafiscalref->getchavenferef())) > 0) && $this->notafiscal->getoperacao() !== "PR"){
					$refNfe = $notafiscalref->getchavenfe(); // Chave da NFe referenciada
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->refNFe = $refNfe;
						$resp = $this->nfe->tagrefNFe($std);
					}else{
						$resp = $this->nfe->tagrefNFe($refNfe);
					}
				}elseif((strlen($this->notafiscal->getcupom()) > 0) || (strlen($this->notafiscal->getcupomnotafiscal()) > 0)){ // Se estiver um cupom informado, gerar informacoes do ecf
					$arr_cupomnotafiscal = explode(";", $this->notafiscal->getcupomnotafiscal());
					$arr_cupomnotafiscal = array_filter($arr_cupomnotafiscal);
					if(strlen($this->notafiscal->getchavecfe()) > 0){
						$std = new stdClass();
						$std->refNFe = $this->notafiscal->getchavecfe();
						$resp = $this->nfe->tagrefNFe($std);
					}else if(count($arr_cupomnotafiscal) > 0){
						foreach($arr_cupomnotafiscal as $cupomnotafiscal){
							$arr_cupom = explode("|", $cupomnotafiscal);
							if(!is_null($arr_cupom[0]) && !is_null($arr_cupom[1])){
								$mod = "2C";  // Modelo do documento
								$nECF = $arr_cupom[1]; // Numero do ECF
								$nCOO = $arr_cupom[0]; // Numero do cupom
								if($this->versaoapi == self::VERSAO_API5){
									$std = new stdClass();
									$std->mod = $mod;
									$std->nECF = $nECF;
									$std->nCOO = $nCOO;
									$resp = $this->nfe->tagrefECF($std);
								}else{
									$resp = $this->nfe->tagrefECF($mod, $nECF, $nCOO);
								}
							}
						}
					}else{
						$mod = "2C"; // Modelo do documento
						$nECF = $this->notafiscal->getnumeroecf(); // Numero do ECF
						if(strlen($this->notafiscal->getcupom()) > 0){
							$nCOO = $this->notafiscal->getcupom(); // Numero do cupom
						}else{
							$nCOO = $this->notafiscal->getcupomnotafiscal();
						}
						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->mod = $mod;
							$std->nECF = $nECF;
							$std->nCOO = $nCOO;
							$resp = $this->nfe->tagrefECF($std);
						}else{
							$resp = $this->nfe->tagrefECF($mod, $nECF, $nCOO);
						}
					}
				}else{
					if(strlen($this->notafiscal->getidnotafiscalref()) > 0){
						switch($notafiscalref->gettipoparceiro()){
							case "C": $parceiroref = objectbytable("cliente", $notafiscalref->getcodparceiro(), $this->con);
								break;
							case "E": $parceiroref = objectbytable("estabelecimento", $notafiscalref->getcodparceiro(), $this->con);
								break;
							case "F": $parceiroref = objectbytable("fornecedor", $notafiscalref->getcodparceiro(), $this->con);
								break;
							default: $_SESSION["ERROR"] = "Tipo de parceiro (".$notafiscalref->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
								return FALSE;
						}
						if($notafiscalref->gettipoparceiro() == "C"){
							$estadoref = objectbytable("estado", $parceiroref->getufent(), $this->con);
						}else{
							$estadoref = objectbytable("estado", $parceiroref->getuf(), $this->con);
						}

						if($this->notafiscal->getoperacao() !== "PR"){
							$cUF = $estadoref->getcodoficial();
							$AAMM = convert_date($notafiscalref->getdtemissao(), "Y-m-d", "ym");
							$CNPJ = removeformat($parceiroref->getcpfcnpj());
							$mod = "01";
							$serie = $notafiscalref->getserie();
							$nNFRef = $notafiscalref->getnumnotafis();
							if($this->versaoapi == self::VERSAO_API5){
								$std = new stdClass();
								$std->cUF = $cUF;
								$std->AAMM = $AAMM;
								$std->CNPJ = $CNPJ;
								$std->mod = $mod;
								$std->serie = $serie;
								$std->nNF = $nNFRef;
								$resp = $this->nfe->tagrefNF($std);
							}else{
								$resp = $this->nfe->tagrefNF($cUF, $AAMM, $CNPJ, $mod, $serie, $nNFRef);
							}
						}else{
							$cUF = $estadoref->getcodoficial();
							$AAMM = convert_date($notafiscalref->getdtemissao(), "Y-m-d", "ym");
							if($parceiroref->gettppessoa() == "J"){
								$CNPJ = removeformat($parceiroref->getcpfcnpj());
								$CPF = "";
							}else{
								$CNPJ = "";
								$CPF = removeformat($parceiroref->getcpfcnpj());
							}
							$numIE = removeformat($parceiroref->getrgie());
							$mod = "04";
							$serie = $notafiscalref->getserie();
							$nNFRef = $notafiscalref->getnumnotafis();
							if($this->versaoapi == self::VERSAO_API5){
								$std = new stdClass();
								$std->cUF = $cUF;
								$std->AAMM = $AAMM;
								$std->CNPJ = $CNPJ;
								$std->CPF = $CPF;
								$std->IE = $numIE;
								$std->mod = $mod;
								$std->serie = $serie;
								$std->nNF = $nNFRef;
								$resp = $this->nfe->tagrefNFP($std);
							}else{
								$resp = $this->nfe->tagrefNFP($cUF, $AAMM, $CNPJ, $CPF, $numIE, $mod, $serie, $nNFRef);
							}
						}
					}
				}
			}

			//Identificacao do emitente
			$CNPJ = removeformat($this->estabelecimento->getcpfcnpj()); //CNPJ do emitente
			$CPF = "";
			$xNome = $this->estabelecimento->getrazaosocial(); // Razao social do emitente
			$xFant = $this->estabelecimento->getnome(); // Nome fantasia do emitente
			$IE = (strlen(trim(removeformat($this->estabelecimento->getrgie()), "0")) > 0 ? removeformat($this->estabelecimento->getrgie()) : ""); // Inscricao estadual do emitente
			//if($this->notafiscal->gettotalicmssubst() > 0 && $iesubsttributario->exists()){
			$IEST = removeformat($iesubsttributario->getiest());
			//}else{
			//	$IEST = "";
			//}
			$IM = "";
			$CNAE = "";
			if($operacaonota->getoperacao() == "IM"){
				$CRT = $this->estabelecimento->getregimetributario();
				// Se for do lucro presumido deve se mandar como normal CRT :: Verificar rejeicao 812 [robson]
				if($parceiro->getuf() == "EX" && $this->estabelecimento->getregimetributario() == "2"){
					$CRT = "3";
				}
			}else{
				/*
				  if($this->estabelecimento->getregimetributario() == 2){
				  $CRT = "3";
				  }else{
				  $CRT = $this->estabelecimento->getregimetributario();
				  }
				 *
				 */
				$CRT = $this->estabelecimento->getregimetributario();
			}

			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->xNome = $xNome;
				$std->xFant = $xFant;
				$std->IE = $IE;
				$std->IEST = $IEST;
				$std->IM = $IM;
				$std->CNAE = $CNAE;
				$std->CRT = $CRT;
				$std->CNPJ = $CNPJ; //indicar apenas um CNPJ ou CPF
				$std->CPF = $CPF;
				$resp = $this->nfe->tagemit($std);
			}else{
				$resp = $this->nfe->tagemit($CNPJ, $CPF, $xNome, $xFant, $IE, $IEST, $IM, $CNAE, $CRT);
			}

			// Endrereco do emitente
			$xLgr = $this->estabelecimento->getendereco(); // Endereco do emitente
			$nro = $this->estabelecimento->getnumero(); // Numero do endereco do emitente
			$xCpl = (strlen($this->estabelecimento->getcomplemento()) > 0 ? $this->estabelecimento->getcomplemento() : ""); // Complemento do endereco do emitente
			$xBairro = $this->estabelecimento->getbairro(); // Bairro do emitente
			$cMun = $cidade_estabelecimento->getcodoficial(); // Codigo da cidade do emitente (Tabela IBGE)
			$xMun = utf8_encode($cidade_estabelecimento->getnome()); // Nome da cidade do emitente
			$UF = $estado_estabelecimento->getuf(); // Sigla do estado do emitente
			$CEP = removeformat($this->estabelecimento->getcep()); // CEP do emitente
			$cPais = ltrim($pais_estabelecimento->getcodpais(), "0"); // Codigo do pais do emitente (1058 = Brasil)
			$xPais = $pais_estabelecimento->getnome(); // Nome do pais do emitente
			$fone = (strlen($this->estabelecimento->getfone1()) > 0 ? removeformat($this->estabelecimento->getfone1()) : ""); // Telefone do emitente (DD + telefone)

			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->xLgr = $xLgr;
				$std->nro = $nro;
				$std->xCpl = $xCpl;
				$std->xBairro = $xBairro;
				$std->cMun = $cMun;
				$std->xMun = $xMun;
				$std->UF = $UF;
				$std->CEP = $CEP;
				$std->cPais = $cPais;
				$std->xPais = $xPais;
				$std->fone = $fone;
				$resp = $this->nfe->tagenderEmit($std);
			}else{
				$resp = $this->nfe->tagenderEmit($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF, $CEP, $cPais, $xPais, $fone);
			}

			//identificaÃ¯Â¿Â½Ã¯Â¿Â½o do destinatario
			if($parceiro->gettppessoa() == "J"){
				$CPF = "";
				$CNPJ = removeformat($parceiro->getcpfcnpj()); // CNPJ do destinatario
				if(strlen(trim($CNPJ)) < 14){
					$CPF = $CNPJ;
					$CNPJ = ""; // CNPJ do destinatario
				}
			}else{
				$CPF = removeformat($parceiro->getcpfcnpj()); // CNPJ do destinatario
				$CNPJ = "";
			}
			if($operacaonota->getoperacao() == "EX" && $estado_parceiro->getuf() == "EX"){
				$idEstrangeiro = $parceiro->getidestrangeiro(); //identificaÃ¯Â¿Â½Ã¯Â¿Â½o de estrangeiro / passaporte
			}
			if($operacaonota->getoperacao() == "IM"){
				$idEstrangeiro = "";
			}
			if($tpAmb == 1){
				if($parceiro->gettppessoa() == "J"){
					$xNome = $parceiro->getrazaosocial(); // Nome do destinatario
				}else{
					$xNome = $parceiro->getnome(); // Nome do destinatario
				}
			}else{
				$xNome = "NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL";
			}
			if($indIEDest == "9"){
				$indFinal = "1";
			}
			//$IE = (strlen(trim(removeformat($parceiro->getrgie()),"0")) > 0 && $parceiro->gettppessoa() == "J" ? removeformat($parceiro->getrgie()) : ""); // Inscricao estadual do destinatario
			$IE = (strlen(trim(removeformat($parceiro->getrgie()), "0")) > 0 && strtoupper($parceiro->getrgie()) != "ISENTO" && $parceiro->gettppessoa() == "J" ? removeformat($parceiro->getrgie()) : ""); // Inscricao estadual do destinatario
			if(method_exists($parceiro, "getsuframa") && strlen($parceiro->getsuframa()) > 0){
				$ISUF = $parceiro->getsuframa();
			}else{
				$ISUF = "";
			}
			$IM = "";
			$email = trim($parceiro->getemail()); //email do destinatario

			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->xNome = $xNome;
				$std->indIEDest = $indIEDest;
				$std->IE = $IE;
				$std->ISUF = $ISUF;
				$std->IM = $IM;
				$std->email = $email;
				$std->CNPJ = $CNPJ; //indicar apenas um CNPJ ou CPF ou idEstrangeiro
				$std->CPF = $CPF;
				$std->idEstrangeiro = $idEstrangeiro;
				$resp = $this->nfe->tagdest($std);
			}else{
				$resp = $this->nfe->tagdest($CNPJ, $CPF, $idEstrangeiro, $xNome, $indIEDest, $IE, $ISUF, $IM, $email);
			}
			//Endereco do destinatario
			if($this->notafiscal->gettipoparceiro() == "C"){ // Verifica se o destinatario e um cliente
				$xLgr = $parceiro->getenderres(); // Endereco do destinatario
				$nro = $parceiro->getnumerores(); // Numero do endereco do destinatario
				$xCpl = (strlen($parceiro->getcomplementores()) > 0 ? $parceiro->getcomplementores() : ""); // Complemento do endereco do destinatario
				$xBairro = $parceiro->getbairrores(); // Bairro do destinatario
				$CEP = removeformat($parceiro->getcepres()); // CEP do destinatario
				$fone = (strlen($parceiro->getfoneres()) > 0 ? removeformat($parceiro->getfoneres()) : ""); // Telefone do destinatario (DDD + telefone)
			}else{
				$xLgr = $parceiro->getendereco(); // Endereco do destinatario
				$nro = $parceiro->getnumero(); // Numero do endereco do destinatario
				$xCpl = (strlen($parceiro->getcomplemento()) > 0 ? $parceiro->getcomplemento() : ""); // Complemento do endereco do destinatario
				$xBairro = $parceiro->getbairro(); // Bairro do destinatario
				$CEP = removeformat($parceiro->getcep()); // CEP do destinatario
				$fone = (strlen($parceiro->getfone1()) > 0 ? "0".removeformat($parceiro->getfone1()) : ""); // Telefone do destinatario (DDD + telefone)
			}
			$cMun = $cidade_parceiro->getcodoficial(); // Codigo oficial da cidade do destinatario
			$xMun = utf8_encode($cidade_parceiro->getnome()); // Nome da cidade do destinatario
			$UF = $estado_parceiro->getuf(); // Sigla do estado do destinatario
			$cPais = ltrim($pais_parceiro->getcodpais(), "0"); // Codigo do pais do destinatario (1058 = Brasil)
			$xPais = $pais_parceiro->getnome(); // Nome do pais do destinatario

			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->xLgr = $xLgr;
				$std->nro = $nro;
				$std->xCpl = $xCpl;
				$std->xBairro = $xBairro;
				$std->cMun = $cMun;
				$std->xMun = $xMun;
				$std->UF = $UF;
				$std->CEP = $CEP;
				$std->cPais = $cPais;
				$std->xPais = $xPais;
				$std->fone = $fone;
				$resp = $this->nfe->tagenderDest($std);
			}else{
				$resp = $this->nfe->tagenderDest($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF, $CEP, $cPais, $xPais, $fone);
			}

			if($operacaonota->getparceiro() == "C" && ($parceiro->getcepres() != $parceiro->getcepent() || $parceiro->getnumerores() != $parceiro->getnumeroent())){
				$cidade_parceiro_entrega = objectbytable("cidade", $parceiro->getcodcidadeent(), $this->con);

				if($parceiro->gettppessoa() == "J"){
					$CNPJ = removeformat($parceiro->getcpfcnpj());
					if(strlen(trim($CNPJ)) < 14){
						$CPF = $CNPJ;
						$CNPJ = NULL;
					}
				}else{
					$CPF = removeformat($parceiro->getcpfcnpj());
					$CNPJ = NULL;
				}
				$xLgr = $parceiro->getenderent();
				$nro = $parceiro->getnumeroent();
				$xCpl = trim($parceiro->getcomplementoent());
				$xBairro = $parceiro->getbairroent();
				$cMun = $cidade_parceiro_entrega->getcodoficial();
				$xMun = utf8_encode($cidade_parceiro_entrega->getnome());
				$UF = $cidade_parceiro_entrega->getuf();

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->xLgr = $xLgr;
					$std->nro = $nro;
					$std->xCpl = $xCpl;
					$std->xBairro = $xBairro;
					$std->cMun = $cMun;
					$std->xMun = $xMun;
					$std->UF = $UF;
					$std->CNPJ = $CNPJ; //indicar um CNPJ ou CPF
					$std->CPF = $CPF;
					$resp = $this->nfe->tagentrega($std);
				}else{
					$resp = $this->nfe->tagentrega($CNPJ, $CPF, $xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF);
				}
			}

			if($this->versaoapi == self::VERSAO_API5){
				$std = new stdClass();
				$std->CNPJ = removeformat($contabilidade->getcpfcnpj()); //indicar um CNPJ ou CPF
				$std->CPF = NULL;
				if(strlen($this->estabelecimento->getcodcontabilidade()) > 0){
					//$resp = $this->nfe->tagautXML($std);
				}
			}else{
				$CNPJ = removeformat($this->estabelecimento->getcpfcnpj());
				$CPF = NULL;
				//$resp = $this->nfe->tagautXML($CNPJ, CPF);
			}

			if(is_object($this->notacomplemento)){
				$nItem = "1";
				$cProd = "1";
				$cEAN = "SEM GTIN";
				$xProd = (strlen($this->notacomplemento->gettextonota()) > 0 ? $this->notacomplemento->gettextonota() : "COMPLEMENTO DE NOTA FISCAL"); // Descricao do produto
				$NCM = "00000000";
				$NVE = "";
				$EXTIPI = "";
				$CFOP = substr(removeformat($this->notacomplemento->getnatoperacao()), 0, 4); // Natureza de operacao
				$uCom = "UN";
				$qCom = "0";
				$vUnCom = "0";
				$vProd = "0";
				$cEANTrib = "SEM GTIN";
				$uTrib = "UN";
				$qTrib = "0";
				$vUnTrib = "0";
				$vFrete = "";
				$vSeg = "";
				$vDesc = "";
				$vOutro = "";
				$indTot = "0";
				$xPed = "";
				$nItemPed = "";
				$nFCI = "";
				$vTotTrib = "";

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->item = $nItem; //item da NFe
					$std->cProd = $cProd;
					$std->cEAN = $cEAN;
					$std->xProd = $xProd;
					$std->NCM = $NCM;
					if($this->versaonfe == self::VERSAO_NFE4){
						$std->cBenf; //incluido no layout 4.00
					}
					$std->EXTIPI = $EXTIPI;
					$std->CFOP = $CFOP;
					$std->uCom = $uCom;
					$std->qCom = $qCom;
					$std->vUnCom = $vUnCom;
					$std->vProd = $vProd;
					$std->cEANTrib = $cEANTrib;
					$std->uTrib = $uTrib;
					$std->qTrib = $qTrib;
					$std->vUnTrib = $vUnTrib;
					$std->vFrete = $vFrete;
					$std->vSeg = $vSeg;
					$std->vDesc = $vDesc;
					$std->vOutro = $vOutro;
					$std->indTot = $indTot;
					$std->xPed = $xPed;
					$std->nItemPed = $nItemPed;
					$std->nFCI = $nFCI;
					$resp = $this->nfe->tagprod($std);
				}else{
					$resp = $this->nfe->tagprod($nItem, $cProd, $cEAN, $xProd, $NCM, $EXTIPI, $CFOP, $uCom, $qCom, $vUnCom, $vProd, $cEANTrib, $uTrib, $qTrib, $vUnTrib, $vFrete, $vSeg, $vDesc, $vOutro, $indTot, $xPed, $nItemPed, $nFCI);
				}
				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->item = $nItem; //item da NFe
					$std->vTotTrib = $vTotTrib;
					$resp = $this->nfe->tagimposto($std);
				}else{
					$resp = $this->nfe->tagimposto($nItem, $vTotTrib);
				}

				//$orig = "0";
				$orig = $this->notacomplemento->getorigem();
				if($this->estabelecimento->getregimetributario() == "1"){
					$cst = "";
					$csosn = $this->notacomplemento->getcsticms();
				}else{
					//$cst = substr($this->notacomplemento->getcsticms(),1,2);
					$cst = $this->notacomplemento->getcsticms();
					$csosn = "";
				}
				$modBC = "3";
				$pRedBC = "";
				//$csosn = "900";
				$pCredSN = "0";
				if($this->estabelecimento->getregimetributario() != "1"){
					$vCredICMSSN = "";
					if($this->notacomplemento->gettotalbaseicms() > 0){
						$vBC = number_format($this->notacomplemento->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
					}else{
						$vBC = "0";
					}
					$pICMS = "0";
					if($this->notacomplemento->gettotalicms() > 0){
						$vICMS = number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms
					}else{
						$vICMS = "0";
					}
				}else{
					if($this->notacomplemento->gettotalbaseicms() > 0 && $this->estabelecimento->getpermitecredicms() == "S"){
						$vCredICMSSN = number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms
					}else{
						$vCredICMSSN = "0";
					}
					if($csosn == "900"){
						$vCredICMSSN = "0";
						$vBC = number_format($this->notacomplemento->gettotalbaseicms(), 2, ".", ""); // Total da base do icms;
						$pICMS = "0";
						$vICMS = number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms;
					}else{
						$vBC = "0";
						$pICMS = "0";
						$vICMS = "0";
					}
				}
				$vICMSDeson = "";
				$motDesICMS = "";
				$modBCST = "4";
				$pMVAST = "";
				$pRedBCST = "";

				if($cst == "20"){
					$pRedBC = "0.00";
				}

				if($this->notacomplemento->gettotalbaseicmssubst() > 0){
					$vBCST = number_format($this->notacomplemento->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
				}else{
					$vBCST = "0";
				}
				$pICMSST = "0";
				if($this->notacomplemento->gettotalicmssubst() > 0){
					$vICMSST = number_format($this->notacomplemento->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto;
				}else{
					$vICMSST = "0";
				}
				$pDif = "";
				$vICMSDif = "";
				$vICMSOp = "";
				$vBCSTRet = NULL;
				$vICMSSTRet = NULL;
				$vBCSTDest = "";
				$vICMSSTDest = "";
				$pST = "";

				if($this->estabelecimento->getregimetributario() == "1"){
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->orig = $orig;
						$std->CSOSN = $csosn;
						$std->pCredSN = $pCredSN;
						$std->vCredICMSSN = $vCredICMSSN;
						$std->modBCST = $modBCST;
						$std->pMVAST = $pMVAST;
						$std->pRedBCST = $pRedBCST;
						$std->vBCST = $vBCST;
						$std->pICMSST = $pICMSST;
						$std->vICMSST = $vICMSST;
						$std->vBCFCPST = $vBCFCPST; //incluso no layout 4.00
						$std->pFCPST = $pFCPST; //incluso no layout 4.00
						$std->vFCPST = $vFCPST; //incluso no layout 4.00
						$std->vBCSTRet = $vBCSTRet;
						$std->pST = $pST;
						$std->vICMSSTRet = $vICMSSTRet;
						$std->vBCFCPSTRet = $vBCFCPSTRet; //incluso no layout 4.00
						$std->pFCPSTRet = $pFCPSTRet; //incluso no layout 4.00
						$std->vFCPSTRet = $vFCPSTRet; //incluso no layout 4.00
						$std->modBC = $modBC;
						$std->vBC = $vBC;
						$std->pRedBC = $pRedBC;
						$std->pICMS = $pICMS;
						$std->vICMS = $vICMS;
						$resp = $this->nfe->tagICMSSN($std);
					}else{
						$pRedBC = "";
						$resp = $this->nfe->tagICMSSN($nItem, $orig, $csosn, $modBC, $vBC, $pRedBC, $pICMS, $vICMS, $pCredSN, $vCredICMSSN, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $vBCSTRet, $vICMSSTRet);
					}
				}else{
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->orig = $orig;
						$std->CST = $cst;
						$std->modBC = $modBC;
						$std->vBC = $vBC;
						$std->pICMS = $pICMS;
						$std->vICMS = $vICMS;
						$std->pFCP = $pFCP;
						$std->vFCP = $vFCP;
						$std->vBCFCP = $vBCFCP;
						$std->modBCST = $modBCST;
						$std->pMVAST = $pMVAST;
						$std->pRedBCST = $pRedBCST;
						$std->vBCST = $vBCST;
						$std->pICMSST = $pICMSST;
						$std->vICMSST = $vICMSST;
						$std->vBCFCPST = $vBCFCPST;
						$std->pFCPST = $pFCPST;
						$std->vFCPST = $vFCPST;
						$std->vICMSDeson = $vICMSDeson;
						$std->motDesICMS = $motDesICMS;
						$std->pRedBC = $pRedBC;
						$std->vICMSOp = $vICMSOp;
						$std->pDif = $pDif;
						$std->vICMSDif = $vICMSDif;
						$std->vBCSTRet = $vBCSTRet;
						$std->pST = $pST;
						$std->vICMSSTRet = $vICMSSTRet;
						$std->vBCFCPSTRet = $vBCFCPSTRet;
						$std->pFCPSTRet = $pFCPSTRet;
						$std->vFCPSTRet = $vFCPSTRet;
						$resp = $this->nfe->tagICMS($std);
					}else{
						$resp = $this->nfe->tagICMS($nItem, $orig, $cst, $modBC, $pRedBC, $vBC, $pICMS, $vICMS, $vICMSDeson, $motDesICMS, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $pDif, $vICMSDif, $vICMSOp, $vBCSTRet, $vICMSSTRet);
					}
				}

				$cstpis = "07";
				$vBCPis = "";
				$pPIS = "";
				$vPIS = "";
				$qBCProd = "";
				$vAliqProd = "";

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->item = $nItem; //item da NFe
					$std->CST = $cstpis;
					$std->vBC = $vBCPis;
					$std->pPIS = $pPIS;
					$std->vPIS = $vPIS;
					$std->qBCProd = $qBCProd;
					$std->vAliqProd = $vAliqProd;
					$resp = $this->nfe->tagPIS($std);
				}else{
					$resp = $this->nfe->tagPIS($nItem, $cstpis, $vBCPis, $pPIS, $vPIS, $qBCProd, $vAliqProd);
				}

				$cstcofins = "07";
				$vBCCofins = "";
				$pCOFINS = "";
				$vCOFINS = "";
				$qBCProd = "";
				$vAliqProd = "";

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->item = $nItem; //item da NFe
					$std->CST = $cstcofins;
					$std->vBC = $vBCCofins;
					$std->pCOFINS = $pCOFINS;
					$std->vCOFINS = $vCOFINS;
					$std->qBCProd = $qBCProd;
					$std->vAliqProd = $vAliqProd;
					$resp = $this->nfe->tagCOFINS($std);
				}else{
					$resp = $this->nfe->tagCOFINS($nItem, $cstcofins, $vBCCofins, $pCOFINS, $vCOFINS, $qBCProd, $vAliqProd);
				}

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->item = $nItem; //item da NFe
					$std->CEST = "0000000";
					$std->indEscala = NULL; //incluido no layout 4.00
					$std->CNPJFab = NULL; //incluido no layout 4.00
					$this->nfe->tagCEST($std);
				}else{
					//$this->nfe->tagCEST($nItem, "0000000");
				}
				$cstipi = "";
				$clEnq = "";
				$cnpjProd = "";
				$cSelo = "";
				$qSelo = "";
				$cEnq = "";
				$vBCIpi = "";
				$pIPI = "";
				$qUnid = "";
				$vUnid = "";
				$vIPI = "";
				$cEnq = "999"; // Codigo de enquadramento legal do ipi
				//IPI - Imposto sobre produtos indestrializados
				if($this->notacomplemento->gettotalipi() > 0){
					$cstipi = "50";
					$vBCIpi = "0";
					$pIPI = "0";
					$vIPI = $this->notacomplemento->gettotalipi();
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->clEnq = $clEnq;
						$std->CNPJProd = $cnpjProd;
						$std->cSelo = $cSelo;
						$std->qSelo = $qSelo;
						$std->cEnq = $cEnq;
						$std->CST = $cstipi;
						$std->vIPI = $vIPI;
						$std->vBC = $vBCIpi;
						$std->pIPI = $pIPI;
						$std->qUnid = $qUnid;
						$std->vUnid = $vUnid;
						$resp = $this->nfe->tagIPI($std);
					}else{
						$resp = $this->nfe->tagIPI($nItem, $cstipi, $clEnq, $cnpjProd, $cSelo, $qSelo, $cEnq, $vBCIpi, $pIPI, $qUnid, $vUnid, $vIPI);
					}
				}

				if($this->estabelecimento->getregimetributario() != "1"){
					if($this->notacomplemento->gettotalbaseicms() > 0){
						$vBC = number_format($this->notacomplemento->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
					}else{
						$vBC = "0";
					}
					if($this->notacomplemento->gettotalicms() > 0){
						$vICMS = number_format($this->notacomplemento->gettotalicms(), 2, ".", ""); // Total do icms
					}else{
						$vICMS = "0";
					}
					$vICMSDeson = "0";
					if($this->notacomplemento->gettotalbaseicmssubst() > 0){
						$vBCST = number_format($this->notacomplemento->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de calculo do icms substituto
					}else{
						$vBCST = "0";
					}
					if($this->notacomplemento->gettotalicmssubst() > 0){
						$vST = number_format($this->notacomplemento->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
					}else{
						$vST = "0";
					}
				}else{
					$vBC = "0";
					$pICMS = "0";
					$vICMS = "0";
					$vBCST = "0";
					$vICMSST = "0";
					$vICMSDeson = "0";
					if($this->notacomplemento->gettotalicmssubst() > 0){
						$vST = number_format($this->notacomplemento->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
					}else{
						$vST = "0";
					}
				}
				$vProd = "0";
				$vFrete = "0";
				$vSeg = "0";
				$vDesc = "0";
				$vII = "0";
				if($this->notacomplemento->gettotalipi() > 0){
					$vIPI = $this->notacomplemento->gettotalipi();
				}else{
					$vIPI = "0";
				}
				$vPIS = "0";
				$vCOFINS = "0";
				$vOutro = "0";
				if($this->notacomplemento->gettotalliquido() == 0){
					$vNF = "0";
				}else{
					$vNF = number_format($this->notacomplemento->gettotalliquido(), 2, ".", ""); // Total da nota fiscal
				}
				$vTotTrib = "0";

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->vBC = $vBC;
					$std->vICMS = $vICMS;
					$std->vICMSDeson = $vICMSDeson;
					$std->vFCP = $vFCP; //incluso no layout 4.00
					$std->vBCST = $vBCST;
					$std->vST = $vST;
					$std->vFCPST = $vFCPST; //incluso no layout 4.00
					$std->vFCPSTRet = $vFCPSTRet; //incluso no layout 4.00
					$std->vProd = $vProd;
					$std->vFrete = $vFrete;
					$std->vSeg = $vSeg;
					$std->vDesc = $vDesc;
					$std->vII = $vII;
					$std->vIPI = $vIPI;
					$std->vIPIDevol = $vIPIDevol; //incluso no layout 4.00
					$std->vPIS = $vPIS;
					$std->vCOFINS = $vCOFINS;
					$std->vOutro = $vOutro;
					$std->vNF = $vNF;
					$std->vTotTrib = $vTotTrib;
					$resp = $this->nfe->tagICMSTot($std);
				}else{
					$resp = $this->nfe->tagICMSTot($vBC, $vICMS, $vICMSDeson, $vBCST, $vST, $vProd, $vFrete, $vSeg, $vDesc, $vII, $vIPI, $vPIS, $vCOFINS, $vOutro, $vNF, $vTotTrib);
				}
				$modFrete = "0";
				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->modFrete = $modFrete;
					$resp = $this->nfe->tagtransp($std);
				}else{
					$resp = $this->nfe->tagtransp($modFrete);
				}

				if($this->versaonfe == "4.00"){
					$std = new stdClass();
					$std->vTroco = null; //incluso no layout 4.00
					$resp = $this->nfe->tagpag($std);
					//$resp = $this->nfe->tagpag();
					$std = new stdClass();
					$std->tPag = "90";
					$std->vPag = "0";
					$std->CNPJ = NULL;
					$std->tBand = NULL;
					$std->cAut = NULL;
					$std->tpIntegra = NULL; //incluso no layout 4.00
					$resp = $this->nfe->tagdetPag($std);
					//$resp = $this->nfe->tagDetPag("1", $codpag, $vDup);
				}
				$infAdFisco = "";
				$infCpl = trim(removespecial(str_replace("\n", " ", $this->notacomplemento->getobservacao()))); // Observacao

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->infAdFisco = $infAdFisco;
					$std->infCpl = $infCpl;
					$resp = $this->nfe->taginfAdic($std);
				}else{
					$resp = $this->nfe->taginfAdic($infAdFisco, $infCpl);
				}
			}else{
				$param_impdescricomple = param("NOTAFISCAL", "IMPDESCRICOMPLE", $con);
				$param_pedidotrunca = param("NOTAFISCAL", "PEDIDOTRUNCA", $con);
				$n = 1;
				$total_produto = 0;
				$total_produtos = 0;
				$tbasep_simples = 0;
				$tvicms_simples = 0;
				$totalbaseicms = 0;
				$vtotalipi = 0;
				$vtotaloutro = 0;
				$vtotaldesc = 0;
				$baseicms = 0;
				$vTotTribNFe = 0;
				$arr_natoperacao = array();
				foreach($arr_itnotafiscal as $itnotafiscal){
					$nItem = "";
					$cProd = "";
					$cEAN = "";
					$xProd = "";
					$NCM = "";
					$NVE = "";
					$EXTIPI = "";
					$CFOP = "";
					$uCom = "";
					$qCom = "";
					$vUnCom = "";
					$vProd = "";
					$cEANTrib = "";
					$uTrib = "";
					$qTrib = "";
					$vUnTrib = "";
					$vFrete = "";
					$vSeg = "";
					$vDesc = "";
					$vOutro = "";
					$indTot = "";
					$xPed = "";
					$nItemPed = "";
					$nFCI = "";

					if($itnotafiscal->gettotalicmssubst() > 0){
						$aliqicmssubst = number_format((($itnotafiscal->gettotalicmssubst() + ((($itnotafiscal->gettotalbruto() - $itnotafiscal->gettotaldesconto() + $itnotafiscal->gettotalacrescimo() + ($natoperacao->getcalcfretebaseicms() == "S" ? $itnotafiscal->gettotalfrete() : 0)) * (1 - $itnotafiscal->getredicms() / 100)) * $itnotafiscal->getaliqicms() / 100)) / $itnotafiscal->gettotalbaseicmssubst()) * 100, 0);
					}else{
						$aliqicmssubst = 0;
					}

					$arr_natoperacao[] = $itnotafiscal->getnatoperacao();
					$produto = objectbytable("produto", $itnotafiscal->getcodproduto(), $this->con);
					$operacao = objectbytable("operacaonota", $this->notafiscal->getoperacao(), $this->con);
					$classfiscal = objectbytable("classfiscal", ($operacao->gettipo() == "E" && !in_array($operacao->getoperacao(), array("DC", "IM", "PR")) ? $produto->getcodcfnfe() : $produto->getcodcfnfs()), $this->con);
					if(strlen($natoperacao->getcodcf()) > 0 && !($classfiscal->gettptribicms() == "I" && $natoperacao->getalteracfisento() == "S") && !($classfiscal->gettptribicms() == "F" && $natoperacao->getalteracficmssubst() == "S")){
						$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
					}
					$itnatoperacao = objectbytable("natoperacao", $itnotafiscal->getnatoperacao(), $this->con);
					$unidade = objectbytable("unidade", $itnotafiscal->getcodunidade(), $this->con);
					$ipi = objectbytable("ipi", $produto->getcodipi(), $this->con);
					$piscofins = objectbytable("piscofins", ($operacaonota->gettipo() == "E" && !in_array($operacaonota->getoperacao(), array("DC", "IM")) ? $produto->getcodpiscofinsent() : $produto->getcodpiscofinssai()), $this->con);
					$produtoean = objectbytable("produtoean", NULL, $this->con);
					$produtoean->setcodproduto($produto->getcodproduto());
					$arr_produtoean = object_array($produtoean);
					$codean = (sizeof($arr_produtoean) > 0 ? reset($arr_produtoean)->getcodean() : "");

					if($param_notafiscal_tipoeannota == 2 || ($param_notafiscal_tipoeannota == 1 && (!valid_ean($codean) || $codean == plutoean13($produto->getcodproduto())))){
						$codean = "SEM GTIN";
					}

					if(strlen(trim($codean)) == 0){
						$codean = "SEM GTIN";
					}

					/*
					  if(strlen($codean) <= 0){
					  $codean = plutoean13($itnotafiscal->getcodproduto());
					  }
					 */
					$basecalculo = $itnotafiscal->gettotalbruto() - $itnotafiscal->gettotaldesconto() + $itnotafiscal->gettotalacrescimo() + $itnotafiscal->gettotalii() + $itnotafiscal->gettotalfrete();

					// Calcula preco unitario do produto
//					$precounitario = $itnotafiscal->getprecopolitica() + ($itnotafiscal->gettotalacrescimo() / $itnotafiscal->getquantidade());
//					if($natoperacao->getoperacao() == "IM" && ($itnatoperacao->gettotnfigualbcicms() == "S" || $itnatoperacao->getsumicmstotalnf() == "S")){
//						$precounitario += $itnotafiscal->gettotalii() / $itnotafiscal->getquantidade();
//					}
//					$precounitario = number_format($precounitario, 4, ".", "");
					$precounitario = number_format($itnotafiscal->getpreco() + $itnotafiscal->getvalacresc(), 4, ".", "");
					if($natoperacao->getacrescimocomooutrasdespesas() == "S"){
						$precounitario = number_format($itnotafiscal->getpreco(), 4, ".", "");
					}else{
						//$precounitario = number_format($itnotafiscal->getpreco() + ($itnotafiscal->getvalacresc() * $itnotafiscal->getquantidade()), 4, ".", "");
						$precounitario = number_format($itnotafiscal->getpreco() + $itnotafiscal->getvalacresc(), 4, ".", "");
					}

					$classfiscal_ncm = NULL;
					if(strlen($produto->getidncm())){
						$ncm = objectbytable("ncm", $produto->getidncm(), $this->con);
						$codigoncm = $ncm->getcodigoncm();
						$classfiscal_ncm = objectbytable("classfiscal", ($operacao->gettipo() == "S" ? $ncm->getcodcfnfs() : $ncm->getcodcfnfe()), $this->con);
						$ncmunidade = objectbytable("ncmunidade", NULL, $this->con);
						$ncmunidade->setcodigoncm(removeformat($codigoncm));
						$arr_ncmunidade = object_array($ncmunidade);
						$ncmunidade = array_shift($arr_ncmunidade);
					}else{
						$ncm = NULL;
						$codigoncm = "";
					}

					$numeroserie = objectbytable("numeroserie", NULL, $this->con);
					$numeroserie->setidnotafiscal($this->notafiscal->getidnotafiscal());
					$numeroserie->setcodproduto($itnotafiscal->getcodproduto());
					$arr_numeroserie_ob = object_array($numeroserie);
					$arr_numeroserie = array();
					foreach($arr_numeroserie_ob as $numeroserie){
						$arr_numeroserie[] = $numeroserie->getnumeroserie();
					}

					//$nItem = str_pad($n,3,"0",STR_PAD_LEFT);	// Inclui um novo item na nota
					$nItem = $n; // Inclui um novo item na nota
					$cProd = $itnotafiscal->getcodproduto(); // Codigo do produto
					$cEAN = $codean; // EAN do produto
					if($this->descricaoproduto == "D"){
						$desc = $produto->getdescricaofiscal();
					}else{
						if($this->descricaoproduto == "2"){
							if(strlen($produto->getdescricaofiscal2()) == 0){
								$desc = $produto->getdescricaofiscal();
							}else{
								$desc = $produto->getdescricaofiscal2();
							}
						}else{
							if(strlen(trim($itnotafiscal->getcomplemento())) == 0){
								$desc = $produto->getdescricaofiscal();
							}else{
								$desc = $itnotafiscal->getcomplemento();
							}
						}
					}

					$xProd = str_replace("\r", "", str_replace("\n", "", $desc)); // Descricao do produto
					$NCM = removeformat($codigoncm); // Codigo NCM do produto (com 2 ou 8 digitos)
					$NVE = "";
					$EXTIPI = "";
					$CFOP = substr(removeformat($itnatoperacao->getnatoperacao()), 0, 4); // Natureza de operacao do produto
					$uCom = $unidade->getsigla(); // Unidade comercial
					$qCom = $itnotafiscal->getquantidade(); // Quantidade comercial
					$vUnCom = $precounitario; // Preco comercial
//					$total_produto = $itnotafiscal->getquantidade() * $precounitario;
//					$total_produto = $param_pedidotrunca == "T" ? trunc($total_produto, 2) : round($total_produto, 2);
//					$total_produtos += $total_produto;

					$total_produtos += $itnotafiscal->gettotalliquido();
					if($natoperacao->getoperacao() != "IM"){
						//$vProd = number_format($itnotafiscal->gettotalliquido() - $itnotafiscal->gettotalfrete() + $itnotafiscal->gettotaldesconto() - $itnotafiscal->gettotalseguro() - $itnotafiscal->gettotalipi() - $itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Total bruto
						//$vProd = number_format($itnotafiscal->gettotalbruto(), 2, ".", "");
						if($natoperacao->getacrescimocomooutrasdespesas() == "S"){
							$vProd = number_format($itnotafiscal->gettotalbruto(), 2, ".", "");
						}else{
							$vProd = number_format($itnotafiscal->gettotalbruto() + $itnotafiscal->gettotalacrescimo(), 2, ".", "");
						}
					}else{
						$vProd = number_format($itnotafiscal->gettotalbruto(), 2, ".", "");
					}
					$cEANTrib = $codean; // EAN tributavel
					if((in_array($CFOP, array("5.102", "1501", "2501", "5501", "5502", "5504", "5505", "6501", "6502", "6504", "6505")) || $idDest == "3") && isset($ncmunidade)){
						$uTrib = removeformat($ncmunidade->getunidade()); // Unidade tributavel
					}else{
						$uTrib = $unidade->getsigla(); // Unidade tributavel
					}
					$qTrib = $itnotafiscal->getquantidade(); // Quantidade tributavel
					$vUnTrib = $precounitario; // Preco tributavel
					if($itnotafiscal->gettotalfrete() > 0){
						$vFrete = number_format($itnotafiscal->gettotalfrete(), 2, ".", ""); // Total frete
					}
					if($itnotafiscal->gettotalseguro() > 0){
						$vSeg = number_format($itnotafiscal->gettotalseguro(), 2, ".", ""); //Total do seguro
					}
					if(number_format($itnotafiscal->gettotaldesconto(), 2, ".", "") > 0){
						$vDesc = number_format($itnotafiscal->gettotaldesconto(), 2, ".", ""); // Total desconto
						$vtotaldesc += $vDesc;
					}
					if($natoperacao->getoperacao() == "IM" && $itnotafiscal->getvalsiscomex() > 0){
						$vOutro = number_format($itnotafiscal->getvalsiscomex() + $itnotafiscal->gettotalpis() + $itnotafiscal->gettotalcofins(), 2, ".", ""); //Total de outras
						$vtotaloutro += $vOutro;
					}elseif(in_array($natoperacao->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N"){
						$nvoutro = $itnotafiscal->gettotalicmssubst() + $itnotafiscal->gettotalipi();
						if($nvoutro > 0){
							$vOutro = number_format($nvoutro, 2, ".", ""); //Total de outras
							$vtotaloutro += $vOutro;
						}
					}
					if($natoperacao->getacrescimocomooutrasdespesas() == "S"){
						$vOutro += number_format($itnotafiscal->gettotalacrescimo(), 2, ".", ""); //Total de outras
						$vtotaloutro += $vOutro;
					}
					//quando o emitente for optante do simples nacional e for uma venda para consumidor final e o mesmo
					//for de outro estado, deve somar a parcela do difal do destinatÃ¡rio mais o fundo de combate a probreza
					// e colocar em outras despesas acessÃ³rias, que consequentemente sera somado ao total da NF-e
					// JESUS - por enquanto isto esta descartado
					/*
					  if($this->estabelecimento->getregimetributario() == "1"){
					  $vOutro = number_format($itnotafiscal->getvaloricmsufdest() + $itnotafiscal->getvalorfcpufdest(), 2, ".", ""); //Total de outras
					  $vtotaloutro += $vOutro;
					  }
					 *
					 *
					 */
					if($this->notafiscal->getfinalidade() == "3" && $this->notafiscal->gettipoajuste() != "0" && in_array($operacaonota, array("AE","AS"))){
						$indTot = "0";
					}else{
						$indTot = "1";
					}

					if(strlen($itnotafiscal->getseqitemcliente()) > 0){
						$nItemPed = $itnotafiscal->getseqitemcliente(); // Item do pedido de compra do cliente
					}
					$nFCI = "";

					if(sizeof($arr_numeroserie) > 0){
						$InfAdProd = "N/S: ".implode(", ", $arr_numeroserie);
					}
					$InfAdProd = "";
					$complemento = array();
					if(strlen($itnotafiscal->getcomplemento()) > 0){
						$complemento[] = $itnotafiscal->getcomplemento();
					}
					if(strlen($itnotafiscal->getnumerolote()) > 0){
						$complemento[] = "Lote=".$itnotafiscal->getnumerolote();
					}
					if(strlen($itnotafiscal->getdtvalidade()) > 0){
						$complemento[] = "Val=".$itnotafiscal->getdtvalidade(TRUE);
					}

					switch($param_notafiscal_nfedescoucomp){
						case("0"):
							if(sizeof($complemento) > 0 && $operacaonota->getoperacao() != "IM"){
								$InfAdProd = implode(" ", $complemento);
							}
							break;
						case("1"):
							if($this->descricaoproduto != "C"){
								$InfAdProd = $itnotafiscal->getcomplemento();
							}
						case("2"):
							$InfAdProd = "";
							break;
					}

					if(strlen($itnotafiscal->getpedcliente()) > 0){
						$InfAdProd .= " PEDIDO ".$itnotafiscal->getpedcliente(); // Numero do pedido de compra do cliente
						$xPed = $itnotafiscal->getpedcliente(); // Numero do pedido de compra do cliente
						//$xProd .= " - PEDIDO ".$itnotafiscal->getpedcliente();
					}

					$InfAdProd = str_replace(array("&"), " ", $InfAdProd);
					$InfAdProd = str_replace("  ", " ", $InfAdProd);
					$InfAdProd = trim($InfAdProd);
					$cBenef = NULL;
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->cProd = $cProd;
						$std->cEAN = $cEAN;
						$std->xProd = $xProd;
						$std->NCM = $NCM;
						if($this->versaonfe == self::VERSAO_NFE4){
							$std->cBenf; //incluido no layout 4.00
						}

						$std->EXTIPI = $EXTIPI;
						$std->CFOP = $CFOP;
						$std->uCom = $uCom;
						$std->qCom = $qCom;
						$std->vUnCom = $vUnCom;
						$std->vProd = $vProd;
						$std->cEANTrib = $cEANTrib;
						$std->uTrib = $uTrib;
						$std->qTrib = $qTrib;
						$std->vUnTrib = $vUnTrib;
						$std->vFrete = $vFrete;
						$std->vSeg = $vSeg;
						$std->vDesc = $vDesc;
						$std->vOutro = $vOutro;
						$std->indTot = $indTot;
						$std->xPed = $xPed;
						$std->nItemPed = $nItemPed;
						$std->nFCI = $nFCI;
						$resp = $this->nfe->tagprod($std);
					}else{
						$resp = $this->nfe->tagprod($nItem, $cProd, $cEAN, $xProd, $NCM, $EXTIPI, $CFOP, $uCom, $qCom, $vUnCom, $vProd, $cEANTrib, $uTrib, $qTrib, $vUnTrib, $vFrete, $vSeg, $vDesc, $vOutro, $indTot, $xPed, $nItemPed, $nFCI);
					}
					if(strlen($InfAdProd)){
						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->infAdProd = $InfAdProd;
							$resp = $this->nfe->taginfAdProd($std);
						}else{
							$resp = $this->nfe->taginfAdProd($nItem, $InfAdProd);
						}
					}

					$vTotTrib = number_format($itnotafiscal->gettotalliquido() * ($produto->getaliqmedia() / 100), 2, ".", "");
					$vTotTribNFe += $vTotTrib;
					if($this->estabelecimento->getregimetributario() == "1"){
						$vTotTrib = "";
						$vTotTribNFe = 0;
					}
					if($vTotTrib == 0){
						$vTotTrib = "";
					}

					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->vTotTrib = $vTotTrib;
						$resp = $this->nfe->tagimposto($std);
					}else{
						$resp = $this->nfe->tagimposto($nItem, $vTotTrib);
					}
					$orig = "";
					$cst = "";
					$csosn = "";
					$modBC = ($itnotafiscal->getvalorpauta() > 0 && in_array($itnotafiscal->gettptribicms(), array("T", "R")) ? "1" : "3");
					$pRedBC = "";
					$pCredSN = "0";
					$vCredICMSSN = "0";
					$vBC = "0";
					$pICMS = "0";
					$vICMS = "0";
					$vICMSDeson = "";
					$motDesICMS = "";
					$modBCST = "0";
					$pMVAST = "";
					$pRedBCST = "";
					$vBCST = "0";
					$pICMSST = "0";
					$vICMSST = "0";
					$pDif = "";
					$vICMSDif = "";
					$vICMSOp = "";
					$vBCSTRet = "0";
					$vICMSSTRet = "0";
					$pST = "0";
					$vBCSTDest = "0";
					$vICMSSTDest = "0";
					$vBCFCPST = NULL;
					$pFCPST = NULL;
					$vFCPST = NULL;
					if($operacaonota->getoperacao() == "IM"){
						$orig = "1";
					}else{
						if($natoperacao->getusartributacaoncm() == "S" && !is_null($classfiscal_ncm)){
							$orig = substr($classfiscal_ncm->getcodcst(), 0, 1);
						}else{
							$orig = substr($classfiscal->getcodcst(), 0, 1);
						}
					}

					$cst = substr($itnotafiscal->getcsticms(), 1, 2);
					$c_cst = substr($cst, 0, 1);

					// CSOSN
					// 101 - Tributado pelo simples nacional com permissÃ£o de credito
					// 102 - Tributado pelo simples nacional sem permissÃ£o de credito
					// 103 - Insensao pelo simples nacional com faixa de receita bruta
					// 201 - Tributado pelo simples nacional com permissÃ£o de credito e com cobranÃ§a do icms por ST
					// 202 - Tributado pelo simples nacional sem permissÃ£o de credito e com cobranÃ§a por ST
					// 203 - Insensao pelo simples nacional com faixa de receita bruta e com cobranÃ§a por ST
					// 300 - Imune
					// 400 - Nao tributado pelo simples nacional
					// 500 - Icms cobrado anteriormente por substituicao tributaria (Substituido ou por antecipacao)
					//ICMS - imposto sobre cisrculacao de mercadorias
					if($this->estabelecimento->getregimetributario() == "1"){ //&& $operacaonota->getoperacao() != "IM"){
						if($itnotafiscal->gettptribicms() != "F"){
							if(in_array($itnotafiscal->getcsticms(), array("101", "900")) && substr($itnotafiscal->getnatoperacao(), 0, 1) != "7"){
								$orig = $orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
								$csosn = $itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional

								$basep_simples = $itnotafiscal->gettotalbaseicms();
								$icms_simples = $itnotafiscal->getaliqicms();
								$vicms_simples = $basep_simples * $icms_simples / 100;
								$tbasep_simples += $basecalculo;
								$tvicms_simples += $vicms_simples;
								if(!in_array($operacaonota->getoperacao(), array("DF", "RF", "IM"))){
									$pCredSN = number_format($icms_simples, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
									$vCredICMSSN = number_format($vicms_simples, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
								}else{
									$pCredSN = number_format(0, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
									$vCredICMSSN = number_format(0, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
									if($natoperacao->getdestacaricmsp() == "S"){
										$vBC = number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
										$pICMS = number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
										$vICMS = number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
									}else{
										$vBC = number_format(0, 2, ".", ""); // Total da base do icms
										$pICMS = number_format(0, 2, ".", ""); // Aliquota de icms
										$vICMS = number_format(0, 2, ".", ""); // Total do icms
									}
								}
							}else{
								$orig = $orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
								$csosn = $itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional
							}
							//$resp = $this->nfe->tagICMSSN($nItem, $orig, $csosn, $modBC, $vBC, $pRedBC, $pICMS, $vICMS, $pCredSN, $vCredICMSSN, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $vBCSTRet, $vICMSSTRet);
						}else{
							if((in_array($operacaonota->getoperacao(), array("VD", "DC", "PR")) || ($operacaonota->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S")) && $itnotafiscal->gettotalicmssubst() > 0){
								$orig = $orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
								$csosn = $itnotafiscal->getcsticms(); // Codigo da situacao da operacao - simples nacional

								$modBCST = ($itnotafiscal->getaliqiva() > 0 ? "4" : "5"); // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
								$pMVAST = number_format($itnotafiscal->getaliqiva(), 2, ".", ""); // Aliquota do iva
								if($itnotafiscal->getredicms() > 0){
									$pRedBCST = number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
								}
								$vBCST = number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
								$pICMSST = number_format($aliqicmssubst, 2, ".", ""); // Aliquota do imposto do icms substituto
								$vICMSST = number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
								if(in_array($itnotafiscal->getcsticms(), array("201", "900"))){
									$basep_simples = $itnotafiscal->gettotalbaseicmssubst() / (1 + $itnotafiscal->getaliqiva() / 100);
									$icms_simples = $classfiscal->getaliqicms();
									$vicms_simples = $basep_simples * $icms_simples / 100;
									$tbasep_simples += $basecalculo;
									$tvicms_simples += $vicms_simples;
									if($operacaonota->getoperacao() != "DF"){
										$pCredSN = number_format($classfiscal->getaliqicms(), 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
										$vCredICMSSN = number_format($vicms_simples, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
									}else{
										$pCredSN = number_format(0, 2, ".", ""); // Aliquota aplicavel de calculo do credito (simples nacional)
										$vCredICMSSN = number_format(0, 2, ".", ""); // Valor do credito do ICMS que pode ser aproveitado (simples nacional)
										if(in_array($itnotafiscal->getcsticms(), array("900"))){
											$vBC = number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
											$pICMS = number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
											$vICMS = number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms

											if($itnotafiscal->getpercfcpst() > 0){
												$vBCFCPST = number_format($itnotafiscal->getbasecalculofcpst(), 2, ".", "");	// Base de calculo
												$pFCPST = number_format($itnotafiscal->getpercfcpst(), 2, ".", "");				// Percentual de FCP
												$vFCPST = number_format($itnotafiscal->getvalorfcpst(), 2, ".", "");			// Valor de FCP
											}
										}

									}
								}
							}else{
								$orig = $orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
								$csosn = $itnotafiscal->getcsticms();

								$it_totalbaseicms_st += $itnotafiscal->gettotalbaseicms();
								$it_totalicms_st += $itnotafiscal->gettotalicms();
								if($natoperacao->getoperacao() == "DF" && $itnotafiscal->gettotalicmssubst() > 0){
									$vBCSTRet = number_format(0, 2, ".", ""); // Valor da base de calculo do ICSM ST retido
									$vICMSSTRet = number_format(0, 2, ".", ""); // Valor do ICSM ST retido
									$pST = number_format(0, 2, ".", "");
								}else{
									$vBCSTRet = number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Valor da base de calculo do ICSM ST retido
									$vICMSSTRet = number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Valor do ICSM ST retido
									$pST = number_format(0, 2, ".", "");
								}
								if(in_array($itnotafiscal->getcsticms(), array("900"))){
									$vBC = number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
									$pICMS = number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
									$vICMS = number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
								}
							}
						}
						$vBCSTRet = NULL;
						$pST = NULL;
						$vICMSSTRet = NULL;

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->orig = $orig;
							$std->CSOSN = $csosn;
							$std->pCredSN = $pCredSN;
							$std->vCredICMSSN = $vCredICMSSN;
							$std->modBCST = $modBCST;
							$std->pMVAST = $pMVAST;
							$std->pRedBCST = $pRedBCST;
							$std->vBCST = $vBCST;
							$std->pICMSST = $pICMSST;
							$std->vICMSST = $vICMSST;
							$std->vBCFCPST = $vBCFCPST; //incluso no layout 4.00
							$std->pFCPST = $pFCPST; //incluso no layout 4.00
							$std->vFCPST = $vFCPST; //incluso no layout 4.00
							$std->vBCSTRet = $vBCSTRet;
							$std->pST = $pST;
							$std->vICMSSTRet = $vICMSSTRet;
							$std->vBCFCPSTRet = $vBCFCPSTRet; //incluso no layout 4.00
							$std->pFCPSTRet = $pFCPSTRet; //incluso no layout 4.00
							$std->vFCPSTRet = $vFCPSTRet; //incluso no layout 4.00
							$std->modBC = $modBC;
							$std->vBC = $vBC;
							$std->pRedBC = $pRedBC;
							$std->pICMS = $pICMS;
							$std->vICMS = $vICMS;
							$resp = $this->nfe->tagICMSSN($std);
						}else{
							$vBCSTRet = "0";
							$pST = "0";
							$vICMSSTRet = "0";
							$resp = $this->nfe->tagICMSSN($nItem, $orig, $csosn, $modBC, $vBC, $pRedBC, $pICMS, $vICMS, $pCredSN, $vCredICMSSN, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $vBCSTRet, $vICMSSTRet);
						}
					}else{
						if(in_array($c_cst, array("0", "1", "2", "4", "6", "7", "9"))){
							$orig = $orig; // Origem da mercadoria (0 = nacional; 1 = estrangeira (importacao direta) 2 = estrangeira (adquirida no mercado interno))
							$cst = $cst; // CST do produto
							if(in_array($c_cst, array("6"))){
								$vBCSTRet = "0.00";
								$vICMSSTRet = "0.00";
							}
						}
						if(in_array($c_cst, array("0", "1", "2", "4", "7", "9"))){
							//$modBC = "3"; // Modo de calculo da base do icms (0 = margem valor agregado (%); 1 = pauta (valor); 2 = preco tabelado max (valor); 3 = valor da operacao)
							$modBC = ($itnotafiscal->getvalorpauta() > 0 && in_array($itnotafiscal->gettptribicms(), array("T", "R")) ? "1" : "3");
							if($this->estabelecimento->regime_simples() && $operacaonota->getoperacao() != "IM"){ // Verifica se o estabelecimento esta no simples
								$vBC = "0.00"; // Total da base do icms
								$pICMS = "0.00"; // Aliquota de icms
								$vICMS = "0.00"; // Total do icms
							}else{
								if($itnatoperacao->getdestacaricmsp() == "S"){
									$vBC = number_format($itnotafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base do icms
									$pICMS = number_format($itnotafiscal->getaliqicms(), 2, ".", ""); // Aliquota de icms
									$vICMS = number_format($itnotafiscal->gettotalicms(), 2, ".", ""); // Total do icms
								}else{
									$vBC = number_format(0, 2, ".", ""); // Total da base do icms
									$pICMS = number_format(0, 2, ".", ""); // Aliquota de icms
									$vICMS = number_format(0, 2, ".", ""); // Total do icms
								}
							}
						}
						if(in_array($c_cst, array("1", "2","3", "7", "9"))){ //&& ((($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S") || $natoperacao->getoperacao() != "DF"))){
							if(in_array($c_cst, array("2", "7", "9"))){
								if($itnatoperacao->getdestacaricmsp() == "S"){
									$pRedBC = number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
								}else{
									$pRedBC = number_format(0, 2, ".", "");
								}
							}
							if(in_array($c_cst, array("1","3", "7", "9")) && ((($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S") || $natoperacao->getoperacao() != "DF"))){
								$modBCST = ($itnotafiscal->getaliqiva() > 0 ? "4" : "5"); // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
								$pMVAST = number_format($itnotafiscal->getaliqiva(), 2, ".", ""); // Aliquota do iva
								if($itnotafiscal->getredicms() > 0){
									$pRedBCST = number_format($itnotafiscal->getredicms(), 2, ".", ""); // Reducao na base de calculo
								}
								$vBCST = number_format($itnotafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de icms substituto
								$pICMSST = number_format($aliqicmssubst, 2, ".", ""); // Aliquota do imposto do icms substituto
								$vICMSST = number_format($itnotafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
								if($itnotafiscal->getpercfcpst() > 0){
									$vBCFCPST = number_format($itnotafiscal->getbasecalculofcpst(), 2, ".", "");	// Base de calculo
									$pFCPST = number_format($itnotafiscal->getpercfcpst(), 2, ".", "");				// Percentual de FCP
									$vFCPST = number_format($itnotafiscal->getvalorfcpst(), 2, ".", "");			// Valor de FCP
								}
								if($itnotafiscal->getvalordesoneracao() > 0){
									$vICMSDeson = number_format($itnotafiscal->getvalordesoneracao(), 2, ".", "");			// Valor de FCP
									$motDesICMS = $itnotafiscal->getmotivodesoneracao();
								}
							}else{
								$modBCST = ($itnotafiscal->getaliqiva() > 0 ? "4" : "5"); // Modo de calculo da base do icms substituto (0 = preco tabelado; 1 = lista negativa; 2 = lista positiva; 3 = lista neutra; 4 = margem valor agregado (%); 5 = pauta (valor))
								$pMVAST = number_format(0, 2, ".", ""); // Aliquota do iva
								if($itnotafiscal->getredicms() > 0){
									$pRedBCST = number_format(0, 2, ".", ""); // Reducao na base de calculo
								}
								$vBCST = number_format(0, 2, ".", ""); // Total da base de icms substituto
								$pICMSST = number_format(0, 2, ".", ""); // Aliquota do imposto do icms substituto
								$vICMSST = number_format(0, 2, ".", ""); // Total do icms substituto
								if($itnotafiscal->getpercfcpst() > 0){
									$vBCFCPST = number_format(0, 2, ".", "");										// Base de calculo
									$pFCPST = number_format(0, 2, ".", "");											// Percentual de FCP
									$vFCPST = number_format(0, 2, ".", "");											// Valor de FCP
								}
							}
						}
						if(in_array($c_cst, array("6"))){
							$vBCST = "0.00"; // Total da base de icms substituto
							$vICMSST = "0.00"; // Aliquota do imposto do icms substituto
						}

						if(in_array($c_cst, array("5"))){
							$vBC = "";   // Total da base do icms
							$pICMS = ""; // Aliquota de icms
							$vICMS = ""; // Total do icms
						}

						$vBCSTRet = NULL;
						$pST = NULL;
						$vICMSSTRet = NULL;

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->orig = $orig;
							$std->CST = $cst;
							$std->modBC = $modBC;
							$std->vBC = $vBC;
							$std->pICMS = $pICMS;
							$std->vICMS = $vICMS;
							$std->pFCP = $pFCP;
							$std->vFCP = $vFCP;
							$std->vBCFCP = $vBCFCP;
							$std->modBCST = $modBCST;
							$std->pMVAST = $pMVAST;
							$std->pRedBCST = $pRedBCST;
							$std->vBCST = $vBCST;
							$std->pICMSST = $pICMSST;
							$std->vICMSST = $vICMSST;
							$std->vBCFCPST = $vBCFCPST;
							$std->pFCPST = $pFCPST;
							$std->vFCPST = $vFCPST;
							$std->vICMSDeson = $vICMSDeson;
							$std->motDesICMS = $motDesICMS;
							$std->pRedBC = $pRedBC;
							$std->vICMSOp = $vICMSOp;
							$std->pDif = $pDif;
							$std->vICMSDif = $vICMSDif;
							$std->vBCSTRet = $vBCSTRet;
							$std->pST = $pST;
							$std->vICMSSTRet = $vICMSSTRet;
							$std->vBCFCPSTRet = $vBCFCPSTRet;
							$std->pFCPSTRet = $pFCPSTRet;
							$std->vFCPSTRet = $vFCPSTRet;
							$resp = $this->nfe->tagICMS($std);
						}else{
							$vBCSTRet = "0";
							$pST = "0";
							$vICMSSTRet = "0";
							$resp = $this->nfe->tagICMS($nItem, $orig, $cst, $modBC, $pRedBC, $vBC, $pICMS, $vICMS, $vICMSDeson, $motDesICMS, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $pDif, $vICMSDif, $vICMSOp, $vBCSTRet, $vICMSSTRet);
						}
					}

					if($indIEDest == "9" && $idDest == "2" && in_array(substr(removeformat($itnatoperacao->getnatoperacao()), 0, 4), array("6108", "6107")) && $natoperacao->getoperacao() == "VD"){
						$vBCUFDest = "0";
						$vBCFCPUFDest = "0";
						$pFCPUFDest = "0";
						$pICMSUFDest = "0";
						$pICMSInter = "0";
						$pICMSInterPart = "0";
						$vFCPUFDest = "0";
						$vICMSUFDest = "0";
						$vICMSUFRemet = "0";
						if($itnotafiscal->getbasecalcufdest() > 0){
							$vBCUFDest = number_format($itnotafiscal->getbasecalcufdest(), 2, ".", "");
						}
						if($itnotafiscal->gettotalbaseicms() > 0){
							$vBCFCPUFDest = number_format($itnotafiscal->gettotalbaseicms(), 2, ".", "");
						}
						if($itnotafiscal->getaliqfcpufdest() > 0){
							$pFCPUFDest = number_format($itnotafiscal->getaliqfcpufdest(), 2, ".", "");
						}
						if($itnotafiscal->getaliqicmsufdest() > 0){
							$pICMSUFDest = number_format($itnotafiscal->getaliqicmsufdest(), 2, ".", "");
						}
						if($itnotafiscal->getaliqicmsinter() > 0){
							$pICMSInter = number_format($itnotafiscal->getaliqicmsinter(), 2, ".", "");
						}
						if($itnotafiscal->getaliqicminterpart() > 0){
							$pICMSInterPart = number_format($itnotafiscal->getaliqicminterpart(), 2, ".", "");
						}
						if($itnotafiscal->getvalorfcpufdest() > 0){
							$vFCPUFDest = number_format($itnotafiscal->getvalorfcpufdest(), 2, ".", "");
						}
						if($itnotafiscal->getvaloricmsufdest() > 0){
							$vICMSUFDest = number_format($itnotafiscal->getvaloricmsufdest(), 2, ".", "");
						}
						if($itnotafiscal->getvaloricmsufremet() > 0){
							$vICMSUFRemet = number_format($itnotafiscal->getvaloricmsufremet(), 2, ".", "");
						}

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->vBCUFDest = $vBCUFDest;
							$std->vBCFCPUFDest = $vBCFCPUFDest;
							$std->pFCPUFDest = $pFCPUFDest;
							$std->pICMSUFDest = $pICMSUFDest;
							$std->pICMSInter = $pICMSInter;
							$std->pICMSInterPart = $pICMSInterPart;
							$std->vFCPUFDest = $vFCPUFDest;
							$std->vICMSUFDest = $vICMSUFDest;
							$std->vICMSUFRemet = $vICMSUFRemet;
							$resp = $this->nfe->tagICMSUFDest($std);
						}else{
							$resp = $this->nfe->tagICMSUFDest($nItem, $vBCUFDest, $pFCPUFDest, $pICMSUFDest, $pICMSInter, $pICMSInterPart, $vFCPUFDest, $vICMSUFDest, $vICMSUFRemet);
						}
					}

					$cstipi = "";
					$clEnq = "";
					$cnpjProd = "";
					$cSelo = "";
					$qSelo = "";
					$cEnq = "";
					$vBCIpi = "";
					$pIPI = "";
					$qUnid = "";
					$vUnid = "";
					$vIPI = "";
					$cEnq = "999"; // Codigo de enquadramento legal do ipi
					//IPI - Imposto sobre produtos indestrializados
					if(((($itnotafiscal->getvalipi() > 0 || $itnotafiscal->getpercipi() > 0) && (($natoperacao->getoperacao() != "DF") || ($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "S"))))){
						if($itnotafiscal->gettotalipi() > 0 && $operacao->gettipo() == "S"){
							$cstipi = $ipi->getcodcstsai();
						}else{
							$cstipi = $ipi->getcodcstent(); // CST do ipi do produto
						}
						if($cstipi == "52"){
							$cEnq = "399";
						}elseif($cstipi == "02"){
							$cEnq = "301";
						}elseif($cstipi == "54"){
							$cEnq = "099";
						}elseif($cstipi == "04"){
							$cEnq = "001";
						}elseif($cstipi == "55"){
							$cEnq = "199";
						}elseif($cstipi == "05"){
							$cEnq = "101";
						}
						if(in_array($natoperacao->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N"){
							$vIPI = NULL;
						}else{
							$vIPI = number_format($itnotafiscal->gettotalipi(), 2, ".", ""); // Total do ipi
						}
						$vtotalipi += $vIPI;
						if(in_array($cstipi, array("00", "49", "50", "99"))){
							if($itnotafiscal->gettipoipi() == "F"){
								$qUnid = $itnotafiscal->getquantidade(); // Quantidade do produto
								$vUnid = number_format($itnotafiscal->getvalipi(), 4, ".", ""); // Valor do ipi
							}else{
								$vBCIpi = number_format($basecalculo, 2, ".", ""); // Base de calculo do ipi
								$pIPI = number_format($itnotafiscal->getpercipi(), 2, ".", ""); // Percetual do ipi
							}
						}

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->clEnq = $clEnq;
							$std->CNPJProd = $cnpjProd;
							$std->cSelo = $cSelo;
							$std->qSelo = $qSelo;
							$std->cEnq = $cEnq;
							$std->CST = $cstipi;
							$std->vIPI = $vIPI;
							$std->vBC = $vBCIpi;
							$std->pIPI = $pIPI;
							$std->qUnid = $qUnid;
							$std->vUnid = $vUnid;
							$resp = $this->nfe->tagIPI($std);
						}else{
							$resp = $this->nfe->tagIPI($nItem, $cstipi, $clEnq, $cnpjProd, $cSelo, $qSelo, $cEnq, $vBCIpi, $pIPI, $qUnid, $vUnid, $vIPI);
						}
					}elseif($this->notafiscal->getoperacao() == "IM"){
						$cEnq = "999"; // Codigo de enquadramento legal do ipi
						$cstipi = $ipi->getcodcstent(); // CST do ipi do produto

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->clEnq = $clEnq;
							$std->CNPJProd = $cnpjProd;
							$std->cSelo = $cSelo;
							$std->qSelo = $qSelo;
							$std->cEnq = $cEnq;
							$std->CST = $cstipi;
							$std->vIPI = $vIPI;
							$std->vBC = $vBCIpi;
							$std->pIPI = $pIPI;
							$std->qUnid = $qUnid;
							$std->vUnid = $vUnid;
							$resp = $this->nfe->tagIPI($std);
						}else{
							$resp = $this->nfe->tagIPI($nItem, $cstipi, $clEnq, $cnpjProd, $cSelo, $qSelo, $cEnq, $vBCIpi, $pIPI, $qUnid, $vUnid, $vIPI);
						}
					}

					//II - Imposto de Importação
					if($this->notafiscal->getoperacao() == "IM"){
						$vBCII = number_format($itnotafiscal->gettotalbaseii(), 2, ".", ""); // Total base do II
						$vDespAdu = number_format($itnotafiscal->getdespaduaneira(), 2, ".", ""); // Despesa aduaneira
						$vII = number_format($itnotafiscal->gettotalii(), 2, ".", ""); // Total do II
						$vIOF = number_format($itnotafiscal->getvaliof(), 2, ".", ""); // Total do IOF

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->vBC = $vBCII;
							$std->vDespAdu = $vDespAdu;
							$std->vII = $vII;
							$std->vIOF = $vIOF;
							$resp = $this->nfe->tagII($std);
						}else{
							$resp = $this->nfe->tagII($nItem, $vBCII, $vDespAdu, $vII, $vIOF);
						}
						//$resp = $this->nfe->tagII($nItem, $vBC, $vDespAdu, $vII, $vIOF);
					}

					//PIS
					$cstpis = "";
					$vBCPis = "";
					$pPIS = "";
					$vPIS = "";
					$qBCProd = "";
					$vAliqProd = "";
					$cstpis = $piscofins->getcodcst();
					if($this->estabelecimento->getregimetributario() == 1){
						$cstpis = "99";
						$vBCPis = "0.00"; // Base de calculo do Cofins
						$pPIS = "0.00"; // Aliquota do PIS
						$vPIS = "0.00"; // Aliquota do Cofins
					}elseif(in_array($cstpis, array("01", "02", "50", "51", "52", "53", "54", "55", "56"))){
						$cstpis = $piscofins->getcodcst();
						$vBCPis = number_format($itnotafiscal->gettotalbasepis(), 2, ".", ""); // Base de calculo do PIS
						$pPIS = number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
						$vPIS = number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
					}elseif(in_array($cstpis, array("03"))){
						//echo alert("CST 03");
						$cstpis = $piscofins->getcodcst();
						$vPIS = number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
					}elseif(in_array($cstpis, array("04", "06", "07", "08", "09"))){
						//echo alert("CST 04");
						$cstpis = $piscofins->getcodcst();
					}elseif(in_array($cstpis, array("05"))){
						//echo alert("CST 05");
						$cstpis = $piscofins->getcodcst();
						$vBCPis = number_format($itnotafiscal->gettotalbasepis(), 2, ".", ""); // Base de calculo do PIS
						$pPIS = number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
						$vPIS = number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
					}elseif(in_array($cstpis, array("49", "40", "70", "71", "73", "99", "98"))){
						//echo alert("CST 99");
						$cstpis = $piscofins->getcodcst();
						$vBCPis = number_format($itnotafiscal->gettotalbasepis(), 2, ".", ""); // Base de calculo do PIS
						$pPIS = number_format($itnotafiscal->getaliqpis(), 2, ".", ""); // Aliquota do PIS
						$vPIS = number_format($itnotafiscal->gettotalpis(), 2, ".", ""); // Valor total do PIS
					}
					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->CST = $cstpis;
						$std->vBC = $vBCPis;
						$std->pPIS = $pPIS;
						$std->vPIS = $vPIS;
						$std->qBCProd = $qBCProd;
						$std->vAliqProd = $vAliqProd;
						$resp = $this->nfe->tagPIS($std);
					}else{
						$resp = $this->nfe->tagPIS($nItem, $cstpis, $vBCPis, $pPIS, $vPIS, $qBCProd, $vAliqProd);
					}

					// COFINS
					$cstcofins = "";
					$vBCCofins = "";
					$pCOFINS = "";
					$vCOFINS = "";
					$qBCProd = "";
					$vAliqProd = "";
					$cstcofins = $piscofins->getcodcst();
					if($this->estabelecimento->getregimetributario() == 1){
						$cstcofins = "99";
						$vBCCofins = "0.00"; // Base de calculo do Cofins
						$pCOFINS = "0.00"; // Aliquota do Cofins
						$vCOFINS = "0.00"; // Valor total do Cofins
					}elseif(in_array($cstcofins, array("01", "02", "50", "51", "52", "53", "54", "55", "56"))){
						$cstcofins = $piscofins->getcodcst();
						$vBCCofins = number_format($itnotafiscal->gettotalbasecofins(), 2, ".", ""); // Base de calculo do Cofins
						$pCOFINS = number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
						$vCOFINS = number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
					}elseif(in_array($cstcofins, array("03"))){
						$cstcofins = $piscofins->getcodcst();
						$vCOFINS = number_format(($itnotafiscal->getquantidade() * $itnotafiscal->getaliqcofins() / 100), 2); // Valor total do Cofins
					}elseif(in_array($cstcofins, array("04", "06", "07", "08", "09"))){
						$cstcofins = $piscofins->getcodcst();
					}elseif(in_array($cstcofins, array("05"))){
						$cstcofins = $piscofins->getcodcst();
						$vBCCofins = number_format($itnotafiscal->gettotalbasecofins(), 2, ".", ""); // Base de calculo do Cofins
						$pCOFINS = number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
						$vCOFINS = number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
					}elseif(in_array($cstcofins, array("49", "70", "71", "73", "99", "98"))){
						$cstcofins = $piscofins->getcodcst();
						$vBCCofins = number_format($itnotafiscal->gettotalbasecofins(), 2, ".", ""); // Base de calculo do Cofins
						$pCOFINS = number_format($itnotafiscal->getaliqcofins(), 2, ".", ""); // Aliquota do Cofins
						$vCOFINS = number_format($itnotafiscal->gettotalcofins(), 2, ".", ""); // Valor total do Cofins
					}

					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = $nItem; //item da NFe
						$std->CST = $cstcofins;
						$std->vBC = $vBCCofins;
						$std->pCOFINS = $pCOFINS;
						$std->vCOFINS = $vCOFINS;
						$std->qBCProd = $qBCProd;
						$std->vAliqProd = $vAliqProd;
						$resp = $this->nfe->tagCOFINS($std);
					}else{
						$resp = $this->nfe->tagCOFINS($nItem, $cstcofins, $vBCCofins, $pCOFINS, $vCOFINS, $qBCProd, $vAliqProd);
					}
					if(compare_date(date("Y-m-d"), "2016-01-01", "Y-m-d", ">=")){
						$codigocest = $produto->getcest();
						if($itnotafiscal->gettptribicms() == "F" && strlen($produto->getcest()) > 0){
							$codigocest = removeformat($produto->getcest());

							if($this->versaoapi == self::VERSAO_API5){
								$std = new stdClass();
								$std->item = $nItem; //item da NFe
								$std->CEST = $codigocest;
								$std->indEscala = NULL; //incluido no layout 4.00
								$std->CNPJFab = NULL; //incluido no layout 4.00
								$this->nfe->tagCEST($std);
							}else{
								$this->nfe->tagCEST($nItem, $codigocest);
							}
						}else{
							if(is_object($ncm) && $itnotafiscal->gettptribicms() == "F" && !is_null($ncm->getidcest())){
								$cest = objectbytable("cest", $ncm->getidcest(), $this->con);
								$codigocest = removeformat($cest->getcest());

								if($this->versaoapi == self::VERSAO_API5){
									$std = new stdClass();
									$std->item = $nItem; //item da NFe
									$std->CEST = $codigocest;
									$std->indEscala = NULL; //incluido no layout 4.00
									$std->CNPJFab = NULL; //incluido no layout 4.00
									$this->nfe->tagCEST($std);
								}else{
									$this->nfe->tagCEST($nItem, $codigocest);
								}
							}else{
								$codigoncm = "";
							}
						}
					}
					//DI
					if($this->notafiscal->getoperacao() == "IM"){
						$nDI = "";
						$nAdicao = "";
						$nSeqAdicC = "";
						$cFabricante = "";
						$vDescDI = "";
						$nDraw = "";

						$nDI = "";
						$dDI = "";
						$xLocDesemb = "";
						$UFDesemb = "";
						$dDesemb = "";
						$tpViaTransp = "";
						$vAFRMM = "";
						$tpIntermedio = "";
						$CNPJ = "";
						$UFTerceiro = "";
						$cExportador = "";
						//complementar


						$nDI = $this->notafiscal->getnumerodi(); // Numero da DI
						$dDI = $this->notafiscal->getdtregistrodi(); // Data d eregistro da DI
						$xLocDesemb = $this->notafiscal->getlocaldesembaraco(); // Local do desembaraco
						$UFDesemb = $this->notafiscal->getufdesembaraco(); // UF do local do desembaraco
						$dDesemb = $this->notafiscal->getdtdesembaraco(); // Data do desembaraco
						$cExportador = $this->notafiscal->getcodparceiro(); // Codigo do exportador
						$tpViaTransp = $this->notafiscal->getviatransporte(); // Via de transporte internacional
						if($this->notafiscal->gettotalvalorafrmm() > 0){
							$vAFRMM = $this->notafiscal->gettotalvalorafrmm(); // Valor da AFRMM - Adicional ao Frete para RenovaÃ¯Â¿Â½Ã¯Â¿Â½o da Marinha Mercante
						}
						$tpIntermedio = $this->notafiscal->gettipoimportacao(); // Forma de importaÃ¯Â¿Â½Ã¯Â¿Â½o quanto a intermediaÃ¯Â¿Â½Ã¯Â¿Â½o
						if($tpIntermedio != "1"){
							$CNPJ = $this->notafiscal->getcnpjadquirente(); // CNPJ do adquirente ou do encomendante
							$UFTerceiro = $this->notafiscal->getufterceiro(); // Sigla da UF do adquirente ou do encomendante
						}

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->nDI = $nDI;
							$std->dDI = $dDI;
							$std->xLocDesemb = $xLocDesemb;
							$std->UFDesemb = $UFDesemb;
							$std->dDesemb = $dDesemb;
							$std->tpViaTransp = $tpViaTransp;
							$std->vAFRMM = $vAFRMM;
							$std->tpIntermedio = $tpIntermedio;
							$std->CNPJ = $CNPJ;
							$std->UFTerceiro = $UFTerceiro;
							$std->cExportador = $cExportador;
							$resp = $this->nfe->tagDI($std);
						}else{
							$resp = $this->nfe->tagDI($nItem, $nDI, $dDI, $xLocDesemb, $UFDesemb, $dDesemb, $tpViaTransp, $vAFRMM, $tpIntermedio, $CNPJ, $UFTerceiro, $cExportador);
						}
						$nAdicao = $itnotafiscal->getnumadicao(); // Numero da adicao
						$nSeqAdicC = $n; // Sequencial do item na adicao ???????????
						$cFabricante = $this->notafiscal->getcodparceiro(); // Codigo do fabricante estrangeiro
						if($itnotafiscal->getvaldesctodi() > 0){
							$vDescDI = number_format($itnotafiscal->getvaldesctodi(), 2); // Valor de desconto do item na DI
						}

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->item = $nItem; //item da NFe
							$std->nDI = $nDI; //numero da DI
							$std->nAdicao = $nAdicao;
							$std->nSeqAdic = $nSeqAdicC;
							$std->cFabricante = $cFabricante;
							$std->vDescDI = $vDescDI;
							$std->nDraw = $nDraw;
							$resp = $this->nfe->tagadi($std);
						}else{
							$resp = $this->nfe->tagadi($nItem, $nDI, $nAdicao, $nSeqAdicC, $cFabricante, $vDescDI, $nDraw);
						}
					}

					if($itnotafiscal->gettptribicms() != "F"){
						$totalbaseicms += $itnotafiscal->gettotalbaseicms();
						$totalicms += $itnotafiscal->gettotalicms();
					}
					$n++;
				} //fim for

				$arr_natoperacao = array_unique($arr_natoperacao);
				foreach($arr_natoperacao as $i => $_natoperacao){
					$arr_natoperacao[$i] = objectbytable("natoperacao", $_natoperacao, $this->con);
				}
				$vtotalipi = $vtotalipi + 0;
				//Totalizacao da NF-e
				$vBC = "0";
				$vICMS = "0";
				$vICMSDeson = "0";
				$vBCST = "0";
				$vST = "0";
				$vProd = "0";
				$vFrete = "0";
				$vSeg = "0";
				$vDesc = "0";
				$vII = "0";
				$vIPI = "0";
				$vPIS = "0";
				$vCOFINS = "0";
				$vOutro = "0";
				$vNF = "0";
				$vTotTrib = "";
				$vFCP = "0";
				$vFCPST = "0";
				$vFCPSTRet = "0";
				$vIPIDevol = "0";

				$natoperacao = objectbytable("natoperacao", $this->notafiscal->getnatoperacao(), $this->con);

				if($this->estabelecimento->regime_simples() && !in_array($operacaonota->getoperacao(), array("IM", "DF"))){ // Verifica se o estabelecimento esta no simples
					if(in_array($operacaonota->getoperacao(), array("DF", "RF"))){
						$vBC = number_format($this->notafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
						$vICMS = number_format($this->notafiscal->gettotalicms(), 2, ".", ""); // Total do icms
					}else{
						$vBC = "0.00"; // Total da base de calculo do icms
						$vICMS = "0.00"; // Total do icms
					}
				}else{
					if($natoperacao->getdestacaricmsp() == "S"){
						$vBC = number_format($this->notafiscal->gettotalbaseicms(), 2, ".", ""); // Total da base de calculo do icms
						$vICMS = number_format($this->notafiscal->gettotalicms(), 2, ".", ""); // Total do icms
					}else{
						$vBC = "0.00"; // Total da base de calculo do icms
						$vICMS = "0.00"; // Total do icms
					}
				}

				if(($operacaonota->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "N" && $this->notafiscal->gettotalbaseicmssubst() > 0)){
					$vBCST = "0";
					$vST = "0.00"; // Total do icms substituto
				}else{
					$vBCST = number_format($this->notafiscal->gettotalbaseicmssubst(), 2, ".", ""); // Total da base de calculo do icms substituto
					$vST = number_format($this->notafiscal->gettotalicmssubst(), 2, ".", ""); // Total do icms substituto
				}

				$vFCPST = number_format($this->notafiscal->getvalorfcpst(), 2, ".", ""); // Valor FCP ST

				if($this->notafiscal->getfinalidade() == "3"){
					$total_produtos = 0;
				}
//				$total_produtos = $param_pedidotrunca == "T" ? trunc($total_produtos, 2) : round($total_produtos, 2);
//				$vProd = number_format($total_produtos, 2, ".", ""); // Total bruto
				//$vProd = number_format($this->notafiscal->gettotalbruto() + $this->notafiscal->gettotalacrescimo(), 2, ".", ""); // Total bruto
				if($natoperacao->getacrescimocomooutrasdespesas() == "S"){
					$vProd = number_format($this->notafiscal->gettotalbruto(), 2, ".", ""); // Total bruto
				}else{
					$vProd = number_format($this->notafiscal->gettotalbruto() + $this->notafiscal->gettotalacrescimo(), 2, ".", ""); // Total bruto
				}
				if($this->notafiscal->gettotalfrete() > 0){
					$vFrete = number_format($this->notafiscal->gettotalfrete(), 2, ".", ""); // Total frete
				}
				$vSeg = "0"; // Total seguro
				if($this->notafiscal->gettotalseguro() > 0){
					$vSeg = number_format($this->notafiscal->gettotalseguro(), 2, ".", "");
				}
				if($vtotaldesc > 0){
					//$vDesc = number_format($this->notafiscal->gettotaldesconto(),2,".",""); // Total desconto
					$vDesc = number_format($vtotaldesc, 2, ".", "");
				}
				if($this->notafiscal->gettotalii() > 0){
					$vII = number_format($this->notafiscal->gettotalii(), 2, ".", ""); // Total do II
				}
				if(in_array($operacaonota->getoperacao(), array("DF", "RF")) && $parceiro->getdestacaipisubst() == "N" && ($this->notafiscal->gettotalbaseicmssubst() > 0 || $this->notafiscal->gettotalipi() > 0)){
					$vIPI = "0"; // Total ipi
				}else{
					$vIPI = number_format($this->notafiscal->gettotalipi(), 2, ".", ""); // Total ipi
				}

				if($this->notafiscal->gettotalpis() > 0 && $this->estabelecimento->getregimetributario() != 1){
					$vPIS = number_format($this->notafiscal->gettotalpis(), 2, ".", ""); // Total de pis
				}
				if($this->notafiscal->gettotalcofins() > 0 && $this->estabelecimento->getregimetributario() != 1){
					$vCOFINS = number_format($this->notafiscal->gettotalcofins(), 2, ".", ""); // Total de cofins
				}

				$vOutro = number_format($vtotaloutro, 2, ".", "");

				$vNF = number_format($this->notafiscal->gettotalliquido(), 2, ".", ""); // Total da nota fiscal

				if($vTotTribNFe == 0){
					$vTotTribNFe = "";
				}else{
					$vTotTribNFe = number_format($vTotTribNFe, 2, ".", "");
				}

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->vBC = $vBC;
					$std->vICMS = $vICMS;
					$std->vICMSDeson = $vICMSDeson;
					$std->vFCP = $vFCP; //incluso no layout 4.00
					$std->vBCST = $vBCST;
					$std->vST = $vST;
					$std->vFCPST = $vFCPST; //incluso no layout 4.00
					$std->vFCPSTRet = $vFCPSTRet; //incluso no layout 4.00
					$std->vProd = $vProd;
					$std->vFrete = $vFrete;
					$std->vSeg = $vSeg;
					$std->vDesc = $vDesc;
					$std->vII = $vII;
					$std->vIPI = $vIPI;
					$std->vIPIDevol = $vIPIDevol; //incluso no layout 4.00
					$std->vPIS = $vPIS;
					$std->vCOFINS = $vCOFINS;
					$std->vOutro = $vOutro;
					$std->vNF = $vNF;
					$std->vTotTrib = $vTotTribNFe;
					$resp = $this->nfe->tagICMSTot($std);
				}else{
					$resp = $this->nfe->tagICMSTot($vBC, $vICMS, $vICMSDeson, $vBCST, $vST, $vProd, $vFrete, $vSeg, $vDesc, $vII, $vIPI, $vPIS, $vCOFINS, $vOutro, $vNF, $vTotTribNFe);
				}

				$modFrete = $this->notafiscal->getmodfrete(); // Modalidade do frete (0 = por conta do emitente; 1 = por conta do destinatario; 2 = por conta de terceiro; 9 = sem frete)

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->modFrete = $modFrete;
					$resp = $this->nfe->tagtransp($std);
				}else{
					$resp = $this->nfe->tagtransp($modFrete);
				}

				//transportadora
				if($transportadora->exists()){
					$cidade_transp = objectbytable("cidade", $transportadora->getcodcidade(), $this->con);
					$xNome = $transportadora->getnome(); // Nome ou razao social da transportadora
					if($transportadora->gettppessoa() == "J"){
						$CPF = "";
						$CNPJ = removeformat($transportadora->getcpfcnpj()); // CNPJ da transportadora
						$IE = removeformat($transportadora->getrgie()); // IE da transportadora
					}else{
						$CPF = removeformat($transportadora->getcpfcnpj()); // CNPJ da transportadora
						$CNPJ = "";
						$IE = ""; // IE da transportadora
					}
					$xEnder = $transportadora->getendereco().", ".$transportadora->getnumero(); // Endereco da transportadora
					$xMun = utf8_encode($cidade_transp->getnome()); // Cidade da transportadora
					$UFTransp = $cidade_transp->getuf(); // UF da transportadora

					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->xNome = $xNome;
						$std->IE = $IE;
						$std->xEnder = $xEnder;
						$std->xMun = $xMun;
						$std->UF = $UFTransp;
						$std->CNPJ = $CNPJ; //sÃ³ pode haver um ou CNPJ ou CPF, se um deles Ã© especificado o outro deverÃ¡ ser null
						$std->CPF = $CPF;
						$resp = $this->nfe->tagtransporta($std);
					}else{
						$resp = $this->nfe->tagtransporta($CNPJ, $CPF, $xNome, $IE, $xEnder, $xMun, $UFTransp);
					}

					$placa = removeformat($this->notafiscal->gettranspplacavei()); // Placa do veiculo
					if(strlen($placa) > 0){
						$UFPlaca = $this->notafiscal->gettranspufvei(); // UF da placa do veiculo
						$RNTC = $this->notafiscal->gettransprntc(); // Registro nacional de transportador de carga (ANTT)

						if($this->versaoapi == self::VERSAO_API5){
							$std = new stdClass();
							$std->placa = $placa;
							$std->UF = $UFPlaca;
							$std->RNTC = $RNTC;
							$resp = $this->nfe->tagveicTransp($std);
						}else{
							$this->nfe->tagveicTransp($placa, $UFPlaca, $RNTC);
						}
					}
				}

				//Volumes
				$qVol = "";
				$esp = "";
				$marca = "";
				$nVol = "";
				$pesoL = "";
				$pesoB = "";
				$aLacres = "";

				$qVol = ceil($this->notafiscal->gettotalquantidade()); //Quantidade de volumes transportados
				if($qVol > 0){

					if(strlen($this->notafiscal->getmarca()) > 0){
						$marca = $this->notafiscal->getmarca(); // Marca dos volumes transportados
					}
					if(strlen($this->notafiscal->getespecie()) > 0){
						$esp = $this->notafiscal->getespecie(); //EspÃƒÂ©cie dos volumes transportados
					}
					if(strlen($this->notafiscal->getnumeracao()) > 0){
						$nVol = $this->notafiscal->getnumeracao(); // Numeracao dos volumes transportados
					}

					$pesoL = "";
					$pesoB = "";
					if(ceil($this->notafiscal->getpesoliquido()) > 0){
						$pesoL = number_format($this->notafiscal->getpesoliquido(), 3, ".", "");
					}
					if($this->notafiscal->getpesobruto() > 0){
						$pesoB = number_format($this->notafiscal->getpesobruto(), 3, ".", "");
					}
					$aLacres = "";

					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->item = 1; //indicativo do numero do volume
						$std->qVol = $qVol;
						$std->esp = $esp;
						$std->marca = $marca;
						$std->nVol = $nVol;
						$std->pesoL = $pesoL;
						$std->pesoB = $pesoB;
						$resp = $this->nfe->tagvol($std);
					}else{
						$resp = $this->nfe->tagvol($qVol, $esp, $marca, $nVol, $pesoL, $pesoB, $aLacres);
					}
				}

				if($this->notafiscal->getoperacao() == "EX" && substr($this->notafiscal->getnatoperacao(), 0, 1) == "7"){ //se exportacao
					$UFSaidaPais = $this->notafiscal->getufdesembaraco(); //UF do estado onde ocorrera o embarque dos produtos;
					$xLocExporta = $this->notafiscal->getlocaldesembaraco();  //Local onde ocorrera o embarque dos produtos;
					$xLocDespacho = $this->notafiscal->getlocaldesembaraco();  //Local onde ocorrera o embarque dos produtos;

					if($this->versaoapi == self::VERSAO_API5){
						$std = new stdClass();
						$std->UFSaidaPais = $UFSaidaPais;
						$std->xLocExporta = $xLocExporta;
						$std->xLocDespacho = $xLocDespacho;
						$resp = $this->nfe->tagexporta($std);
					}else{
						$resp = $this->nfe->tagexporta($UFSaidaPais, $xLocExporta, $xLocDespacho);
					}
				}
				$arr_detPag = array();
				$arr_lancamento = [];
				if(!is_object($this->notacomplemento) && ($param_notafiscal_gerarlanctonfe != 2) && (($this->versaonfe == "3.10") || ($forma_pagamento->getespecie() == "BL" && $this->versaonfe == "4.00"))){
					if($param_notafiscal_gerarlanctonfe != 2 &&
							$forma_pagamento->getespecie() == "BL" &&
							!in_array($this->notafiscal->getoperacao(), array("DF", "DC", "AE", "AS")) && TRUE){
						//!in_array(substr(removeformat($this->notafiscal->getnatoperacao()), 0, 4), array("5929", "6929"))){
						$lancamento = objectbytable("lancamento", NULL, $this->con);
						$lancamento->setidnotafiscal($this->notafiscal->getidnotafiscal());
						$lancamento->setorder("parcela");
						$arr_lancamento = object_array($lancamento);
						$lancamento = reset($arr_lancamento);

						if($param_notafiscal_gerarlanctonfe == 1 && $lancamento->getdtvencto() == $this->notafiscal->getdtemissao()){
							array_shift($arr_lancamento);
						}
						$cnt = 1;
						if(sizeof($arr_lancamento) > 0){
							$total_bruto = 0;
							$total_desconto = 0;
							$total_acrescimo = 0;
							$total_liquido = 0;
							foreach($arr_lancamento as $lancamento){
								$total_bruto += $lancamento->getvalorparcela();
								$total_desconto += $lancamento->getvalordescto();
								$total_acrescimo += $lancamento->getvaloracresc();
								$total_liquido += $lancamento->getvalorliquido();
							}
							//Dados da fatura
							$nFat = $lancamento->getcodlancto();
							$vOrig = number_format($total_bruto, 2, ".", "");
							$vDesc = ( $total_desconto > 0 ? number_format($total_desconto, 2, ".", "") : "0.00");
							$vLiq = number_format($total_bruto - $total_desconto + $total_acrescimo, 2, ".", "");

							if($this->versaoapi == self::VERSAO_API5){
								$std = new stdClass();
								$std->nFat = $nFat;
								$std->vOrig = $vOrig;
								$std->vDesc = $vDesc;
								$std->vLiq = $vLiq;
								$resp = $this->nfe->tagfat($std);
							}else{
								$resp = $this->nfe->tagfat($nFat, $vOrig, $vDesc, $vLiq);
							}

							foreach($arr_lancamento as $lancamento){
								if(compare_date($lancamento->getdtvencto(), (!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "<")){
									$vencimento = (!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao());
								}else{
									$vencimento = $lancamento->getdtvencto();
								}
								$nDup = $lancamento->getparcela();
								$dVenc = $vencimento;
								$vDup = number_format($lancamento->getvalorliquido(), 2, ".", "");

								if($this->versaoapi == self::VERSAO_API5){
									//if(!in_array($this->notafiscal->getoperacao(), array("DF","DC"))){
									$std = new stdClass();
									$std->nDup = str_pad($nDup, 3, "0", STR_PAD_LEFT);
									$std->dVenc = $dVenc;
									$std->vDup = $vDup;
									$resp = $this->nfe->tagdup($std);
									$arr_detPag[] = $vDup;
									//}
								}else{
									if(!in_array($this->notafiscal->getoperacao(), array("DF", "DC"))){
										$resp = $this->nfe->tagdup($nDup, $dVenc, $vDup);
									}
								}
								++$cnt;
							}
						}
					}
				}
				//$infAdFisco = 'Informacao adicional do fisco';
				//$infCpl = 'Informacoes complementares do emitente';
				//$resp = $this->nfe->taginfAdic($infAdFisco, $infCpl);

				$arr_observacao = array();
				if(in_array($this->estabelecimento->getregimetributario(), array("1"))){
					foreach($arr_natoperacao as $natoperacao){
						$arr_observacao[] = $natoperacao->getobservacaosimples();
					}
				}else{
					foreach($arr_natoperacao as $natoperacao){
						$arr_observacao[] = $natoperacao->getobservacao();
					}
				}
				if(sizeof($arr_observacao) > 0){
					$observacao = implode(" ", array_unique($arr_observacao))." ";
				}else{
					$observacao = "";
				}
				if(strlen($this->notafiscal->getcodfunc()) > 0){
					$funcionario = objectbytable("funcionario", $this->notafiscal->getcodfunc(), $this->con);
					$vendedor = $funcionario->getcodfunc()." - ".$funcionario->getnome();
				}

				if(strlen($this->notafiscal->getnumpedido()) > 0){
					$pedido = objectbytable("pedido", array($this->notafiscal->getcodestabelec(), $this->notafiscal->getnumpedido()), $this->con);
				}else{
					$pedido = objectbytable("pedido", null, $this->con);
				}

				$natoperacao = objectbytable("natoperacao", $this->notafiscal->getnatoperacao(), $this->con);
				$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
				$observacao = str_replace(
						array("[numnotafis]",
					"[serie]",
					"[totaldesconto]",
					"[totalacrescimo]",
					"[totalfrete]",
					"[totalicms]",
					"[totalbaseicms]",
					"[totalicmssubst]",
					"[totalbaseicmssubst]",
					"[totalipi]",
					"[totalbruto]",
					"[totalliquido]",
					"[aliqicms]",
					"[transpfone]",
					"[cupom]",
					"[numeroecf]",
					"[tbasep_simples]",
					"[icms_simples]",
					"[tvicms_simples]",
					"[vendedor]",
					"[refpedido]",
					"[numpedido]"), array($this->notafiscal->getnumnotafis(),
					$this->notafiscal->getserie(),
					$this->notafiscal->gettotaldesconto(TRUE),
					$this->notafiscal->gettotalacrescimo(TRUE),
					$this->notafiscal->gettotalfrete(TRUE),
					$this->notafiscal->gettotalicms(TRUE),
					$this->notafiscal->gettotalbaseicms(TRUE),
					$this->notafiscal->gettotalicmssubst(TRUE),
					$this->notafiscal->gettotalbaseicmssubst(TRUE),
					$this->notafiscal->gettotalipi(TRUE),
					$this->notafiscal->gettotalbruto(TRUE),
					$this->notafiscal->gettotalliquido(TRUE),
					$classfiscal->getaliqicms(TRUE),
					$transportadora->getfone(),
					$this->notafiscal->getcupom(),
					$this->notafiscal->getnumeroecf(),
					number_format($tbasep_simples, 2, ",", "."),
					number_format($icms_simples, 2, ",", ""),
					number_format($tvicms_simples, 2, ",", "."),
					$vendedor,
					$pedido->getrefpedido(),
					$this->notafiscal->getnumpedido()), $observacao
				);

				$observacao .= " ".$this->notafiscal->getobservacaofiscal();
				$observacao = trim(removespecial(str_replace("\n", " ", $observacao)));

				$query = "SELECT (SUM(CASE WHEN ncm.aliqmedia > 0 THEN (itnotafiscal.totalliquido * (ncm.aliqmedia/100)) ELSE (itnotafiscal.totalliquido * (produto.aliqmedia/100)) END)) AS aliqmedia ";
				$query .= "FROM itnotafiscal ";
				$query .= "INNER JOIN produto USING (codproduto) ";
				$query .= "INNER JOIN ncm USING (idncm) ";
				$query .= "WHERE itnotafiscal.idnotafiscal = ".$this->notafiscal->getidnotafiscal();

				$res = $this->con->query($query);
				$totalaliqmedia = $res->fetchColumn();

				$porcentaliqmedia = ($totalaliqmedia / $this->notafiscal->gettotalliquido()) * 100;
				$infAdFisco = "";
				$infCpl = $observacao;
				if($natoperacao->getoperacao() == "DF" && $parceiro->getdestacaipisubst() == "N"){
					if($this->notafiscal->gettotalicmssubst() > 0 || $this->notafiscal->gettotalipi() > 0){
						$observacao_compl = "Informacoes adicionais de interesse do Fisco: DEVOLUCAO DE MERCADORIA VALOR OUTRAS DESPESAS ";
					}
					if($this->notafiscal->gettotalicmssubst() > 0){
						$observacao_compl .= " ICMS ST R$ ".$this->notafiscal->gettotalicmssubst(TRUE);
					}
					if($this->notafiscal->gettotalipi() > 0){
						$observacao_compl .= " IPI R$".$this->notafiscal->gettotalipi(TRUE);
					}
					if($natoperacao->getimprimpostoibpt() == "S"){
						if($totalaliqmedia > 0){
							$infCpl .= ($operacaonota->getoperacao() == "VD" ? " Valor aproximado dos tributos R$ ".number_format($totalaliqmedia, 2, ",", ".")." (".number_format($porcentaliqmedia, 2, ",", ".")."%) Fonte IBPT" : "").$observacao_compl; // Observacao (natureza de operacao)
						}
					}
				}else{
					if($natoperacao->getimprimpostoibpt() == "S"){
						if($totalaliqmedia > 0){
							$infCpl .= ($operacaonota->getoperacao() == "VD" ? " Valor aproximado dos tributos R$ ".number_format($totalaliqmedia, 2, ",", ".")." (".number_format($porcentaliqmedia, 2, ",", ".")."%) Fonte IBPT" : ""); // Observacao (natureza de operacao)
						}
					}
				}
				switch($forma_pagamento->getespecie()){
					case "DH": $codpag = "01";
						break;
					case "CH": $codpag = "02";
						break;
					case "CC": $codpag = "03";
						break;
					case "CD": $codpag = "04";
						break;
					case "CV": $codpag = "05";
						break;
					case "BL": $codpag = "14";
						break;
					default: $codpag = "99";
				}

				if(in_array($this->notafiscal->getoperacao(), array("DF", "DC", "AE", "AS"))){
					$codpag = "90";
					$vDup = 0;
				}else{
					//if($codpag != "14"){
					$vDup = number_format($this->notafiscal->gettotalliquido(), 2, ".", "");
					//}
				}
				if(sizeof($arr_lancamento) == 0){
					$codpag = "90";
					$vDup = 0;
				}
				if($this->versaonfe == "4.00"){
					$std = new stdClass();
					$std->vTroco = null; //incluso no layout 4.00
					$resp = $this->nfe->tagpag($std);
					//foreach($arr_detPag as $valor){
					$std = new stdClass();
					//$std->indPag = $indPag;
					$std->tPag = $codpag;
					$std->vPag = $vDup;
					$std->CNPJ = NULL;
					$std->tBand = NULL;
					$std->cAut = NULL;
					$std->tpIntegra = NULL; //incluso no layout 4.00
					$resp = $this->nfe->tagdetPag($std);
					//}
					//$resp = $this->nfe->tagDetPag("1", $codpag, $vDup);
				}

				if($this->versaoapi == self::VERSAO_API5){
					$std = new stdClass();
					$std->infAdFisco = str_replace("\r", "", str_replace("\n", "", $infAdFisco));
					$std->infCpl = str_replace("\r", "", str_replace("\n", "", $infCpl));
					$resp = $this->nfe->taginfAdic($std);
				}else{
					$resp = $this->nfe->taginfAdic($infAdFisco, $infCpl);
				}
				$this->notafiscal = objectbytable("notafiscal", $this->notafiscal->getidnotafiscal(), $this->con);
			}
		}catch(Exception $ex){
			$_SESSION["ERROR"] = "Erro na gera&ccedil;o das informa&ccedil;&otilde;es da NF-e<br> Erro gerado: ".$ex;
			return FALSE;
		}

		$nfeautorizada = FALSE;

		$linhaslog = array();
		$gravarlog = TRUE;
		$linhaslog[] = "Iniciar Montagem da Nota Fiscal";
		$nfeautorizada = FALSE;

		$linhaslog = array();
		$gravarlog = FALSE;
		$linhaslog[] = "Iniciar Montagem da Nota Fiscal";


		if(!$transmitir){
			return TRUE;
		}
		$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS.$chavenfe.LITERALNFE;
		/*
		 * se ja existir um xml na pasta log da nota que o usuÃ¡rio esta tentando transmitir, o sistema
		 * tenta fazer fazer a consulta para verificar se a mesma ja foi transmitida e ja esta autorizada no SEFAZ
		 */
		if(file_exists($nomexml)){
			$arr_retorno = array();
			$aRespSefaz = "";
			if($this->consultarnfephp(TRANSMITIRNFE, "", $chavenfe, FALSE)){
				if((is_object($this->notacomplemento) ? $this->notacomplemento->getcodigostatus() == "100" : $this->notafiscal->getcodigostatus() == "100")){
					return TRUE;
				}
			}else{
				return FALSE;
			}
		}

		if($this->montarnfephp()){
			$linhaslog[] .= "Nota Fiscal Montada";
			header('Content-type: text/xml; charset=UTF-8');
			$xmlnfe = $this->getxml();
			$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
			if(!is_dir(($nomexml))){
				$linhaslog[] .= "Criar local de log dos arquivos xml";
				mkdir($nomexml, 0777);
			}
			$nomexml = $nomexml.$chavenfe.LITERALNFE;
			$bytes = file_put_contents($nomexml, $xmlnfe); //grava o xml na pasta de destino
			if($bytes = 0){
				$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel gravar o xml da NF-e na pasta 'Log'";
				return FALSE;
			}

			$linhaslog[] .= "XML gravado na pasta log";
			$linhaslog[] .= "Iniciar a assinatura digital da NF";
			switch($this->versaoapi){
				case "4.0.0":
					$xmlnfe = $this->assinarnfe($nomexml);
					break;
				case "5.0.0":
					$xmlnfe = $this->assinarnfe($xmlnfe);
					break;
			}

			if($xmlnfe){
				$linhaslog[] .= "Iniciar a validacao da NF";
				file_put_contents($nomexml, $xmlnfe);

				if(!$this->validarnfe($xmlnfe)){
					return FALSE;
				}else{
					$bytes = file_put_contents($nomexml, $xmlnfe);
					if($bytes > 0){
						$linhaslog[] .= "NF validada";

						$xmlnfe = file_get_contents($nomexml);
						$linhaslog[] .= "Iniciar envio da NF";

						if($this->autorizanfe($xmlnfe, $recibo)){
							if(is_object($this->notacomplemento)){
								$this->notacomplemento->setchavenfe($chavenfe);
								$this->notacomplemento->setrecibonfe($recibo);
								if(!$this->notacomplemento->save()){
									//$this->con->rollback();
									$_SESSION["ERROR"] = "N&atilde;o foi possivel atualizar dados da NF-e de complemento no banco de dados. NF-e n&atilde;o transmitida. Tente a transmiss&atilde;o novamente";
									return TRUE;
								}
							}else{
								$this->notafiscal->setrecibonfe($recibo);
								$this->notafiscal->setchavenfe($chavenfe);
								if(!$this->notafiscal->save()){
									//$this->con->rollback();
									$_SESSION["ERROR"] = "N&atilde;o foi possivel atualizar dados da NF-e no banco de dados. NF-e n&atilde;o transmitida. Tente a transmiss&atilde;o novamente";
									return TRUE;
								}
							}
							//return FALSE;
							if($this->consultarnfephp(TRANSMITIRNFE, $recibo, "", "", FALSE)){
								$linhaslog[] .= "NF Consultada com sucesso";
							}else{
								$linhaslog[] .= "Nao foi possivel consultar a NF";
							}
							if($gravarlog){
								file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
							}
							return TRUE;
						}else{
							if(is_object($this->notacomplemento)){
								$this->notacomplemento->setchavenfe($chavenfe);
								$this->notacomplemento->setrecibonfe($recibo);
								if(!$this->notacomplemento->save()){
									//$this->con->rollback();
									$_SESSION["ERROR"] = "N&atilde;o foi possivel atualizar dados da NF-e de complemento no banco de dados. NF-e n&atilde;o transmitida. Tente a transmiss&atilde;o novamente";
									return TRUE;
								}
							}else{
								$this->notafiscal->setrecibonfe($recibo);
								$this->notafiscal->setchavenfe($chavenfe);
								if(!$this->notafiscal->save()){
									//$this->con->rollback();
									$_SESSION["ERROR"] = "N&atilde;o foi possivel atualizar dados da NF-e no banco de dados. NF-e n&atilde;o transmitida. Tente a transmiss&atilde;o novamente";
									return TRUE;
								}
							}
							return FALSE;
						}
					}else{
						$_SESSION["ERROR"] .= "N&atilde;o foi possivel gravar o arquivo xml na pasta 'Log'";
					}
				}
			}else{
				if(file_exists($nomexml)){
					unlink($nomexml);
				}
			}
		}
		return FALSE;
	}

	function montarnfephp(){
		try{
			switch($this->versaoapi){
				case "4.0.0":
					$resp = $this->nfe->montaNFe(); //monta o xml da NF-e
					break;
				case "5.0.0":
					$resp = $this->nfe->montaNFe(); //monta o xml da NF-e
					//$resp = $this->nfe->getXML();
					break;
			}

			if(!$resp){
				$linhaslog[] .= "Houve erros ao montar a NF";
				foreach($this->nfe->erros as $err){
					$e .= 'tag: &lt;'.$err['tag'].'&gt; ---- '.$err['desc'].'<br>';
					$linhaslog[] .= 'tag: &lt;'.$err['tag'].'&gt; ---- '.$err['desc'];
				}
				$_SESSION["ERROR"] .= $e;
				if($gravarlog){
					file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
				}
				return FALSE;
			}else{
				return TRUE;
			}
		}catch(Exception $ex){
			return $ex;
		}
	}

	function getxml(){
		$xmlnfe = $this->nfe->getXML(); // obtem o xml da NF-e
		$linhaslog[] .= "XML Recuperado";
		return $xmlnfe;
	}

	function assinarnfe($xmlnfe){
		switch($this->versaoapi){
			case "4.0.0":
				$xmlnfe = $this->nfeTools->assina($xmlnfe);
				break;
			case "5.0.0":
				$xmlnfe = $this->nfeTools->signNFe($xmlnfe);
				break;
		}
		if($xmlnfe){
			return $xmlnfe;
		}else{
			$linhaslog[] .= "Houve ao assinar a NF";
			$linhaslog[] .= $this->nfe->errMsg;
			$_SESSION["ERROR"] = "Ocorreu erro na assinatura da NF-e.<br>".$this->nfe->errMsg;
			if($gravarlog){
				file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
			}
			return FALSE;
		}
	}

	function validarnfe($xmlnfe){
		//$xsdFile = $this->xsdArquivo; //'../nfephp/schemes/PL_008f/nfe_v3.10.xsd';

		switch($this->versaoapi){
			case "4.0.0":
				$resp = $this->nfeTools->validarXml($xmlnfe);
				break;
			case "5.0.0":
				$resp = TRUE; //$this->nfeTools->isValid($this->versaonfe, $xmlnfe, "nfe");
				break;
		}

		if(!$resp){
			$_SESSION["ERROR"] = "Estrutura do XML da NFe contem erros --- <br>";
			$err = "";
			$linhaslog[] .= "Houve erro na validaÃ§ao da NF";
			foreach($this->nfeTools->errors as $erro){
				foreach($erro as $msgerro){
					$linhaslog[] .= $erro;
					//$err .= $erro.'<br>';
					$err .= $msgerro."<br>";
				}
			}
			$_SESSION["ERROR"] .= $err;
			if($gravarlog){
				file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
			}
			return FALSE;
		}else{
			return TRUE;
		}
	}

	function autorizanfe($xmlnfe, &$recibo){
		$lote = substr(str_replace(',', '', number_format(microtime(true) * 1000000, 0)), 0, 15);
		try{
			switch($this->versaoapi){
				case "4.0.0":
					$aResp = $this->nfeTools->sefazEnviaLote($xmlnfe, $this->ambiente, $Lote, $retorno, "0", FALSE);
					break;
				case "5.0.0":
					$aResp = $this->nfeTools->sefazEnviaLote([$xmlnfe], $lote);
					break;
			}
		}catch(Exception $ex){
			file_put_contents("../temp/soapdebug.xml", $ex->getMessage());
			throw new Exception($ex->getMessage());
		}

		if($aResp){
			sleep($this->timeoutconsulta);

			switch($this->versaoapi){
				case "4.0.0":
					break;
				case "5.0.0":
					$retorno = new \NFePHP\NFe\Common\Standardize($aResp);
					$retorno = $retorno->toArray();
					$retorno["bStat"] = TRUE;
					break;
			}

			if($retorno['bStat']){ // se o lote foi processado
				$recibo = $retorno['infRec']['nRec'];
				if(empty($recibo)){
					$recibo = $retorno['nRec'];
				}
				$chave = "";
				$linhaslog[] .= "NF Enviada ao sefaz";
				return TRUE;
			}else{
				$linhaslog[] .= "Hove erro no envio da NF";
				$linhaslog[] .= "Erro gerado: ".$this->nfeTools->errMsg;
				$_SESSION["ERROR"] .= "Houve erro !! ".$this->nfeTools->errMsg;
				if($gravarlog){
					file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
				}
				return FALSE;
			}
		}else{
			$linhaslog[] .= "Hove no envio da NF";
			$linhaslog[] .= "Erro gerado: ".$this->nfeTools->errMsg;
			$_SESSION["ERROR"] .= "houve erro !! ".$this->nfeTools->errMsg;
			$_SESSION["ERROR"] .= str_replace(array("\n", "\r"), array("\r\n", ""), htmlspecialchars($this->nfeTools->soapDebug));
			if($gravarlog){
				file_put_contents("../temp/log".date("Y-m-d H:i:s").".txt", implode("\r\n", $linhaslog));
			}
			return FALSE;
		}
	}

	function inutilizarnfephp($justificativa){
		$msgcertificado = "";
		switch($this->versaoapi){
			case "4.0.0":
				$dif = floor(($this->nfeTools->certExpireTimestamp - mktime(0, 0, 0, date("m"), date("d"), date("Y"))) / 86400);
				break;
			case "5.0.0":
				$data = $this->certificado->getValidTo()->format("Y-m-d");
				$validCert = strtotime($data);
				$hoje = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
				$dif = floor(($validCert - $hoje) / 86400);
				break;
		}
		if($dif <= 40){
			if($dif){
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado ir&aacute; vencer em {$dif} dia(s), por favor fa&ccedil;a a renova&ccedil;&atilde;o do mesmo para evitar transtornos na hora de emitir suas notas<br><br>";
			}else{
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado esta vencido, providencie um novo certificado para poder transmitir suas notas fiscais. dia(s) de vencido <br><br>";
			}
		}

		$_SESSION["ERROR"] = "";
		$_SESSION["INUTILIZACAO"] = $msgcertificado;
		$nAno = date("y");
		$nSerie = $this->notafiscal->getserie();
		$nIni = $this->notafiscal->getnumnotafis();
		$nFin = $this->notafiscal->getnumnotafis();
		$xJust = $justificativa;
		$tpAmb = $this->ambiente;
		$arr_retorno = array();
		switch($this->versaoapi){
			case "4.0.0":
				$xml = $this->nfeTools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust, $tpAmb, $arr_retorno);
				break;
			case "5.0.0":
				$xml = $this->nfeTools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust, $tpAmb);
				$std_xml = new \NFePHP\NFe\Common\Standardize($xml);
				$arr_retorno = $std_xml->toArray();
				$arr_retorno = $arr_retorno["infInut"];
				$arr_retorno["bStat"] = TRUE;
				break;
		}
		if($xml){
			if($arr_retorno["bStat"]){
				$cstat = $arr_retorno["cStat"];
				$xmotivo = $arr_retorno["xMotivo"];
				$nProt = $arr_retorno["nProt"];
				$datarecebimento = explode(" ", $arr_retorno["dhRecbto"]);
				$datarecebimento = $datarecebimento[0];
				$estabelecimento = objectbytable("estabelecimento", $this->notafiscal->getcodestabelec(), $this->con);
				$estadoestabelc = objectbytable("estado", $estabelecimento->getuf(), $this->con);
				$nUf = $estadoestabelc->getcodoficial();
				$Cnpj = removeformat($estabelecimento->getcpfcnpj());
				$id = 'ID'
						.$nUf
						.$nAno
						.$Cnpj
						."55"
						.str_pad($nSerie, 3, '0', STR_PAD_LEFT)
						.str_pad($nIni, 9, '0', STR_PAD_LEFT)
						.str_pad($nFin, 9, '0', STR_PAD_LEFT);
				$nomexml = $this->pathinut.removeformat($this->estabelecimento->getcpfcnpj()).DS;
				if(!is_dir($nomexml)){
					mkdir($nomexml, 0777);
				}
				$nomexml .= convert_date($this->notafiscal->getdtemissao(), "Y-m-d", "Ym").DS;
				if(!is_dir($nomexml)){
					mkdir($nomexml, 0777);
				}

				if(in_array($cstat, array("102", "206", "256", "563"))){
					if($cstat != "563"){
						file_put_contents($nomexml.$id."-procInut.xml", $xml);
					}
					$this->notafiscal->setcodigostatus($cstat);
					$this->notafiscal->setxmotivo(($cstat == "563" ? "InutilizaÃ§Ã£o de nÃºmero homologado" : $xmotivo));
					$this->notafiscal->setdataautorizacao($datarecebimento);
					$this->notafiscal->setstatus("I");
					$this->notafiscal->setprotocolonfe($nProt);
					$this->notafiscal->setdataautorizacao($datarecebimento);
					$this->notafiscal->setxmlnfe($xmlnfe);
					if(!$this->notafiscal->save()){
						//$this->con->roolback();
						$_SESSION["ERROR"] .= "Inutiliza&ccedil;&atilde;o efetuado com sucesso no SEFAZ!<br>Mas n&atilde;o foi possivel atualizar o status da NF-e no banco de dados.";
						return FALSE;
					}else{
						$_SESSION["INUTILIZACAO"] .= "Inutiliza&ccedil;&atilde;o efetuado com sucesso no SEFAZ!";
						return TRUE;
					}
				}else{
					$_SESSION["ERROR"] .= $cstat."\n";
					$_SESSION["ERROR"] .= $xmotivo."\n";
					$_SESSION["ERROR"] .= $this->nfeTools->errMsg;
					return FALSE;
				}
			}else{
				$_SESSION["ERROR"] .= $this->nfeTools->errMsg;
				return FALSE;
			}
		}else{
			$cstat = $arr_retorno["cStat"];
			if(!$arr_retorno["bStat"] && $cstat == 563){
				$this->notafiscal->setcodigostatus("102");
				$this->notafiscal->setxmotivo("InutilizaÃ§Ã£o de numero homologado");
				$this->notafiscal->setdataautorizacao(date("Y-m-d"));
				$this->notafiscal->setstatus("I");
				$this->notafiscal->setdataautorizacao(date("Y-m-d"));
				if(!$this->notafiscal->save()){
					//$this->con->roolback();
					$_SESSION["ERROR"] .= "Inutiliza&ccedil;&atilde;o efetuado com sucesso no SEFAZ!<br>Mas n&atilde;o foi possivel atualizar o status da NF-e no banco de dados.";
					return FALSE;
				}else{
					$_SESSION["INUTILIZACAO"] .= "Inutiliza&ccedil;&atilde;o efetuado com sucesso no SEFAZ!";
					return TRUE;
				}
			}else{
				$_SESSION["ERROR"] .= $this->nfeTools->errMsg;
				return FALSE;
			}
		}
	}

	function cancelarnfephp($xJust){

		if($carregarconfiguracoes){
			$this->carregaconfiguracoes();
		}
		$msgcertificado = "";
		switch($this->versaoapi){
			case "4.0.0":
				$dif = floor(($this->nfeTools->certExpireTimestamp - mktime(0, 0, 0, date("m"), date("d"), date("Y"))) / 86400);
				break;
			case "5.0.0":
				$data = $this->certificado->getValidTo()->format("Y-m-d");
				$validCert = strtotime($data);
				$hoje = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
				$dif = floor(($validCert - $hoje) / 86400);
				break;
		}

		if($dif <= 40){
			if($dif > 0){
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado ir&aacute; vencer em {$dif} dia(s), por favor fa&ccedil;a a renova&ccedil;&atilde;o do mesmo para evitar transtornos na hora de emitir suas notas<br><br>";
			}else{
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado esta vencido, providencie um novo certificado para poder transmitir suas notas fiscais. dia(s) de vencido <br><br>";
			}
		}

		switch($this->versaoapi){
			case "4.0.0":
				$resp = $this->nfeTools->sefazCancela(
						(!is_object($this->notacomplemento) ? $this->notafiscal->getchavenfe() : $this->notacomplemento->getchavenfe()), $this->ambiente, $xJust, (!is_object($this->notacomplemento) ? $this->notafiscal->getprotocolonfe() : $this->notacomplemento->getprotocolonfe()), $aResposta
				);
				break;
			case "5.0.0":
				$resp = $this->nfeTools->sefazCancela(
						(!is_object($this->notacomplemento) ? $this->notafiscal->getchavenfe() : $this->notacomplemento->getchavenfe()), $xJust, (!is_object($this->notacomplemento) ? $this->notafiscal->getprotocolonfe() : $this->notacomplemento->getprotocolonfe())
				);
				$stdResp = new \NFePHP\NFe\Common\Standardize($resp);
				$aResposta = $stdResp->toArray();
				$aResposta["bStat"] = TRUE;
				break;
		}

		if($aResposta["bStat"]){
			$cstat = $aResposta["cStat"];
			$xmotivo = $aResposta["xMotivo"];
			if($cstat == "128"){
				switch($this->versaoapi){
					case "4.0.0":
						$aResposta = $aResposta["evento"][0];
						break;
					case "5.0.0":
						$aResposta = $aResposta["retEvento"]["infEvento"];
						break;
				}

				if(isset($aResposta)){
					$cstat = $aResposta["cStat"];
					$xmotivo = $aResposta["xMotivo"];
				}
				if($cstat == "135" || $cstat == "155" || $cstat == "573"){
					sleep($this->timeoutconsulta);
					if($this->consultarnfephp(CANCELARNFE, "", (!is_object($this->notacomplemento) ? $this->notafiscal->getchavenfe() : $this->notacomplemento->getchavenfe()), FALSE)){
						return TRUE;
					}else{
						return FALSE;
					}
				}
				$_SESSION["ERROR"] = "N&atilde;o foi possivel cancelar a NF-e ".$cstat." - ".$xmotivo;
				return FALSE;
			}else{
				$_SESSION["ERROR"] = $this->nfeTools->errMsg;
				return FALSE;
			}
		}else{
			$_SESSION["ERROR"] = $this->nfeTools->errMsg;
			return FALSE;
		}
	}

	function imprimirccenfephp(){
		$_SESSION["IMPRIMIREVENTO"] = "";
		$cidade_estabelecimento = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);
		$datacce = $this->notafiscal->getdatacce();
		$chavenfe = $this->notafiscal->getchavenfe();
		$seqcce = $this->notafiscal->getsequenciaevento();
		$xlmlcce = $this->pathcce.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date($datacce, "Y-m-d", "Ym").DS.$chavenfe.LITERALCCE;

		$aEnd = array('razao' => $this->estabelecimento->getrazaosocial(),
			'logradouro' => $this->estabelecimento->getendereco(),
			'numero' => $this->estabelecimento->getnumero(),
			'complemento' => $this->estabelecimento->getcomplemento(),
			'bairro' => $this->estabelecimento->getbairro(),
			'CEP' => removeformat($this->estabelecimento->getcep()),
			'municipio' => $cidade_estabelecimento->getnome(),
			'UF' => $this->estabelecimento->getuf(),
			'telefone' => removeformat($this->estabelecimento->getfone1()),
			'email' => $this->estabelecimento->getemail());
		if(is_file($xlmlcce)){
			switch($this->versaoapi){
				case "4.0.0":
					$xlmlcce = NFePHP\Common\Files\FilesFolders::readFile($xlmlcce);

					//$cce = new Dacce($xlmlcce, "P", "A4", $this->pathlogo, "F", $aEnd, "", "Times", 1);
					$cce = new $this->classDacce($xlmlcce, "P", "A4", $this->pathlogo, "F", $aEnd, "", "Times", 1);

					$pdf = $cce->printDACCE($this->pathpdf.$chavenfe.LITERALPDF, "F");
					break;
				case "5.0.0":
					if(file_exists($xlmlcce)){
						$xlmlcce = file_get_contents($xlmlcce);
						$cce = new $this->classDacce($xlmlcce, "P", "A4", $this->pathlogo, "F", $aEnd, "", "Times", 1);
						$cce->printDocument($this->pathpdf.$chavenfe.LITERALPDF, "F");
					}
					break;
			}
			if(is_file($this->pathpdf.$chavenfe.LITERALPDF)){
				$arquivopdf = "window.open('".$this->pathpdf.$chavenfe.LITERALPDF."');";
				echo script($arquivopdf);
			}
			$_SESSION["IMPRIMIREVENTO"] = "Impress&atilde;o do evento gerado com sucesso!";
			return TRUE;
		}else{
			$_SESSION["ERROR"] = "N&atilde;o foi possivel localizar o arquivo do evento <br>".$xlmlcce;
			return FALSE;
		}
	}

	function imprimirnfephp($chavenfe, $arr_imprimirvias, $exibirmsg = TRUE){
		$_SESSION["IMPRIMIRNFE"] = "";
		$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
		if(is_null($chavenfe)){
			if(!is_object($this->notacomplemento)){
				$chavenfe = $this->notafiscal->calcular_chavenfe();
			}else{
				$chavenfe = $this->notacomplemento->calcular_chavenfe();
			}
		}
		$nomexml .= $chavenfe.LITERALNFE;
		if(is_dir($this->pathpdf)){
			if(!is_file($nomexml) || (!is_object($this->notacomplemento) ? $this->notafiscal->getstatus() == "P" : $this->notacomplemento->getstatus() == "P")){
				if((!is_object($this->notacomplemento) ? strlen($this->notafiscal->getxmlnfe()) == 0 : strlen($this->notacomplemento->getxmlnfe()) == 0)){
					$this->gerarnfephp(FALSE);
					$this->montarnfephp();
					$xmlnfe = $this->getxml();

					$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
					if(!is_dir(($nomexml))){
						$linhaslog[] .= "Criar local de log dos arquivos xml";
						mkdir($nomexml, 0777);
					}
					$nomexml = $nomexml.$chavenfe.LITERALNFE;
					$bytes = file_put_contents($nomexml, $xmlnfe); //grava o xml na pasta de destino
					if($bytes = 0){
						$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel gravar o xml da NF-e na pasta 'Log'";
						return FALSE;
					}
				}
			}

			if(!is_file($nomexml)){
				$xmlnfe = (!is_object($this->notacomplemento) ? $this->notafiscal->getxmlnfe() : $this->notacomplemento->getxmlnfe());
				if(!$this->nfeTools->sefazValidate($xmlnfe)){
					$_SESSION["ERROR"] = "NÃ£o foi possivel validar o XML da NFe para a impressÃ£o";
					return FALSE;
				}else{
					file_put_contents($nomexml, $xmlnfe);
				}
			}

			if(is_file($nomexml)){
				if(count($arr_imprimirvias) == 0){
					$arr_imprimirvias[] = "";
				}
				$this->carregaconfiguracoes();
				switch($this->versaoapi){
					case "4.0.0":
						if(is_file($this->aConfig)){
							$aConfig = NFePHP\Common\Files\FilesFolders::readFile($this->aConfig);
							$aConfig = (array) json_decode($aConfig);
							$format = $aConfig["aDocFormat"]->format;
							$paper = $aConfig["aDocFormat"]->paper;
						}else{
							$format = "P";
							$paper = "A4";
						}
						$xmlnfe = NFePHP\Common\Files\FilesFolders::readFile($nomexml);
						$danfe = new $this->classDanfe($xmlnfe, $format, "A4", $this->pathlogo, "F", "../temp/pdfnfe/".$chavenfe.LITERALPDF);
						if($this->notafiscal->getfinalidade() == "3"){
							$danfe->exibirValorTributos = false;
						}
						$id = $danfe->montaDANFE($format, "A4", "L", NFEPHP_SITUACAO_EXTERNA_NONE, FALSE, "", 2, 2, 2, $arr_imprimirvias);
						$pdfnfe = $danfe->printDANFE($this->pathpdf.$id.LITERALPDF, "F");
						if(is_file($this->pathpdf.$id.LITERALPDF)){
							$arquivopdf = "window.open('".$this->pathpdf.$id.LITERALPDF."');";
							echo script($arquivopdf);
						}
						if($exibirmsg){
							$_SESSION["IMPRIMIRNFE"] = "Impress&atilde;o gerada com sucesso!";
						}
						break;
					case "5.0.0":
						$tam_pdf = 0;
						$cont = 0;
						while($tam_pdf < 1000 && $cont < 10){
							$xmlnfe = file_get_contents($nomexml);
							$arquivologo = (strpos($this->pathlogo, ".jpg") === False ? $this->pathlogo : $this->pathlogo);
							$danfe = new $this->classDanfe($xmlnfe, "P", "A4", $arquivologo, "../temp/pdfnfe/".$chavenfe.LITERALPDF);
							if($this->notafiscal->getfinalidade() == "3"){
								$danfe->exibirValorTributos = false;
							}
							$id = $danfe->montaDANFE("P", "A4", "L", $danfe::SIT_NONE, FALSE, "", 2, 2, 2, $arr_imprimirvias);
							$pdfnfe = $danfe->printDocument($this->pathpdf.$id.LITERALPDF, "F");
							$tam_pdf = filesize($this->pathpdf.$id.LITERALPDF);
							$cont++;
							sleep(1);
						}
						if(is_file($this->pathpdf.$id.LITERALPDF)){
							$arquivopdf = "window.open('".$this->pathpdf.$id.LITERALPDF."');";
							echo script($arquivopdf);
						}
						if($exibirmsg){
							$_SESSION["IMPRIMIRNFE"] = "Impress&atilde;o gerada com sucesso!";
						}
						break;
				}
				return TRUE;
			}else{
				$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar a impress&atilde;o da NF-e, N&atilde;o encontrado o xml da NF-e";
			}
		}else{
			if($exibirmsg){
				$_SESSION["ERROR"] = "N&atilde;o foi possivel gerar a impress&atilde;o da NF-e, o local de arquivos PDF n&atilde;o encontrado";
			}
			return FALSE;
		}
	}

	/*
	 *
	 */

	function consultarnfephp($operacao, $recibonfe = "", $chavenfe = "", $carregarconfiguracoes = TRUE){
		if($carregarconfiguracoes){
			$this->carregaconfiguracoes();
		}
		$msgcertificado = "";
		switch($this->versaoapi){
			case "4.0.0":
				$dif = floor(($this->nfeTools->certExpireTimestamp - mktime(0, 0, 0, date("m"), date("d"), date("Y"))) / 86400);
				break;
			case "5.0.0":
				$data = $this->certificado->getValidTo()->format("Y-m-d");
				$validCert = strtotime($data);
				$hoje = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
				$dif = floor(($validCert - $hoje) / 86400);
				//$dif = floor((\NFePHP\Common\Certificate::getValidTo() - mktime(0, 0, 0, date("m"), date("d"), date("Y"))) / 86400);
				break;
		}

		if($dif <= 40){
			if($dif > 0){
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado ir&aacute; vencer em {$dif} dia(s), por favor fa&ccedil;a a renova&ccedil;&atilde;o do mesmo para evitar transtornos na hora de emitir suas notas<br><br>";
			}else{
				$msgcertificado = "Aten&ccedil;&atilde;o! seu certificado esta vencido, providencie um novo certificado para poder transmitir suas notas fiscais. dia(s) de vencido <br><br>";
			}
		}
		$_SESSION["ENVIARNFE"] = $msgcertificado;
		$_SESSION["CONSULTARNFE"] = $msgcertificado;
		$_SESSION["CANCELARNFE"] = $msgcertificado;
		$_SESSION["ERROR"] = $msgcertificado;
		$salvarnfe = TRUE;
		if(!empty($recibonfe)){
			$chavenfe = "";
		}
		$aRespSefaz = "";
		if($this->consultarnfesefaz($recibonfe, $chavenfe, $arr_retorno, $aRespSefaz)){
			if($arr_retorno["bStat"]){
				switch($this->versaoapi){
					case "4.0.0":
						//checa se a consulta retornou um cancelamento ou um protocolo de autorizacao
						$cstat = $arr_retorno["cStat"];
						if(($cstat != "101") || ($cstat == "101" && is_array($arr_retorno["aCanc"]) && count($arr_retorno["aCanc"]) > 0)){
							if(is_array($arr_retorno["aCanc"]) && count($arr_retorno["aCanc"]) > 0){
								$arr_retorno = $arr_retorno["aCanc"];
							}elseif($recibonfe == "" && is_array($arr_retorno["aProt"]) && count($arr_retorno["aProt"]) > 0){
								$arr_retorno = $arr_retorno["aProt"];
							}elseif($recibonfe != "" && is_array($arr_retorno["aProt"][0]) && count($arr_retorno["aProt"][0]) > 0){
								$arr_retorno = $arr_retorno["aProt"][0];
							}
						}
						//seta variaveis com seus valores
						$cstat = $arr_retorno["cStat"];
						if($cstat == "204"){
							$chavenfe = $arr_retorno["chNFe"];
							$retorno = $this->consultarnfephp(CONSULTARNFE, "", $chavenfe);
							return $retorno;
						}
						$xmotivo = $arr_retorno["xMotivo"];
						$chavenfe = $arr_retorno["chNFe"];
						$nProt = $arr_retorno["nProt"];
						$datarecebimento = explode(" ", $arr_retorno["dhRecbto"]);
						$datarecebimento = $datarecebimento[0];
						break;
					case "5.0.0":
						$std_retorno = new \NFePHP\NFe\Common\Standardize($aRespSefaz);
						$arr_retorno = $std_retorno->toArray();

						$cstat = $arr_retorno["cStat"];
						if(($cstat != "101") || ($cstat == "101" && is_array($arr_retorno["retCancNFe"]) && count($arr_retorno["retCancNFe"]) > 0)){
							if(is_array($arr_retorno["retCancNFe"]) && count($arr_retorno["retCancNFe"]) > 0){
								$arr_retorno = $arr_retorno["retCancNFe"]["infCanc"];
							}elseif(($recibonfe == "" || $recibonfe != "") && is_array($arr_retorno["protNFe"]) && count($arr_retorno["protNFe"]) > 0){
								$arr_retorno = $arr_retorno["protNFe"]["infProt"];
							}
						}

						$cstat = $arr_retorno["cStat"];
						if($cstat == "204"){
							$chavenfe = $arr_retorno["chNFe"];
							$recibonfe = substr($arr_retorno["xMotivo"], strpos($arr_retorno["xMotivo"], "[") + 6, 15);
							$retorno = $this->consultarnfephp(CONSULTARNFE, "", $chavenfe, $carregarconfiguracoes);
							return $retorno;
						}
						if($cstat == "613"){
							$chavenfe = substr($arr_retorno["xMotivo"], strpos($arr_retorno["xMotivo"], "[") + 1, 44);
							$retorno = $this->consultarnfephp(CONSULTARNFE, "", $chavenfe, $carregarconfiguracoes);
							return $retorno;
						}
						$xmotivo = $arr_retorno["xMotivo"];
						$chavenfe = $arr_retorno["chNFe"];
						$nProt = $arr_retorno["nProt"];
						$datarecebimento = explode(" ", $arr_retorno["dhRecbto"]);
						$datarecebimento = $datarecebimento[0];
						$anoMes = convert_date(substr($datarecebimento, 0, 10), "Y-m-d", "Ym");

						$nomeprotocolo = $this->pathprotocolos.removeformat($this->estabelecimento->getcpfcnpj());
						if(!is_dir(($nomeprotocolo))){
							mkdir($nomeprotocolo, 0777);
						}
						//$anoMes = date("Ym");
						$nomeprotocolo .= DS.$anoMes;
						if(!is_dir(($nomeprotocolo))){
							mkdir($nomeprotocolo, 0777);
						}
						if(strlen($recibonfe) > 0){
							$nomeprotocolo .= DS."{$recibonfe}".LITERALPROTOCOLORECIBO;
						}else{
							if(!in_array($cstat, array("101", "151", "155"))){
								$nomeprotocolo .= DS."{$chavenfe}".LITERALPROTOCOLOCHAVE;
							}else{
								$nomeprotocolo .= DS."{$chavenfe}".LITERALCANCELAMENTO;
							}
						}
						if(in_array($cstat, array("100", "150", "110", "301", "302", "101", "151", "155"))){
							$nbytes = file_put_contents($nomeprotocolo, $aRespSefaz);
						}
						break;
				}
				file_put_contents("../temp/retorno.txt", implode($arr_retorno));
				if(in_array($cstat, array("100", "150", "110", "301", "302"))){
					$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;

					//verifica se a pasta log para o emitente existe
					if(!is_dir(($nomexml))){
						mkdir($nomexml, 0777);
					}
					//adiciona o nome do arquivo ao caminho
					$nomexml .= $chavenfe.LITERALNFE;
					//checa se o arquivo existe na pasta log
					if(!is_file($nomexml)){
						$nomexmlnfe = $this->pathnfe.removeformat($this->estabelecimento->getcpfcnpj()).DS;
						//$nomexmlnfe .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
						$nomexmlnfe .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
						$nomexmlnfe .= $chavenfe.LITERALNFE;
						//se nÃ£o existe na pasta log, checa se existe na pasta de armazenamento mensal da nf-e
						if(!is_file($nomexmlnfe)){
							$_SESSION["ERROR"] = "N&atilde;o foi possivel localizar o xml da referente a NF-e";
							return FALSE;
						}else{
							//se existe na pasta d enf-e mensal, le o xml e grava na pasta de log
							$xmlnfe = file_get_contents($nomexmlnfe);
							$bytes = file_put_contents($nomexml, $xmlnfe);
						}
					}else{
						$xmlnfe = file_get_contents($nomexml);
					}

					//define o nome o arquivo de protocolo gravado na pasta temporaria
					//$nomeprotocolo = $this->nfeTools->temDir.$chavenfe.LITERALPROTOCOLO;
					if($this->versaoapi != "5.0.0"){
						$pathNfe = $this->nfeTools->aConfig["pathNFeFiles"];
						$subPasta = "temporarias";
						if(!is_object($this->notacomplemento)){
							//$anoMes = convert_date($this->notafiscal->getdtemissao(), "Y-m-d","Ym");
							$anoMes = date("Ym");
							//$anoMes = convert_date(substr($datarecebimento, 0,10), "Y-m-d", "Ym");
						}else{
							//$anoMes = convert_date($this->notacomplemento->getdtemissao(), "Y-m-d","Ym");
							$anoMes = date("Ym");
							//$anoMes = convert_date(substr($datarecebimento, 0,10), "Y-m-d", "Ym");
						}
						$nomeprotocolo = NFePHP\Common\Files\FilesFolders::getFilePath($this->ambiente, $pathNfe, $subPasta).DS.$anoMes.DS."{$recibonfe}".LITERALPROTOCOLORECIBO;
						if(!file_exists($nomeprotocolo)){
							$nomeprotocolo = NFePHP\Common\Files\FilesFolders::getFilePath($this->ambiente, $pathNfe, $subPasta).DS.$anoMes.DS."{$chavenfe}".LITERALPROTOCOLOCHAVE;
						}
					}
					//verifica se o arquivo de protocolo existe na pasta temporÃ¡ria
					//file_put_contents("../temp/temp.txt", $nomexml."\n\r".$nomeprotocolo);
					if(file_exists($nomeprotocolo)){
						//adiciona o protocolo ao xml da NF-e
						switch($this->versaoapi){
							case "4.0.0":
								$xmlnfe = $this->nfeTools->addProtocolo($nomexml, $nomeprotocolo, TRUE);
								break;
							case "5.0.0":
								//$xmlnfe = \NFePHP\NFe\Complements::toAuthorize($xmlnfe, $aRespSefaz);
								$xmlnfe = \NFePHP\NFe\Factories\Protocol::add($xmlnfe, $aRespSefaz);
								break;
						}

						if($xmlnfe){
							//grava o xml da NF-e na pasta de log do emitente
							$bytes = file_put_contents($nomexml, $xmlnfe);
							//se gravado o arquivo na pasta log, gravar tambem nas pasta definitiva
							if($bytes > 0){
								//checa se NF-e foi autorizada
								if(in_array($cstat, array("100", "150"))){
									//monta o caminho da pasta de NF-e do emitente
									$nomexml = $this->pathnfe.removeformat($this->estabelecimento->getcpfcnpj()).DS;
									//checa se  pasta de NF-e do emitente existe
									if(!is_dir(($nomexml))){
										mkdir($nomexml, 0777);
									}
									//adiciona ao path o ano e mes da NF-e
									//$nomexml .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
									$nomexml .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
									//checa se existe
									if(!is_dir(($nomexml))){
										mkdir($nomexml, 0777);
									}
								}else{ // ou denegada
									//se a nota foi denegada, monta o caminho para a pasta de notas denegada do emitente
									$nomexml = $this->pathden.removeformat($this->estabelecimento->getcpfcnpj()).DS;
									//checa se  pasta de NF-e denegada do emitente existe
									if(!is_dir(($nomexml))){
										mkdir($nomexml, 0777);
									}
									//adiciona ao path o ano e mes da NF-e
									//$nomexml .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
									$nomexml .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
									//checa se existe
									if(!is_dir(($nomexml))){
										mkdir($nomexml, 0777);
									}
								}
								$nomexml .= $chavenfe.LITERALNFE;
								$bytes = file_put_contents($nomexml, $xmlnfe);
							}else{
								$_SESSION["ERROR"] = "N&atilde;o foi possivel gravar o xml na pasta de log de NF-e";
								return FALSE;
							}
						}else{
							$_SESSION["ERROR"] = "N&atilde; foi possivel adiconar o protocolo da NF-e<br>".$this->nfeTools->errMsg;
							return FALSE;
						}
					}else{
						if($operacao == TRANSMITIRNFE){
							$_SESSION["ERROR"] .= "N&atilde;o foi encontrado o protocolo da NF-e. Fa&ccedil;a uma consulta da NF-e";
						}else{
							$_SESSION["ERROR"] .= "N&atilde;o foi encontrado o protocolo da NF-e. Fa&ccedil;a uma consulta da NF-e novamente";
						}
						return FALSE;
					}
					/*
					  }else{
					  $_SESSION["ERROR"] .= "N&atilde;o foi encontrado o protocolo da NF-e. Fa&ccedil;a a transmiss&atilde;o da NF-e";
					  return FALSE;
					  }
					 *
					 */
				}elseif(in_array($cstat, array("101", "151", "155"))){ //se for cancelamento precisa mover o arquivo da NF-e para a pasta de canceladas e gravar tambem o protocolo de cancelamento na mesma pasta
					//monta o path completo com nome do xml que foi autorizado anteriormente
					$nomexml = $this->pathnfe.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date($this->notafiscal->getdtemissao(), "Y-m-d", "Ym").DS;
					$nomexml .= $chavenfe.LITERALNFE;
					//checa se o xml existe
					if(is_file($nomexml)){
						//carrega o xml
						$xmlnfe = file_get_contents($nomexml);
						//se carregado
						if(strlen($xmlnfe) > 0){
							//monta o path de NF-e cancelada do emitente
							$nomexmlcanc = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS;
							//se nÃ£o existir cria
							if(!is_dir(($nomexmlcanc))){
								mkdir($nomexmlcanc, 0777);
							}
							//complementa o path com o ano e mes do da emissao da NF-e
							//$nomexmlcanc .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
							$nomexmlcanc .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
							//se nÃ£o existir cria
							if(!is_dir(($nomexmlcanc))){
								mkdir($nomexmlcanc, 0777);
							}
							//adicona o nome do xml cancelado no sefaz
							$nomexmlcanc .= $chavenfe.LITERALNFECANCELADA;
							//grava o xml da NF-e cancelada na pasta de xml cancelado do emitente
							$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
							//apaga do xml de NF-e autorizadas o xml cancelado
							unlink($nomexml);
						}
					}else{ //se nÃ£o exitir na pasta de NF-e autorizadao, pega o xml da pasta log
						//monta p path de log do emitente
						$nomexmlcanc = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
						//adicona o nome do xml ao path
						$nomexmlcanc .= $chavenfe.LITERALNFE;
						if(!file_exists($nomexmlcanc)){
							$nomexmlcanc = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
							//adicona o nome do xml ao path
							$nomexmlcanc .= $chavenfe.LITERALNFECANCELADA;
						}
						//carrega o xml da nf-e da pasta log
						$xmlnfe = file_get_contents($nomexmlcanc);
						if(strlen($xmlnfe) > 0){
							//comeca a montar o path de cancelamentos emitente
							$nomexmlcanc = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS;
							//checa se existe
							if(!is_dir(($nomexmlcanc))){
								mkdir($nomexmlcanc, 0777);
							}
							//adiciona o mes da enissÃ£o da NF-e
							//$nomexmlcanc .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
							$nomexmlcanc .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
							//checa se existe
							if(!is_dir(($nomexmlcanc))){
								mkdir($nomexmlcanc, 0777);
							}
							//adicona o nome do xml cancelado
							$nomexmlcanc .= $chavenfe.LITERALNFECANCELADA;
							//grava o xml cancelado na pasta de cacelados
							$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
						}
					}
					//monta o nome do arquivo de protocolo de cancelamento
					//$nomeprotcanc = $this->nfeTools->canDir.$chavenfe.LITERALCANCELAMENTO;
					switch($this->versaoapi){
						case "4.0.0":
							$pathNfe = $this->nfeTools->aConfig["pathNFeFiles"];
							$subPasta = "canceladas";
							if(!is_object($this->notacomplemento)){
								$anoMes = date("Ym");
								//$anoMes = convert_date(substr($datarecebimento, 0,10), "Y-m-d", "Ym");
							}else{
								//$anoMes = convert_date($this->notacomplemento->getdtemissao(), "Y-m-d","Ym");
								$anoMes = date("Ym");
								//$anoMes = convert_date(substr($datarecebimento, 0,10), "Y-m-d", "Ym");
							}
							$nomeprotcancori = NFePHP\Common\Files\FilesFolders::getFilePath($this->ambiente, $pathNfe, $subPasta).DS.$anoMes.DS."{$chavenfe}".LITERALCANCELAMENTO;

							$nomeprotcanc = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS;

							if(!is_dir(($nomeprotcanc))){
								mkdir($nomeprotcanc, 0777);
							}
							//adicona o ano eo mes da NF-e
							//$nomeprotcanc .= convert_date($this->notafiscal->getdtemissao(),"Y-m-d", "Ym").DS;
							$nomeprotcanc .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
							//checa se existe
							if(!is_dir(($nomeprotcanc))){
								mkdir($nomeprotcanc, 0777);
							}
							$nomeprotcanc .= $chavenfe.LITERALCANCELAMENTO;
							break;
						case "5.0.0":
							$nomeprotocolo = $this->pathprotocolos.removeformat($this->estabelecimento->getcpfcnpj()).DS;
							if(!is_object($this->notacomplemento) ? strlen($this->notafiscal->getdatacancelamento()) : strlen($this->notacomplemento->getdatacancelamento()) > 0){
								$nomeprotocolo .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdatacancelamento() : $this->notacomplemento->getdatacancelamento()), "Y-m-d", "Ym").DS;
							}else{
								$anoMes = convert_date(substr($datarecebimento, 0, 10), "Y-m-d", "Ym");
								//$nomeprotocolo .= date("Ym").DS;
								$nomeprotocolo .= $anoMes.DS;
							}
							$nomeprotocolo .= $chavenfe.LITERALCANCELAMENTO;

							$nomeprotcancori = $nomeprotocolo;
							$nomeprotcanc = $nomeprotocolo;
							break;
					}

					//checa se o arquivo existe
					if(is_file($nomeprotcancori)){
						//carrega o xml de cancelamento
						$xmlnfe = file_get_contents($nomeprotcancori);
						if(strlen($xmlnfe) > 0){
							//monta o path de cancelamentos do emitente
							switch($this->versaoapi){
								case "4.0.0":
									$bytes = file_put_contents($nomeprotcanc, $xmlnfe);
									$xmlnfe = $this->nfeTools->addCancelamento($nomexmlcanc, $nomeprotcancori, TRUE);
									$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
									if($bytes > 0){
										$nomexmlcanc = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
										$nomexmlcanc .= $chavenfe.LITERALNFE;
										$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
									}
									break;
								case "5.0.0":
									$xmlcanc = file_get_contents($nomexmlcanc);
									//$xmlnfe = \NFePHP\NFe\Factories\Protocol::add($xmlcanc, $aRespSefaz,"cancNFe");
									$xmlnfe = \NFePHP\NFe\Complements::cancelRegister($xmlcanc, $aRespSefaz);
									$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
									$nomexmlnfe = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
									$nomexmlnfe .= $chavenfe.LITERALNFE;
									$bytes = file_put_contents($nomexmlnfe, $xmlnfe);
									break;
							}
						}
					}else{
						if(!empty($aRespSefaz)){
							file_put_contents("../temp/protcanc.txt", $nomexmlcanc."\r\n".$nomeprotcanc);
							switch($this->versaoapi){
								case "4.0.0":
									$bytes = file_put_contents($nomeprotcanc, $xmlnfe);
									if($bytes){
										$xmlnfe = $this->nfeTools->addCancelamento($nomexmlcanc, $nomeprotcanc, TRUE);
										$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
										if($bytes > 0){
											$nomexmlcanc = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
											$nomexmlcanc .= $chavenfe.LITERALNFE;
											$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
										}
									}
									break;
								case "5.0.0":
									$xmlcanc = file_get_contents($nomexmlcanc);
									//$xmlnfe = \NFePHP\NFe\Factories\Protocol::add($xmlcanc, $aRespSefaz, "cancNFe");
									$xmlnfe = \NFePHP\NFe\Complements::cancelRegister($xmlcanc, $aRespSefaz);
									$bytes = file_put_contents($nomexmlcanc, $xmlnfe);
									$nomexmlnfe = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
									$nomexmlnfe .= $chavenfe.LITERALNFE;
									$bytes = file_put_contents($nomexmlnfe, $xmlnfe);
									break;
							}
						}
					}
				}

				if(in_array($cstat, array("100", "101", "110", "150", "151", "155", "301", "302"))){
					if(!is_object($this->notacomplemento)){
						$this->notafiscal->setcodigostatus($cstat);
						$this->notafiscal->setxmotivo($xmotivo);
						$this->notafiscal->setchavenfe($chavenfe);
					}else{
						$this->notacomplemento->setcodigostatus($cstat);
						$this->notacomplemento->setxmotivo($xmotivo);
						$this->notacomplemento->setchavenfe($chavenfe);
					}
					if(in_array($cstat, array("100", "150", "110", "301", "302"))){
						if(!is_object($this->notacomplemento)){
							if(in_array($cstat, array("110", "301", "302"))){
								$this->notafiscal->setstatus("D");
							}else{
								$this->notafiscal->setstatus("A");
							}
							$this->notafiscal->setxmlprotocoloautodeneg($aRespSefaz);
							$this->notafiscal->setprotocolonfe($nProt);
							$this->notafiscal->setdataautorizacao($datarecebimento);
							$this->notafiscal->setxmlnfe($xmlnfe);
							$this->notafiscal->setimpresso("S");
						}else{
							if(in_array($cstat, array("110", "301", "302"))){
								$this->notacomplemento->setstatus("D");
							}else{
								$this->notacomplemento->setstatus("A");
							}
							$this->notacomplemento->setxmlprotocoloautodeneg($aRespSefaz);
							$this->notacomplemento->setprotocolonfe($nProt);
							$this->notacomplemento->setdataautorizacao($datarecebimento);
							$this->notacomplemento->setxmlnfe($xmlnfe);
						}
					}elseif(in_array($cstat, array("101", "151", "155"))){
						if(empty($xmlsefaz) && !empty($aRespSefaz)){
							$xmlsefaz = $aRespSefaz;
						}
						if(!is_object($this->notacomplemento)){
							$this->notafiscal->setstatus("C");
							$this->notafiscal->setsequenciaevento("1");
							$this->notafiscal->settipoevento("110111");
							$this->notafiscal->setprotocolocanc($nProt);
							$this->notafiscal->setdatacancelamento($datarecebimento);
							//$this->notafiscal->setxmlevento($xmlsefaz);
							$this->notafiscal->setxmlprotocolocanc($aRespSefaz);
							$this->notafiscal->setxmlnfe($xmlnfe);
						}else{
							$this->notacomplemento->setstatus("C");
							$this->notacomplemento->setprotocolocanc($nProt);
							$this->notacomplemento->setdatacancelamento($datarecebimento);
							$this->notacomplemento->setxmlprotocolocanc($aRespSefaz);
							$this->notacomplemento->setxmlnfe($xmlnfe);
						}
					}

					if(!is_object($this->notacomplemento) ? !$this->notafiscal->save() : !$this->notacomplemento->save()){
						//$this->con->rollback();
						$_SESSION["ERROR"] .= $arr_retorno["error"];
						return FALSE;
					}else{
						if($operacao == TRANSMITIRNFE){
							$_SESSION["ENVIARNFE"] .= "NF-e transmitida com sucesso ao SEFAZ"."<br>".$cstat." - ".$xmotivo;
						}elseif($operacao == CONSULTARNFE){
							$_SESSION["CONSULTARNFE"] .= "Consulta da NF-e realizada com sucesso no SEFAZ"."<br>".$cstat." - ".$xmotivo;
						}elseif($operacao == CANCELARNFE){
							$_SESSION["CANCELARNFE"] .= "Cancelamento da NF-e efetuado com sucesso no SEFAZ"."<br>".$cstat." - ".$xmotivo;
						}
						return TRUE;
					}
				}else{
					if($operacao == TRANSMITIRNFE){
						$_SESSION["ENVIARNFE"] .= "NF-e transmitida com sucesso ao SEFAZ"."<br>".$cstat." - ".$xmotivo;
					}elseif($operacao == CONSULTARNFE){
						$_SESSION["CONSULTARNFE"] .= "Consulta da NF-e realizada com sucesso no SEFAZ"."<br>".$cstat." - ".$xmotivo;
					}elseif($operacao == CANCELARNFE){
						$_SESSION["CANCELARNFE"] .= "Cancelamento da NF-e efetuado com sucesso no SEFAZ"."<br>".$cstat." - ".$xmotivo;
					}
				}
				//se houve rejeiÃ§Ã£o, excluir o xml da pasta log do emitente
				//monta o path da pasta log do emitente
				$nomexml = $this->pathlog.removeformat($this->estabelecimento->getcpfcnpj()).DS;
				//adicona o nome do xml ao path
				$nomexml .= $chavenfe.LITERALNFE;
				//checa se o arquivo existe

				/* if(is_file($nomexml)){
				  unlink($nomexml);
				  }
				 *
				 */
				return TRUE;
			}else{
				$_SESSION["ENVIARNFE"] .= "Consulta realizada com sucesso ao SEFAZ"."<br>".$arr_retorno["cStat"]." - ".$arr_retorno["xMotivo"];
				return TRUE;
			}
		}else{
			$_SESSION["ERROR"] .= $arr_retorno["error"];
			return FALSE;
		}
	}

	/*
	 * Metodo responsavel por executar os metodos de consulta da NF-e no SEFAZ
	 * se existe um nuemro de recibo, consulta pelo recibo, se nÃ£o, consulta pela chave da NF-e
	 */

	function consultarnfesefaz($recibonfe = "", $chavenfe = "", &$arr_retorno = array(), &$resposta = ""){
		if(empty($recibonfe) && empty($chavenfe)){
			$_SESSION["ERROR"] = "NÃ£o foi informado um recibo ou uma chave de NF-e para consulta.";
			return FALSE;
		}
		if(!empty($recibonfe)){
			$chavenfe = "";
			if($aResp = $this->nfeTools->sefazConsultaRecibo($recibonfe, $this->ambiente, $arr_retorno)){
				$arr_retorno["sucesso"] = "Consulta da NF-e realizada com sucesso";
				if($this->versaoapi == "5.0.0"){
					$arr_retorno["bStat"] = TRUE;
				}
				$resposta = $aResp;
				return TRUE;
			}else{
				$arr_retorno["error"] = "Houve erro na consulta da NF-e no SEFAZ !! <br>Erro: ".$this->nfeTools->errMsg;
				return FALSE;
			}
		}
		if(!empty($chavenfe)){
			if($aResp = $this->nfeTools->sefazConsultaChave($chavenfe, $this->ambiente, $arr_retorno)){
				$arr_retorno["sucesso"] = "Consulta da NF-e realizada com sucesso";
				if($this->versaoapi == "5.0.0"){
					$arr_retorno["bStat"] = TRUE;
				}
				$resposta = $aResp;
				return TRUE;
			}else{
				$arr_retorno["error"] = "Houve erro na consulta da NF-e no SEFAZ !! <br>Erro: ".$this->nfeTools->errMsg;
				return FALSE;
			}
		}
	}

	function emitirccenfephp($justificativa, $nSeq){
		$_SESSION["ERROR"] = "";
		$_SESSION["CCE"] = "";
		$chavenfe = $this->notafiscal->getchavenfe();
		$xCorrecao = $justificativa;
		//$nSeq = $this->notafiscal->getsequenciaevento() + 1;
		$tpAmb = $this->ambiente;

		switch($this->versaoapi){
			case "4.0.0":
				$this->nfeTools->sefazCCe($chavenfe, $tpAmb, $xCorrecao, $nSeq, $arr_retorno);
				break;
			case "5.0.0":
				$xmlcce = $this->nfeTools->sefazCCe($chavenfe, $xCorrecao, $nSeq);
				$nomexmlcce = $this->pathcce.removeformat($this->estabelecimento->getcpfcnpj());
				$std_xmlcce = new \NFePHP\NFe\Common\Standardize($xmlcce);
				if(!is_dir(($nomexmlcce))){
					mkdir($nomexmlcce, 0777);
				}
				$arr_retorno = $std_xmlcce->toArray();
				$arr_retorno["bStat"] = TRUE;
				break;
		}

		if($arr_retorno["bStat"]){
			$cstat = $arr_retorno["cStat"];
			$xmotivo = $arr_retorno["xMotivo"];
			if($cstat == "128"){
				switch($this->versaoapi){
					case "4.0.0":
						$arr_retorno = $arr_retorno["evento"][0];
						break;
					case "5.0.0":
						$arr_retorno = $arr_retorno["retEvento"]["infEvento"];
						break;
				}
				$cstat = $arr_retorno["cStat"];
				$xmotivo = $arr_retorno["xMotivo"];
				$tipoevento = $arr_retorno["tpEvento"];
				$nprot = $arr_retorno["nProt"];
				$datarec = explode("T", $arr_retorno["dhRegEvento"]);
				$datarec = $datarec[0];
				$nSeq = $arr_retorno["nSeqEvento"];
				if($cstat == "573"){
					$nSeq++;
					return $this->emitirccenfephp($justificativa, $nSeq);
				}
				if($cstat == "135"){
					if($this->versaoapi == "5.0.0"){
						$xmlnfe = \NFePHP\NFe\Complements::addEnvEventoProtocol($this->nfeTools->lastRequest, $xmlcce);
					}
					//$nomexmlcce = $this->nfeTools->cccDir.$chavenfe."-".$nSeq.LITERALCCE;
					if(!is_object($this->notacomplemento)){
						$anoMes = convert_date($datarec, "Y-m-d", "Ym");
					}else{
						$anoMes = convert_date($datarec, "Y-m-d", "Ym");
					}
					switch($this->versaoapi){
						case "4.0.0":
							$nomexmlcce = $this->nfeTools->aConfig["pathNFeFiles"];
							$subPasta = "cartacorrecao";
							$nomexmlcce = NFePHP\Common\Files\FilesFolders::getFilePath($this->ambiente, $nomexmlcce, $subPasta).DS.$anoMes.DS."{$chavenfe}".LITERALCCE;
							break;
						case "5.0.0":
							$nomexmlcce = $this->pathprotocolos.removeformat($this->estabelecimento->getcpfcnpj()).DS;
							if(!is_dir(($nomexmlcce))){
								mkdir($nomexmlcce, 0777);
							}
							$nomexmlcce .= convert_date($datarec, "Y-m-d", "Ym").DS;
							if(!is_dir(($nomexmlcce))){
								mkdir($nomexmlcce, 0777);
							}
							$nomexmlcce .= $chavenfe.LITERALCCE;
							file_put_contents($nomexmlcce, $xmlcce);
							break;
					}
					if(is_file($nomexmlcce) || $this->versaoapi == "5.0.0"){
						if($this->versaoapi == "4.0.0"){
							$xmlnfe = file_get_contents($nomexmlcce);
						}
						//monta o path das carta de correÃ§Ãµes do emitente
						$nomexmlcce = $this->pathcce.removeformat($this->estabelecimento->getcpfcnpj()).DS;
						//checa se existe
						if(!is_dir(($nomexmlcce))){
							mkdir($nomexmlcce, 0777);
						}
						//adiciona o mes da enissÃ£o da NF-e
						$nomexmlcce .= convert_date($datarec, "Y-m-d", "Ym").DS;
						//checa se existe
						if(!is_dir(($nomexmlcce))){
							mkdir($nomexmlcce, 0777);
						}
						//adicona o nome do xml cancelado
						//$nomexmlcce .= $chavenfe."-".$nSeq.LITERALCCE;
						$nomexmlcce .= $chavenfe.LITERALCCE;
						//grava a carta de correÃ§Ã£o no path de de CC-e do emitente
						file_put_contents($nomexmlcce, $xmlnfe);
					}
					$this->notafiscal->setdatacce($datarec);
					$this->notafiscal->setprotocolocce($nprot);
					$this->notafiscal->setsequenciaevento($nSeq);
					$this->notafiscal->settipoevento($tipoevento);
					$this->notafiscal->setcce($justificativa);
					if(!$this->notafiscal->save()){
						//$this->con->roolback();
						$_SESSION["ERROR"] .= "NÃ£o foi possivel atualizar a CC-e no banco de dados. Fa&ccedil;a a transmiss&atilde;o da CC-e novamente.";
						return FALSE;
					}else{
						$_SESSION["CCE"] .= "Transmiss&atilde;o da CC-e efetuado com sucesso!<br>".$cstat."-".$xmotivo;
						return TRUE;
					}
				}else{
					$_SESSION["ERROR"] .= "CC-e transmitida ao SEFAZ com rejei&ccedil;&atilde;o!<br>".$cstat."-".$xmotivo;
					return FALSE;
				}
			}
		}else{
			$cstat = $arr_retorno["cStat"];
			$xmotivo = $arr_retorno["xMotivo"];
			if($cstat == "573"){
				$this->notafiscal->setsequenciaevento($nSeq);
				if(!$this->notafiscal->save()){
					//$this->con->roolback();
				}
			}
			$_SESSION["ERROR"] .= "Houve erro na transmiss&atilde;o da CC-e!<br>".$cstat."-".$xmotivo."<br>".$this->nfeTools->errMsg."<br>";
			return FALSE;
		}
	}

	function enviaremailnfphp($emailextra){
		unset($_SESSION["ERROR"]);
		unset($_SESSION["ENVIAREMAIL"]);

		switch($this->versaoapi){
			case "4.0.0":
				if(file_exists($this->aConfig)){
					$aConfig = NFePHP\Common\Files\FilesFolders::readFile($this->aConfig);
					$aConfig = (array) json_decode($aConfig);
				}else{
					$_SESSION["ERROR"] = "N&atilde;o encontrado arquivo de configuraÃ§Ãµes para envio do email";
					return FALSE;
				}
				break;
			case "5.0.0":
				$this->carregaconfiguracoes();
				$aConfig = (array) json_decode($this->aConfig);
				break;
		}

		$modeloemail = objectbytable("modeloemail", $this->estabelecimento->getcodmodeloemail(), $this->con);
		/* $paramfiscal = objectbytable("paramfiscal",$this->notafiscal->getcodestabelec(),$this->con); */
		switch($this->notafiscal->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidaderes(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpaisres(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", "01058", $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpais(), $this->con);
				break;
			default:
				$_SESSION["ERROR"] = "Tipo de parceiro (".$this->notafiscal->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
				return FALSE;
		}

		$numnotafis = (!is_object($this->notacomplemento) ? $this->notafiscal->getnumnotafis() : $this->notacomplemento->getnumnotafis());
		$arquivo = "";
		$nomepdf = "";
		try{
			$chavenfe = (!is_object($this->notacomplemento) ? $this->notafiscal->getchavenfe() : $this->notacomplemento->getchavenfe());
			if(strlen($emailextra) == 0){
				if($this->notafiscal->gettipoparceiro() != "C" || strlen(trim($parceiro->getemailnfe())) == 0){
					$destinatario = $parceiro->getemail();
				}else{
					$destinatario = $parceiro->getemailnfe();
				}
			}
			//$destinatario = $parceiro->getemail();
			$arr_destinatario = array();
			$arr_anexos = array();
			$arr_destinatario_aux = explode(";", $destinatario);
			foreach($arr_destinatario_aux as $destinatario){
				$destinatario = trim($destinatario);
				if(strlen($destinatario) > 0){
					$arr_destinatario[] = $destinatario;
				}
			}

			if(strlen($emailextra) > 0){
				$arr_destinatario_aux = explode(";", $emailextra);
				foreach($arr_destinatario_aux as $destinatario){
					$destinatario = trim($destinatario);
					if(strlen($destinatario) > 0){
						$arr_destinatario[] = $destinatario;
					}
				}
			}

			if($this->ambiente == "2"){
				$arr_destinatario = array();
				//$arr_destinatario[] = $modeloemail->getusuario();
				switch($this->versaoapi){
					case "4.0.0":
						$arr_destinatario[] = $aConfig["aMailConf"]->mailUser;
						break;
					case "5.0.0":
						$arr_destinatario[] = $aConfig["mailUser"];
						break;
				}
			}

			if(count($arr_destinatario) == 0){
				$_SESSION["ERROR"] = "Email n&atilde;o enviado. O cliente n&atilde;o possui um email informado";
				return FALSE;
			}
			if((!is_object($this->notacomplemento) && $this->notafiscal->getstatus() == "A") || (is_object($this->notacomplemento) && $this->notacomplemento->getstatus() == "A")){
				$nomexml = $this->pathnfe.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
				$nomexml .= $chavenfe.LITERALNFE;
			}else{
				$nomexml = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
				$nomexml .= $chavenfe.LITERALNFECANCELADA;
			}

			//verifica se existe o xml da NF-e

			if(is_file($nomexml)){
				//verifica se existe algum evento para a nf-e
				if(!is_object($this->notacomplemento)){
					if(strlen($this->notafiscal->gettipoevento()) > 0){
						//se for cancelamento
						if($this->notafiscal->gettipoevento() == "110111"){
							switch($this->versaoapi){
								case "4.0.0":
									$xmlevento = $this->pathcanc.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date($this->notafiscal->getdtemissao(), "Y-m-d", "Ym").DS;
									$xmlevento .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
									$xmlevento .= $chavenfe.LITERALCANCELAMENTO;
									break;
								case "5.0.0":
									$xmlevento = $this->pathprotocolos.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date($this->notafiscal->getdatacancelamento(), "Y-m-d", "Ym").DS;
									//$xmlevento .= convert_date((!is_object($this->notacomplemento) ? $this->notafiscal->getdtemissao() : $this->notacomplemento->getdtemissao()), "Y-m-d", "Ym").DS;
									$xmlevento .= $chavenfe.LITERALPROTOCOLOCHAVE;
									break;
							}
							//se for carta de correcao
						}else{
							$xmlevento = $this->pathcce.removeformat($this->estabelecimento->getcpfcnpj()).DS.convert_date($this->notafiscal->getdtemissao(), "Y-m-d", "Ym").DS;
							$xmlevento .= convert_date($this->notafiscal->getdatacce(), "Y-m-d", "Ym").DS;
							$xmlevento .= $chavenfe."-".$this->notafiscal->getsequenciaevento()."-".LITERALCCE;
							$pdfevento = $this->pathpdf.$chavenfe.LITERALPDF;
							if(!is_file($pdfevento)){
								$xlmlcce = file_get_contents($xmlevento);
								//$cce = new DacceNFePHP($xlmlcce, "P", "A4", $this->pathlogo, "F", $aEnd, "", "Times", 1);
								$cce = new $this->classDacce($xlmlcce, "P", "A4", $this->pathlogo, "F", $aEnd, "", "Times", 1);
								$pdf = $cce->printDACCE($this->pathpdf.$chavenfe."-".$seqcce."-".LITERALPDF, "F");
							}
						}
					}
				}
				$nomepdf = $this->pathpdf.$chavenfe.LITERALPDF;
				if(!is_file($nomepdf)){
					$xmlnfe = file_get_contents($nomexml);
					switch($this->versaoapi){
						case "4.0.0":
							$danfe = new $this->classDanfe($xmlnfe, "F", "A4", $this->pathlogo, "F", "../temp/pdfnfe/".$chavenfe.LITERALPDF);
							$id = $danfe->montaDANFE("P", "A4", "L", NFEPHP_SITUACAO_EXTERNA_NONE, FALSE, "", 2, 2, 2, array(""));
							$pdfnfe = $danfe->printDANFE($this->pathpdf.$id.LITERALPDF, "F");
							break;
						case "5.0.0":
							$tam_pdf = 0;
							$cont = 0;
							while($tam_pdf < 1000 || $cont < 10){
								$danfe = new $this->classDanfe($xmlnfe, "P", "A4", $this->pathlogo, "../temp/pdfnfe/".$chavenfe.LITERALPDF);
								$id = $danfe->montaDANFE("P", "A4", "L", $danfe::SIT_NONE, FALSE, "", 2, 2, 2, array(""));
								$danfe->printDocument($this->pathpdf.$id.LITERALPDF, "F");
								$tam_pdf = filesize($this->pathpdf.$id.LITERALPDF);
								$cont++;
								sleep(1);
								break;
							}
					}
				}

				if(is_file($pdfevento)){
					$arr_anexos[] = $pdfevento;
				}
				if(is_file($nomepdf)){
					$arr_anexos[] = $nomepdf;
				}
				if(is_file($xmlevento)){
					$arr_anexos[] = $xmlevento;
				}
				$arr_anexos[] = $nomexml;
				$email = new Email($this->con);
				$email->setanexo($arr_anexos);
				//$email->setcorpo($modeloemail->getcorpo());
				$email->setcorpo("Segue no anexo arquivo XML e PDF referente a NF-e {$numnotafis} emitida");
				$email->setdestinatario($arr_destinatario);

				switch($this->versaoapi){
					case "4.0.0":
						$email->sethost($aConfig["aMailConf"]->mailSmtp);
						$email->setporta($aConfig["aMailConf"]->mailPort);
						$email->settipoautenticacao($aConfig["aMailConf"]->mailProtocol);
						$email->setsenha($aConfig["aMailConf"]->mailPass);
						$email->setusuario($aConfig["aMailConf"]->mailUser);
						break;
					case "5.0.0":
						$email->sethost($aConfig["mailSmtp"]);
						$email->setporta($aConfig["mailPort"]);
						$email->settipoautenticacao($aConfig["mailProtocol"]);
						$email->setsenha($aConfig["mailPass"]);
						$email->setusuario($aConfig["mailUser"]);
						break;
				}
				$email->settitulo("Referente a NF-e {$numnotafis} emitida");
				if($email->enviar()){
					$dest = implode(";", $arr_destinatario);
					$_SESSION["EMAILNFE"] = "E-mail enviado com sucesso!<br><br>Remetente: {$aConfig["mailUser"]}<br>Destinatário: {$dest}";
					return TRUE;
				}else{
					return FALSE;
				}
			}else{
				$_SESSION["ERROR"] = "N&atilde;o foi possivel encontrar o arquivo xml da NF-e";
				return FALSE;
			}
		}catch(Exception $ex){
			$_SESSION["ERROR"] = "Houve erro ao tentar enviar o email ao destinat&aacute;rio";
			return FALSE;
		}
	}

	/**
	 *
	 * @param type $path
	 * @param type $files
	 * @param type $deleleOriginal
	 */
	function createZip($path = 'arquivo.zip', $files = array(), $deleleOriginal = false){
		/**
		 * Cria o arquivo .zip
		 */
		$zip = new ZipArchive;
		$zip->open($path, ZipArchive::CREATE);

		/**
		 * Checa se o array nÃ£o estÃ¡ vazio e adiciona os arquivos
		 */
		if(!empty($files)){
			/**
			 * Loop do(s) arquivo(s) enviado(s)
			 */
			foreach($files as $file){
				/**
				 * Adiciona os arquivos ao zip criado
				 */
				$zip->addFile($file, basename($file));

				/**
				 * Verifica se $deleleOriginal estÃ¡ setada como true,
				 * se sim, apaga os arquivos
				 */
				if($deleleOriginal === true){
					/**
					 * Apaga o arquivo
					 */
					unlink($file);

					/**
					 * Seta o nome do diretÃ³rio
					 */
					$dirname = dirname($file);
				}
			}

			/**
			 * Verifica se $deleleOriginal estÃ¡ setada como true,
			 * se sim, apaga a pasta dos arquivos
			 */
			if($deleleOriginal === true && !empty($dirname)){
				rmdir($dirname);
			}
		}

		/**
		 * Fecha o arquivo zip
		 */
		$zip->close();
	}

	function weekOfMonth($date){
		// estract date parts
		list($y, $m, $d) = explode('-', date('Y-m-d', strtotime($date)));

		// current week, min 1
		$w = 1;

		// for each day since the start of the month
		for($i = 1; $i <= $d; ++$i){
			// if that day was a sunday and is not the first day of month
			if($i > 1 && date('w', strtotime("$y-$m-$i")) == 0){
				// increment current week
				++$w;
			}
		}

		// now return
		return $w;
	}

	/*
	  function downloadlistanfesefaz($ultimo_nsu = 0){
	  try{
	  switch ($this->versaoapi){
	  case "4.0.0":
	  $arr_retorno = array();
	  $retorno = $this->nfeTools->sefazDistDFe("AN", $this->ambiente, removeformat($this->estabelecimento->getcpfcnpj()), $ultimo_nsu, 0, $arr_retorno);
	  break;
	  case "5.0.0":
	  $retorno = $this->nfeTools->sefazDistDFe($ultimo_nsu);

	  $std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
	  $arr_retorno = $std_retorno->toArray();
	  unset($arr_retorno["loteDistDFeInt"]);
	  $aDocZip = Array();
	  if($this->formatarretornosefaz($retorno, $aDocZip)){
	  $arr_retorno["bStat"] = TRUE;
	  }else{
	  $arr_retorno["bStat"] = FALSE;
	  }
	  $arr_retorno["aDoc"] = $aDocZip;
	  break;
	  }

	  //$arr_retorno["bStat"] = TRUE;
	  }catch(Exception $ex){
	  $arr_retorno["ERRO"] = $ex->getMessage();
	  }
	  return $arr_retorno;
	  }

	  function formatarretornosefaz($retornoDist, &$aDocs = array()){
	  try{
	  $dom = DOMDocument::loadXML($retornoDist, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
	  $tagDist = $dom->getElementsByTagName("retDistDFeInt")->item(0);

	  if(!isset($tagDist)){
	  return FALSE;
	  }else{
	  $docs = $tagDist->getElementsByTagName('docZip');
	  foreach($docs as $doc){
	  $xml = gzdecode(base64_decode($doc->nodeValue));
	  $aDocs[] = array(
	  "NSU" => $doc->getAttribute("NSU"),
	  'schema' => $doc->getAttribute('schema'),
	  "doc" => $xml
	  );
	  }
	  }
	  }catch(Exception $ex){
	  return FALSE;
	  }
	  return TRUE;
	  }

	  function setestabelecimento($estabelecimento){
	  $this->estabelecimento = $estabelecimento;
	  $this->versaoapi = $this->estabelecimento->getversaoapi();
	  $this->ambiente = $this->estabelecimento->getambientenfe();
	  if(is_null($this->versaoapi) || strlen($this->versaoapi) == 0){
	  $this->versaoapi = "4.0.0";
	  }
	  }

	  function carregaconfiguracoes(){
	  switch($this->versaoapi){
	  case "4.0.0":
	  include_once "../lib/nfephp-4.0.0/bootstrap.php";
	  $this->classToolsNFe = "\NFePHP\NFe\ToolsNFe";
	  $this->classMakeNFe = "\NFePHP\NFe\MakeNFe";
	  $this->classDanfe = "\NFePHP\Extras\Danfe";
	  $this->classDacce = "\NFePHP\Extras\Dacce";
	  $filefolderconfig = "..".DS."lib".DS."nfephp-4.0.0".DS."config".DS.str_pad(removeformat($this->estabelecimento->getcpfcnpj()), 5, "0", STR_PAD_LEFT)."_config.json";
	  if(!is_file($filefolderconfig)){
	  throw new Exception("NÃ£o encontrado o arquivo de configuraÃ§Ãµes da NF-e! Favor entrar em contato com o suporte tÃ©cnico.");
	  }
	  $this->aConfig = $filefolderconfig;
	  if(!isset($this->nfeTools)){
	  $this->nfeTools = new $this->classToolsNFe($this->aConfig);
	  $this->nfeTools->setModelo("55");
	  }
	  if(!isset($this->nfe)){
	  $this->nfe = new \NFePHP\NFe\MakeNFe();
	  }
	  break;
	  case "5.0.0":
	  require_once("../lib/nfephp-5.0.0/bootstrap.php");
	  $this->classToolsNFe = "\NFePHP\NFe\Tools";
	  $this->classMakeNFe = "\NFePHP\NFe\Make";
	  $this->classDanfe = "\NFePHP\NFe\NFe\Danfe";
	  $this->classDacce = "\NFePHP\NFe\NFe\Dacce";

	  $this->aConfig = json_encode(array(
	  "atualizacao" => date("Y-m-d")." ".date("h:i:s")."-02:00",
	  "tpAmb" => $this->ambiente,
	  "razaosocial" => $this->estabelecimento->getrazaosocial(),
	  "cnpj" => removeformat($this->estabelecimento->getcpfcnpj()),
	  "siglaUF" => $this->estabelecimento->getuf(),
	  "schemes" => $this->estabelecimento->getnomeschema(),
	  "versao" => $this->versaonfe,
	  "mailSmtp" => $this->estabelecimento->getservidorsmtp(),
	  "mailPort" => $this->estabelecimento->getporta(),
	  "mailProtocol" => $this->estabelecimento->gettipoautenticacao(),
	  "mailPass" => $this->estabelecimento->getsenhaemail(),
	  "mailUser" => $this->estabelecimento->getusuarioemail()
	  ));
	  $this->certificado = \NFePHP\Common\Certificate::readPfx($this->carregacertificado(), $this->estabelecimento->getsenhachaveprivada());
	  if(!isset($this->nfeTools)){
	  $this->nfeTools = new $this->classToolsNFe($this->aConfig, $this->certificado);
	  $this->nfeTools->Model("55");
	  }
	  if(!isset($this->nfe)){
	  switch($this->versaonfe){
	  case "3.10":
	  $this->nfe = \NFePHP\NFe\Make::v310();
	  break;
	  case "4.00":
	  $this->nfe = \NFePHP\NFe\Make::v400();
	  break;
	  }
	  }
	  break;
	  }
	  }

	  function manifestonfesefaz($evento, $chavenfe, $xJust = ""){
	  switch($this->versaoapi){
	  case "4.0.0":
	  $arr_retorno = array();
	  $retorno = $this->nfeTools->sefazManifesta($chavenfe, $this->ambiente, $xJust, $evento, $arr_retorno);
	  break;
	  case "5.0.0":
	  $retorno = $this->nfeTools->sefazManifesta($chavenfe, $evento, $xJust);
	  $std_retorno = new \NFePHP\NFe\Common\Standardize($retorno);
	  $arr_retorno = $std_retorno->toArray();
	  $arr_retorno["bStat"] = TRUE;
	  break;
	  }
	  return $arr_retorno;
	  }
	 *
	 */
}