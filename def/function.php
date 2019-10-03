<?php
require_once(__DIR__."/../class/encoding.class.php");

// Retorna um alert() do javascript
function alert($text){
	$text = str_replace("'", "\'", $text);
	$text = str_replace("\n", "\\n", $text);
	return script("alert('".$text."')");
}

// Ordena (array) de 2 niveis exemplo buscado do banco de dados
function aasort(&$array, $key){ // Seu array e o campo a ser ordenado
	$sorter = array();
	$ret = array();
	reset($array);
	foreach($array as $ii => $va){
		$sorter[$ii] = $va[$key];
	}
	asort($sorter);
	foreach($sorter as $ii => $va){
		$ret[$ii] = $array[$ii];
	}
	return ($ret);
}

// Lista (array) dos codigos de produtos que contem foto
function array_codproduto_foto($con){
	$param_cadastro_dirfotoprod = param("CADASTRO", "DIRFOTOPROD", $con);
	$arr_codproduto = array();
	if(is_dir($param_cadastro_dirfotoprod)){
		$dir = opendir($param_cadastro_dirfotoprod);
		while($file = readdir($dir)){
			$file = strtolower($file);
			if(strpos($file, ".jpg") !== FALSE){
				$arr_codproduto[] = str_replace(".jpg", "", $file);
			}
			if(is_dir($file)){
				if(is_numeric($file)){
					$arr_codproduto[] = $file;
				}				
			}
		}
	}
	return $arr_codproduto;
}

// Reindexa um array
function array_reindex($arr){
	return array_merge($arr);
}

// Remove valores repetidos para array multi-dimensionais
function array_unique_multi($arr){
	foreach($arr as $i => $val){
		$arr[$i] = serialize($val);
	}
	$arr = array_unique($arr);
	foreach($arr as $i => $val){
		$arr[$i] = unserialize($val);
	}
	return $arr;
}

// Converte um array para xml
function array2xml($arr, $sub = null){
	if(is_null($sub)){
		reset($arr);
		$first = key($arr);
		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><".$first."></".$first.">");
		$arr = $arr[$first];
	}else{
		$xml = $sub;
	}
	foreach($arr as $key => $val){
		if(is_array($val)){
			if(!is_numeric($key)){
				$sub = $xml->addChild("$key");
				array2xml($val, $sub);
			}else{
				$sub = $xml->addChild("item".$key);
				array2xml($val, $sub);
			}
		}else{
			$xml->addChild($key, htmlspecialchars($val));
		}
	}
	return $xml->asXML();
}

// Preenche as variaveis necessarias para criar um grafico em colunas para o BI
function bi_column($con, $query, &$arr_data){

	$arr_data = array();

	$res = $con->query($query);
	$arr = $res->fetchAll(2);

	if(FALSE && count($arr) > 0){
		$a = $arr[0]["a"];
		// Verifica se "a" esta no formato: "99 - 99" (hora em hora)
		if(substr($a, -1) == "h" && !is_null(value_numeric(substr($a, 0, (strlen($a) - 1))))){
			// Preenche os valores restantes, para ir de 00 ate 23
			for($i = 0; $i <= 23; $i++){
				$achou = false;
				foreach($arr as $row){
					if($i == (float) substr($row["a"], 0, (strlen($row["a"]) - 1))){
						$achou = TRUE;
						break;
					}
				}
				if(!$achou){
					$arr[] = array(
						"a" => str_pad($i, 2, "0", STR_PAD_LEFT)."h",
						"y" => 0
					);
				}
			}
			// Reordena o array
			$arr_aux = array();
			foreach($arr as $row){
				$arr_aux[$row["a"]] = $row;
			}
			ksort($arr_aux);
			$arr = array();
			foreach($arr_aux as $row){
				$arr[] = $row;
			}
		}
	}

	foreach($arr as $row){
		$arr_data[] = array(
			"label" => $row["a"],
			"value" => $row["y"]
		);
	}
}

// Preenche as variaveis necessarias para criar um grafico em linha para o BI
function bi_line($con, $query, &$arr_category, &$arr_dataset){

	// Limpa as variaveis
	$arr_category = array();
	$arr_dataset = array();

	// Executa o SQL no banco de dados
	$res = $con->query($query);
	$arr = $res->fetchAll(2);

	// Captura todas as "variaveis" (chaves)
	$arr_a = array();
	foreach($arr as $row){
		$arr_a[] = $row["a"];
	}
	$arr_a = array_unique($arr_a);

	// Cria um novo array no formato:
	// array[x][a] = y;
	$arr_aux = array();
	foreach($arr as $row){
		$arr_aux[$row["x"]][$row["a"]] = $row["y"];
	}
	$arr = $arr_aux;

	// Verifica se "x" eh do tipo data
	$x_is_date = FALSE;
	if(!is_null(value_date($row["x"]))){
		$x_is_date = TRUE;

		// Captura a primeira e ultima data
		reset($arr);
		$dataini = explode("-", key($arr));
		$dataini = mktime(0, 0, 0, $dataini[1], $dataini[2], $dataini[0]);
		end($arr);
		$datafin = explode("-", key($arr));
		$datafin = mktime(0, 0, 0, $datafin[1], $datafin[2], $datafin[0]);

		// Cria as datas nos intervalos sem datas
		while($dataini < $datafin){
			$dataatu = date("Y-m-d", $dataini);
			if(!isset($arr[$dataatu])){
				$arr[$dataatu] = array();
			}
			// Verifica se todas as "variaveis"(chaves) estao criadas para a data
			foreach($arr_a as $a){
				if(!isset($arr[$dataatu][$a])){
					$arr[$dataatu][$a] = 0;
				}
			}
			// Acrescenta um dia na data inicial
			$dataini = strtotime("+1 day", $dataini);
		}
		ksort($arr);
	}

	// Preenche as variaveis "arr_category" e "arr_dataset"
	$arr_dataset_aux = array();
	foreach($arr as $x => $arr2){
		if($x_is_date){
			$x = convert_date($x, "Y-m-d", "d/m/Y");
		}
		$arr_category[] = array("label" => $x);
		foreach($arr2 as $a => $y){
			$arr_dataset_aux[$a]["seriesname"] = $a;
			$arr_dataset_aux[$a]["data"][] = array("value" => (float) $y);
		}
	}
	foreach($arr_dataset_aux as $dataset){
		$arr_dataset[] = $dataset;
	}
	$arr_category = array(array("category" => $arr_category));
}

function bi_line_saldo($con, $query, &$arr_category, &$arr_dataset){

	// Limpa as variaveis
	$arr_category = array();
	$arr_dataset = array();

	// Executa o SQL no banco de dados
	$res = $con->query($query);
	$arr = $res->fetchAll(2);

	// Captura todas as "variaveis" (chaves)
	$arr_a = array();
	foreach($arr as $row){
		$arr_a[] = $row["a"];
	}
	$arr_a = array_unique($arr_a);

	// Cria um novo array no formato:
	// array[x][a] = y;
	$arr_aux = array();
	foreach($arr as $row){
		$arr_aux[$row["x"]][$row["a"]] = $row["y"];
	}
	$arr = $arr_aux;

	// Verifica se "x" eh do tipo data
	$x_is_date = FALSE;
	if(!is_null(value_date($row["x"]))){
		$x_is_date = TRUE;

		// Captura a primeira e ultima data
		reset($arr);
		$dataini = explode("-", key($arr));
		$dataini = mktime(0, 0, 0, $dataini[1], $dataini[2], $dataini[0]);
		end($arr);
		$datafin = explode("-", key($arr));
		$datafin = mktime(0, 0, 0, $datafin[1], $datafin[2], $datafin[0]);

		// Cria as datas nos intervalos sem datas
		while($dataini < $datafin){
			$dataatu = date("Y-m-d", $dataini);
			if(!isset($arr[$dataatu])){
				$arr[$dataatu] = $arr_aux;
			}else{
				$arr_aux = $arr[$dataatu];
			}
			// Verifica se todas as "variaveis"(chaves) estao criadas para a data
			foreach($arr_a as $a){
				if(!isset($arr[$dataatu][$a])){
					$arr[$dataatu][$a] = $aux_dataatu[$a];
				}else{
					$aux_dataatu[$a] = $arr[$dataatu][$a];
				}
			}
			// Acrescenta um dia na data inicial
			$dataini = strtotime("+1 day", $dataini);
		}
		ksort($arr);
	}

	// Preenche as variaveis "arr_category" e "arr_dataset"
	$arr_dataset_aux = array();
	foreach($arr as $x => $arr2){
		if($x_is_date){
			$x = convert_date($x, "Y-m-d", "d/m");
		}
		$arr_category[] = array("label" => $x);
		foreach($arr2 as $a => $y){
			$arr_dataset_aux[$a]["seriesname"] = $a;
			$arr_dataset_aux[$a]["data"][] = array("value" => (float) $y);
		}
	}
	foreach($arr_dataset_aux as $dataset){
		$arr_dataset[] = $dataset;
	}
	$arr_category = array(array("category" => $arr_category));
}

// Retorna as opcoes padroes para criar os graficos
function bi_options($arr = array()){
	$arr_option = array(
		"anchorAlpha" => "0",
		"animation" => "0",
		"bgAlpha" => "0",
		"canvasBgAlpha" => "0",
		"captionPadding" => "30",
		"divLineColor" => "CCCCCC",
		"enableSmartLabels" => "1",
		"legendBorderAlpha" => "0",
		"legendShadow" => "0",
		"lineAlpha" => "100",
		"lineThickness" => "2",
		"lineShadow" => "0",
		"numberPrefix" => "R$ ",
		"paletteColors" => "#408DDB,#D63838,#20BA46,#E3E848,#C95FBD,#DBA123,#53D2DB,#B5B5B5,#936EC4,#A9C447,#9C8875,#333333,#EEEEEE",
		"plotGradientColor" => "",
		"showAlternatehGridColor" => "0",
		"showBorder" => "0",
		"showCanvasBorder" => "0",
		"showPlotBorder" => "0",
		"use3dlighting" => "0",
		"exportEnabled" => "1"
	);
	foreach($arr as $i => $val){
		$arr_option[$i] = $val;
	}
	return $arr_option;
}

// Retorna as opcoes padroes para criar os graficos em coluna
function bi_options_column($arr = array()){
	$arr_option = bi_options(array(
		"chartBottomMargin" => "25",
		"chartTopMargin" => "50",
		"showLabels" => "1",
		"showLegend" => "1",
		"showValues" => "1",
		"exportEnabled" => "1"
	));
	foreach($arr as $i => $val){
		$arr_option[$i] = $val;
	}
	return $arr_option;
}

// Retorna as opcoes padroes para criar os graficos em linha
function bi_options_line($arr = array()){
	$arr_option = bi_options(array(
		"chartBottomMargin" => "25",
		"chartTopMargin" => "50",
		"showValues" => "0",
		"exportEnabled" => "1"
	));
	foreach($arr as $i => $val){
		$arr_option[$i] = $val;
	}
	return $arr_option;
}

// Retorna as opcoes padroes para criar os graficos em pizza
function bi_options_pie($arr = array()){
	$arr_option = bi_options(array(
		"chartBottomMargin" => "25",
		"legendPosition" => "bottom",
		"showLabels" => "0",
		"showLegend" => "1",
		"showValues" => "1",
		"exportEnabled" => "1"
	));
	foreach($arr as $i => $val){
		$arr_option[$i] = $val;
	}
	return $arr_option;
}

function bi_options_bar($arr = array()){
	$arr_option = bi_options(array(
		"chartBottomMargin" => "25",
		"legendPosition" => "bottom",
		"showLabels" => "1",
		"showLegend" => "1",
		"showValues" => "1",
		"exportEnabled" => "1",
		"numberprefix" => "",
	));
	foreach($arr as $i => $val){
		$arr_option[$i] = $val;
	}
	return $arr_option;
}

// Preenche as variaveis necessarias para criar um grafico em pizza para o BI
function bi_pie($con, $query, &$arr_data){

	$arr_data = array();

	$res = $con->query($query);
	$arr = $res->fetchAll(2);

	foreach($arr as $row){
		$arr_data[] = array(
			"label" => $row["a"],
			"value" => $row["y"]
		);
	}
}

// Retorna o resultado do calculo passado por parametro como texto
// Se for passado uma conexao, ele usara o banco de dados para calcular
function calc_string($string, $con = NULL){
	// exemplo: calc_string("(1 + 2) * 3"); (retorna 9)
	if(is_object($con)){
		$res = $con->query("SELECT (".$string.")");
		return $res->fetchColumn();
	}else{
		//$string = ereg_replace("[^0-9\+-\*\/\(\) ]", "", trim($string));
		$string = preg_replace("[^0-9\+-\*\/\(\) ]", "", trim($string));
		$compute = create_function("", "return (".$string.");");
		return $compute();
	}
}

/*
  Autor.......: Jesus
  Data........: 23/04/2012
  Objetivo....: Calcular o digito verificador do modulo 11
  Parametros..:
  $NumDado: Dados para calculo do digito
  $NumDig.: Numero de digitos a ser calculado (padr�o = 1 digito)
  $LimMult: Valor limite para o multiplicador (padr�o = 9)
  Retorno.....: Digito(s) verficador calculado(s)
 */

function calcula_digito_mod11($NumDado, $NumDig = 1, $LimMult = 9){
	$Dado = removeformat($NumDado);
	for($n = 1; $n <= $NumDig; $n++){
		$Soma = 0;
		$Mult = 2;
		for($i = strlen($Dado) - 1; $i >= 0; $i--){
			$Soma += $Mult * intval(substr($Dado, $i, 1));
			if(++$Mult > $LimMult)
				$Mult = 2;
		}
		$digito = 11 - $Soma % 11;
		if($digito == 0 || $digito >= 10){
			$Dado .= 1;
		}else{
			$Dado .= $digito;
		}
	}
	//$Dado .= strval(fmod(fmod(($Soma * 10), 11), 10));
	return substr($Dado, strlen($Dado) - $NumDig);
}

/*
  Autor.......: Jesus
  Data........: 23/04/2012
  Objetivo....: Calcular o digito verificador do modulo 10
  Parametros..:
  $NumDado: Dados para calculo do digito
  Retorno.....: Digito verficador calculado
 */

function calcula_digito_mod10($NumDado){
	$Dado = removeformat($NumDado);
	$Result = array();
	$Mult = 2;
	for($i = strlen($Dado) - 1; $i >= 0; $i--){
		$Result[] = array_sum(str_split($Mult * intval(substr($Dado, $i, 1))));
		$Mult = ($Mult == 2 ? 1 : 2);
	}
	$soma = array_sum($Result);
	$digito = 10 - $soma % 10;
	return ($digito == 10 ? 0 : $digito);
}

// Arredanda para cima com casa decimais
function ceil_float($n, $d){
	$p = strpos($n, ".");
	if($p !== FALSE){
		$ns = str_pad(ceil((float) substr($n, $p + 1, $d).".".substr($n, $p + $d + 1)), $d, "0", STR_PAD_LEFT);
		$n = (float) (substr($n, 0, $p).".".$ns);
	}
	return $n;
}

function compare_date($date1, $date2, $format, $operator){ // compara duas datas de acordo com o operador
	// exemplo 1: compare_date("20/02/2009","23/02/2009","d/m/Y","<"); (retorna FALSE)
	// exemplo 2: compare_date("2001-08-10","2001-08-10","Y-m-d","=="); (retorna TRUE)
	$separator = substr($format, 1, 1);
	$format = explode($separator, strtoupper($format));
	$date1 = explode($separator, $date1);
	$date2 = explode($separator, $date2);
	foreach($format as $i => $char){
		switch($char){
			case "D":
				$day1 = $date1[$i];
				$day2 = $date2[$i];
				break;
			case "M":
				$mon1 = $date1[$i];
				$mon2 = $date2[$i];
				break;
			case "Y":
				$yea1 = $date1[$i];
				$yea2 = $date2[$i];
				break;
		}
	}
	$date1 = @mktime(0, 0, 0, $mon1, $day1, $yea1);
	$date2 = @mktime(0, 0, 0, $mon2, $day2, $yea2);
	switch($operator){
		case "==": return ($date1 == $date2);
			break;
		case "!=": return ($date1 != $date2);
			break;
		case ">" : return ($date1 > $date2);
			break;
		case "<" : return ($date1 < $date2);
			break;
		case ">=": return ($date1 >= $date2);
			break;
		case "<=": return ($date1 <= $date2);
			break;
	}
}

