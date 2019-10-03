<?php

abstract class Sped{

	protected $con; // Conexao com o banco de dados
	protected $matriz; // Estabelecimento matriz
	protected $arr_estabelecimento; // Estabelecimentos emitentes das notas fiscais
	protected $datainicial; // Data inicial para geracao do arquivo (formato: Y-m-d)
	protected $datafinal; // Data final para geracao do arquivo (formato: Y-m-d)
	protected $datainventario; // Data do inventario
	protected $datareg0205; // Data do inventario
	protected $arquivo; // Arquivo aberto para criacao dos registros
	protected $arquivo_nome; // Nome do arquivo aberto para criacao dos registros
	protected $n_registro; // Totalizador de linhas por registro (array[registro] => n_registros)
	protected $progresso_t; // Total de progressos
	private $progresso_i; // Contador da barra de progresso
	protected $param_fiscal_spedenviarncm; // Parametro que verifica se deve enviar o NCM dos produtos
	protected $tipoescrituracao; //recebe o tipo da escrituracao "0" => Escrituracao Oroginal, "1" Escrituracao Retificadora
	protected $numerorecibo; //contem o numero do recebibo da excrituracao original caso o tipoescrituracao seja "1"

	function __construct($con){
		// Conexao com o banco de dados
		$this->con = $con;

		// Parametro que define se deve enviar o NCM dos produtos no arquivo
		$this->param_fiscal_spedenviarncm = param("FISCAL", "SPEDENVIARNCM", $this->con);

		// Limpa a variavel que armazena as informacoes que serao utilizadas para imprimir o relatorio final
		unset($_SESSION["SPED"]);
	}

	/*	 * ***************************
	  F U N C O E S   P U B L I C A S
	 * *************************** */

