<?php

require_once("websac/require_file.php");
require_file("def/function.php");
require_file("class/connection.class.php");
require_file("class/report_column.class.php");
require_file("class/pdf.class.php");

class Report{

	private $con; // Conexao com o banco de dados
	private $con_sql; // Conexao com outros banco de dados
	public $html; // HTML gerado apos gerar o relatorio
	private $pdf; // Arquivo PDF gerado apos gerar o relatorio
	private $format; // Formato de saida do relatorio (html/excel/pdf1/pdf2)
	private $query; // Query principal para montar o relatorio
	private $result; // Retorno da query enviada ao banco de dados
	private $table; // Array d retorno da query, onde se encontra o valor de todas as colunas
	private $title; // Titulo do relatorio
	private $subtitle; // SubTitulo do relatorio
	private $orientation; // Orientacao da folha do relatorio (P = retrato, L = paisagem)
	private $codestabelec; // Codigo do estabelecimento (para imprimir o cabecalho certo no formato PDF)
	private $columns = array(); // Vetor com o objeto das colunas que retornaram da query
	private $flag_breaker = FALSE; // Flag que identifica se existe alguma quebra
	private $flag_totalize = FALSE; // Flag que identifica se existe algum campo para totalizar
	private $flag_newline = FALSE;
	private $total = array(); // Totais do relatorio e quebras. Formato: $total [total = [campo => valor, ...], nivel = [total = [campo => valor, ...], nivel = ...]]
	private $breaker = array(); // Quebras de pagina, onde o indice representa o nivel da quebra
	private $padding_columns = 5; // Espaco as esquerda nas colunas (px)
	private $pdf_rowheight = 4; // Altura das linhas em PDF
	private $width = "100%"; // Largura do relatorio
	private $fontsize_html; // Tamanho da fonte em HTML
	private $fontsize_pdf; // Tamanho da fonte em PDF
	private $report_observacaorodape; // Observacao ao final do aquivo
	private $report_observacaorodapetitulo; // Titulo ao final do aquivo
	private $extra_columns = array(); // Coluna extras no relatorio (funcao addcolumn())
	private $arr_newlinecolumn = array();
	private $bgcolor = array(// Cores do fundo das linhas
		"label" => array(0, 0, 0),
		"break" => array(array(100, 100, 100), array(170, 170, 170), array(200, 200, 200)),
		"row0" => array(255, 255, 255),
		"row1" => array(235, 235, 235),
		"total" => array(0, 0, 0),
	);
	private $fontcolor = array(// Cores das fontes
		"label" => array(255, 255, 255),
		"break" => array(array(255, 255, 255), array(255, 255, 255), array(255, 255, 255)),
		"row0" => array(0, 0, 0),
		"row1" => array(0, 0, 0),
		"total" => array(255, 255, 255)
	);

	function __construct($con = NULL){
		if(is_null($con)){
			$con = new Connection();
		}
		$this->con = $con;
		$this->con_sql = $con;
		$this->orientation = "P";
		$this->setfontsize(13);
		$this->codestabelec = NULL;
	}

	function addcolumn($name, $definition = null, $stopinrow = null){
		/*
		  Definicao deve ser passada no formato SQL
		  Formatos:
		  Valor normal da coluna -> nome_coluna
		  Total da coluna -> SUM(nome_coluna)
		  Acumulativo da coluna -> ACU(nome_coluna)
		  Numero da linha -> ROW()
		 */
		$this->extra_columns[] = array(
			"name" => $name,
			"definition" => $definition,
			"stopinrow" => $stopinrow
		);
		$this->columns[$name] = new ReportColumn($name);
	}

	function calcfooter($column, $definition){
		$this->extra_columns[] = array(
			"name" => $column,
			"definition" => $definition
		);
		$this->columns[$column] = new ReportColumn($column);
	}

	private function calcaverage($column){
		foreach($this->table as $row){
			if(is_numeric($row[$column])){
				$sum += $row[$column];
			}
		}
		$avg = $sum / sizeof($this->table);
		$this->columns[$column]->setaverage($avg);
	}

	private function calctotal($column){
		foreach($this->table as $row){
			if(is_numeric($row[$column])){
				$sum += $row[$column];
			}
		}
		$this->columns[$column]->settotal($sum);
	}

	private function calctotalbreaker(){
		$this->total = $this->_calctotalbreaker();
	}