function compare_length($string_1, $string_2){ // Compara dois textos e retorna o maior
	if(strlen($string_1) >= strlen($string_2)){
		return $string_1;
	}else{
		return $string_2;
	}
}

function compare_time($time1, $time2, $format, $operator){ // Compara dois horarios de acordo com o operador
	// exemplo 1: compare_time("01:12:00","02:00:00","H:i:s","<"); (retorna TRUE)
	// exemplo 2: compare_time("59-59-23","00-00-12","s-i-H","=="); (retorna FALSE)
	$separator = substr($format, 1, 1);
	$format = explode($separator, strtoupper($format));
	$time1 = explode($separator, $time1);
	$time2 = explode($separator, $time2);
	foreach($format as $i => $char){
		switch($char){
			case "H":
				$hou1 = $time1[$i];
				$hou2 = $time2[$i];
				break;
			case "i":
				$min1 = $time1[$i];
				$min2 = $time2[$i];
				break;
			case "s":
				$sec1 = $time1[$i];
				$sec2 = $time2[$i];
				break;
		}
	}
	$time1 = mktime($hou1, $min1, $sec1, 0, 0, 0);
	$time2 = mktime($hou2, $min2, $sec2, 0, 0, 0);
	switch($operator){
		case "==": return ($time1 == $time2);
			break;
		case "!=": return ($time1 != $time2);
			break;
		case ">": return ($time1 > $time2);
			break;
		case "<": return ($time1 < $time2);
			break;
		case ">=": return ($time1 >= $time2);
			break;
		case "<=": return ($time1 <= $time2);
			break;
	}
}

// Cria o arquivo de configuracao da versao nova do WebSac (.env)
function configurar_websac_v2(){
	$dirname_v2 = __DIR__."/../v2/backend/";

	// Verifica se existe a versao nova
	if(!is_dir($dirname_v2)){
		return true;
	}

	// Verifica se ja nao esta configurado
	if(file_exists("{$dirname_v2}/.env")){
		return true;
	}

	// Carrega a configuracao atual do WebSac
	$config = parse_ini_file(__DIR__."/../support/config.ini");

	// Parametros a serem gravados no .env
	$env = array(
		"APP_ENV" => "local",
		"APP_DEBUG" => "true",
		"APP_KEY" => "",
		"APP_TIMEZONE" => "America/Sao_Paulo",
		"DB_CONNECTION" => "pgsql",
		"DB_HOST" => $config["dbhost"],
		"DB_PORT" => $config["dbport"],
		"DB_DATABASE" => $config["dbname"],
		"DB_USERNAME" => $config["dbuser"],
		"DB_PASSWORD" => $config["dbpass"],
		"CACHE_DRIVER" => "file",
		"QUEUE_DRIVER" => "sync"
	);

	// Escreve o arquivo .env
	$file = fopen("{$dirname_v2}/.env", "w+");
	foreach($env as $id => $value){
		fwrite($file, "{$id}={$value}\r\n");
	}
	fclose($file);
}

function console($text){
	$text = str_replace('"', '\"', $text);
	echo script("console.log(\"".$text."\")");
}

// Consulta um produto pelo codigo interno ou codigo ean e retorno o codigo interno
function consultarproduto(Connection $con, $codproduto){
	require_once("../class/consultaproduto.class.php");

	$consultaproduto = new ConsultaProduto($con);
	$consultaproduto->addcodproduto($codproduto);
	$consultaproduto->consultar();
	$encontrado = $consultaproduto->getencontrado();
	return array_shift($encontrado);
}

function convert_date($date, $from_format, $to_format){ // converte a data de um formato para outro
	// exemplo: convert_date("20/02/2009","d/m/Y","Y-m-d"); (retorna "2009-02-20")
	if(strlen($date) == 0){
		return $date;
	}
	if(strlen($from_format) == 3){
		$date = substr($date, 0, 2)."-".substr($date, 2, 2)."-".substr($date, 4);
		$from_format = substr($from_format, 0, 1)."-".substr($from_format, 1, 1)."-".substr($from_format, 2);
	}
	$separator = substr($from_format, 1, 1);
	$format = explode($separator, strtoupper($from_format));
	$date = explode($separator, $date);
	foreach($format as $i => $char){
		switch($char){
			case "D": $day = $date[$i];
				break;
			case "M": $mon = $date[$i];
				break;
			case "Y": $yea = $date[$i];
				break;
		}
	}
	$date = mktime(0, 0, 0, $mon, $day, $yea);
	return date($to_format, $date);
}

// transformas os caracteres especiais no formato para as variaveis de cabacario
function convertheader($value){
	$aux = "";
	for($i = 0; $i < strlen($value); $i++){
		$char = substr($value, $i, 1);
		switch($char){
			case "&": $aux .= "%26";
				break;
			case "%": $aux .= "%25";
				break;
			case "'": $aux .= "%27";
				break;
			case '"': $aux .= "%22";
				break;
			default : $aux .= $char;
				break;
		}
	}
	return $aux;
}

// Copia um arquivo (o memso da funcao copy)
function copy_file($url_origem, $arquivo_destino){
	$minha_curl = curl_init($url_origem);
	$fs_arquivo = fopen($arquivo_destino, "w");
	curl_setopt($minha_curl, CURLOPT_FILE, $fs_arquivo);
	curl_setopt($minha_curl, CURLOPT_HEADER, 0);
	curl_exec($minha_curl);
	curl_close($minha_curl);
	return fclose($fs_arquivo);
}

// Cria o  diretorio informado (cria o caminho todo ate o diretorio final)
function create_dir($dir){
	$arr = array($dir);
	while(true){
		$dir = dirname($dir);
		if($dir == dirname($dir)){
			break;
		}
		$arr[] = $dir;
	}
	$arr = array_reverse($arr);
	foreach($arr as $dir){
		if(!is_dir($dir)){
			mkdir($dir);
		}
	}
}

// Retorna o CSOSN (codigo da situacao de operacao simples nacional)
function csosn($tptribicms, $estabelecimento){
	$csosn = NULL;
	if($estabelecimento->getregimetributario() == "1"){
		$permitecredicms = ($estabelecimento->getpermitecredicms() === "S");
		if($tptribicms != "F"){
			if($permitecredicms == "S"){
				$csosn = "101";
			}else{
				$csosn = "102";
			}
		}else{
			if($permitecredicms == "S"){
				$csosn = "201";
			}else{
				$csosn = "202";
			}
		}
	}
	return $csosn;
}

// Retorna o CST equivalente ao CSOSN informado
function csosn2csticms($csosn, $aliqicms = 0, $redicms = 0, $aliqiva = 0, $valorpauta = 0, $tptribicms = null){
	switch($csosn){
		case "101":
			if($redicms == 0){
				return "000"; // Tributado integralmente
			}else{
				return "020"; // Tributado com reducao na base de calculo
			}
		case "102":
			if(!is_null($tptribicms) && $tptribicms == "N"){
				return "041"; // Nao tributado
			}else{
				return "040"; // Insento
			}
		case "103":
		case "300":
		case "400":
			if($aliqicms > 0){
				if($redicms == 0){
					return "000"; // Tributado integralmente
				}else{
					return "020"; // Tributado com reducao na base de calculo
				}
			}else{
				if(!is_null($tptribicms) && $tptribicms == "N"){
					return "041"; // Nao tributado
				}else{
					return "040"; // Insento
				}
			}
		case "201":
			if($redicms == 0){
				return "010"; // Tributado com ST
			}else{
				return "070"; // ST com reducao na base de calculo
			}
		case "202":
		case "203":
			if($aliqicms == 0){
				return "010"; // Tributado com ST
			}elseif($redicms > 0){
				return "070"; // ST com reducao na base de calculo
			}else{
				return "030"; // Isento ou nao tributado e com cobranca de ST
			}
		case "500":
			return "060"; // ICMS pago anteriormente por ST
		case "900":
			return "090"; // Outras
	}
	return null;
}

// Retorna o CST de ICMS apartir dos dados informados
function csticms($tptribicms, $aliqicms, $redicms, $aliqiva, $valorpauta, $classfiscal = NULL, $natoperacao = NULL, $totalicmssubst = null){
	if(is_object($natoperacao) && strlen($natoperacao->getcodcf()) > 0){
		$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf());
	}

	if(!is_null($classfiscal) && $classfiscal->getforcarcst() === "S"){
		$csticms = $classfiscal->getcodcst();
	}else{
		if(is_object($natoperacao) && $natoperacao->getgeracsticms090() == "S" && (!in_array($tptribicms, array("I", "F")) || ($tptribicms === "I" && $natoperacao->getalteracfisento() === "S") || ($tptribicms === "F" && $natoperacao->getalteracficmssubst() === "S"))){
			$csticms = "090";
		}elseif($tptribicms == "T" && $aliqicms > 0 && $redicms == 0){
			$csticms = "000";
		}elseif($tptribicms == "F" && $aliqicms > 0 && $redicms == 0 && (($aliqiva > 0 || $valorpauta > 0) || ($totalicmssubst != null && $totalicmssubst > 0))){
			$csticms = "010";
		}elseif(in_array($tptribicms, array("R", "T")) && $aliqicms > 0 && $redicms > 0){
			$csticms = "020";
		}elseif($tptribicms == "I" || ($tptribicms == "T" && $aliqicms == 0)){
			$csticms = "040";
		}elseif($tptribicms == "N"){
			$csticms = "041";
		}elseif($tptribicms == "F" && ($aliqiva == 0 || $aliqicms == 0) && $valorpauta == 0){
			$csticms = "060";
		}elseif($tptribicms == "F" && $aliqicms > 0 && $redicms > 0 && ($aliqiva > 0 || $valorpauta > 0)){
			$csticms = "070";
		}else{
			$csticms = "040";
		}
	}
	return $csticms;
}

/*
  Autor..............: Jesus
  Data...............: 02/06/11
  Objetivo...........: calcular o digito verificador para o nosso numero do boleto bancario
  Observações........: os parametros agencia e conta são utilizados apenas pelo banco itaú
  Parametros.........:
  $banco.........: numero do oficial do banco
  $nosso_numero..: sequencial refrente ao nosso numero sem o digito
  $carteira......: o codigo referente a carteira utilizada no banco
  $agencia.......: numero da agência do cedente
  $conta_corrente: numero da conta corrente do cedente
  retono.............: o digito calculado
 */

function dac_nosso_numero($banco, $nosso_numero, $carteira = "", $agencia = "", $conta_corrente = ""){
	if(strlen($nosso_numero) == 0){
		return " ";
	}
	switch($banco){
		case "237":
			$base = str_pad($carteira, 2, "0", STR_PAD_LEFT).str_pad($nosso_numero, 11, "0", STR_PAD_LEFT);
			$j = 2;
			$soma = 0;
			for($i = strlen($base) - 1; $i >= 0; $i--){
				$soma += substr($base, $i, 1) * $j++;
				if($j == 8){
					$j = 2;
				}
			}
			$resto = $soma % 11;
			$digito = 11 - $resto;
			if($digito == 11){
				$digito = "0";
			}elseif($digito == 10){
				$digito = "P";
			}
			return $digito;
		case "341":
			$digito = calcula_digito_mod10(str_pad($agencia, 4, "0").str_pad($conta_corrente, 5, "0").str_pad($carteira, 3, "0", STR_PAD_LEFT).str_pad($nosso_numero, 8, "0", STR_PAD_LEFT));
			return $digito;
		case "033":
			$digito = calcula_digito_mod10(str_pad($agencia, 4, "0").str_pad($conta_corrente, 5, "0").str_pad($carteira, 3, "0", STR_PAD_LEFT).str_pad($nosso_numero, 8, "0", STR_PAD_LEFT));
			return $digito;
	}
}

// Retorna o tipo de codificacao do banco de dados
function database_encoding(){
	$default = "WIN1252";
	if(strlen($_SESSION["DATABASE_ENCODING"]) === 0){
		$file_name = dirname($_SERVER["SCRIPT_FILENAME"]).str_repeat("/..", substr_count($_SERVER["SCRIPT_NAME"], "/") - 2)."/support/config.ini";
		if(file_exists($file_name)){
			$ini = parse_ini_file($file_name);
			if(strlen($ini["dbenco"]) > 0){
				$_SESSION["DATABASE_ENCODING"] = $ini["dbenco"];
			}else{
				$_SESSION["DATABASE_ENCODING"] = $default;
			}
		}else{
			$_SESSION["DATABASE_ENCODING"] = $default;
		}
	}
	return strtoupper($_SESSION["DATABASE_ENCODING"]);
}

function date_day($date){ // Retorna o dia de uma data
	$day = NULL;
	for($i = 0; $i < strlen($date); $i++){
		if(is_numeric(substr($date, $i, 1))){
			$day .= substr($date, $i, 1);
		}else{
			return $day;
		}
	}
	return $day;
}

function date_month($date){ // Retorna o mes de uma data
	$month = NULL;
	$cont = 0;
	for($i = 0; $i < strlen($date); $i++){
		if(is_numeric(substr($date, $i, 1))){
			if($cont == 1){
				$month .= substr($date, $i, 1);
			}
		}else{
			if($cont > 0){
				return $month;
			}else{
				$cont++;
			}
		}
	}
	return $month;
}

function date_year($date){ // Retorna o ano de uma data
	$year = NULL;
	$cont = 0;
	for($i = 0; $i < strlen($date); $i++){
		if(is_numeric(substr($date, $i, 1))){
			if($cont == 2){
				$year .= substr($date, $i, 1);
			}
		}else{
			if($cont > 1){
				return $year;
			}else{
				$cont++;
			}
		}
	}
	return $year;
}

function debug_microtime_begin(){
	global $debug_microtime;
	$debug_microtime = microtime(TRUE);
}

function debug_microtime_end($return = FALSE){
	global $debug_microtime;
	$microtime = microtime(TRUE) - $debug_microtime;
	if($return){
		return $microtime;
	}else{
		var_dump($microtime);
	}
}

// Retorna o dia da semana da data passada
function diasemana($data){
	$data = value_date($data);
	if(!is_null($data)){
		$arr_data = explode("-", $data);
		$i_semana = date("w", mktime(0, 0, 0, $arr_data[1], $arr_data[2], $arr_data[0]));
		switch($i_semana){
			case "0": return "domingo";
				break;
			case "1": return "segunda-feira";
				break;
			case "2": return "terça-feira";
				break;
			case "3": return "quarta-feira";
				break;
			case "4": return "quinta-feira";
				break;
			case "5": return "sexta-feira";
				break;
			case "6": return "sabado";
				break;
			default : return NULL;
				break;
		}
	}else{
		return NULL;
	}
}

function download($file_name){
	$file_name = str_replace("\\", "/", $file_name);
	//echo script("$.download(\"../form/download.php?f=".$file_name."\")");
	echo script("window.open(\"../form/download.php?f=".$file_name."\")");
}

function escpos_guilhotina(){
	return "\x1dV".chr(1);
}

