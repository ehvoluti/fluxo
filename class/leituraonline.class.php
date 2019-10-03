<?php

require_once("../class/coral.class.php");
require_once("../class/gzsistemas.class.php");
require_once("../class/siac.class.php");
require_once("../class/syspdv.class.php");
require_once("../class/pdvconfig.class.php");
require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");
require_once("../class/emporium.class.php");
require_once("../class/scanntech.class.php");
require_once("../class/zanthus.class.php");

class LeituraOnline{

	private $con;
	private $estabelecimento;
	private $dtmovto; // Formato: Y-m-d
	private $caixa;
	private $cupom;
	private $buscar_importado = FALSE;
	private $coral;
	private $gzsistemas;
	private $syspdv;
	private $emporium;
	private $siac;
	private $scanntech;
	private $zanthus;
	private $pdvvenda = array();
	private $pdvfinalizador = array();
	private $arr_arquivo = array();
	private $arr_arquivo_error = array();
	private $pdvvasilhame = array();
	private $pdvrecebepdv = array();

	function __construct($con = NULL){
		if(is_null($con)){
			$this->con = new Connection();
		}else{
			$this->con = $con;
		}
		$this->coral = new Coral();
		$this->gzsistemas = new GzSistemas();
		$this->syspdv = new SysPdv();
		$this->emporium = new Emporium();
		$this->siac = new Siac();
		$this->scanntech = new Scanntech();
		$this->zanthus = new Zanthus();
	}

	function arquivar(){
		// Verifica se e PDV Coral
		if($this->estabelecimento->getcodfrentecaixa() == "3"){
			$this->zanthus->marcarMovimentos();
		}elseif($this->estabelecimento->getcodfrentecaixa() == "2"){
			$this->coral->armazenar_arquivos();
		}else{			
			foreach($this->arr_arquivo as $arquivo){
				if(file_exists($arquivo["arquivo"])){
					$dir = dirname($arquivo["arquivo"])."/IMPORTADO/";
					if(!is_dir($dir)){
						mkdir($dir);
					}
					$dir .= str_replace("-", " ", $arquivo["data"])."/";
					if(!is_dir($dir)){
						mkdir($dir);
					}
					copy($arquivo["arquivo"], $dir.basename($arquivo["arquivo"]));
					unlink($arquivo["arquivo"]);
				}
			}

			foreach($this->arr_arquivo_error as $arquivo){
				if(file_exists($arquivo["arquivo"])){
					$dir = dirname($arquivo["arquivo"])."/IMPORTADO/";
					if(!is_dir($dir)){
						mkdir($dir);
					}
					$dir .= "ERROR/";
					if(!is_dir($dir)){
						mkdir($dir);
					}
					$dir .= str_replace("-", " ", $arquivo["data"])."/";
					if(!is_dir($dir)){
						mkdir($dir);
					}
					copy($arquivo["arquivo"], $dir.basename($arquivo["arquivo"]));
					unlink($arquivo["arquivo"]);
				}
			}
		}
	}

	function buscar_importado($bool){
		if(is_bool($bool)){
			$this->buscar_importado = $bool;
		}
	}