	private function _calctotalbreaker($filter = array()){
		// $filtrer		[nivel => valor, ...]
		$nivel = count($filter) - 1;
		$arr = array();
		foreach($this->table as $row){
			$sumrow = TRUE;
			foreach($filter as $n => $value){
				if($row[$this->breaker[$n]["key"]] != $value){
					$sumrow = FALSE;
				}
			}
			if($sumrow){
				foreach($this->columns as $column){
					if($column->gettotalize()){
						$arr["total"][$column->getname()] += $row[$column->getname()];
						if($nivel != count($this->breaker) - 1){
							$breaker = $this->breaker[$nivel + 1]["key"];
							if(!isset($arr[$nivel + 1][$row[$breaker]])){
								$arr[$nivel + 1][$row[$breaker]] = $this->_calctotalbreaker(array_merge($filter, array($nivel + 1 => $row[$breaker])));
							}
						}
					}
				}
			}
		}
		return $arr;
	}

	private function checkchangevalue($column, $value){
		$arrchangevalue = $this->columns[$column]->getchangevalue();
		if(isset($arrchangevalue[$value])){
			return $arrchangevalue[$value];
		}else{
			return $value;
		}
	}

	private function checktype($value, $type){
		switch($type){
			case "date": return implode("/", array_reverse(explode("-", $value)));
				break;
			case "integer": return round($value, 0);
				break;
			case "numeric": return number_format(value_numeric($value), 2, ",", ".");
				break;
			case "numeric3": return number_format(value_numeric($value), 3, ",", ".");
				break;
			case "numeric4": return number_format(value_numeric($value), 4, ",", ".");
				break;
			case "time": return substr($value, 0, 8);
				break;
			default: return $value;
				break;
		}
	}