function extenso_real($valor = 0, $maiusculas = false){
	$singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
	$plural = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões",
		"quatrilhões");

	$c = array("", "cem", "duzentos", "trezentos", "quatrocentos",
		"quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
	$d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta",
		"sessenta", "setenta", "oitenta", "noventa");
	$d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze",
		"dezesseis", "dezesete", "dezoito", "dezenove");
	$u = array("", "um", "dois", "três", "quatro", "cinco", "seis",
		"sete", "oito", "nove");

	$z = 0;
	$rt = "";

	$valor = number_format($valor, 2, ".", ".");
	$inteiro = explode(".", $valor);
	for($i = 0; $i < count($inteiro); $i++)
		for($ii = strlen($inteiro[$i]); $ii < 3; $ii++)
			$inteiro[$i] = "0".$inteiro[$i];

	$fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
	for($i = 0; $i < count($inteiro); $i++){
		$valor = $inteiro[$i];
		$rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
		$rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
		$ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

		$r = $rc.(($rc && ($rd || $ru)) ? " e " : "").$rd.(($rd &&
				$ru) ? " e " : "").$ru;
		$t = count($inteiro) - 1 - $i;
		$r .= $r ? " ".($valor > 1 ? $plural[$t] : $singular[$t]) : "";
		if($valor == "000")
			$z++;
		elseif($z > 0)
			$z--;
		if(($t == 1) && ($z > 0) && ($inteiro[0] > 0))
			$r .= (($z > 1) ? " de " : "").$plural[$t];
		if($r)
			$rt = $rt.((($i > 0) && ($i <= $fim) &&
					($inteiro[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ").$r;
	}

	if(!$maiusculas){
		return($rt ? $rt : "zero");
	}else{

		if($rt)
			$rt = preg_replace(" E ", " e ", ucwords($rt));
		//$rt = ereg_replace(" E ", " e ", ucwords($rt));
		return (($rt) ? ($rt) : "Zero");
	}
}

function fastmessage($text){
	$text = str_replace("\"", "\\\"", $text);
	$text = str_replace("\n", "<br>", $text);
	return script("$.fastMessage(\"".$text."\")");
}

function find_special($string){ // Verifica se existe caracter especial no texto
	$string = utf8_decode($string);
	for($i = 0; $i < strlen($string); $i++){
		$o = ord(substr($string, $i, 1));
		if($o >= 192 && $o <= 255){
			return TRUE;
		}
	}
	return FALSE;
}

function format_bytes($bytes, $precision = 2){
	$units = array("B", "KB", "MB", "GB", "TB");
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes = $bytes / pow(1024, $pow);
	return round($bytes, $precision)." ".$units[$pow];
}

function formatar_cpfcnpj($cpfcnpj){
	$cpfcnpj = trim(removeformat($cpfcnpj));
	if(strlen(ltrim($cpfcnpj, "0")) === 0){
		$cpfcnpj = NULL;
	}else{
		if(strlen($cpfcnpj) == 14){
			$cpfcnpj = substr($cpfcnpj, 0, 2).".".substr($cpfcnpj, 2, 3).".".substr($cpfcnpj, 5, 3)."/".substr($cpfcnpj, 8, 4)."-".substr($cpfcnpj, 12, 2);
		}else{
			$cpfcnpj = substr($cpfcnpj, 0, 3).".".substr($cpfcnpj, 3, 3).".".substr($cpfcnpj, 6, 3)."-".substr($cpfcnpj, 9, 2);
		}
	}
	return $cpfcnpj;
}

function formatar_telefone($telefone){
	$telefone = trim(removeformat($telefone));
	if(strlen(ltrim($telefone, "0")) === 0){
		$telefone = NULL;
	}else{
		if(strlen($telefone) == 12){
			$telefone = "(".substr($telefone, 1, 2).") ".substr($telefone, 3, 5)."-".substr($telefone, 8, 4);
		}elseif(strlen($telefone) == 11){
			$telefone = "(".substr($telefone, 0, 2).") ".substr($telefone, 2, 5)."-".substr($telefone, 7, 4);
		}elseif(strlen($telefone) == 10){
			$telefone = "(".substr($telefone, 0, 2).") ".substr($telefone, 2, 4)."-".substr($telefone, 6, 4);
		}elseif(strlen($telefone) == 8){
			$telefone = "(00) ".substr($telefone, 0, 4)."-".substr($telefone, 4, 4);
		}
	}
	return $telefone;
}

function getallrequest(){ // Tranforma todas as variaveis que vem por $_REQUEST em variavel pelo indice
	global $_REQUEST;
	foreach($_REQUEST as $index => $value){
		global $$index;
		$$index = $value;
	}
}

function getbetweenquotes($text, $quoteindex = 0, $quotetype = '"'){ // pega o conteudo que se encontra dentro das aspas de um texto
	$begin = NULL;
	$end = 0;
	$i = 0;
	$pos = 0;
	while(true){
		$pos = strpos($text, $quotetype, $pos + 1);
		if($pos === FALSE){
			break;
		}else{
			if($i == $quoteindex){
				$begin = $pos + 1;
			}elseif($begin != NULL){
				$end = $pos;
				break;
			}
			$i++;
		}
	}
	return substr($text, $begin, $end - $begin);
}

function grade_complcadastro($con, $tabela){
	$grid = new Grid();
	$grid->setwidth("100%");
	$grid->setcolumnswidth(array("24%", "24%", "4%", "24%", "24%"));

	$complcadastro = objectbytable("complcadastro", NULL, $con);
	$complcadastro->setorder("ordem");
	$complcadastro->settabela($tabela);
	$query = $complcadastro->getquery();
	$arr_complcadastro = object_array($complcadastro);

	$i = 0;
	$row = array();
	foreach($arr_complcadastro as $complcadastro){
		switch($complcadastro->gettipo()){
			case "B": $mask = "simnao";
				break;
			case "D": $mask = "data";
				break;
			case "F": $mask = "decimal2";
				break;
			case "I": $mask = "inteiro";
				break;
			case "S": $mask = NULL;
				break;
			case "T": $mask = "hora";
				break;
		}

		$id = "complcadastro_".$complcadastro->getcodcomplcadastro();
		$required = ($complcadastro->getobrigatorio() == "S" ? " required=\"".$complcadastro->getdescricao()." (Informa&ccedil;&otilde;es Adicionais)\"" : "");
		if($mask == "simnao"){
			$campo = ComboBox::draw("simnao", $id, $required);
		}else{
			$campo = "<input type=\"text\" id=\"".$id."\"".(is_null($mask) ? "" : " mask=\"".$mask."\"").$required.">";
		}

		if(sizeof($row) > 0){
			$row[] = "";
		}
		$descricao = utf8_encode($complcadastro->getdescricao());
		$row = array_merge($row, array("<label for=\"".$id."\" title=\"".$descricao."\">".$descricao."</label>", $campo));
		if(++$i % 2 == 0 || $i == sizeof($arr_complcadastro)){
			$grid->addrow($row);
			$row = array();
		}
	}

	return $grid->draw();
}

// Retorna um HTML (table) apartir de um objeto Grid
function grade_ultimascompras($con, $codestabelec, $codproduto, $limit = 5){
	if(is_array($codestabelec)){
		$codestabelec = implode(", ", $codestabelec);
	}

	$grid = new Grid();
	$grid->setheader(array("Data", "Nota Fiscal", "Fornecedor", "Quantidade", "Pre&ccedil;o"));
	$grid->setcolumnsalign(array("center", "center", "left", "right", "right"));
	$grid->setcolumnswidth(array("18%", "16%", "30%", "18%", "18%"));

	$query = "SELECT * FROM ";
	$query .= "((SELECT notafiscal.dtentrega, notafiscal.numnotafis, notafiscal.serie, COALESCE(fornecedor.razaosocial, estabelecimento_parceiro.nome) AS fornecedor, ";
	$query .= "    (itnotafiscal.quantidade * itnotafiscal.qtdeunidade) AS quantidade, ";
	$query .= "    (CASE WHEN itnotafiscal.quantidade = 0 OR itnotafiscal.qtdeunidade = 0 THEN 0 ELSE (itnotafiscal.totalliquido / (itnotafiscal.quantidade * itnotafiscal.qtdeunidade)) END) AS preco, ";
	$query .= "    estabelecimento_origem.cor ";
	$query .= "  FROM itnotafiscal ";
	$query .= "  INNER JOIN notafiscal ON (itnotafiscal.idnotafiscal = notafiscal.idnotafiscal) ";
	$query .= "  LEFT JOIN fornecedor ON (notafiscal.codparceiro = fornecedor.codfornec AND notafiscal.operacao IN ('CP','PR')) ";
	$query .= "  LEFT JOIN estabelecimento AS estabelecimento_parceiro ON (notafiscal.codparceiro = estabelecimento_parceiro.codestabelec AND notafiscal.operacao = 'TE') ";
	$query .= "  LEFT JOIN estabelecimento AS estabelecimento_origem ON (notafiscal.codestabelec = estabelecimento_origem.codestabelec) ";
	$query .= "  WHERE (notafiscal.operacao IN ('CP','TE') ";
	$query .= "    OR notafiscal.operacao = (CASE WHEN param('NOTAFISCAL','PRODRURALIGUALCOMPRA') = 'S' THEN 'PR' ELSE '' END)::bpchar) ";
	if(strlen($codestabelec) > 0){
		$query .= "	  AND notafiscal.codestabelec IN (".$codestabelec.") ";
	}
	$query .= "	   AND itnotafiscal.codproduto = ".$codproduto." ";
	$query .= "  ORDER BY notafiscal.dtentrega DESC ";
	$query .= "  LIMIT {$limit} ";
	$query .= ") UNION ALL (";
	$query .= "  SELECT pedido.dtentrega, pedido.numpedido AS numnotafis, 'PED' AS serie,  COALESCE(fornecedor.razaosocial, estabelecimento_parceiro.nome) AS fornecedor, ";
	$query .= "    (itpedido.quantidade * itpedido.qtdeunidade) AS quantidade, ";
	$query .= "    (CASE WHEN itpedido.quantidade = 0 OR itpedido.qtdeunidade = 0 THEN 0 ELSE (itpedido.totalliquido / (itpedido.quantidade * itpedido.qtdeunidade)) END) AS preco, ";
	$query .= "    estabelecimento_origem.cor ";
	$query .= "  FROM itpedido ";
	$query .= "  INNER JOIN pedido ON (itpedido.codestabelec = pedido.codestabelec AND itpedido.numpedido = pedido.numpedido) ";
	$query .= "  LEFT JOIN fornecedor ON (pedido.codparceiro = fornecedor.codfornec AND pedido.operacao IN ('CP','PR')) ";
	$query .= "  LEFT JOIN estabelecimento AS estabelecimento_parceiro ON (pedido.codparceiro = estabelecimento_parceiro.codestabelec AND pedido.operacao = 'TE') ";
	$query .= "  LEFT JOIN estabelecimento AS estabelecimento_origem ON (pedido.codestabelec = estabelecimento_origem.codestabelec) ";
	$query .= "  WHERE pedido.operacao IN ('CP','TE','PR') AND itpedido.status = 'P' AND pedido.status = 'P' ";
	if(strlen($codestabelec) > 0){
		$query .= "	  AND pedido.codestabelec IN (".$codestabelec.") ";
	}
	$query .= "	   AND itpedido.codproduto = ".$codproduto." ";
	$query .= "  ORDER BY pedido.dtentrega DESC ";
	$query .= "  LIMIT {$limit} )) AS tmp ";
	$query .= "ORDER BY dtentrega DESC ";

	$res = $con->query($query);
	$arr = $res->fetchAll(2);

	foreach($arr as $row){
		$grid->addrow(array(
			convert_date($row["dtentrega"], "Y-m-d", "d/m/Y"),
			$row["numnotafis"]."-".$row["serie"],
			$row["fornecedor"],
			number_format($row["quantidade"], 4, ",", "."),
			number_format($row["preco"], 2, ",", ".")
		), null, null, "cor='{$row["cor"]}'");
	}
	return $grid->draw();
}

// Retorna um HTML (table)
function grade_vendamensal($con, $codestabelec, $codproduto){
	/*
	 * Tratamento feito para o Mihara (gambiarra)
	 * Apagar em breve.
	 * Murilo Feres (13/08/2017)
	 */
	if(param("SISTEMA", "CODIGOCW", $con) == "1238"){
		$query = "SELECT * FROM(SELECT ano, mes, SUM(quantidade) AS quantidade ";
		$query .= "FROM consvendames ";
		$query .= "WHERE codproduto = ".$codproduto." ";
		$query .= " AND ((ano = ".(date("Y") - 1)." AND mes >= ".date("m").") OR (ano = ".date("Y")." AND mes <= ".date("m").")) ";
		if(strlen($codestabelec) > 0){
			if(in_array((int) $codestabelec, array(4, 5))){
				$codestabelec = "4, 5";
				$query .= " AND (codestabelec != 4 OR (mes < 8 AND ano = 2017) OR ano < 2017) ";
			}
			if(in_array((int) $codestabelec, array(3, 8))){
				$codestabelec = "3, 8";
				$query .= " AND (codestabelec != 3 OR (mes < 6 AND ano = 2018) OR ano < 2018) ";
			}
			$query .= " AND codestabelec IN (".$codestabelec.") ";
		}
		$query .= "GROUP BY ano, mes ";
		$query .= "ORDER BY ano, mes) AS tmp ORDER BY ano ASC, mes ASC ";
	}else{
		$query = "SELECT * FROM(SELECT ano, mes, SUM(quantidade) AS quantidade ";
		$query .= "FROM consvendames ";
		$query .= "WHERE codproduto = ".$codproduto." ";
		$query .= " AND ((ano = ".(date("Y") - 1)." AND mes >= ".date("m").") OR (ano = ".date("Y")." AND mes <= ".date("m").")) ";
		if(strlen($codestabelec) > 0){
			$query .= " AND codestabelec IN (".$codestabelec.") ";
		}
		$query .= "GROUP BY ano, mes ";
		$query .= "ORDER BY ano, mes) AS tmp ORDER BY ano ASC, mes ASC ";
	}
	$res = $con->query($query);
	$arr = $res->fetchAll(2);
	$ano = date("Y");
	$mes = date("m");
	$html = "<table style=\"font-size:11px; height:100%\">";
	for($i = 12; $i > -1; $i--){
		$quantidade = 0;
		foreach($arr as $row){
			if($row["ano"] == $ano && $row["mes"] == $mes){
				$quantidade = $row["quantidade"];
				break;
			}
		}
		$html .= "<tr><td style=\"background-color:".($i % 2 == 0 ? "#DDD" : "#EEE")."; width:60%\">".$ano." ".month_description($mes, FALSE)."</td>";
		$html .= "<td style=\"background-color:".($i % 2 == 0 ? "#EEE" : "#FFF")."; text-align:right\">".number_format($quantidade, 2, ",", ".")."</td></tr>";
		$mes--;
		if($mes == 0){
			$mes = 12;
			$ano--;
		}
	}
	$html .= "</table>";
	return $html;
}

function guiagnre_calcularitens(Connection $con, $idnotafiscal){
	$notafiscal = objectbytable("notafiscal", $idnotafiscal, $con);
	$estabelecimento = objectbytable("estabelecimento", $notafiscal->getcodestabelec(), $con);
	$operacaonota = objectbytable("operacaonota", $notafiscal->getoperacao(), $con);
	$natoperacao = objectbytable("natoperacao", $notafiscal->getnatoperacao(), $con);

	$itnotafiscal = objectbytable("itnotafiscal", null, $con);
	$itnotafiscal->setidnotafiscal($idnotafiscal);

	$arr_itnotafiscal = object_array($itnotafiscal);

	switch($operacaonota->getparceiro()){
		case "C":
			$parceiro = objectbytable("cliente", $notafiscal->getcodparceiro(), $con);
			$parceiro_uf = $parceiro->getufres();
			break;
		case "E":
			$parceiro = objectbytable("estabelecimento", $notafiscal->getcodparceiro(), $con);
			$parceiro_uf = $parceiro->getuf();
			break;
		case "F":
			$parceiro = objectbytable("fornecedor", $notafiscal->getcodparceiro(), $con);
			$parceiro_uf = $parceiro->getuf();
			break;
	}


	$itemcalculo = new ItemCalculo($con);

	$itemcalculo->setestabelecimento($estabelecimento);
	$itemcalculo->setoperacaonota($operacaonota);
	$itemcalculo->setnatoperacao($natoperacao);
	$itemcalculo->setparceiro($parceiro);

	foreach($arr_itnotafiscal as $itnotafiscal){
		$itemcalculo->setitem($itnotafiscal);
		$itemcalculo->calcular();
	}

	return $arr_itnotafiscal;
}

// Arruma a reducao de icms na importacao de notafiscal por xml
function importarnotafiscal_xml_reducao($icms){

	// Reducao de ICMS
	//   Indice do array principal => valor incorreto da reducao
	//   Valores => valor correto da reducao
	$arr_reducao = array(
		"33.3" => 33.33,
		"41.66" => 41.67,
		"41.7" => 41.67,
		"58.34" => 41.67,
		"61.1" => 61.11,
		"100" => 00.00
	);

	if(in_array($icms->CST, array("10", "70"))){
		$redicms = (float) $icms->pRedBCST;
		if(in_array($redicms, array(1))){
			$redicms = 0;
		}elseif(strlen($redicms) == 0 || in_array($redicms, array(0, 100))){
			$redicms = (float) $icms->pRedBC;
		}
	}elseif($icms->CST == "20"){
		$redicms = (float) $icms->pRedBC;
	}else{
		$redicms = 0;
	}

	$redicms_s = (string) $redicms;
	if(isset($arr_reducao[$redicms_s])){
		$redicms = $arr_reducao[$redicms_s];
	}

	return $redicms;
}

function insertaround($text, $word, $begin, $end){
	$text = str_ireplace($word, $begin.$word.$end, $text);
	return $text;
}

function is_windows(){
	return isset($_SERVER["WINDIR"]);
}

// Metodo padrao para erro do JSON de retorno
function json_error($message, $extra = NULL, $die = TRUE){
	$json_out = array(
		"status" => "2",
		"message" => $message
	);
	if(is_array($extra)){
		$json_out = array_merge($json_out, $extra);
	}
	if($die){
		die(json_encode($json_out));
	}else{
		return $json_out;
	}
}

// Metodo padrao para sucesso do JSON de retorno
function json_success($extra = NULL, $die = TRUE){
	$json_out = array(
		"status" => "0"
	);
	if(is_string($extra)){
		$extra = json_decode($extra, TRUE);
	}
	if(is_array($extra)){
		$json_out = array_merge($json_out, $extra);
	}
	if($die){
		die(json_encode($json_out));
	}else{
		return $json_out;
	}
}

function juntar_array($a, $b){
	foreach($b as $c){
		$a[] = $c;
	}
	return $a;
}

function manter_numeros($numero){
	return preg_replace("/[^0-9]/", "",$numero);
}

function messagebox($type, $title, $text, $focusonclose = null, $afterclose = null){
	if(mobile() == false){
		$text = str_replace("\"", "\\\"", $text);
		$text = str_replace("\n", "<br>", $text);
		$html = "<script type=\"text/javascript\"> ";
		$html .= "$.messageBox({ ";
		$html .= "	type:\"".$type."\", ";
		$html .= "	title:\"".$title."\", ";
		$html .= "	text:\"".$text."\" ";
		if($focusonclose != NULL){
			$html .= ",	focusOnClose:".'$'."(".$focusonclose.") ";
		}
		if($afterclose != NULL){
			$html .= ", afterClose:function(){".$afterclose."} ";
		}
		$html .= "}); ";
		$html .= "</script> ";
		return $html;
	}else{
		return alert($text);
	}
}

function mobile(){
	$info = strtolower($_SERVER["HTTP_USER_AGENT"]);
	$devices = array("android", "blackberry", "ipad", "iphone", "ipod", "opera mini");
	foreach($devices as $device){
		if(strpos($info, $device) !== FALSE){
			return true;
		}
	}
}

function mobile_redirect($in_root = FALSE){
	global $con;

	if(!is_object($con)){
		require_file("class/connection.class.php");
		$con = new Connection();
	}

	$exceptions = array("pontooperacao", "pontovenda", "spexpress");
	foreach($exceptions as $exception){
		if(strpos($_SERVER["SCRIPT_FILENAME"], $exception) !== FALSE){
			return true;
		}
	}
	$info = strtolower($_SERVER["HTTP_USER_AGENT"]);
	$devices = array("android", "blackberry", "ipad", "iphone", "ipod", "opera mini", "windows phone");
	if(param("SISTEMA", "ATIVAMOBILE", $con) == "S" && ($_REQUEST["mobile"] != "S")){
		foreach($devices as $device){
			if(strpos($info, $device) !== FALSE){
				header("Location: ".(!$in_root ? "../" : "")."mobile/");
				die();
			}
		}
	}
}

function month_description($int, $special_char = FALSE){
	switch($int){
		case "1": return "Janeiro";
		case "2": return "Fevereiro";
		case "3": return ($special_char ? "Março" : "Mar&ccedil;o");
		case "4": return "Abril";
		case "5": return "Maio";
		case "6": return "Junho";
		case "7": return "Julho";
		case "8": return "Agosto";
		case "9": return "Setembro";
		case "10": return "Outubro";
		case "11": return "Novembro";
		case "12": return "Dezembro";
	}
}

// Retona o ultimo dia do mes
function month_last_day($year, $month){
	return date("d", mktime(0, 0, 0, $month + 1, 1, $year) - 1);
}

// Executa a pesquisa no objeto (cadastro) e retorna um array com todos os objetos (registros)
function object_array($object){
	$table_name = $object->gettablename();
	$arr_column = $object->getcolumnnames();
	$arr_object = array();
	$arr = $object->searchbyobject(NULL, NULL, TRUE);

	switch($table_name){
		case "itpedido_conf": $table_name = "itpedidoconf";
			break;
	}

	if(is_array($arr)){
		foreach($arr as $row){
			$ob = objectbytable($table_name, NULL, $object->getconnection());
			foreach($arr_column as $column){
				@call_user_func(array($ob, "set".$column), $row[$column]);
			}
			$arr_object[] = $ob;
		}
	}
	return $arr_object;
}

function object_array_key($object, $arr_key, $order = NULL){
	if(is_array(reset($arr_key))){
		$arr_key = array_unique_multi($arr_key);
	}else{
		$arr_key = array_unique($arr_key);
	}
	if(count($arr_key) === 0){
		return array();
	}
	$con = $object->getconnection();
	$table_name = $object->gettablename();
	$arr_primarykey = $object->getprimarykey();
	if(!is_array($arr_primarykey)){
		$arr_primarykey = array($arr_primarykey);
	}
	$n = 0; // Contador de chaves
	$x = 0; // Contador de instrucoes
	$arr_where = array();
	foreach($arr_key as $key){
		if(!is_array($key)){
			$key = array($key);
		}
		foreach($key as $i => $key_2){
			if(strlen(trim($key_2)) == 0){
				continue 2;
			}
			$key[$i] = "'".$key_2."'";
		}
		$arr_where[$x][] = "(".implode(",", $key).")";
		if(++$n >= 2000){
			$x++;
			$n = 0;
		}
	}
	$arr = array();
	foreach($arr_where as $where){
		$query = "SELECT * FROM ".$table_name." WHERE (".implode(", ", $arr_primarykey).") IN (".implode(", ", $where).")";
		if(!is_null($order)){
			$query .= " ORDER BY ".$order;
		}
		if(!($res = $con->query($query))){
			$error = $con->errorInfo();
			die($query."<br><br>".$error[2]);
		}
		$arr = array_merge($arr, $res->fetchAll(2));
	}
	$arr_object_aux = array();
	$arr_column = $object->getcolumnnames();
	foreach($arr as $row){
		$object = objectbytable($table_name, NULL, $con);
		foreach($arr_column as $column){
			@call_user_func(array($object, "set".$column), $row[$column]);
		}
		$arr_object_aux[] = $object;
	}
	$arr_object = array();
	foreach($arr_object_aux as $object){
		$arr_key = array();
		foreach($arr_primarykey as $primarykey){
			$arr_key[] = call_user_func(array($object, "get".$primarykey));
		}
		$arr_object[implode(";", $arr_key)] = $object;
	}
	return $arr_object;
}

// Memoria da funcao, para nao ficar executando o mesmo SQL varias vezes na mesma execucao
$objectbytable_memory = array();
// Retorna o objeto apartir do nome da tabela
function objectbytable($table, $key = null, Connection $connection = null, $force = false){
	global $objectbytable_memory;

	switch($table){
		case "leitura_data":
			$table = "leituradata";
			break;
		case "v_parceiro":
			$table = "vparceiro";
			break;
	}

	if(false && !$force && isset($objectbytable_memory[$table]) && (is_array($key) || strlen($key) > 0)){
		$key_aux = (is_array($key) ? implode(";", $key) : $key);
		if(isset($objectbytable_memory[$table][$key_aux])){
			$object = clone $objectbytable_memory[$table][$key_aux];
			if(!is_null($connection)){
				$object->setconnection($connection);
			}
			return $object;
		}
	}

	require_file("class/".$table.".class.php");
	switch($table){
		case "administestabelec": $object = new AdministEstabelec();
			break;
		case "administradora": $object = new Administradora();
			break;
		case "administradorabin": $object = new AdministradoraBin();
			break;
		case "arquivo": $object = new Arquivo();
			break;
		case "atividade": $object = new Atividade();
			break;
		case "balanca": $object = new Balanca();
			break;
		case "banco": $object = new Banco();
			break;
		case "bancoestab": $object = new BancoEstab();
			break;
		case "biconfig": $object = new BiConfig();
			break;
		case "carimbo": $object = new Carimbo();
			break;
		case "carrossel": $object = new Carrossel();
			break;
		case "cartao": $object = new Cartao();
			break;
		case "catlancto": $object = new CatLancto();
			break;
		case "catrestricao": $object = new CatRestricao();
			break;
		case "centrocusto": $object = new CentroCusto();
			break;
		case "cest": $object = new Cest();
			break;
		case "cheque": $object = new Cheque();
			break;
		case "cidade": $object = new Cidade();
			break;
		case "codigoservico": $object = new CodigoServico();
			break;
		case "classfiscal": $object = new ClassFiscal();
			break;
		case "classificacao": $object = new Classificacao();
			break;
		case "clicartaoprop": $object = new CliCartaoProp();
			break;
		case "cliente": $object = new Cliente();
			break;
		case "clientecompl": $object = new ClienteCompl();
			break;
		case "clienteestab": $object = new ClienteEstab();
			break;
		case "clienteinteresse": $object = new ClienteInteresse();
			break;
		case "comissao": $object = new Comissao();
			break;
		case "complcadastro": $object = new ComplCadastro();
			break;
		case "composicao": $object = new Composicao();
			break;
		case "concorrente": $object = new Concorrente();
			break;
		case "condpagto": $object = new CondPagto();
			break;
		case "controlecarne": $object = new ControleCarne();
			break;
		case "controleprocfinan": $object = new ControleProcFinan();
			break;
		case "controleprevenda": $object = new ControlePreVenda();
			break;
		case "controlewebservice": $object = new ControleWebService();
			break;
		case "consvendadia": $object = new ConsVendaDia();
			break;
		case "consvendames": $object = new ConsVendaMes();
			break;
		case "contabilidade": $object = new Contabilidade();
			break;
		case "contingencianfe": $object = new ContingenciaNFe();
			break;
		case "cotacao": $object = new Cotacao();
			break;
		case "cotacaofornec": $object = new CotacaoFornec();
			break;
		case "cotacaotemp": $object = new CotacaoTemp();
			break;
		case "cupom": $object = new Cupom();
			break;
		case "cupomlancto": $object = new CupomLancto();
			break;
		case "datavalidade": $object = new DataValidade();
			break;
		case "departamento": $object = new Departamento();
			break;
		case "dominio": $object = new Dominio();
			break;
		case "ecf": $object = new Ecf();
			break;
		case "ecommerce": $object = new Ecommerce();
			break;
		case "embalagem": $object = new Embalagem();
			break;
		case "emitente": $object = new Emitente();
			break;
		case "equivalente": $object = new Equivalente();
			break;
		case "especie": $object = new Especie();
			break;
		case "estabelecimento": $object = new Estabelecimento();
			break;
		case "estabelecimentoiest": $object = new EstabelecimentoIEST();
			break;
		case "estado": $object = new Estado();
			break;
		case "estadotributo": $object = new EstadoTributo();
			break;
		case "etiqcliente": $object = new EtiqCliente();
			break;
		case "etiqgondola": $object = new EtiqGondola();
			break;
		case "etiqnotafiscal": $object = new EtiqNotaFiscal();
			break;
		case "familia": $object = new Familia();
			break;
		case "familiafornec": $object = new FamiliaFornec();
			break;
		case "favoritos": $object = new Favoritos();
			break;
		case "feriado": $object = new Feriado();
			break;
		case "fidelidadepontos": $object = new FidelidadePontos();
			break;
		case "fidelidadepremio": $object = new FidelidadePremio();
			break;
		case "fidelidaderesgatepremio": $object = new FidelidadeResgatePremio();
			break;
		case "fidelidaderesgate": $object = new FidelidadeResgate();
			break;
		case "finalizadora": $object = new Finalizadora();
			break;
		case "fornecedor": $object = new Fornecedor();
			break;
		case "fornecestab": $object = new FornecEstab();
			break;
		case "fornecedorcompl": $object = new FornecedorCompl();
			break;
		case "frentecaixa": $object = new FrenteCaixa();
			break;
		case "funcionario": $object = new Funcionario();
			break;
		case "funcmeta": $object = new FuncMeta();
			break;
		case "grupo": $object = new Grupo();
			break;
		case "grupocategoria": $object = new GrupoCategoria();
			break;
		case "grupocta": $object = new GrupoCta();
			break;
		case "grupofatmto": $object = new GrupoFatmto();
			break;
		case "grupoocorrencia": $object = new GrupoOcorrencia();
			break;
		case "grupoprod": $object = new GrupoProd();
			break;
		case "grupoprograma": $object = new GrupoPrograma();
			break;
		case "gruporestricao": $object = new GrupoRestricao();
			break;
		case "guiagnre": $object = new GuiaGnre();
			break;
		case "histcupom": $object = new HistCupom();
			break;
		case "historico": $object = new Historico();
			break;
		case "historicopadrao": $object = new HistoricoPadrao();
			break;
		case "icmspdv": $object = new IcmsPdv();
			break;
		case "ibptestabelec": $object = new IbptEstabelec();
			break;
		case "impressaoetiqcliente": $object = new ImpressaoEtiqCliente();
			break;
		case "impressaotemp": $object = new ImpressaoTemp();
			break;
		case "instrucaobancaria": $object = new InstrucaoBancaria();
			break;
		case "interesse": $object = new Interesse();
			break;
		case "inventario": $object = new Inventario();
			break;
		case "ipi": $object = new Ipi();
			break;
		case "itcomposicao": $object = new ItComposicao();
			break;
		case "itcotacao": $object = new ItCotacao();
			break;
		case "itcotacaoestab": $object = new ItCotacaoEstab();
			break;
		case "itcotacaofornec": $object = new ItCotacaoFornec();
			break;
		case "itcupom": $object = new ItCupom();
			break;
		case "itinventario": $object = new ItInventario();
			break;
		case "itinventariotemp": $object = new ItInventarioTemp();
			break;
		case "itnegociacaopreco": $object = new ItNegociacaoPreco();
			break;
		case "itnotadiversa": $object = new ItNotaDiversa();
			break;
		case "itnotafiscal": $object = new ItNotaFiscal();
			break;
		case "itnotafiscalservico": $object = new ItNotaFiscalServico();
			break;
		case "itoferta": $object = new ItOferta();
			break;
		case "itorcamento": $object = new ItOrcamento();
			break;
		case "itorcamentoconf": $object = new ItOrcamentoConf();
			break;
		case "itpedido": $object = new ItPedido();
			break;
		case "itpedidoconf": $object = new ItPedidoConf();
			break;
		case "itpontovenda": $object = new ItPontoVenda();
			break;
		case "itpontovendans": $object = new ItPontoVendaNs();
			break;
		case "itrma": $object = new ItRma();
			break;
		case "ittabelapreco": $object = new ItTabelaPreco();
			break;
		case "ittv": $object = new ItTV();
			break;
		case "lancamento": $object = new Lancamento();
			break;
		case "lancamentogru": $object = new LancamentoGru();
			break;
		case "layout": $object = new Layout();
			break;
		case "leituradata": $object = new LeituraData();
			break;
		case "limitecredito": $object = new LimiteCredito();
			break;
		case "logdelproduto": $object = new LogDelProduto();
			break;
		case "logetiqueta": $object = new LogEtiqueta();
			break;
		case "loglogin": $object = new LogLogin();
			break;
		case "logpagina": $object = new LogPagina();
			break;
		case "logupdate": $object = new LogUpdate();
			break;
		case "manifestonfe": $object = new ManifestoNFe();
			break;
		case "maparesumo": $object = new MapaResumo();
			break;
		case "maparesumoimposto": $object = new MapaResumoImposto();
			break;
		case "marca": $object = new Marca();
			break;
		case "mensagem": $object = new Mensagem();
			break;
		case "mercadologico": $object = new Mercadologico();
			break;
		case "modeloemail": $object = new ModeloEmail();
			break;
		case "modelosaneamento": $object = new ModeloSaneamento();
			break;
		case "moeda": $object = new Moeda();
			break;
		case "movimento": $object = new Movimento();
			break;
		case "movimentolote": $object = new MovimentoLote();
			break;
		case "natoperacao": $object = new NatOperacao();
			break;
		case "natoperacaoestab": $object = new NatOperacaoEstab();
			break;
		case "natreceita": $object = new NatReceita();
			break;
		case "ncm": $object = new Ncm();
			break;
		case "ncmestado": $object = new NcmEstado();
			break;
		case "ncmunidade": $object = new NCMUnidade();
			break;
		case "negociacaopreco": $object = new NegociacaoPreco();
			break;
		case "notacomplemento": $object = new NotaComplemento();
			break;
		case "notadiversa": $object = new NotaDiversa();
			break;
		case "notafiscal": $object = new NotaFiscal();
			break;
		case "notafrete": $object = new NotaFrete();
			break;
		case "notafiscalimposto": $object = new NotaFiscalImposto();
			break;
		case "notafiscalpaulista": $object = new NotaFiscalPaulista(false);
			break;
		case "notafiscalreferenciada": $object = new NotaFiscalReferenciada();
			break;
		case "notafiscalservico": $object = new NotaFiscalServico();
			break;
		case "notafiscaleletronicaestabelecimento": $object = new notafiscaleletronicaestabelecimento();
			break;
		case "notificacao": $object = new Notificacao();
			break;
		case "notificacaousuario": $object = new NotificacaoUsuario();
			break;
		case "numeroserie": $object = new NumeroSerie();
			break;
		case "nutricional": $object = new Nutricional();
			break;
		case "ocorrencia": $object = new Ocorrencia();
			break;
		case "orcamento": $object = new Orcamento();
			break;
		case "orcamentopedido": $object = new OrcamentoPedido();
			break;
		case "ordemservico": $object = new OrdemServico();
			break;
		case "ordemservicoteste": $object = new OrdemServicoTeste();
			break;
		case "oferta": $object = new Oferta();
			break;
		case "ofertaestab": $object = new OfertaEstab();
			break;
		case "ofx": $object = new Ofx();
			break;
		case "operacaonota": $object = new OperacaoNota();
			break;
		case "outroscreditodebito": $object = new OutrosCreditoDebito();
			break;
		case "pais": $object = new Pais();
			break;
		case "paramcoletor": $object = new ParamColetor();
			break;
		case "paramcomissao": $object = new ParamComissao();
			break;
		case "paramestoque": $object = new ParamEstoque();
			break;
		case "parametro": $object = new Parametro();
			break;
		case "paramecommerce": $object = new ParamEcommerce();
			break;
		case "paramfiscal": $object = new ParamFiscal();
			break;
		case "paramfidelizacao": $object = new ParamFidelizacao();
			break;
		case "paramnotafiscal": $object = new ParamNotaFiscal();
			break;
		case "paramplanodecontas": $object = new ParamPlanodeContas();
			break;
		case "parampontooperacao": $object = new ParamPontoOperacao();
			break;
		case "parampontovenda": $object = new ParamPontoVenda();
			break;
		case "parampontovendausuario": $object = new ParamPontoVendaUsuario();
			break;
		case "pedido": $object = new Pedido();
			break;
		case "pedidodistrib": $object = new PedidoDistrib();
			break;
		case "perdcomp": $object = new PerDcomp();
			break;
		case "piscofins": $object = new PisCofins();
			break;
		case "planocontas": $object = new PlanoContas();
			break;
		case "pontovenda": $object = new PontoVenda();
			break;
		case "premio": $object = new Premio();
			break;
		case "prevenda": $object = new PreVenda();
			break;
		case "processo": $object = new Processo();
			break;
		case "prodconcorrente": $object = new ProdConcorrente();
			break;
		case "prodfornec": $object = new ProdFornec();
			break;
		case "produto": $object = new Produto();
			break;
		case "produtocompl": $object = new ProdutoCompl();
			break;
		case "produtoean": $object = new ProdutoEan();
			break;
		case "produtoestab": $object = new ProdutoEstab();
			break;
		case "produtoestabsaldo": $object = new ProdutoEstabSaldo();
			break;
		case "produtolocalizacao": $object = new ProdutoLocalizacao();
			break;
		case "programa": $object = new Programa();
			break;
		case "prosoft": $object = new Prosoft();
			break;
		case "recebepdv": $object = new RecebePdv();
			break;
		case "recebepdvlancto": $object = new RecebePdvLancto();
			break;
		case "receita": $object = new Receita();
			break;
		case "recibo": $object = new Recibo();
			break;
		case "regiao": $object = new Regiao();
			break;
		case "relatorio": $object = new Relatorio();
			break;
		case "relatoriocoluna": $object = new RelatorioColuna();
			break;
		case "relatoriofiltro": $object = new RelatorioFiltro();
			break;
		case "representante": $object = new Representante();
			break;
		case "restricao": $object = new Restricao();
			break;
		case "rma": $object = new Rma();
			break;
		case "sazonal": $object = new Sazonal();
			break;
		case "setorocorrencia": $object = new SetorOcorrencia();
			break;
		case "simprod": $object = new SimProd();
			break;
		case "situacaolancto": $object = new SituacaoLancto();
			break;
		case "statuscliente": $object = new StatusCliente();
			break;
		case "subcatlancto": $object = new SubCatLancto();
			break;
		case "subgrupo": $object = new SubGrupo();
			break;
		case "subocorrencia": $object = new SubOcorrencia();
			break;
		case "tv": $object = new TV();
			break;
		case "tabcontrol": $object = new TabControl();
			break;
		case "tabelapreco": $object = new TabelaPreco();
			break;
		case "tabelaajusteicms": $object = new TabelaAjusteIcms();
			break;
		case "tesouraria": $object = new Tesouraria();
			break;
		case "tesourariafinaliz": $object = new TesourariaFinaliz();
			break;
		case "tipodocumento": $object = new TipoDocumento();
			break;
		case "tipomensagem": $object = new TipoMensagem();
			break;
		case "transportadora": $object = new Transportadora();
			break;
		case "unidade": $object = new Unidade();
			break;
		case "usuaestabel": $object = new UsuaEstabel();
			break;
		case "usuapesquisa": $object = new UsuaPesquisa();
			break;
		case "usuarestricao": $object = new UsuaRestricao();
			break;
		case "usuario": $object = new Usuario();
			break;
		case "usuariodepartamento": $object = new UsuarioDepartamento();
			break;
		case "usuariologado": $object = new UsuarioLogado();
			break;
		case "usuatipomensagem": $object = new UsuaTipoMensagem();
			break;
		case "vparceiro": $object = new VParceiro();
			break;
		case "vasilhame": $object = new Vasilhame();
			break;
		case "valorpadrao": $object = new ValorPadrao();
			break;
	}

	if($connection !== NULL){ // Verifica conexao
		$object->setconnection($connection);
	}
	if($key !== NULL){ // Verifica chave
		$obKeys = $object->getprimarykey();
		if(is_array($obKeys)){ // Chave composta
			if(!is_array($key)){
				$key = array($key);
			}
			foreach($obKeys as $i => $obKey){
				call_user_func(array($object, "set".$obKey), $key[$i]);
			}
		}else{ // Chave simples
			call_user_func(array($object, "set".$obKeys), (is_array($key) ? $key[0] : $key));
		}
		$object->searchbyobject();
	}

	if(is_object($object)){
		$key_aux = (is_array($key) ? implode(";", $key) : $key);
		$objectbytable_memory[$table][$key_aux] = $object;
	}

	return $object;
}

function param($idparam, $codparam, $con = NULL){
	$parametro = objectbytable("parametro", array($idparam, $codparam), $con);
	return $parametro->getvalor();
}

function parceiro($operacaonota, $codparceiro = NULL){
	$con = $operacaonota->getconnection();
	switch($operacaonota->getparceiro()){
		case "C": return objectbytable("cliente", $codparceiro, $con);
			break;
		case "E": return objectbytable("estabelecimento", $codparceiro, $con);
			break;
		case "F": return objectbytable("fornecedor", $codparceiro, $con);
			break;
	}
}

function pdfwidth($orientation, $width){
	$pdf_totalwidth = array("P" => 190, "L" => 277); // Largura total da folha em PDF (formato retrato e paisagem)

	$pos = strpos($width, "%");
	if($pos !== FALSE){
		return substr($width, 0, $pos + 1) * $pdf_totalwidth[$orientation] / 100;
	}else{
		$pos = strpos($width, "px");
		if($pos !== FALSE){
			return substr($width, 0, $pos + 1);
		}else{
			return $width;
		}
	}
}

function plutoean13($plu){
	$ean = str_pad($plu, 12, "0", STR_PAD_LEFT);
	$k1 = substr($ean, 1, 1) + substr($ean, 3, 1) + substr($ean, 5, 1) + substr($ean, 7, 1) + substr($ean, 9, 1) + substr($ean, 11, 1);
	$k2 = substr($ean, 0, 1) + substr($ean, 2, 1) + substr($ean, 4, 1) + substr($ean, 6, 1) + substr($ean, 8, 1) + substr($ean, 10, 1) + substr($ean, 12, 1);
	$kt = 3 * $k1 + $k2;
	$digito = 10 * (floor($kt / 10) + 1) - $kt;
	if($digito == 10){
		$digito = 0;
	}
	return str_pad($plu.$digito, 8, "0", STR_PAD_LEFT);
}

function pre($arr){
	print("<pre>");
	print_r($arr);
	print("</pre>");
}

function pretty_byte($size){
	$unit = array("B", "KB", "MB", "GB", "TB", "PB");
	return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2)." ".$unit[$i];
}

// Formata o JSON e deixa ele bonitao
function pretty_json($json){
	$result = '';
	$pos = 0;
	$strLen = strlen($json);
	$indentStr = '&nbsp;&nbsp;&nbsp;&nbsp;';
	$newLine = "<br>";
	$prevChar = '';
	$outOfQuotes = true;
	for($i = 0; $i <= $strLen; $i++){
		$char = substr($json, $i, 1);
		if($char == '"' && $prevChar != '\\'){
			$outOfQuotes = !$outOfQuotes;
		}elseif(($char == '}' || $char == ']') && $outOfQuotes){
			$result .= $newLine;
			$pos --;
			for($j = 0; $j < $pos; $j++){
				$result .= $indentStr;
			}
		}
		$result .= $char;
		if(($char == ',' || $char == '{' || $char == '[') && $outOfQuotes){
			$result .= $newLine;
			if($char == '{' || $char == '['){
				$pos ++;
			}
			for($j = 0; $j < $pos; $j++){
				$result .= $indentStr;
			}
		}
		$prevChar = $char;
	}
	return $result;
}

// Le um arquivo da maquina local ou servidor
function read_file($file_name, $on_server = TRUE, $line_break = "\r\n"){
	$arr_line = array();
	if($on_server){
		if(file_exists($file_name)){
			$arr_line = explode($line_break, (file_get_contents($file_name)));
		}
	}else{

	}
	return $arr_line;
}

function recalcular_juros_lancamento($codlancto, Connection $con){
	if(param("FINANCEIRO", "ATUALIZAJUROSLANC", $con) === "S"){
		$sql = "UPDATE lancamento SET ";
		$sql .= "	valorjuros = ((CURRENT_DATE - lancamento.dtvencto) * (((lancamento.valorparcela + lancamento.valoracresc - lancamento.valordescto) * (CASE WHEN banco.valormoradiaria > 0 THEN banco.valormoradiaria ELSE estabelecimento.taxajuromensal END) / 100) / 30)) ";
		$sql .= "FROM estabelecimento, banco, cliente ";
		$sql .= "WHERE pagrec = 'R' ";
		$sql .= "	AND prevreal = 'R' ";
		$sql .= "	AND status = 'A' ";
		$sql .= "	AND dtvencto < CURRENT_DATE ";
		$sql .= "	AND estabelecimento.codestabelec = lancamento.codestabelec ";
		$sql .= "	AND (CASE WHEN lancamento.codbanco IS NULL THEN TRUE ELSE banco.codbanco = lancamento.codbanco END) ";
		$sql .= "	AND (lancamento.tipoparceiro = 'C' AND lancamento.codparceiro = cliente.codcliente) ";
		$sql .= " AND lancamento.codlancto = {$codlancto} ";

		$con->exec($sql);
	}
}

function removeformat($text){
	return str_replace(array(" ", "-", ".", "/", "\\", ":", ",", "(", ")", "º", "[", "]", "'"), "", $text);
}

function removespecial($text){
	$old = array("á", "à", "ã", "â", "é", "è", "ê", "í", "ì", "î", "ó", "ò", "õ", "ô", "ú", "ù", "û", "ü", "ç", "Á", "À", "Ã", "Â", "É", "È", "Ê", "Í", "Ì", "Î", "Ó", "Ò", "Õ", "Ô", "Ú", "Ù", "Û", "Ü", "Ç", "ª", "º");
	$new = array("a", "a", "a", "a", "e", "e", "e", "i", "i", "i", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "E", "E", "E", "I", "I", "I", "O", "O", "O", "O", "U", "U", "U", "U", "C", "a", "o");
	$text = str_replace($old, $new, $text);
	$text = iconv("UTF-8", "UTF-8//IGNORE", utf8_encode($text));
	$text = str_replace($old, $new, $text);
	return $text;
}

function require_table_class($table_name){
	require_file("class/".$table_name.".class.php");
}

function restricao($codrestricao){
	return in_array($codrestricao, $_SESSION["WRestricao"]);
}

function scanmob_param($con){
	$param = param("INTEGRACAO", "SCANMOB", $con);
	if(strlen(trim($param)) > 0){
		$param = explode(";", $param);
		$param = array(
			"email" => trim($param[0]),
			"senha" => trim($param[1])
		);
	}else{
		$param = FALSE;
	}
	return $param;
}

function script($script){
	return "<script type=\"text/javascript\"> ".$script." </script>";
}

function setprogress($percent, $text = "", $force = FALSE){
	$time = time() + microtime();
	if($force || !isset($_SESSION["PROGRESS"]["TIME"]) || $time - $_SESSION["PROGRESS"]["TIME"] >= 1){
		$text = removespecial($text);
		$_SESSION["PROGRESS"] = array(
			"PERCENT" => $percent,
			"TEXT" => utf8_encode($text),
			"TIME" => $time
		);
		session_write_close();
		@session_start();
		/*
		  $session_orig = $_SESSION;
		  $session_prog = array(
		  "PERCENT" => $percent,
		  "TEXT" => utf8_encode($text),
		  "TIME" => $time
		  );
		  session_unset();
		  $_SESSION["PROGRESS"] = $session_prog;
		  session_write_close();
		  @session_start();
		  $_SESSION = $session_orig;
		  $_SESSION["PROGRESS"] = $session_prog;
		 */
	}
}

function setvalue($arr_id, $arr_value, $tag_javascript = TRUE, $attribute = "val"){
	if(!is_array($arr_id)){
		$arr_id = array($arr_id);
	}
	if(!is_array($arr_value)){
		$arr_value_aux = array();
		for($i = 0; $i < sizeof($arr_id); $i++){
			$arr_value_aux[$i] = $arr_value;
		}
		$arr_value = $arr_value_aux;
	}
	foreach($arr_id as $i => $id){
		$script .= "$(\"#".$id."\").".$attribute."(\"".$arr_value[$i]."\");";
	}
	return $tag_javascript ? script($script) : $script;
}

function sql_tipopreco($tipopreco, $oferta = TRUE, $alias = "preco"){
	if($tipopreco != "A"){
		$tipopreco = "V";
	}
	$str_preco = "produtoestab.preco".($tipopreco == "A" ? "atc" : "vrj");
	if($oferta){
		$sql = "(CASE WHEN ".$str_preco."of > 0 THEN ".$str_preco."of ELSE ".$str_preco." END)";
	}else{
		$sql = $str_preco;
	}
	if(strlen($alias) > 0){
		$sql .= " AS ".$alias;
	}
	return $sql;
}

// Retorna o texto do RGB apartir de um array
function stringrgb($arr){
	return "RGB(".$arr[0].",".$arr[1].",".$arr[2].")";
}

// O mesmo que o str_replace, porem com limite
function str_replace_limit($find, $replacement, $subject, $limit = 0, &$count = 0){
	if($limit == 0){
		return str_replace($find, $replacement, $subject, $count);
	}
	$ptn = "/".preg_quote($find, "/")."/";
	return preg_replace($ptn, $replacement, $subject, $limit, $count);
}

// Retorna a sugestao de compra do produto
// Os parametros podem ser passados os objetos ou os codigos
function sugestaocompra($con, $estabelecimento, $produto, $fornecedor = NULL){
	if(is_object($estabelecimento)){
		$estabelecimento = $estabelecimento->getcodestabelec();
	}
	if(is_object($produto)){
		$produto = $produto->getcodproduto();
	}
	if(!is_null($fornecedor) && is_object($fornecedor)){
		$fornecedor = $fornecedor->getcodfornec();
	}

	if(is_null($fornecedor)){
		$query = "SELECT sugestaocompra(".$estabelecimento.",".$produto.")";
	}else{
		$query = "SELECT sugestaocompra(".$estabelecimento.",".$produto.",".$fornecedor.")";
	}

	$res = $con->query($query);
	return ceil($res->fetchColumn());
}

// Retorna o preco do produto de acordo com a tabela de precos
function tabelapreco($con, $codtabela, $codestabelec, $codproduto){
	$tabelapreco = objectbytable("tabelapreco", $codtabela, $con);
	$ittabelapreco = objectbytable("ittabelapreco", array($codtabela, $codproduto), $con);
	if($ittabelapreco->exists()){
		$percpreco = $ittabelapreco->getpercpreco();
	}else{
		$percpreco = $tabelapreco->getpercpreco();
	}
	$produtoestab = objectbytable("produtoestab", array($codestabelec, $codproduto), $con);
	if(param("PONTOVENDA", "HABILITAPRECOPDV", $con) == "S" && $produtoestab->getprecopdv() > 0){
		$preco = ($produtoestab->getprecopdv());
	}else{
		switch($tabelapreco->gettipopreco()){
			case "A":
				$preco = ($produtoestab->getprecoatcof() > 0 ? $produtoestab->getprecoatcof() : $produtoestab->getprecoatc());
				break;
			case "C":
				$preco = $produtoestab->getcustorep();
				break;
			case "V":
				$preco = ($produtoestab->getprecovrjof() > 0 ? $produtoestab->getprecovrjof() : $produtoestab->getprecovrj());
				break;
		}
	}

	return $preco * ($percpreco / 100);
}

// Verifica se existe quebra de linha em um texto, e substitue o caracter da quebra por '\\n' (Essa funcao e usado para mandar texto com quebras para o javascript ou html)
function textwarp($text){
	$aux = "";
	for($i = 0; $i < strlen($text); $i++){
		if(ord(substr($text, $i, 1)) === 10 || ord(substr($text, $i, 1)) === 13){
			$aux .= "\\n";
		}else{
			$aux .= substr($text, $i, 1);
		}
	}
	return $aux;
}

// Trunca um numero decimal
function trunc($number, $decimals){
	$number = vsprintf("%0.".$decimals."f", $number * pow(10, $decimals));
	$pos = strpos($number, ".");
	if($pos !== FALSE){
		$number = substr($number, 0, ($pos === FALSE ? NULL : $pos));
	}
	$number = $number / pow(10, $decimals);
	return $number;
}

// Deixa a primeira letra maiuscula de todas as palavras de um sentenca
function ucfirst_sentence($str){
	return preg_replace('/\b(\w)/e', 'strtoupper("$1")', strtolower($str));
}

// Converte um texto de UTF8 para WIN1252
function utf8_to_win1252($data){
	return iconv("UTF-8", "Windows-1252", $data);
}

function valid_chave_nfe($chavenfe){
	$digito_atual = substr($chavenfe, 43, 1);
	$chavenfe = substr($chavenfe, 0, 43);
	$chavenfe_rev = strrev($chavenfe);
	$ponderacao = 0;
	$peso = 2;
	for($i = 0; $i < strlen($chavenfe_rev); $i++){
		$ponderacao += substr($chavenfe_rev, $i, 1) * $peso;
		if(++$peso > 9){
			$peso = 2;
		}
	}
	$digito = 11 - ($ponderacao % 11);
	if($digito > 9){
		$digito = 0;
	}
	if($digito_atual == $digito){
		return TRUE;
	}else{
		return FALSE;
	}
}

/*
  Autor............: Jesus
  Data.............: 24/04/2012
  Objetivo.........: validar o codigo de barras (boleto bancario,boleto concessionarias , boleto tributos)
  Parametros.......:
  $codigobarras: codigo de barras a ser validado
  $tipo........: tipo do codigo de barras ("B"->boleto,"C"->concessionaria,tributo)
  retono...........: retorna TRUE se for valido, coso contrario retorna FALSE
 */

function valid_codigo_barras_boletos($codigobarras, $tipo){
	$codigobarras = removeformat($codigobarras);
	if($tipo == "B"){
		$valido = TRUE;
		$bloco1 = substr($codigobarras, 0, 10);
		$bloco2 = substr($codigobarras, 10, 11);
		$bloco3 = substr($codigobarras, 21, 11);
		if(strlen($bloco1) == 10){
			$digitobloco = calcula_digito_mod10(substr($bloco1, 0, 9));
			$valido = ($digitobloco == substr($bloco1, 9, 1));
		}
		if($valido && strlen($bloco2) == 11){
			$digitobloco = calcula_digito_mod10(substr($bloco2, 0, 10));
			$valido = ($digitobloco == substr($bloco2, 10, 1));
		}
		if($valido && strlen($bloco3) == 11){
			$digitobloco = calcula_digito_mod10(substr($bloco3, 0, 10));
			$valido = ($digitobloco == substr($bloco3, 10, 1));
		}
		if($valido && strlen($codigobarras) == 47){
			$bancofavorecido = substr($codigobarras, 0, 3);
			$moeda = substr($codigobarras, 3, 1);
			$fatorvencimento = substr($codigobarras, 33, 4);
			$valortitulo = substr($codigobarras, 37, 10);
			$campolivre = substr($codigobarras, 4, 5).substr($codigobarras, 10, 10).substr($codigobarras, 21, 10);
			$digitogeral = substr($codigobarras, 32, 1);
			$digitogeralcalculado = calcula_digito_mod11($bancofavorecido.$moeda.$fatorvencimento.$valortitulo.$campolivre);
			$valido = ($digitogeral == $digitogeralcalculado);
		}
		return $valido;
	}elseif($tipo == "C"){
		$valido = TRUE;
		$bloco1 = substr($codigobarras, 0, 12);
		$bloco2 = substr($codigobarras, 12, 12);
		$bloco3 = substr($codigobarras, 24, 12);
		$bloco4 = substr($codigobarras, 36, 12);
		if(strlen($bloco1) == 12){
			$digitobloco = calcula_digito_mod11(substr($bloco1, 0, 11));
			$valido = ($digitobloco == substr($bloco1, 11, 1));
			if(!$valido){
				$digitobloco = calcula_digito_mod10(substr($bloco1, 0, 11));
				$valido = ($digitobloco == substr($bloco1, 11, 1));
			}
		}
		if($valido && strlen($bloco2) == 12){
			$digitobloco = calcula_digito_mod11(substr($bloco2, 0, 11));
			$valido = ($digitobloco == substr($bloco2, 11, 1));
			if(!$valido){
				$digitobloco = calcula_digito_mod10(substr($bloco2, 0, 11));
				$valido = ($digitobloco == substr($bloco2, 11, 1));
			}
		}
		if($valido && strlen($bloco3) == 12){
			$digitobloco = calcula_digito_mod11(substr($bloco3, 0, 11));
			$valido = ($digitobloco == substr($bloco3, 11, 1));
			if(!$valido){
				$digitobloco = calcula_digito_mod10(substr($bloco3, 0, 11));
				$valido = ($digitobloco == substr($bloco3, 11, 1));
			}
		}
		if($valido && strlen($bloco4) == 12){
			$digitobloco = calcula_digito_mod11(substr($bloco4, 0, 11));
			$valido = ($digitobloco == substr($bloco4, 11, 1));
			if(!$valido){
				$digitobloco = calcula_digito_mod10(substr($bloco4, 0, 11));
				$valido = ($digitobloco == substr($bloco4, 11, 1));
			}
		}
		return $valido;
	}else{
		return FALSE;
	}
}

// Verifica se o CPF informado e valido
function valid_cpf($cpf){
	$cpf = removeformat($cpf);
	if(!is_numeric($cpf) || strlen($cpf) != 11 || $cpf == "00000000000" || $cpf == "11111111111" || $cpf == "22222222222" || $cpf == "33333333333" || $cpf == "44444444444" || $cpf == "55555555555" || $cpf == "66666666666" || $cpf == "77777777777" || $cpf == "88888888888" || $cpf == "99999999999"){
		return FALSE;
	}else{
		for($t = 9; $t < 11; $t++){
			for($d = 0, $c = 0; $c < $t; $c++){
				$d += $cpf{$c} * (($t + 1) - $c);
			}
			$d = ((10 * $d) % 11) % 10;
			if($cpf{$c} != $d){
				return false;
			}
		}
		return true;
	}
}

function valid_cnpj($cnpj){
	$cnpj = removeformat($cnpj);
	if(strlen($cnpj) <> 14 or ! is_numeric($cnpj)){
		return FALSE;
	}
	$j = 5;
	$k = 6;
	$soma1 = "";
	$soma2 = "";
	for($i = 0; $i < 13; $i++){
		$j = ($j == 1 ? 9 : $j);
		$k = ($k == 1 ? 9 : $k);
		$soma2 += ($cnpj{$i} * $k);
		if($i < 12){
			$soma1 += ($cnpj{$i} * $j);
		}
		$k--;
		$j--;
	}
	$digito1 = ($soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11);
	$digito2 = ($soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11);
	return (($cnpj{12} == $digito1) and ( $cnpj{13} == $digito2));
}

function valid_date($date){ // Verifica se a data e valida
	$day = date_day($date);
	$month = date_month($date);
	$year = date_year($date);
	if(!is_numeric($day) || !is_numeric($month) || !is_numeric($year)){
		return FALSE;
	}elseif($month < 1 || $month > 12){
		return FALSE;
	}elseif(($day < 1) || ($day > 30 && ($month == 4 || $month == 6 || $month == 9 || $month == 11 )) || ($day > 31)){
		return FALSE;
	}elseif($month == 2 && ($day > 29 || ($day > 28 && (floor($year / 4) != $year / 4)))){
		return FALSE;
	}else{
		return TRUE;
	}
}

function valid_ean($ean){ // Verifica se um ean e valido
	if(!is_string($ean) || strlen($ean) < 8){
		return FALSE;
	}
	$par = 0;
	$imp = 0;
	$ultE = substr($ean, strlen($ean) - 1, 1);
	for($i = 0; $i < strlen($ean) - 1; $i++){
		if($i % 2 == 0){
			$par += substr($ean, $i, 1);
		}else{
			$imp += substr($ean, $i, 1);
		}
	}
	$imp = $imp * 3;
	$tot = $par + $imp;
	$ultT = substr($tot, strlen($tot) - 1, 1);
	$con = 10 - $ultT;
	if($con == 10){
		$con = 0;
	}
	return ($con == $ultE);
}

function value_check($value, $check){ // Verifica se um valor existe dentro de um dos valores de um vetor (paremtro 'check')
	if(is_string($value) && ((is_array($check) && in_array($value, $check)) || (is_string($check) && $value == $check))){
		return $value;
	}else{
		return NULL;
	}
}

function value_date($value){ // Verifica de a data e valida e retorna no formato 'Y-m-d'
	if(substr($value, 4, 1) == "-" && substr($value, 7, 1) == "-" && strlen($value) == 10){
		$value = implode("/", array_reverse(explode("-", $value)));
	}
	if(valid_date($value)){
		return date_year($value)."-".date_month($value)."-".date_day($value);
	}else{
		return NULL;
	}
}

function value_datetime($value){ // Verifica de a data e hora e valida e retorna no formato 'Y-m-d H:i:s'
	$value = explode(" ", $value);
	$date = value_date($value[0]);
	$time = value_time($value[1]);
	if(strlen($date) === 0){
		return null;
	}
	if(strlen($time) === 0){
		$time = "00:00:00";
	}
	return "{$date} {$time}";
}

function value_numeric($value){ // Verifica de um numero e valido e retona com o separador decimal '.'
	$value = trim($value);
	$v = strpos($value, ",");
	$p = strpos($value, ".");
	if($v === FALSE && $p === FALSE){ // Valor inteiro sem separador de decimal e milhar (nao precisa de tratamento)
	}elseif($v !== FALSE && $p === FALSE){ // Virgula no separador decimal e sem separador de milhar
		$value = str_replace(",", ".", $value);
	}elseif($v === FALSE && $p !== FALSE){ // Ponto no separador de decimal e sem separador de milhar (nao precisa de tratamento)
	}elseif($v > $p){ // Virgula no separador de decimal e ponto no separador de milhar
		$value = str_replace(".", "", $value);
		$value = str_replace(",", ".", $value);
	}elseif($p > $v){ // Ponto no separador de decimal e virgula no separador de milhar
		$value = str_replace(",", "", $value);
	}
//	if((string)(float) $value == $value){
	if(is_numeric($value)){
		if(strcmp($value, round($value)) == 0 && $value < 2147483647){
			$value = (int) $value;
		}else{
			$value = (float) $value;
		}
		return $value;
	}else{
		return NULL;
	}
}

function value_string($value, $length = NULL){ // Verifica se o texto e valido e corta fora o resto do texto de nao for do tamanho maximo
	if(is_string($value) || is_numeric($value)){
		//$value = trim($value);
		return ($length != NULL ? substr($value, 0, $length) : $value);
	}else{
		return NULL;
	}
}

// Verifica se o horario e valido no formato 'hh:mm:ss' ou 'hh:mm'
function value_time($value){
	$separator = ":";
	if(strlen($value) == 12){
		$value = substr($value, 0, 8);
	}
	if((strlen($value) == 8) && (substr($value, 2, 1) == $separator) && (substr($value, 5, 1) == $separator)){
		$hou = (int) substr($value, 0, 2);
		$min = (int) substr($value, 3, 2);
		$sec = (int) substr($value, 6, 2);
	}elseif((strlen($value) == 5) && (substr($value, 2, 1) == $separator)){
		$hou = (int) substr($value, 0, 2);
		$min = (int) substr($value, 3, 2);
		$sec = 0;
	}else{
		return NULL;
	}
	if(($hou < 0 || $hou > 23) || ($min < 0 || $min > 59) || ($sec < 0 || $sec > 59)){
		return NULL;
	}else{
		return str_pad($hou, 2, "0", STR_PAD_LEFT).$separator.str_pad($min, 2, "0", STR_PAD_LEFT).$separator.str_pad($sec, 2, "0", STR_PAD_LEFT);
	}
}

// Converte um texto de WIN1252 para UTF8
function win1252_to_utf8($data){
	return iconv("Windows-1252", "UTF-8", $data);
}

// Cria um arquivo na maquina local ou servidor
function write_file($file_name, $arr_line, $on_server = FALSE, $mode = "w+", $force_download = FALSE){
	if(!is_array($arr_line)){
		$arr_line = array($arr_line);
	}
	if($on_server){
		$file_name_temp = "../temp/".date("YmdHis");
		$file = fopen($file_name_temp, $mode);
		fwrite($file, implode("\r\n", $arr_line));
		fclose($file);
		@chmod($file_name_temp, 0777);
		if(strtolower(substr($file_name, 0, 6)) === "ftp://"){
			$ftp_full_url = substr($file_name, 6);
			$ftp_arr_url = explode("@", $ftp_full_url);
			if(count($ftp_arr_url) === 1){
				$ftp_login = array();
				$ftp_url = $ftp_full_url;
			}else{
				$ftp_login = explode(":", $ftp_arr_url[0]);
				$ftp_url = $ftp_arr_url[1];
			}
			$ftp_dir = explode("/", $ftp_url);
			$ftp_host = array_shift($ftp_dir);
			$ftp_file = array_pop($ftp_dir);
			$ftp = ftp_connect($ftp_host);
			if($ftp === FALSE){
				return FALSE;
			}
			if(!ftp_login($ftp, $ftp_login[0], $ftp_login[1])){
				return FALSE;
			}
			foreach($ftp_dir as $dir){
				if(!ftp_chdir($ftp, $dir)){
					return FALSE;
				}
			}
			if(!ftp_put($ftp, $ftp_file, $file_name_temp, FTP_BINARY)){
				return FALSE;
			}
			unlink($file_name_temp);
		}else{
			if(file_exists($file_name)){
				unlink($file_name);
			}
			rename($file_name_temp, $file_name);
		}
		return TRUE;
	}else{
		if($force_download){
			$dirname = __DIR__."/../temp/download/";
			if(!is_dir($dirname)){
				mkdir($dirname, 0777, true);
			}
			$filename = $dirname.basename($file_name);
			$file = fopen($filename, "w+");
			fwrite($file, implode("\r\n", $arr_line));
			fclose($file);
			download($filename);
		}else{
			$file_name = str_replace("\\", "\\\\", $file_name);
			foreach($arr_line as $i => $line){
				$line = str_replace("\\", "\\\\", $line);
				$line = str_replace("\"", "\\\"", $line);
				$line = str_replace("\r\n", "\\r\\n", $line);
				$line = "\"".$line."\"";
				$arr_line[$i] = $line;
			}
			return script("write_file(\"".$file_name."\",[".implode(",", $arr_line)."],\"".$mode."\")");
		}
	}
}

function wsfiler(Connection $con, $codestabelec, $nome, $conteudo){
	$arquivo = objectbytable("arquivo", NULL, $con);
	$arquivo->setcodestabelec($codestabelec);
	$arquivo->setnome($nome);
	$arquivo->setconteudo($conteudo);
	return $arquivo->save();
}

function xml2array($xml){
	if(is_string($xml)){
		$xml = @simplexml_load_string($xml);
	}
	if($xml === FALSE){
		return FALSE;
	}
	$json = json_encode($xml);
	$array = json_decode($json, TRUE);
	return $array;
}

function array_sort($array, $on, $order = SORT_ASC){ // array_sort(array,coluna,ordenacao)
	$new_array = array();
	$sortable_array = array();

	if(count($array) > 0){
		foreach($array as $k => $v){
			if(is_array($v)){
				foreach($v as $k2 => $v2){
					if($k2 == $on){
						$sortable_array[$k] = $v2;
					}
				}
			}else{
				$sortable_array[$k] = $v;
			}
		}

		switch($order){
			case SORT_ASC:
				asort($sortable_array);
				break;
			case SORT_DESC:
				arsort($sortable_array);
				break;
		}

		foreach($sortable_array as $k => $v){
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

// Utilizado para PHP 5.2 ou anterior
if(!function_exists('parse_ini_string')){

	function parse_ini_string($str, $ProcessSections = false){
		$lines = explode(" ", $str);
		$return = Array();
		$inSect = false;
		foreach($lines as $line){
			$line = trim($line);
			if(!$line || $line[0] == "#" || $line[0] == ";")
				continue;
			if($line[0] == "[" && $endIdx = strpos($line, "]")){
				$inSect = substr($line, 1, $endIdx - 1);
				continue;
			}
			if(!strpos($line, '=')) // (We don't use "=== false" because value 0 is not valid as well)
				continue;

			$tmp = explode("=", $line, 2);
			if($ProcessSections && $inSect)
				$return[$inSect][trim($tmp[0])] = ltrim($tmp[1]);
			else
				$return[trim($tmp[0])] = ltrim($tmp[1]);
		}
		return $return;
	}

}

function pGunzip1($data){
	$len = strlen($data);
	if($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")){
		$msg = "Não é dado no formato GZIP.";
		$this->pSetError($msg);
		return false;
	}
	$method = ord(substr($data, 2, 1));  // metodo de compressão
	$flags = ord(substr($data, 3, 1));  // Flags
	if($flags & 31 != $flags){
		$msg = "Não são permitidos bits reservados.";
		$this->pSetError($msg);
		return false;
	}
	// NOTA: $mtime pode ser negativo (limitações nos inteiros do PHP)
	$mtime = unpack("V", substr($data, 4, 4));
	$mtime = $mtime[1];
	$headerlen = 10;
	$extralen = 0;
	$extra = "";
	if($flags & 4){
		// dados estras prefixados de 2-byte no cabeçalho
		if($len - $headerlen - 2 < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$extralen = unpack("v", substr($data, 8, 2));
		$extralen = $extralen[1];
		if($len - $headerlen - 2 - $extralen < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$extra = substr($data, 10, $extralen);
		$headerlen += 2 + $extralen;
	}
	$filenamelen = 0;
	$filename = "";
	if($flags & 8){
		// C-style string
		if($len - $headerlen - 1 < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$filenamelen = strpos(substr($data, $headerlen), chr(0));
		if($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$filename = substr($data, $headerlen, $filenamelen);
		$headerlen += $filenamelen + 1;
	}
	$commentlen = 0;
	$comment = "";
	if($flags & 16){
		// C-style string COMMENT data no cabeçalho
		if($len - $headerlen - 1 < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$commentlen = strpos(substr($data, $headerlen), chr(0));
		if($commentlen === false || $len - $headerlen - $commentlen - 1 < 8){
			$msg = "Formato de cabeçalho inválido.";
			$this->pSetError($msg);
			return false;
		}
		$comment = substr($data, $headerlen, $commentlen);
		$headerlen += $commentlen + 1;
	}
	$headercrc = "";
	if($flags & 2){
		// 2-bytes de menor ordem do CRC32 esta presente no cabeçalho
		if($len - $headerlen - 2 < 8){
			$msg = "Dados inválidos.";
			$this->pSetError($msg);
			return false;
		}
		$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
		$headercrc = unpack("v", substr($data, $headerlen, 2));
		$headercrc = $headercrc[1];
		if($headercrc != $calccrc){
			$msg = "Checksum do cabeçalho falhou.";
			$this->pSetError($msg);
			return false;
		}
		$headerlen += 2;
	}
	// Rodapé GZIP
	$datacrc = unpack("V", substr($data, -8, 4));
	$datacrc = sprintf('%u', $datacrc[1] & 0xFFFFFFFF);
	$isize = unpack("V", substr($data, -4));
	$isize = $isize[1];
	// decompressão
	$bodylen = $len - $headerlen - 8;
	if($bodylen < 1){
		$msg = "BUG da implementação.";
		$this->pSetError($msg);
		return false;
	}
	$body = substr($data, $headerlen, $bodylen);
	$data = "";
	if($bodylen > 0){
		switch($method){
			case 8:
				// Por hora somente é suportado esse metodo de compressão
				$data = gzinflate($body, null);
				break;
			default:
				$msg = "Método de compressão desconhecido (não suportado).";
				$this->pSetError($msg);
				return false;
		}
	}  // conteudo zero-byte é permitido
	// Verificar CRC32
	$crc = sprintf("%u", crc32($data));
	$crcOK = $crc == $datacrc;
	$lenOK = $isize == strlen($data);
	if(!$lenOK || !$crcOK){
		$msg = ( $lenOK ? '' : 'Verificação do comprimento FALHOU. ').( $crcOK ? '' : 'Checksum FALHOU.');
		$this->pSetError($msg);
		return false;
	}
	return $data;
}

if(!function_exists('split')){

	function split($string){
		return str_split($string);
	}

}

function notafiscal_idtable($operacao){
	$idtable = "";
	switch($operacao){
		case "AE":
			$idtable = "NFAjuste";
			break;
		case "AS":
			$idtable = "NFAjusteSaida";
			break;
		case "CP":
			$idtable = "NFEntrada";
			break;
		case "DC":
			$idtable = "NFDevCliente";
			break;
		case "DF":
			$idtable = "NFDevFornecedor";
			break;
		case "EX":
			$idtable = "NfExportacao";
			break;
		case "IM":
			$idtable = "NFImportacao";
			break;
		case "PD":
			$idtable = "NFPerda";
			break;
		case "PR":
			$idtable = "NFProdutor";
			break;
		case "RC":
			$idtable = "NFRemessaCli";
			break;
		case "RF":
			$idtable = "NFRemessaFor";
			break;
		case "TE":
			$idtable = "CadNotaFiscalTE";
			break;
		case "TS":
			$idtable = "CadNotaFiscalTS";
			break;
		case "VD":
			$idtable = "NFSaida";
			break;
		case "NC":
			$idtable = "NotaConsumidor";
			break;
		case "SS":
			$idtable = "NotaServicoSS";
			break;
		case "SE":
			$idtable = "NotaServicoES";
			break;
		default:
			return false;
	}
	return $idtable;
}

function pedido_idtable($operacao){
	$idtable = "";
	switch($operacao){
		case "CP":
			$idtable = "PedCompra";
			break;
		case "DC":
			$idtable = "PedDevCliente";
			break;
		case "DF":
			$idtable = "PedDevFornecedor";
			break;
		case "EX":
			$idtable = "PedExportacao";
			break;
		case "IM":
			$idtable = "PedImportacao";
			break;
		case "PD":
			$idtable = "PedPerda";
			break;
		case "PR":
			$idtable = "PedProdutor";
			break;
		case "RC":
			$idtable = "PedRemessaCli";
			break;
		case "RF":
			$idtable = "PedRemessaFor";
			break;
		case "TE":
			$idtable = "CadPedidoTE";
			break;
		case "TS":
			$idtable = "CadPedidoTS";
			break;
		case "VD":
			$idtable = "PedVenda";
			break;
		default:
			return false;
	}
	return $idtable;
}

function calcula_vendamedia($con, $codproduto, $codestabelec, $data1, $data2){
	$query = "SELECT COUNT(*) ";
	$query .= "FROM diastrabalhados ";
	$query .= "WHERE data >= '$data1' AND data <= '$data2' ";
	if(strlen($codestabelec) > 0){
		$query .= " AND codestabelec = $codestabelec";
	}
	$res = $con->query($query);
	$dias_trabalhados = $res->fetchColumn();

	$query = "SELECT (SUM(quantidade) / $dias_trabalhados) ";
	$query .= "FROM consvendadia ";
	$query .= "WHERE codproduto = $codproduto AND dtmovto >= '$data1' AND dtmovto <= '$data2' ";
	if(strlen($codestabelec) > 0){
		$query .= " AND codestabelec = $codestabelec";
	}
	$res = $con->query($query);

	return $res->fetchColumn();
}

function modulo_10_dda($num){
	$numtotal10 = 0;
	$fator = 2;

	// Separacao dos numeros
	for($i = strlen($num); $i > 0; $i--){
		// pega cada numero isoladamente
		$numeros[$i] = substr($num, $i - 1, 1);
		// Efetua multiplicacao do numero pelo (falor 10)
		// 2002-07-07 01:33:34 Macete para adequar ao Mod10 do Ita�
		$temp = $numeros[$i] * $fator;
		$temp0 = 0;
		foreach(preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY) as $k => $v){
			$temp0 += $v;
		}
		$parcial10[$i] = $temp0; //$numeros[$i] * $fator;
		// monta sequencia para soma dos digitos no (modulo 10)
		$numtotal10 += $parcial10[$i];
		if($fator == 2){
			$fator = 1;
		}else{
			$fator = 2; // intercala fator de multiplicacao (modulo 10)
		}
	}

	// v�rias linhas removidas, vide fun��o original
	// Calculo do modulo 10
	$resto = $numtotal10 % 10;
	$digito = 10 - $resto;
	if($resto == 0){
		$digito = 0;
	}

	return $digito;
}

function modulo_11BL($num, $base=9, $r=0)  {
	$soma = 0;
	$fator = 2;

	/* Separacao dos numeros */
	for ($i = strlen($num); $i > 0; $i--) {
		// pega cada numero isoladamente
		$numeros[$i] = substr($num,$i-1,1);
		// Efetua multiplicacao do numero pelo falor
		$parcial[$i] = $numeros[$i] * $fator;
		// Soma dos digitos
		$soma += $parcial[$i];
		if ($fator == $base) {
			// restaura fator de multiplicacao para 2
			$fator = 1;
		}
		$fator++;
	}

	/* Calculo do modulo 11 */
	if ($r == 0) {
		$soma *= 10;
		$digito = $soma % 11;
		if ($digito == 10) {
			$digito = 0;
		}
		return $digito;
	} elseif ($r == 1){
		$resto = $soma % 11;
		return $resto;
	}
}

function formatarIE($uf, $ie, $validar = false, $completarzerosesquerda = false){
	$aIE = array(
				"AC" =>  "99.999.999/999-99",
				"AL" => "999999999",
				"AP" => "999999999",
				"AM" => "99.999.999-9",
				"BA" => "999999-99",
				"CE" => "99999999-9",
				"DF" => "99.999999.999-99",
				"ES" => "99999999-9",
				"GO" => "99.999.999-9",
				"MA" => "999999999",
				"MT" => "9999999999-9",
				"MS" => "99999999-9",
				"MG" => "9999999999999",
				"PA" => "99-999999-9",
				"PB" => "99999999-9",
				"PR" => "99999999-99",
				"PE" => "9999999-99",
				"PI" => "999999999",
				"RJ" => "99.999.99-9",
				"RN" => "99.9.999.999-9",
				"RS" => "999/9999999",
				"RO" => "9999999999999-9",
				"RR" => "99999999-9",
				"SC" => "999.999.999",
				"SP" => "999.999.999.999",
				"SE" => "99999999-9",
				"TO" => "99999999999"
			);

	$maskIE = $aIE[$uf];
	$tamIE = strlen(preg_replace("[()-./,:]", "", $maskIE));
	if($completarzerosesquerda){
		$ie = str_pad($ie, $tamIE,"0", STR_PAD_LEFT);
	}

	if($validar && !CheckIE($ie, $uf)){
		return FALSE;
	}

	switch($uf){
		case "AC":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})#","\\1.\\2.\\3/\\4-\\5",$ie); //"99.999.999/999-99"
			break;
		case "AL":
			$ieresult = $ie; //"999999999"
			break;
		case "AP":
			$ieresult = $ie; //"999999999"
			break;
		case "AM":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{1})#","\\1.\\2.\\3-\\4",$ie); //"99.999.999-9"
			break;
		case "BA":
			$ieresult = preg_replace("#([0-9]{6})([0-9]{2})#","\\1-\\2",$ie); //"999999-99"
			break;
		case "CE":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "DF":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{6})([0-9]{3})([0-9]{2})#","\\1.\\2.\\3-\\4",$ie); //"99.999999.999-99"
			break;
		case "ES":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "GO":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{1})#","\\1.\\2.\\3-\\4",$ie); //"99.999.999-9"
			break;
		case "MA":
			$ieresult = $ie; //"999999999"
			break;
		case "MT":
			$ieresult = preg_replace("#([0-9]{10})([0-9]{1})#","\\1-\\2",$ie); //"9999999999-9"
			break;
		case "MS":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "MG":
			$ieresult = $ie; //"9999999999999"
			break;
		case "PA":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{6})([0-9]{1})#","\\1-\\2-\\3",$ie); //"99-999999-9"
			break;
		case "PB":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "PR":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{2})#","\\1-\\2",$ie); //"99999999-99"
			break;
		case "PE":
			$ieresult = preg_replace("#([0-9]{7})([0-9]{2})#","\\1-\\2",$ie); //"9999999-99"
			break;
		case "PI":
			$ieresult = $ie; //"999999999"
			break;
		case "RJ":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{3})([0-9]{2})([0-9]{1})#","\\1.\\2.\\3-\\4",$ie); //"99.999.99-9"
			break;
		case "RN":
			$ieresult = preg_replace("#([0-9]{2})([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{1})#","\\1.\\2.\\3.\\4-\\5",$ie); //"99.9.999.999-9"
			break;
		case "RS":
			$ieresult = preg_replace("#([0-9]{3})([0-9]{7})#","\\1/\\2",$ie); //"999/9999999"
			break;
		case "RO":
			$ieresult = preg_replace("#([0-9]{13})([0-9]{1})#","\\1-\\2",$ie); //"9999999999999-9"
			break;
		case "RR":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "SC":
			$ieresult = preg_replace("#([0-9]{3})([0-9]{3})([0-9]{3})#","\\1.\\2.\\3",$ie); //"999.999.999"
			break;
		case "SP":
			if(substr($ie, 0, 1) == "P"){
				$ieresult = preg_replace("#([A-Z]{1})([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{3})#","\\1\\2.\\3.\\4.\\5",$ie);
			}else{
				$ieresult = preg_replace("#([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{3})#","\\1.\\2.\\3.\\4",$ie); //"999.999.999.999"
			}
			break;
		case "SE":
			$ieresult = preg_replace("#([0-9]{8})([0-9]{1})#","\\1-\\2",$ie); //"99999999-9"
			break;
		case "TO":
			$ieresult = $ie; //"99999999999
			break;;
		default:
			$ieresult = $ie; //"9?..."
			break;
	}
	return $ieresult;
}

function CheckIEAC($ie) {
	if (strlen($ie) != 13) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != "01") {
			return 0;
		} else {
			$b = 4;
			$soma = 0;
			for ($i = 0; $i <= 10; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
				if ($b == 1) {
					$b = 9;
				}
			}
			$dig = 11 - ($soma % 11);
			if ($dig >= 10) {
				$dig = 0;
			}
			if (!($dig == $ie[11])) {
				return 0;
			} else {
				$b = 5;
				$soma = 0;
				for ($i = 0; $i <= 11; $i++) {
					$soma += $ie[$i] * $b;
					$b--;
					if ($b == 1) {
						$b = 9;
					}
				}
				$dig = 11 - ($soma % 11);
				if ($dig >= 10) {
					$dig = 0;
				}

				return ($dig == $ie[12]);
			}
		}
	}
}