	function getpdvvenda(){
		return $this->pdvvenda;
	}

	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}

	function getpdvvasilhame(){
		return $this->pdvvasilhame;
	}

	function getpdvrecebepdv(){
		return $this->pdvrecebepdv;
	}

	function ler_arquivos(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
		$this->arr_arquivo = array();
		$dir_name = $this->estabelecimento->getdirleituraonline();
		$frentecaixa = objectbytable("frentecaixa", $this->estabelecimento->getcodfrentecaixa(), $this->con);
		switch($this->estabelecimento->getcodfrentecaixa()){
			case 1: // GZ Sistemas
			case 2: // Coral
			case 4: // SysPDV
			case 9: // Emporium
				if(!is_dir($dir_name)){
					$_SESSION["ERROR"] = "Diret&oacute;rio <b>".$dir_name."</b> n&atilde;o encontrado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
					return FALSE;
				}
				break;
			case 3: // Zanthus
				if($frentecaixa->getversao() != "3"){
					if(!is_dir($dir_name)){
						$_SESSION["ERROR"] = "Diret&oacute;rio <b>".$dir_name."</b> n&atilde;o encontrado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
						return FALSE;
					}
				}
				break;
			case 6: // SIAC
				if(!is_file($dir_name)){
					$_SESSION["ERROR"] = "Arquivo de configura&ccedil;&atilde;o <b>".$dir_name."</b> n&atilde;o encontrado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
					return FALSE;
				}
				break;
			case 10: // Scanntech

				break;
			default:
				$_SESSION["ERROR"] = "O PDV informado no estabelecimento n&atilde;o permite leitura de cupons de venda em tempo real.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
				return FALSE;
		}
		switch($this->estabelecimento->getcodfrentecaixa()){
			case "1": // GZ Sistemas
				$files = array();
				$dir = opendir($dir_name);
				$i = 0;
				while($file_name = readdir($dir)){
					if(strlen($file_name) == 12 && substr($file_name, 0, 2) == "LV"){
						$files[] = $file_name;
						if(++$i > 50 && $this->estabelecimento->getleituraonline() == "S"){
							break;
						}
					}
				}
				foreach($files as $file_name){
					$this->gzsistemas->arquivo_venda($dir_name.$file_name);
					$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->gzsistemas->getpdvfinalizador());

					// Captura a data dentro do arquivo
					$arr_linha = explode("\r\n", file_get_contents($dir_name.$file_name));
					$linha = $arr_linha[0];
					$registro = substr($linha, 15, 2); // Tipo de registro

					if($registro == "25"){										
						if(!is_dir($dir_name."IMPORTADO")){
							mkdir($dir_name."IMPORTADO");
						}
						if(!is_dir($dir_name."IMPORTADO/MAPARESUMO/")){
							mkdir($dir_name."IMPORTADO/MAPARESUMO/");
						}
						echo $dir_name.$file_name."<br><br>";	
						copy($dir_name.$file_name, $dir_name."IMPORTADO/MAPARESUMO/".$file_name);
						unlink($dir_name.$file_name);

						continue;
					}else{
						$data = substr($linha, 471, 8);
						$data = substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);						
					}					

					if(!is_null(value_date($data))){
						$this->arr_arquivo[] = array("arquivo" => $dir_name.$file_name, "data" => $data);
					}

					$arr_pdvvenda = $this->gzsistemas->getpdvvenda();
					if(sizeof($arr_pdvvenda) > 0){
						$this->pdvvenda[$dir_name.$file_name] = $arr_pdvvenda[0];
					}
				}
				break;
			case "2": // Coral
				if($this->buscar_importado){
					$dir_name_importado = $dir_name."IMPORTADO/".str_replace("-", " ", $this->dtmovto)."/";
					if(is_dir($dir_name_importado)){
						$dir_name = $dir_name_importado;
					}
				}
				$this->coral->diretorio_venda($dir_name, (!$this->buscar_importado), TRUE);
				$this->pdvvenda = array_merge($this->pdvvenda, $this->coral->getpdvvenda());
				$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->coral->getpdvfinalizador());
				break;
			case "3": // Zanthus
				if($frentecaixa->getversao() == "3"){
					$query = "select max(hrmovto) AS hrmovto, max(dtmovto) AS dtmovto from cupom WHERE codestabelec = {$this->estabelecimento->getcodestabelec()}";
					$res = $this->con->query($query);

					$row = $res->fetch();

					if(strlen($row["dtmovto"]) == 0){
						$dtultvenda = date("Y-m-d");
					}else{
						$dtultvenda = $row["dtmovto"];
					}

					if(strlen($row["hrmovto"]) == 0){
						$hrultvenda = "00:00:00";
					}else{
						$hrultvenda = $row["hrmovto"];
					}

					$this->zanthus->leituraVendas($dtultvenda , $hrultvenda);
				}else{
					if($this->buscar_importado){
						$dir_name_importado = $dir_name."IMPORTADO/".str_replace("-", " ", $this->dtmovto)."/";
						if(is_dir($dir_name_importado)){
							$dir_name = $dir_name_importado;
						}
					}
					$this->zanthus->importvendas($dir_name);
				}
				$this->pdvvenda = array_merge($this->pdvvenda, $this->zanthus->getpdvvenda());
				$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->zanthus->getpdvfinalizador());
				break;
			case "4": // SysPDV
				if($this->buscar_importado){
					$dir_name_importado = $dir_name."IMPORTADO/".str_replace("-", " ", $this->dtmovto)."/";
					if(is_dir($dir_name_importado)){
						$dir_name = $dir_name_importado;
					}
				}
				$dir = opendir($dir_name);
				$i = 0;
				while($file_name = readdir($dir)){
					//if(in_array(strlen($file_name), array(12, 15)) && in_array(substr($file_name, 0, 2), array("LG", "TP")) && (time() - filemtime($dir_name.$file_name)) > 20){
					if(in_array(substr($file_name, 0, 2), array("LG", "TP"))){

						// Tratamento para ler no maximo 50 arquivos
						if(!$this->buscar_importado && $i > 50){
							break;
						}

						$file_name = $dir_name.$file_name;
						$file = fopen($file_name, "r");
						$line = fgets($file, 4096);
						fclose($file);
						unset($data);
						switch(trim(substr($line, 0, 2))){
							case "":
								break;
							case "00":
								$data = substr($line, 6, 8);
								break;
							case "01":
							case "06":
								$data = substr($line, 35, 8);
								break;
							case "02":
							case "05":
							case "07":
							case "08":
							case "09":
							case "13":
								$data = substr($line, 19, 8);
								break;
							case "03":
								$data = substr($line, 30, 8);
								break;
							case "04":
								$data = substr($line, 9, 8);
								break;
							case "19":
							case "20":
								$data = substr($line, 22, 8);
								break;
							case "23":
								$data = substr($line, 16, 8);
								break;
							default :
								$data = str_repeat("0", 8);
								break;
						}
						if(strlen($data) > 0){
							$i++;

							if($this->syspdv->arquivo_venda($file_name)){
								$data = substr($data, 4, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
								$this->arr_arquivo[] = array("arquivo" => $file_name, "data" => $data);
								$arr = $this->syspdv->getpdvvenda();
								$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->syspdv->getpdvfinalizador());
								$this->pdvvasilhame = $this->syspdv->getpdvvasilhame();
								$this->pdvrecebepdv = $this->syspdv->getpdvrecebepdv();
								if(sizeof($arr) > 0){
									$pdvvenda = $arr[0];
									$this->pdvvenda[$file_name] = $pdvvenda;
								}
							}else{
								$data = substr($data, 4, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
								if(is_null(value_date($data))){
									$data = date("Y-m-d");
								}
								$this->arr_arquivo_error[] = array("arquivo" => $file_name, "data" => $data);
							}
						}
					}
				}
				break;
			case "6": // SIAC
				if(!$this->siac->importar_venda($this->estabelecimento->getdirleituraonline(), !$this->buscar_importado, $this->dtmovto, $this->caixa, $this->cupom)){
					return FALSE;
				}
				$this->pdvvenda = $this->siac->getpdvvenda();
				$this->pdvfinalizador = $this->siac->getpdvfinalizador();
				break;
			case "9": // Emporium
				if($this->buscar_importado){
					$dir_name_importado = $dir_name."IMPORTADO/".str_replace("-", " ", $this->dtmovto)."/";
					if(is_dir($dir_name_importado)){
						$dir_name = $dir_name_importado;
					}
				}

				$this->emporium->diretorio_venda($dir_name, (!$this->buscar_importado));
				$this->pdvvenda = array_merge($this->pdvvenda, $this->emporium->getpdvvenda());
				$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->emporium->getpdvfinalizador());