	// Armazena a data inicial de geracao (armazenada no formato Y-m-d)
	public function setdatainicial($data){
		if(strlen($data) == 10){
			$data = $this->valor_data($data);
			$data = substr($data, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
			$this->datainicial = $data;
		}
	}

	// Armazena a data final de geracao (armazenada no formato Y-m-d)
	public function setdatafinal($data){
		if(strlen($data) == 10){
			$data = $this->valor_data($data);
			$data = substr($data, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
			$this->datafinal = $data;
		}
	}

	// Armazena a data da geracao do inventario
	public function setinventario($datainventario){
		$this->datainventario = value_date($datainventario);
	}

	public function setdatareg0205($datareg){
		$this->datareg0205 = value_date($datareg);
	}

	// Armazena o estabelecimento emitente do arquivo
	public function setestabelecimento($arr_estabelecimento){
		$this->arr_estabelecimento = array();
		if(!is_array($arr_estabelecimento)){
			$arr_estabelecimento = array($arr_estabelecimento);
		}
		foreach($arr_estabelecimento as $estabelecimento){
			$estabelecimentofiscal = objectbytable("estabelecimento",$estabelecimento->getcodestabelecfiscal(),  $this->con);
			$this->arr_estabelecimento[$estabelecimento->getcodestabelecfiscal()] = $estabelecimentofiscal ;
		}

		unset($this->matriz);
		foreach($this->arr_estabelecimento as $estabelecimento){
			if(!isset($this->matriz) || substr($estabelecimento->getcpfcnpj(), -7, 4) == "0001"){
				$this->matriz = $estabelecimento;
			}
		}
	}

	public function settipoescrituracao($tipo_escrituracao){
		$this->tipoescrituracao = $tipo_escrituracao;
	}

	public function setnumerorecibo($numero_recibo){
		$this->numerorecibo = $numero_recibo;
	}

	/*	 * *******************************
	  F U N C O E S   P R O T E G I D A S
	 * ******************************* */

	// Retorna o CCS (codigo de contribuicao social) do PIS/Cofins do item informado
	protected function ccspiscofins($item){
		$produto = $this->arr_produto[$item["codproduto"]];
		switch($this->tabela_item($item)){
			case "itcupom":
				$piscofins = $this->arr_piscofins[$produto["codpiscofinssai"]];
				break;
			case "itnotafiscal":
				$notafiscal = $this->arr_notafiscal[$item["idnotafiscal"]];
				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				if($operacaonota["tipo"] == "E"){
					$piscofins = $this->arr_piscofins[$produto["codpiscofinsent"]];
				}else{
					$piscofins = $this->arr_piscofins[$produto["codpiscofinssai"]];
				}
				break;
			case "itnotafiscalservico":
				$piscofins = $this->arr_piscofins[$item["cstpiscofins"]];
				break;
		}
		return $piscofins["codccs"];
	}

	// Retorna o CST (codigo de situacao tributaria) do ICMS do item informado
	protected function csticms($item){
		switch($this->tabela_item($item)){
			case "itcupom":
				return csticms($item["tptribicms"], $item["aliqicms"], 0, 0, 0);
				break;
			case "itnotafiscal":
				return (is_object($item) ? $item->getcsticms() : $item["csticms"]);
				break;
		}
	}

	protected function cstipi($item){
		switch($this->tabela_item($item)){
			case "itcupom":
				return "99";
				break;
			case "itnotafiscal":
				$notafiscal = $this->arr_notafiscal[$item["idnotafiscal"]];
				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				switch($operacaonota["tipo"]){
					case "E":
						$estabelecimento = $this->arr_estabelecimento[$notafiscal["codestabelec"]];
						$tipoatividade = (is_array($estabelecimento) ? $estabelecimento["tipoatividade"] : $this->estabelecimento->gettipoatividade());
						if($tipoatividade === "D"){
							$totalipi = $this->valor_totalipi($item);
							return ($totalipi > 0 ? "00" : "02");
						}else{
							$produto = $this->arr_produto[$item["codproduto"]];
							$ipi = $this->arr_ipi[$produto["codipi"]];
							return $ipi["codcstent"];
						}
					case "S":
						return "99";
				}
				break;
		}
	}

	// Retorna o CST (codigo de situacao tributaria) do PIS/Cofins do item informado
	protected function cstpiscofins($item){
		$produto = $this->arr_produto[$item["codproduto"]];
		switch($this->tabela_item($item)){
			case "itcupom":
				$piscofins = $this->arr_piscofins[$produto["codpiscofinssai"]];
				break;
			case "itnotafiscal":
				$notafiscal = $this->arr_notafiscal[$item["idnotafiscal"]];

				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				if(strlen($item["cstpiscofins"]) > 0){
					$piscofins = $item["cstpiscofins"];
				}elseif($this->valor_natoperacao($item["natoperacao"]) == "1949"){
					$piscofins = "98";
				}elseif($this->valor_natoperacao($item["natoperacao"]) == "1556" && $item["aliqpis"] == 0){
					$piscofins = "99";
				}/*elseif(in_array($operacaonota["operacao"], array("TE", "TS"))){ // Verifica se e transferencia
				 * Retirado para o spedfiscal_contimatic_v1 funcionar corretamente.
					$piscofins = ($operacaonota["tipo"] == "E" ? "98" : "49");
				}*/elseif(in_array($operacaonota["operacao"], array("DF")) && $this->matriz->getregimetributario() == 2){ // Verifica se e devolucao de fornecedor
					$piscofins = "49";
				}elseif($operacaonota["tipo"] == "E"){
					if($item["bonificado"] == "S"){
						$piscofins = "99";
					}else{
						$piscofins = $this->arr_piscofins[$produto["codpiscofinsent"]];
					}
				}else{
					if(in_array($this->valor_natoperacao($item["natoperacao"]), array("5927", "5929", "5904")) && $this->matriz->getregimetributario() == 2){
						$piscofins = "49";
					}elseif($item["bonificado"] == "S"){
						$piscofins = "08";
					}else{
						$piscofins = $this->arr_piscofins[$produto["codpiscofinssai"]];
					}
				}
				break;
		}
		if(is_array($piscofins)){
			return $piscofins["codcst"];
		}else{
			return $piscofins;
		}
	}

	// Retorna o codigo do parceiro (Ex: C20 => Cliente codigo 20)
	protected function codparceiro($notafiscal){
		if(strlen($notafiscal["codparceiro"]) === 0){
			return null;
		}else{
			return $this->arr_operacaonota[$notafiscal["operacao"]]["parceiro"].$notafiscal["codparceiro"];
		}
	}

	// Faz download do arquivo
	public function download(){
		download($this->arquivo_nome);
	}

	// Escreve o bloco no arquivo
	protected function escrever_bloco($bloco){
		foreach($bloco as $registro){
			$this->escrever_registro($registro);
		}
	}

	// Escreve o registro no arquivo
	protected function escrever_registro($registro){
		if(is_array($registro) && sizeof($registro) > 0){
			$this->n_registro[reset($registro)] ++;
			fwrite($this->arquivo, "|".implode("|", array_map("trim", $registro))."|\r\n");
		}
	}

	public function gerar(){
		$this->progresso_i = 0;
	}

	// Identifica o modelo do documento de acordo com a tabela 4.1.1
	protected function modelo($notafiscal){
		if($notafiscal["operacao"] == "NC"){
			$modelo = "02"; //Nota Fiscal de Venda a Consumidor
		}elseif(strlen($notafiscal["chavenfe"]) > 0){
			$modelo = substr($notafiscal["chavenfe"], 20, 2);
		}elseif($notafiscal["status"] == "I"){
			if($notafiscal["tabela"] === "cupom"){
				$modelo = "65";
			}else{
				$modelo = "55";
			}
		}elseif($notafiscal["operacao"] == "VD"){ // Nota fiscal de venda
			if(strlen($notafiscal["cupom"]) > 0){ // Apartir de cupom fiscal
				$modelo = "2D";
			}else{
				$modelo = "01"; // Nota fiscal normal
			}
		}else{
			$modelo = "01";  // Nota fiscal normal
		}
		return $modelo;
	}

	// Incrementa a barra de progresso
	protected function progresso($texto){
		$this->progresso_i++;
		setprogress(($this->progresso_i / $this->progresso_t) * 100, $texto, TRUE);
	}

	// Transforma qualquer tipo de formato de data em 'ddmmaaaa'
	protected function valor_data($data){
		if(strpos($data, "/") !== FALSE){ // No formato 'dd/mm/aaaa'
			$arr = explode("/", $data);
			$time = mktime(0, 0, 0, $arr[1], $arr[0], $arr[2]);
		}elseif(strpos($data, "-") !== FALSE){ // No formato 'aaaa-mm-dd'
			$arr = explode("-", $data);
			$time = mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
		}
		return date("dmY", $time);
	}

	// Retorna a natureza de credito do item
	protected function natcredito($item){
		switch($this->tabela_item($item)){
			case "itcupom":

				break;
			case "itnotafiscal":
				$notafiscal = $this->arr_notafiscal[$item["idnotafiscal"]];
				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				$natoperacao = substr($item["natoperacao"], 2, 3);
				break;
		}
		switch($natoperacao){
			case "556":
			case "401":
			case "101":
				$natcredito = "02";
				break;
			case "551":
				$natcredito = "10";
				break;
			case "202":
			case "402":
			case "411":
				$natcredito = "12";
				break;
			default:
				$natcredito = "01";
				break;
		}
		return $natcredito;
	}

	// Retorna a natureza da receita do item
	protected function natreceita($item){
		$produto = $this->arr_produto[$item["codproduto"]];

		$natreceita = $produto["natreceita"];
		if(strlen($natreceita) == 0){
			$natreceita = "999";
		}

		$natreceita = str_pad($natreceita, 3, "0", STR_PAD_LEFT);

		return $natreceita;
	}

	// Transforma qualquer valor decimal no formato com ',' (virgula) na casa decimal e sem casa de milhar
	protected function valor_decimal($valor, $casas_decimais = NULL){
		$v = strpos($valor, ",");
		$p = strpos($valor, ".");
		if($v === FALSE && $p === FALSE){ // Valor inteiro sem separador de decimal e milhar (nao precisa de tratamento)
		}elseif($v !== FALSE && $p === FALSE){ // Virgula no separador decimal e sem separador de milhar
			$valor = str_replace(",", ".", $valor);
		}elseif($v === FALSE && $p !== FALSE){ // Ponto no separador de decimal e sem separador de milhar (nao precisa de tratamento)
		}elseif($v > $p){ // Virgula no separador de decimal e ponto no separador de milhar
			$valor = str_replace(".", "", $valor);
			$valor = str_replace(",", ".", $valor);
		}elseif($p > $v){ // Ponto no separador de decimal e virgula no separador de milhar
			$valor = str_replace(",", "", $valor);
		}
		if($valor < 0){
			$valor = 0;
		}
		return number_format($valor, $casas_decimais, ",", "");
	}

	// Tranforma de '(DD) TTTT-TTTT' para 'DDTTTTTTTT'
	protected function valor_telefone($valor){
		if(strlen($valor) > 0){
			$valor = removeformat($valor);
		}
		return $valor;
	}

	// Retorna o valor valido do CFOP (ex: 1.10201 => 1102)
	protected function valor_natoperacao($valor){
		return substr(removeformat($valor), 0, 4);
	}

	// Retorna o total de IPI do item
	protected function valor_totalipi($itnotafiscal){
		$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
		$natoperacao = $this->arr_natoperacao[$notafiscal["natoperacao"]];

		$modelo = $this->modelo($notafiscal);

		$estabelecimento = $this->arr_estabelecimento[$notafiscal["codestabelec"]];
		$tipoatividade = (is_array($estabelecimento) ? $estabelecimento["tipoatividade"] : $this->estabelecimento->gettipoatividade());

		if(in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65"))){
			$totalipi = null;
		}elseif($tipoatividade === "D" && $natoperacao["geraspedipi"] === "S"){
			$totalipi = $itnotafiscal["totalipi"];
		}elseif(in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic){
			$totalipi = 0;
		}else{
			$totalipi = $itnotafiscal["totalipi"];
		}

		return $totalipi;
	}

	// Retorna o nome da tabela que o item pertence
	protected function tabela_item($item){
		if(isset($item["idnotafiscal"])){
			return "itnotafiscal";
		}elseif(isset($item["idcupom"]) || isset($item["equipamentofiscal"])){
			return "itcupom";
		}elseif(isset($item["idnotafiscalservico"])){
			return "itnotafiscalservico";
		}
	}

	// Retorna o tipo de credito (tabela 4.3.6) apartir do item da nota fiscal
	protected function tipocredito($itnotafiscal){
		$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
		if($notafiscal["operacao"] == "DF" && $itnotafiscal["aliqpis"] > 0){
			$tipocredito = "101";
		}else{
			switch($this->cstpiscofins($itnotafiscal)){
				case "01":
					$tipocredito = "101";
					break;
				case "02":
					$tipocredito = "102";
					break;
				case "03":
					$tipocredito = "103";
					break;
				case "04":
					$tipocredito = "199";
					break;
				case "05":
					$tipocredito = "199";
					break;
				case "06":
					$tipocredito = "199";
					break;
				case "07":
					$tipocredito = "201";
					break;
				case "08":
					$tipocredito = "299";
					break;
				case "09":
					$tipocredito = "299";
					break;
				case "49":
					$tipocredito = "199";
					break;
				case "50":
					$tipocredito = "101";
					break;
				case "51":
					$tipocredito = "201";
					break;
				case "52":
					$tipocredito = "399";
					break;
				case "53":
					if($itnotafiscal["tptribicms"] == "T"){
						$tipocredito = "199";
					}else{
						$tipocredito = "299";
					}
					break;
				case "54":
					if(in_array($notafiscal["operacao"], array("EX", "IM"))){
						$tipocredito = "399";
					}else{
						$tipocredito = "199";
					}
					break;
				case "55":
					if(in_array($notafiscal["operacao"], array("EX", "IM"))){
						$tipocredito = "399";
					}else{
						$tipocredito = "299";
					}
					break;
				case "56":
					if(in_array($notafiscal["operacao"], array("EX", "IM"))){
						$tipocredito = "399";
					}elseif($itnotafiscal["tptribicms"] == "T"){
						$tipocredito = "199";
					}else{
						$tipocredito = "299";
					}
					break;
				case "60":
					$tipocredito = "106";
					break;
				case "61":
					$tipocredito = "206";
					break;
				case "62":
					$tipocredito = "306";
					break;
				case "63":
					if($itnotafiscal["tptribicms"] == "T"){
						$tipocredito = "106";
					}else{
						$tipocredito = "206";
					}
					break;
				case "64":
					if($itnotafiscal["tptribicms"] == "T"){
						$tipocredito = "106";
					}else{
						$tipocredito = "206";
					}
					break;
				case "65":
					if(in_array($notafiscal["operacao"], array("EX", "IM"))){
						$tipocredito = "306";
					}else{
						$tipocredito = "206";
					}
					break;
				case "66":
					if(in_array($notafiscal["operacao"], array("EX", "IM"))){
						$tipocredito = "306";
					}elseif($itnotafiscal["tptribicms"] == "T"){
						$tipocredito = "106";
					}else{
						$tipocredito = "206";
					}
					break;
				case "67":
					$tipocredito = "199";
					break;
				case "70":
					$tipocredito = "299";
					break;
				case "71":
					$tipocredito = "299";
					break;
				case "72":
					$tipocredito = "299";
					break;
				case "73":
					$tipocredito = "299";
					break;
				case "74":
					$tipocredito = "299";
					break;
				case "75":
					$tipocredito = "199";
					break;
				case "98":
					$tipocredito = "199";
					break;
				case "99":
					$tipocredito = "199";
					break;
				default:
					$tipocredito = "299";
					break;
			}
		}
		return $tipocredito;
	}

	public function comprimir($value){
		$value = json_encode($value);
		return gzcompress($value, 9);
	}

	public function descomprimir($value){
		$value = json_decode($value);
		return gzuncompress($value);
	}
}