// Alagoas
function CheckIEAL($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != "24") {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$soma *= 10;
			$dig = $soma - ( ( (int) ($soma / 11) ) * 11 );
			if ($dig == 10) {
				$dig = 0;
			}

			return ($dig == $ie[8]);
		}
	}
}

//Amazonas
function CheckIEAM($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		if ($soma <= 11) {
			$dig = 11 - $soma;
		} else {
			$r = $soma % 11;
			if ($r <= 1) {
				$dig = 0;
			} else {
				$dig = 11 - $r;
			}
		}

		return ($dig == $ie[8]);
	}
}

//Amapá
function CheckIEAP($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != "03") {
			return 0;
		} else {
			$i = substr($ie, 0, -1);
			if (($i >= 3000001) && ($i <= 3017000)) {
				$p = 5;
				$d = 0;
			} elseif (($i >= 3017001) && ($i <= 3019022)) {
				$p = 9;
				$d = 1;
			} elseif ($i >= 3019023) {
				$p = 0;
				$d = 0;
			}

			$b = 9;
			$soma = $p;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$dig = 11 - ($soma % 11);
			if ($dig == 10) {
				$dig = 0;
			} elseif ($dig == 11) {
				$dig = $d;
			}

			return ($dig == $ie[8]);
		}
	}
}