//				$this->emporium->devolucao($dir_name);
				break;
			case "10": // Scanntech
				$leituraonline = ($this->estabelecimento->getleituraonline() === "S");
				if($leituraonline){
					$pdvconfig = $this->scanntech->getpdvconfig();
					$res = $this->con->query("SELECT MAX(dtmovto) FROM cupom WHERE codestabelec = {$this->estabelecimento->getcodestabelec()}");
					$dtmovto = $res->fetchColumn();
					if(strlen($dtmovto) === 0){
						$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel identificar a data inicial para a leitura online de cupons. Efetue pelo menos uma leitura de vendas manual para indicar a data inicial para leitura online.";
						return FALSE;
					}
					while(compare_date($dtmovto, date("Y-m-d"), "Y-m-d", "<=")){
						$pdvconfig->setdtmovto($dtmovto);
						if(!$this->scanntech->importar_venda(TRUE)){
							return FALSE;
						}
						if(count($this->scanntech->getpdvvenda()) === 0){
							$dtmovto = date("Y-m-d", strtotime("+1 day", strtotime($dtmovto)));
							continue;
						}
						$this->pdvvenda = array_merge($this->pdvvenda, $this->scanntech->getpdvvenda());
						$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->scanntech->getpdvfinalizador());
						break;
					}

					$res = $this->con->query("SELECT MAX(dtmovto) FROM maparesumo WHERE codestabelec = {$this->estabelecimento->getcodestabelec()}");
					$dtmovto = $res->fetchColumn();
					if(strlen($dtmovto) === 0){
						$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel identificar a data inicial para a leitura online do mapa resumo. Efetue pelo menos uma leitura de vendas manual para indicar a data inicial para leitura online.";
						return FALSE;
					}
					$dtmovto = date("Y-m-d", strtotime("+1 day", strtotime($dtmovto)));
					if(compare_date($dtmovto, date("Y-m-d", strtotime("-1 day")), "Y-m-d", "<=")){
						$pdvconfig->setdtmovto($dtmovto);
						if(!$this->scanntech->importar_maparesumo()){
							return FALSE;
						}
					}
				}else{
					$this->scanntech->importar_venda(FALSE);
					$this->pdvvenda = array_merge($this->pdvvenda, $this->scanntech->getpdvvenda());
					$this->pdvfinalizador = array_merge($this->pdvfinalizador, $this->scanntech->getpdvfinalizador());
				}
				break;
		}
		return TRUE;
	}

	function setcaixa($caixa){
		$this->caixa = $caixa;
	}

	function setcupom($cupom){
		$this->cupom = $cupom;
	}

	function setdtmovto($dtmovto){
		$this->dtmovto = value_date($dtmovto);
	}

	function setpdvconfig($pdvconfig){
		$this->estabelecimento = $pdvconfig->getestabelecimento();

		$this->coral->setpdvconfig($pdvconfig);
		$this->gzsistemas->setpdvconfig($pdvconfig);
		$this->syspdv->setpdvconfig($pdvconfig);
		$this->siac->setpdvconfig($pdvconfig);
		$this->scanntech->setpdvconfig($pdvconfig);
		$this->zanthus->setpdvconfig($pdvconfig);
	}

}