	private function create_extra_columns(){ // Cria as colunas extras do relatorio
		foreach($this->extra_columns as $extra_column){
			$name = $extra_column["name"];
			$definition = $extra_column["definition"];
//			$definition = strtolower($definition); // Deixa a definicao toda minuscula
			// Substitui a funcao SUM() (sem quebra)
			if(!$this->flag_breaker){
				while(true){
					$sum = $this->def_getfunction($definition, "sum", 0);
					if($sum !== FALSE){
						$i = 0;
						$aux = $sum;
						while(true){
							$col = $this->def_getcolumn($sum, $i);
							if($col !== FALSE){
								if(isset($this->columns[$col])){
									$aux = str_replace($col, $this->columns[$col]->gettotal(), $aux);
								}
								$i++;
							}else{
								break;
							}
						}
						$definition = str_replace("sum(".$sum.")", "(".$aux.")", $definition);
					}else{
						break;
					}
				}
			}

			// Pega dados do ultimo nivel da quebra
			if($this->flag_breaker){
				$last_breaker = $this->breaker[sizeof($this->breaker) - 1];
				$key_breaker_row = NULL;
				$key_breaker_acu = NULL;
				$i_breaker = 1;
			}

			// Percorre todas as linhas da query
			foreach($this->table as $i => $row){
				$defAux = $definition;

				// Substitui a funcao SUM() (com quebra)
				if($this->flag_breaker){
					while(true){
						$sum = $this->def_getfunction($defAux, "sum", 0);
						if($sum !== FALSE){
							$j = 0;
							$aux = $sum;
							while(true){
								$col = $this->def_getcolumn($sum, $j);
								if($col !== FALSE){
									if(isset($this->columns[$col])){
										$total_sum = $this->total[sizeof($this->breaker) - 1][$row[$this->breaker[sizeof($this->breaker) - 1]["key"]]]["total"][$col];
										$aux = str_replace($col, $total_sum, $aux);
									}
									$j++;
								}else{
									break;
								}
							}
							$defAux = str_replace("sum(".$sum.")", "(".$aux.")", $defAux);
						}else{
							break;
						}
					}
				}

				// Substitui a funcao ROW()
				if($this->flag_breaker){
					if(is_null($key_breaker_row) || $key_breaker_row != $row[$last_breaker["key"]]){
						$i_breaker = 1;
						$key_breaker_row = $row[$last_breaker["key"]];
					}
					$defAux = str_replace("row()", $i_breaker++, $defAux);
				}else{
					$defAux = str_replace("row()", ($i + 1), $defAux);
				}

				// Substitui a funcao ACU()
				$j = 0;
				while(true){
					$acu = $this->def_getfunction($defAux, "acu", 0);
					if($acu !== FALSE){
						$k = 0;
						$aux = $acu;
						while(true){
							$col = $this->def_getcolumn($acu, $k);
							if($col !== FALSE){
								if(isset($this->columns[$acu])){
									if($this->flag_breaker && (is_null($key_breaker_acu) || $key_breaker_acu != $row[$last_breaker["key"]])){
										$key_breaker_acu = $row[$last_breaker["key"]];
										$last_value = 0;
									}else{
										$last_value = $this->table[$i - 1][$name];
									}
									$val = ($i == 0 ? $row[$col] : $row[$col] + $last_value);
									//echo $name;
									//die(var_dump($this->extra_columns));
									foreach($this->extra_columns AS $extra_columns){
										if($extra_columns["name"] == $name){
											if(strlen($extra_columns["stopinrow"]) > 0){
												if((sizeof($this->table) - $extra_columns["stopinrow"]) <= $i){
													$val = 0;
												}
											}
										}
									}

									$aux = str_replace($col, $val, $aux);
								}
								$k++;
							}else{
								break;
							}
						}
						$defAux = str_replace("acu(".$acu.")", "(".$aux.")", $defAux);
						$j++;
					}else{
						break;
					}
				}

				// Busca todas as colunas disponiveis na definicao
				$arr_col = array();
				while(true){
					$col = $this->def_getcolumn($defAux, $j);
					if($col !== FALSE){
						$arr_col[] = $col;
						$j++;
					}else{
						break;
					}
				}

				// Ordena o array de coluna do maior tamanho para o menor
				usort($arr_col, "compare_length");

				// Substitui os valores normais (os que sobraram)
				foreach($arr_col as $col){
					if(array_key_exists($col, $row)){
						$defAux = str_replace($col, (strlen($row[$col]) > 0 ? $row[$col] : 0), $defAux);
					}
				}

				if(strlen(trim(str_replace(array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", ".", "+", "-", "*", "/", "(", ")"), "", $defAux))) == 0){
					$calc_val = calc_string($defAux);
				}else{
					$calc_val = calc_string($defAux, $this->con);
				}
				$this->table[$i][$name] = $calc_val;
			}
			$this->calcaverage($name);
			$this->calctotal($name);
		}
	}

	private function columnexists($name){ // Verifica se a coluna existe
		foreach($this->columns AS $column){
			if($column->getname() == $name){
				return TRUE;
			}
		}
		return FALSE;
	}

	private function def_getcolumn($definition, $index){ // Retorna o nome da coluna que se encontra no indice
		$arrChar = array(" ", "(", ")", "+", "-", "*", "/");
		$arrFunc = array("sum", "acu");
		$arrWords = array();
		$word = "";
		for($i = 0; $i < strlen($definition); $i++){
			$add = FALSE;
			$char = substr($definition, $i, 1);
			if(in_array($char, $arrChar)){
				$add = TRUE;
			}else{
				$word .= $char;
				$add = ($i == strlen($definition) - 1);
			}
			if($add && strlen($word) > 0){
				$arrWords[] = $word;
				$word = "";
			}
		}
		foreach($arrWords as $i => $word){
			if(in_array($word, $arrFunc)){
				unset($arrWords[$i]);
			}
		}
		if(isset($arrWords[$index])){
			return $arrWords[$index];
		}else{
			return FALSE;
		}
	}

	private function def_getfunction($definition, $function, $index){ // Retorna o conteudo de dentro de uma funcao da definicao
		$function .= "(";
		$p = 0;
		$cont = 0;
		while(true){
			$begin = strpos($definition, $function, $begin);
			if($begin !== FALSE){
				$begin += strlen($function);
				if($cont == $index){
					for($i = $begin; $i < strlen($definition); $i++){
						if(substr($definition, $i, 1) == "("){
							$p++;
						}elseif(substr($definition, $i, 1) == ")"){
							if($p == 0){
								return substr($definition, $begin, $i - $begin);
							}else{
								$p--;
							}
						}
					}
					return FALSE;
				}else{
					$cont++;
					continue;
				}
			}else{
				return FALSE;
			}
		}
	}

	// Retorna as cores de fundo parametrizadas para certo elemento
	public function getbgcolor($e){
		return $this->bgcolor[$e];
	}

	// Retorna as colunas do relatorio
	public function getcolumns(){
		return $this->columns;
	}

	// Retorna as linhas da tabela de acordo com o filtro. Ex: $rows = getfilteredrows(array($data => '2010-01-01','valor' => 0.00));
	private function getfilteredrows($filter = array()){
		$fil_rows = array();
		foreach($this->table as $row){
			$addrow = TRUE;
			foreach($filter as $name => $value){
				if($row[$name] != $value){
					$addrow = FALSE;
				}
			}
			$fil_rows = $row;
		}
		return $fil_rows;
	}

	// Retorna as cores de fonte parametrizadas para certo elemento
	public function getfontcolor($e){
		return $this->fontcolor[$e];
	}

	// Retorna o formato do relatorio
	public function getformat(){
		return $this->format;
	}

	// Retorna a orientacao do relatorio
	public function getorientation(){
		return $this->orientation;
	}

	// Retorna o padding das colunas (usado no formato HTML)
	public function getpaddingcolumn(){
		return $this->padding_columns;
	}

	// Retorna a altura das colunas
	public function getrowheight(){
		return $this->pdf_rowheight;
	}

	// Retorna a tabela completa do relatorio
	public function gettable(){
		return $this->table;
	}

	// Retorna o titulo do relatorio
	function gettitle(){
		return $this->title;
	}

	// Retorna o sub titulo do relatorio
	function getsubtitle(){
		return $this->subtitle;
	}

	// Retorna o numero de colunas invisiveis
	private function invisiblecolumns(){
		$cont = 0;
		foreach($this->columns as $column){
			if(!$column->getvisible()){
				$cont++;
			}
		}
		return $cont;
	}

	private function resizecolumns(){
		$t_width = 0;
		foreach($this->columns as $column){
			if($column->getvisible()){
				$t_width += (float) str_replace("%", "", $column->getwidth());
			}
		}
		foreach($this->columns as $i => $column){
			if($column->getvisible()){
				$width = (float) str_replace("%", "", $column->getwidth());
				$this->columns[$i]->setwidth((($width * 100) / $t_width)."%");
			}
		}
	}

	function setbreaker($key, $detail, $level = NULL){
		if($level == NULL){
			$level = sizeof($this->breaker);
		}
		if(is_string($key) && is_string($detail) && is_integer($level)){
			$this->breaker[$level] = array("key" => $key, "detail" => $detail);
			$this->flag_breaker = TRUE;
			$this->columns[$key]->setvisible(FALSE);
			$this->columns[$detail]->setvisible(FALSE);
		}
	}

	function setcodestabelec($codestabelec){
		$this->codestabelec = $codestabelec;
	}

	function setcolumnalign($column, $align){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumnalign($col, $align);
			}
		}elseif(is_string($column) && is_string($align)){
			$this->columns[$column]->setalign($align);
		}
	}

	function setcolumnaverage($column){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumnaverage($col);
			}
		}
		if(is_string($column)){
			$this->columns[$column]->setcalcaverage(TRUE);
			$this->flag_totalize = TRUE;
		}
	}

	function setcolumncalcfooter($column, $calcfooter){
		if(is_string($column) && is_string($calcfooter)){
			$this->columns[$column]->setcalcfooter($calcfooter);
		}
	}

	function setcolumnchangevalue($column, $changevalue){ // Troca o valor do indice do vetor (changevalue) pelo valor
		if(is_string($column) && is_array($changevalue)){
			$this->columns[$column]->setchangevalue($changevalue);
		}
	}

	function setcolumnlabel($column, $label){
		if(is_string($column) && is_string($label)){
			$this->columns[$column]->setlabel($label);
		}
	}

	function setcolumntotalize($column){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumntotalize($col);
			}
		}
		if(is_string($column)){
			$this->columns[$column]->settotalize(TRUE);
			$this->flag_totalize = TRUE;
		}
	}

	function getcolumntotalize($column){
		return $this->columns[$column]->settotalize(TRUE);
	}

	function setcolumntype($column, $type){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumntype($col, $type);
			}
		}elseif(is_string($column) && is_string($type)){
			$this->columns[$column]->settype($type);
		}
	}

	function setcolumnvisible($column, $visible){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumnvisible($col, $visible);
			}
		}elseif(is_string($column) && is_bool($visible)){
			$this->columns[$column]->setvisible($visible);
		}
	}

	function setcolumnwidth($column, $width){
		if(is_array($column)){
			foreach($column as $col){
				$this->setcolumnwidth($col, $width);
			}
		}elseif(is_string($column) && is_string($width)){
			$this->columns[$column]->setwidth($width);
		}
	}

	function setfontsize($value){
		$this->fontsize_html = $value;
		$this->fontsize_pdf = number_format($this->fontsize_html / 1.625, 0);
	}

	function setformat($format){
		if(is_string($format)){
			$this->format = strtolower($format);
		}
	}

	function setorder($args){ // Deve ser passado por parametro as todas colunas na ordem quem serao desenhadas
		if(!is_array($args)){
			$args = func_get_args(); // Captura todos os parametros passados na funcao
		}
		$args = array_unique($args); // Remove valores duplicados
		foreach($this->table as $i => $row){ // Percorre todas as linhas da tabela
			$new_row = array(); // Variavel que armazena a nova linha da tabela
			foreach($args as $column){ // Percorre todos os parametros
				$achou = array_key_exists($column, $row);
				if(!$achou){
					foreach($this->extra_columns as $extra_column){
						if($column == $extra_column["name"]){
							$achou = TRUE;
							break;
						}
					}
				}
				if($achou){
					$new_row[$column] = $row[$column];
				}
			}
			foreach($row as $column => $value){ // Passa para o vetor as colunas que nao foram passadas por parametro
				if(!array_key_exists($column, $new_row)){
					$new_row[$column] = $value;
				}
			}
			$this->table[$i] = $new_row; // Substitui a antiga ordem pela nova
		}
	}

	function setorientation($orientation){
		if(in_array($orientation, array("P", "L"))){
			$this->orientation = $orientation;
		}
	}

	function setquery($query){
		////////// Coloca apenas os estabelecimentos disponiveis para o usuário
		/*
		$query = str_replace("codestabelec = sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec= sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec =sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec=sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		*/
		$query = $this->preparequery($query);
		if(is_string($query)){
			$this->query = $query;
			if(!$this->result = @$this->con_sql->query($this->query)){
				$error = $this->con_sql->errorInfo();
				if(strpos($error[2],"time field value out of range") > -1){
					$error_data = substr($error[2],strpos($error[2],"\"")+1,10);
					$error_data = substr($error_data,8,2)."/".substr($error_data,5,2)."/".substr($error_data, 0,4);
					$t = "O periodo informado esta fora de uma data correta, <b>$error_data</b> n&atilde;o existe.";
//					$t = "O periodo informado esta fora de uma data correta, <b>$error_data</b> n&atilde;o &eacute; uma data correta.";
					die(script("window.opener.$.messageBox({type:\"alert\",text:\"".$t."\"}); window.close()"));
				}else{
					die("<b>Houve um erro na execu&ccedil;&atilde;o da instru&ccedil;&atilde;o enviada ao banco de dados:</b><br>".str_replace("<", "&LT;", $this->query)."<br><br>".$error[2]);
				}
			}elseif($this->result->rowCount() == 0){
				$t = "Nenhum registro foi encontrado no filtro informado.";
				if(strpos($_SERVER["PHP_SELF"], "/mobile/") === FALSE){
					die(script("window.opener.$.messageBox({type:\"alert\",text:\"".$t."\"}); window.close()"));
				}else{
					die(script("window.opener.alert(\"".$t."\"); window.close()"));
				}
			}else{
				$this->table = $this->result->fetchAll(2);
				$this->setcolumns();
				/*
				foreach($this->table[0] as $column => $value){
					$this->columns[$column] = new ReportColumn($column);
					$this->calctotal($column);
				}
				 *
				 */
			}
		}elseif(is_array($query)){
			$this->table = $query;
			$this->setcolumns();
		}else{
			die("Formato de instru&ccedil;&atilde;o SQL inv&aacute;lido");
		}
	}

	function preparequery($query){
		$query = str_replace("codestabelec = sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec= sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec =sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		$query = str_replace("codestabelec=sub_codestabelec", "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')", $query);
		return $query;
	}

	function setcolumns(){
		foreach($this->table[0] as $column => $value){
			$this->columns[$column] = new ReportColumn($column);
			$this->calctotal($column);
		}
	}

	function setsubtitle($subtitle){
		if(is_string($subtitle)){
			$this->subtitle = $subtitle;
		}
	}

	function settitle($title){
		if(is_string($title)){
			$this->title = $title;
		}
	}

	private function visiblecolumns(){ // Retorna o numero de colunas visiveis
		$cont = 0;
		foreach($this->columns as $column){
			if($column->getvisible()){
				$cont++;
			}
		}
		return $cont;
	}

	function draw(){
		// Renumera as quebras para nao haver problemas ao calcular totais das quebras
		ksort($this->breaker);
		$this->breaker = array_merge($this->breaker);

		$this->calctotalbreaker();
		$this->create_extra_columns();
		$this->calctotalbreaker();
		$this->resizecolumns();

		// Inicia um arquivo no formato PDF
		$this->pdf = new PDF($this->con, $this->codestabelec, $this->orientation, ($this->format == "pdf1"));
		$this->pdf->setreport($this);
		$this->pdf->SetTitle($this->title);
		$this->pdf->SetDrawColor(255, 255, 255);
		$this->pdf->SetY("-1");

		// Abre a tag TABLE para o formato HTML e Excel
		$this->html = "<table style=\"font-family:Verdana; font-size:".$this->fontsize_html."px; table-layout:fixed; width:".$this->width."\">";

		if(in_array($this->format, array("html", "excel"))){
			$this->pdf->Header(TRUE);
		}

		$breaker_cab_atual = array();
		$breaker_tot_atual = array();
		$breaker_tot_anterior = array();

		// Percorre todas as linhas
		$line = 0; // Numero da linha que esta desenhando
		$this->pdf->SetFont("Arial", "", $this->fontsize_pdf);
		foreach($this->table as $i => $reg){

			// Verifica se existe alguma quebra
			if($this->flag_breaker){
				// Criar os totalizadores da quebra anterior
				$arr_breaker_rev = array_reverse($this->breaker, TRUE);
				foreach($arr_breaker_rev as $j => $breaker){
					if(!isset($breaker_tot_atual[$j]) || $breaker_tot_atual[$j] != $reg[$arr_breaker_rev[$j]["key"]]){
						if($breaker_tot_atual[$j] != $reg[$breaker["key"]]){
							$breaker_tot_anterior = $breaker_tot_atual;
							$breaker_tot_atual[$j] = $reg[$breaker["key"]];
						}
						if($i > 0 && $this->flag_totalize){
							$this->draw_totalbreaker($reg, $j, $breaker_tot_anterior);
						}
					}
				}

				// Cria o cabecalho da quebra
				foreach($this->breaker as $j => $breaker){
					if(!isset($breaker_cab_atual[$j]) || $breaker_cab_atual[$j] != $reg[$this->breaker[$j]["key"]]){
						$desBreaker = $this->checktype($reg[$breaker["detail"]], $this->columns[$breaker["detail"]]->gettype());
						$desBreaker = (strlen($desBreaker) > 0 ? $desBreaker : "&nbsp;");

						$this->pdf->SetFont("Arial", "B", $this->fontsize_pdf);
						$this->html .= "<tr style=\"color:".stringrgb($this->fontcolor["break"][$j])."; background:".stringrgb($this->bgcolor["break"][$j])."\"><td colspan=\"".(sizeof($reg) - $this->invisiblecolumns())."\" style=\"padding:0px ".($this->padding_columns + (20 * $j))."px; text-align:left\">".$desBreaker."</td></tr>";
						$this->pdf->SetFillColor($this->bgcolor["break"][$j][0], $this->bgcolor["break"][$j][1], $this->bgcolor["break"][$j][2]);
						$this->pdf->SetTextColor($this->fontcolor["break"][$j][0], $this->fontcolor["break"][$j][1], $this->fontcolor["break"][$j][2]);
						$desBreaker = str_repeat(" ", ($j == 0 ? 1 : 5 * $j)).$desBreaker;
						$this->pdf->Cell(0, $this->pdf_rowheight, utf8_encode($desBreaker), 1, 1, "L", TRUE);
						$line = 0; // Zera o contador para comecar sempre com a primeira cor apos a quebra

						$breaker_cab_atual[$j] = $reg[$breaker["key"]];
					}
				}
			}

			$this->html .= "<tr style=\"background:".stringrgb($this->bgcolor["row".($line % 2)])."; color:".stringrgb($this->fontcolor["row".($line % 2)])."\">";
			$this->pdf->SetFillColor($this->bgcolor["row".($line % 2)][0], $this->bgcolor["row".($line % 2)][1], $this->bgcolor["row".($line % 2)][2]);
			$this->pdf->SetTextColor($this->fontcolor["row".($line % 2)][0], $this->fontcolor["row".($line % 2)][1], $this->fontcolor["row".($line % 2)][2]);
			foreach($reg as $col => $val){ // Cria as linhas com os valores
				if($this->columns[$col]->getvisible()){
					$disVal = $this->checktype($this->checkchangevalue($col, $val), $this->columns[$col]->gettype());
					$disVal = (strlen($disVal) > 0 ? $disVal : "");
					switch($this->columns[$col]->gettype()){
						case "date":
							$mso_format = "\"dd\/mm\/yyyy\"";
							break;
						case "string":
							$mso_format = "\"\\@\"";
							break;
						default:
							$mso_format = "General";
							break;
					}
					$this->html .= "<td style='mso-number-format:{$mso_format}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px'>".$disVal."</td>";
					$disVal = $this->pdf->WordWrap($disVal, pdfwidth($this->orientation, $this->columns[$col]->getwidth()));
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $disVal, 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
				}
			}
			$this->html .= "</tr>";
			$this->pdf->SetY($this->pdf->GetY() + $this->pdf_rowheight);
			$line++;
		}

		// Cria o total da ultima quebra
		if($this->flag_breaker && $this->flag_totalize){
			$arr_breaker_rev = array_reverse($this->breaker, TRUE);
			foreach($arr_breaker_rev as $j => $breaker){
				$this->draw_totalbreaker($reg, $j, $breaker_tot_atual);
			}
		}

		//		var_dump($arr_breaker);
		// Percorre uma vez as colunas para criacao dos totalizadores
		if($this->flag_totalize){
			$this->html .= "<tr style=\"background:".stringrgb($this->bgcolor["total"])."; color:".stringrgb($this->fontcolor["total"])."\">";
			$this->pdf->SetFillColor($this->bgcolor["total"][0], $this->bgcolor["total"][1], $this->bgcolor["total"][2]);
			$this->pdf->SetTextColor($this->fontcolor["total"][0], $this->fontcolor["total"][1], $this->fontcolor["total"][2]);
			foreach($this->table[0] as $col => $val){
				if($this->columns[$col]->getvisible()){
					if($this->columns[$col]->gettotalize()){
						$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($this->columns[$col]->gettotal(), $this->columns[$col]->gettype())."</b></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($this->columns[$col]->gettotal(), $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
					}elseif($this->columns[$col]->getcalcaverage()){
						$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($this->columns[$col]->getaverage(), $this->columns[$col]->gettype())."</b></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($this->columns[$col]->getaverage(), $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
					}elseif(strlen($this->columns[$col]->getcolumncalcfooter()) > 0){
						$calcfooter = $this->columns[$col]->getcolumncalcfooter();
						foreach($this->table[0] as $col_2 => $val_2){
							$calcfooter = str_replace($col_2, $this->columns[$col_2]->gettotal(), $calcfooter);
						}
						$res = $this->con->query("SELECT ".$calcfooter);
						$calcfooter = $res->fetchColumn();
						//eval("\$calcfooter = ".$calcfooter.";");
						$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($calcfooter, $this->columns[$col]->gettype())."</b></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($calcfooter, $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
					}else{
						$this->html .= "<td></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, "", 1, 0, "L", TRUE);
					}
				}
			}
			$this->html .= "</tr>";
		}

		if($this->flag_newline){
			$this->html .= "</tr>";
			$this->pdf->SetY($this->pdf->GetY() + $this->pdf_rowheight);
			$line++;
			foreach($this->table[0] as $col => $val){
				if($this->columns[$col]->getvisible()){
					if(strlen($this->arr_newlinecolumn[$col]) > 0){
						$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($this->columns[$col]->gettotal(), $this->columns[$col]->gettype())."</b></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($this->arr_newlinecolumn[$col], $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
					}else{
						$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($this->columns[$col]->gettotal(), $this->columns[$col]->gettype())."</b></td>";
						$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype("", "text	"), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
					}
				}
			}
		}

		$this->html .= "</table>";

		if(strlen($this->report_observacaorodape) > 0){
			$this->pdf->Cell(0, 8, NULL, 0, 1);
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont("Arial", "B");
			$this->pdf->MultiCell(0, 4, $this->report_observacaorodapetitulo);
			$this->pdf->SetFont("Arial", "");
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->MultiCell(0, 4, $this->report_observacaorodape);
		}

		if(param("RELATORIO", "EXIBEFILTROREL", $this->con) == "S"){
			$this->pdf->Cell(0, 8, NULL, 0, 1);
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont("Arial", "B");
			$this->pdf->MultiCell(0, 4, "Filtros Aplicados");
			$this->pdf->SetFont("Arial", "");
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->MultiCell(0, 4, str_replace(array("|", "*"), array("\n", " = "), $_REQUEST["filtros"]));
		}

		switch($this->format){
			case "excel":
			case "html":
				return $this->html;
				break;
			case "pdf1":
			case "pdf2":
				$this->pdf->Output($this->title.".pdf", "I");
				break;
		}
	}

	private function draw_totalbreaker($columns, $nivel, $keys){
		//$nivel = count($keys) - 1;

		$this->html .= "<tr style=\"color:".stringrgb($this->fontcolor["break"][$nivel])."; background:".stringrgb($this->bgcolor["break"][$nivel])."\">";
		$this->pdf->SetFillColor($this->bgcolor["break"][$nivel][0], $this->bgcolor["break"][$nivel][1], $this->bgcolor["break"][$nivel][2]);
		$this->pdf->SetTextColor($this->fontcolor["break"][$nivel][0], $this->fontcolor["break"][$nivel][1], $this->fontcolor["break"][$nivel][2]);
		foreach($columns as $col => $val){
			if($this->columns[$col]->getvisible()){
				if($this->columns[$col]->gettotalize()){
					$total = $this->total;
					for($i = 0; $i <= $nivel; $i++){
						$total = $total[$i][$keys[$i]];
					}
					$totcolbreak = $total["total"][$this->columns[$col]->getname()];
					$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\">".$this->checktype($totcolbreak, $this->columns[$col]->gettype())."</td>";
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($totcolbreak, $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
				}elseif(strlen($this->columns[$col]->getcolumncalcfooter()) > 0){
					$calcfooter = $this->columns[$col]->getcolumncalcfooter();
					foreach($this->table[0] as $col_2 => $val_2){
						$v = $this->total[$nivel][$keys[$nivel]]["total"][$col_2];
						$calcfooter = str_replace($col_2, $v, $calcfooter);
					}
					$res = $this->con->query("SELECT ".$calcfooter);
					$calcfooter = $res->fetchColumn();
					//eval("\$calcfooter = \"$calcfooter\";");

					$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\"><b>".$this->checktype($calcfooter, $this->columns[$col]->gettype())."</b></td>";
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($calcfooter, $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
				}else{
					$this->html .= "<td></td>";
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, "", 1, 0, "L", TRUE);
				}
			}
		}
		$this->html .= "</tr>";
		$this->pdf->SetY($this->pdf->GetY() + $this->pdf_rowheight);
	}

	private function _draw_totalbreaker($columns, $arr_breaker){
		$nivel = sizeof($arr_breaker) - 1;

		$arr_total = $this->total;
		for($i = 0; $i < sizeof($arr_breaker); $i++){
			$arr_total = $arr_total[$i][$arr_breaker[$i]];
		}
		$arr_total = $arr_total["total"];

		$this->html .= "<tr style=\"color:".stringrgb($this->fontcolor["break"][$nivel])."; background:".stringrgb($this->bgcolor["break"][$nivel])."\">";
		$this->pdf->SetFillColor($this->bgcolor["break"][$nivel][0], $this->bgcolor["break"][$nivel][1], $this->bgcolor["break"][$nivel][2]);
		$this->pdf->SetTextColor($this->fontcolor["break"][$nivel][0], $this->fontcolor["break"][$nivel][1], $this->fontcolor["break"][$nivel][2]);
		foreach($columns as $col => $val){
			if($this->columns[$col]->getvisible()){
				if($this->columns[$col]->gettotalize()){
					$totcolbreak = $arr_total[$this->columns[$col]->getname()];
					$this->html .= "<td style=\"text-align:".$this->columns[$col]->getalign()."; padding:0px ".$this->padding_columns."px\">".$this->checktype($totcolbreak, $this->columns[$col]->gettype())."</td>";
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, $this->checktype($totcolbreak, $this->columns[$col]->gettype()), 1, 0, strtoupper(substr($this->columns[$col]->getalign(), 0, 1)), TRUE);
				}else{
					$this->html .= "<td></td>";
					$this->pdf->Cell(pdfwidth($this->orientation, $this->columns[$col]->getwidth()), $this->pdf_rowheight, "", 1, 0, "L", TRUE);
				}
			}
		}
		$this->html .= "</tr>";
		$this->pdf->SetY($this->pdf->GetY() + $this->pdf_rowheight);
	}

	public function setcon_sql($con_sql){
		$this->con_sql = $con_sql;
	}

	public function setreport_extrarodape($titulo = "", $observacao = ""){
		$this->report_observacaorodape = $observacao;
		$this->report_observacaorodapetitulo = $titulo;
	}

	public function setreport_newline($arr_newlinecolumn = array()){
		$this->arr_newlinecolumn = $arr_newlinecolumn;
		$this->flag_newline = TRUE;
	}

}