//Bahia
function CheckIEBA($ie) {
	if (strlen($ie) != 8) {
		return 0;
	} else {
		$arr1 = array("0", "1", "2", "3", "4", "5", "8");
		$arr2 = array("6", "7", "9");

		$i = substr($ie, 0, 1);

		if (in_array($i, $arr1)) {
			$modulo = 10;
		} elseif (in_array($i, $arr2)) {
			$modulo = 11;
		}

		$b = 7;
		$soma = 0;
		for ($i = 0; $i <= 5; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}

		$i = $soma % $modulo;
		if ($modulo == 10) {
			if ($i == 0) {
				$dig = 0;
			} else {
				$dig = $modulo - $i;
			}
		} else {
			if ($i <= 1) {
				$dig = 0;
			} else {
				$dig = $modulo - $i;
			}
		}
		if (!($dig == $ie[7])) {
			return 0;
		} else {
			$b = 8;
			$soma = 0;
			for ($i = 0; $i <= 5; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$soma += $ie[7] * 2;
			$i = $soma % $modulo;
			if ($modulo == 10) {
				if ($i == 0) {
					$dig = 0;
				} else {
					$dig = $modulo - $i;
				}
			} else {
				if ($i <= 1) {
					$dig = 0;
				} else {
					$dig = $modulo - $i;
				}
			}

			return ($dig == $ie[6]);
		}
	}
}

//Ceará
function CheckIECE($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$dig = 11 - ($soma % 11);

		if ($dig >= 10) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}
}

// Distrito Federal
function CheckIEDF($ie) {
	if (strlen($ie) != 13) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != "07") {
			return 0;
		} else {
			$b = 4;
			$soma = 0;
			for ($i = 0; $i <= 10; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
				if ($b == 1) {
					$b = 9;
				}
			}
			$dig = 11 - ($soma % 11);
			if ($dig >= 10) {
				$dig = 0;
			}

			if (!($dig == $ie[11])) {
				return 0;
			} else {
				$b = 5;
				$soma = 0;
				for ($i = 0; $i <= 11; $i++) {
					$soma += $ie[$i] * $b;
					$b--;
					if ($b == 1) {
						$b = 9;
					}
				}
				$dig = 11 - ($soma % 11);
				if ($dig >= 10) {
					$dig = 0;
				}

				return ($dig == $ie[12]);
			}
		}
	}
}

//Espirito Santo
function CheckIEES($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$i = $soma % 11;
		if ($i < 2) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		return ($dig == $ie[8]);
	}
}

//Goias
function CheckIEGO($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$s = substr($ie, 0, 2);

		if (!( ($s == 10) || ($s == 11) || ($s == 15) )) {
			return 0;
		} else {
			$n = substr($ie, 0, 7);

			if ($n == 11094402) {
				if ($ie[8] != 0) {
					if ($ie[8] != 1) {
						return 0;
					} else {
						return 1;
					}
				} else {
					return 1;
				}
			} else {
				$b = 9;
				$soma = 0;
				for ($i = 0; $i <= 7; $i++) {
					$soma += $ie[$i] * $b;
					$b--;
				}
				$i = $soma % 11;
				if ($i == 0) {
					$dig = 0;
				} else {
					if ($i == 1) {
						if (($n >= 10103105) && ($n <= 10119997)) {
							$dig = 1;
						} else {
							$dig = 0;
						}
					} else {
						$dig = 11 - $i;
					}
				}

				return ($dig == $ie[8]);
			}
		}
	}
}

// Maranhão
function CheckIEMA($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != 12) {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$i = $soma % 11;
			if ($i <= 1) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			return ($dig == $ie[8]);
		}
	}
}

// Mato Grosso
function CheckIEMT($ie) {
	if (strlen($ie) != 11) {
		return 0;
	} else {
		$b = 3;
		$soma = 0;
		for ($i = 0; $i <= 9; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 1) {
				$b = 9;
			}
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		return ($dig == $ie[10]);
	}
}

// Mato Grosso do Sul
function CheckIEMS($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != 28) {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$i = $soma % 11;
			if ($i == 0) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			if ($dig > 9) {
				$dig = 0;
			}

			return ($dig == $ie[8]);
		}
	}
}

//Minas Gerais
function CheckIEMG($ie) {
	if (strlen($ie) != 13) {
		return 0;
	} else {
		$ie2 = substr($ie, 0, 3) . "0" . substr($ie, 3);

		$b = 1;
		$soma = "";
		for ($i = 0; $i <= 11; $i++) {
			$soma .= $ie2[$i] * $b;
			$b++;
			if ($b == 3) {
				$b = 1;
			}
		}
		$s = 0;
		for ($i = 0; $i < strlen($soma); $i++) {
			$s += $soma[$i];
		}
		$i = substr($ie2, 9, 2);
		$dig = $i - $s;
		if ($dig != $ie[11]) {
			return 0;
		} else {
			$b = 3;
			$soma = 0;
			for ($i = 0; $i <= 11; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
				if ($b == 1) {
					$b = 11;
				}
			}
			$i = $soma % 11;
			if ($i < 2) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			};

			return ($dig == $ie[12]);
		}
	}
}

//Pará
function CheckIEPA($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != 15) {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$i = $soma % 11;
			if ($i <= 1) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			return ($dig == $ie[8]);
		}
	}
}

//Paraíba
function CheckIEPB($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		if ($dig > 9) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}
}

//Paraná
function CheckIEPR($ie) {
	if (strlen($ie) != 10) {
		return 0;
	} else {
		$b = 3;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 1) {
				$b = 7;
			}
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		if (!($dig == $ie[8])) {
			return 0;
		} else {
			$b = 4;
			$soma = 0;
			for ($i = 0; $i <= 8; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
				if ($b == 1) {
					$b = 7;
				}
			}
			$i = $soma % 11;
			if ($i <= 1) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			return ($dig == $ie[9]);
		}
	}
}

//Pernambuco
function CheckIEPE($ie) {
	if (strlen($ie) == 9) {
		$b = 8;
		$soma = 0;
		for ($i = 0; $i <= 6; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		if (!($dig == $ie[7])) {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b--;
			}
			$i = $soma % 11;
			if ($i <= 1) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			return ($dig == $ie[8]);
		}
	} elseif (strlen($ie) == 14) {
		$b = 5;
		$soma = 0;
		for ($i = 0; $i <= 12; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 0) {
				$b = 9;
			}
		}
		$dig = 11 - ($soma % 11);
		if ($dig > 9) {
			$dig = $dig - 10;
		}

		return ($dig == $ie[13]);
	} else {
		return 0;
	}
}

//Piauí
function CheckIEPI($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}
		if ($dig >= 10) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}
}

// Rio de Janeiro
function CheckIERJ($ie) {
	if (strlen($ie) != 8) {
		return 0;
	} else {
		$b = 2;
		$soma = 0;
		for ($i = 0; $i <= 6; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 1) {
				$b = 7;
			}
		}
		$i = $soma % 11;
		if ($i <= 1) {
			$dig = 0;
		} else {
			$dig = 11 - $i;
		}

		return ($dig == $ie[7]);
	}
}

//Rio Grande do Norte
function CheckIERN($ie) {
	if (!( (strlen($ie) == 9) || (strlen($ie) == 10) )) {
		return 0;
	} else {
		$b = strlen($ie);
		if ($b == 9) {
			$s = 7;
		} else {
			$s = 8;
		}
		$soma = 0;
		for ($i = 0; $i <= $s; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$soma *= 10;
		$dig = $soma % 11;
		if ($dig == 10) {
			$dig = 0;
		}

		$s += 1;
		return ($dig == $ie[$s]);
	}
}

// Rio Grande do Sul
function CheckIERS($ie) {
	if (strlen($ie) != 10) {
		return 0;
	} else {
		$b = 2;
		$soma = 0;
		for ($i = 0; $i <= 8; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 1) {
				$b = 9;
			}
		}
		$dig = 11 - ($soma % 11);
		if ($dig >= 10) {
			$dig = 0;
		}

		return ($dig == $ie[9]);
	}
}

// Rondônia
function CheckIERO($ie) {
	if (strlen($ie) == 9) {
		$b = 6;
		$soma = 0;
		for ($i = 3; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$dig = 11 - ($soma % 11);
		if ($dig >= 10) {
			$dig = $dig - 10;
		}

		return ($dig == $ie[8]);
	} elseif (strlen($ie) == 14) {
		$b = 6;
		$soma = 0;
		for ($i = 0; $i <= 12; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
			if ($b == 1) {
				$b = 9;
			}
		}
		$dig = 11 - ( $soma % 11);
		if ($dig > 9) {
			$dig = $dig - 10;
		}

		return ($dig == $ie[13]);
	} else {
		return 0;
	}
}

//Roraima
function CheckIERR($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		if (substr($ie, 0, 2) != 24) {
			return 0;
		} else {
			$b = 1;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b++;
			}
			$dig = $soma % 9;

			return ($dig == $ie[8]);
		}
	}
}

//Santa Catarina
function CheckIESC($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$dig = 11 - ($soma % 11);
		if ($dig <= 1) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}
}

//São Paulo
function CheckIESP($ie) {
	if (strtoupper(substr($ie, 0, 1)) == "P") {
		if (strlen($ie) != 13) {
			return 0;
		} else {
			$b = 1;
			$soma = 0;
			for ($i = 1; $i <= 8; $i++) {
				$soma += $ie[$i] * $b;
				$b++;
				if ($b == 2) {
					$b = 3;
				}
				if ($b == 9) {
					$b = 10;
				}
			}
			$dig = $soma % 11;
			return ($dig == $ie[9]);
		}
	} else {
		if (strlen($ie) != 12) {
			return 0;
		} else {
			$b = 1;
			$soma = 0;
			for ($i = 0; $i <= 7; $i++) {
				$soma += $ie[$i] * $b;
				$b++;
				if ($b == 2) {
					$b = 3;
				}
				if ($b == 9) {
					$b = 10;
				}
			}
			$dig = $soma % 11;
			if ($dig > 9) {
				$dig = 0;
			}

			if ($dig != $ie[8]) {
				return 0;
			} else {
				$b = 3;
				$soma = 0;
				for ($i = 0; $i <= 10; $i++) {
					$soma += $ie[$i] * $b;
					$b--;
					if ($b == 1) {
						$b = 10;
					}
				}
				$dig = $soma % 11;

				return ($dig == $ie[11]);
			}
		}
	}
}

//Sergipe
function CheckIESE($ie) {
	if (strlen($ie) != 9) {
		return 0;
	} else {
		$b = 9;
		$soma = 0;
		for ($i = 0; $i <= 7; $i++) {
			$soma += $ie[$i] * $b;
			$b--;
		}
		$dig = 11 - ($soma % 11);
		if ($dig > 9) {
			$dig = 0;
		}

		return ($dig == $ie[8]);
	}
}

//Tocantins
function CheckIETO($ie) {
	if (strlen($ie) != 11) {
		return 0;
	} else {
		$s = substr($ie, 2, 2);
		if (!( ($s == '01') || ($s == "02") || ($s == "03") || ($s == "99") )) {
			return 0;
		} else {
			$b = 9;
			$soma = 0;
			for ($i = 0; $i <= 9; $i++) {
				if (!(($i == 2) || ($i == 3))) {
					$soma += $ie[$i] * $b;
					$b--;
				}
			}
			$i = $soma % 11;
			if ($i < 2) {
				$dig = 0;
			} else {
				$dig = 11 - $i;
			}

			return ($dig == $ie[10]);
		}
	}
}

function CheckIE($ie, $uf) {
	if (in_array(strtoupper($ie), array("ISENTO", "ISENTA"))) {
		return TRUE;
	}else{
		$uf = strtoupper($uf);
		$ie = preg_replace("[()-./,:]", "", $ie);
		$comando = '$valida = CheckIE' . $uf . '("' . $ie . '");';
		eval($comando);

		return $valida;
	}
}

/* * **************
 * FUNCOES DO MSSQL
 * ************* */
$__mssql_error = null;

function file_exists_ci($file){
	if(file_exists($file)){
		return $file;
	}
	$lowerfile = strtolower($file);
	foreach(glob(dirname($file).'/*') as $file){
		if(strtolower($file) == $lowerfile){
			return $file;
		}
	}
	return FALSE;
}

if(!function_exists("mssql_connect")){

	function mssql_connect($host, $user, $pass){
		return new PDO("odbc:{$host}", $user, $pass);
	}

}

if(!function_exists("mssql_select_db")){

	function mssql_select_db($dbname, PDO $con){
		$res = $con->query("use {$dbname}");
		if($res === false){
			$__mssql_error = $con->errorInfo();
			return false;
		}else{
			return true;
		}
	}

}


if(!function_exists("mssql_query")){

	function mssql_query($query, PDO $con){
		global $__mssql_error;

		$res = $con->query($query);
		if($res === false){
			$__mssql_error = $con->errorInfo();
		}
		return $res;
	}

}

if(!function_exists("mssql_get_last_message")){

	function mssql_get_last_message(){
		global $__mssql_error;

		return $__mssql_error[2];
	}

}

if(!function_exists("mssql_fetch_assoc")){

	function mssql_fetch_assoc(PDOStatement $res){
		$fetch = $res->fetch(PDO::FETCH_ASSOC);
		return $fetch;
	}

}

if(!function_exists("mssql_num_rows")){

	function mssql_num_rows(PDOStatement $res){
		return $res->rowCount();
	}

}

function preparar_xmlnfe($xmlnfe){
	$domxml = new DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($xmlnfe);
	$outxml = htmlentities( $domxml->saveXML());
	$outxml = str_replace("\n","<br>", $outxml);
	$outxml = str_replace(" ","&nbsp;", $outxml);
	$outxml = str_replace('"','\\"', $outxml);
	return $outxml;
}

