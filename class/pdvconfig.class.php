<?php

class PdvConfig{

	private $con; // Conexao com o banco de dados
	private $estabelecimento; // Objeto do estabelecimento
	private $frentecaixa; // Objeto do frente de caixa
	private $tipopreco; // Tipo de preco (A ou V)
	private $dtmovto; // Data do movimento desejado para importar
	private $urgente; // Se deve gerar apenas os produtos urgentes
	private $datalog; // Data de alteracao dos registros (exportacao de alterados)
	private $horalog; // Hora de alteracao dos registros (exportacao de alterados)
	private $datalogfim; // Data de alteracao dos registros (exportacao de alterados)
	private $horalogfim; // Hora de alteracao dos registros (exportacao de alterados)

	function __construct($con){
		$this->con = $con;
	}

	function atualizar_precopdv($arr_codproduto){
		setprogress(0, "Atualizando produtos exportados", TRUE);
		if(is_array($arr_codproduto) && count($arr_codproduto) > 0){
			$this->con->query("UPDATE produtoestab SET precopdv = ".($this->tipopreco == "A" ? "(CASE WHEN precoatcof > 0 THEN precoatcof ELSE precoatc END)" : "(CASE WHEN precovrjof > 0 THEN precovrjof ELSE precovrj END)")." WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND codproduto IN (".implode(", ", $arr_codproduto).")");
		}
	}

	function cliente_parcial(){
		return strlen($this->datalog) > 0;
	}

	function cliente_parcial_query(){
		if($this->cliente_parcial()){
			$query = "(cliente.datalog >= '".$this->datalog."' AND cliente.datalog <= '".$this->datalogfim."')";
		}else{
			$query = "";
		}
		return $query;
	}

	function file_create($filename, $arr_line, $mode = "w+", $return = FALSE){
		$arr_line[] = "";
		$filename = $this->estabelecimento->getdirpdvexp().$filename;
		if($return){
			return array(
				"filename" => $filename,
				"content" => implode("\r\n", $arr_line)
			);
		}else{
			if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"){
				$file = fopen($filename, $mode);
				fwrite($file, implode("\r\n", $arr_line));
				fclose($file);
				@chmod($filename, 0777);
			}else{
				echo write_file($filename, $arr_line, FALSE, $mode);
			}
		}
	}

	function produto_parcial(){
		return strlen($this->datalog) > 0 && strlen($this->horalog) > 0;
	}

	function produto_parcial_query(){
		if($this->produto_parcial()){
			$query = "(((produto.datalog >= '".$this->datalog."' AND produto.datalog <= '".$this->datalogfim."')  OR (produto.datalog = '".$this->datalog."' AND produto.horalog >= '".$this->horalog."')) OR (produto.codproduto IN (SELECT DISTINCT codproduto FROM logpreco WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND (data > '".$this->datalog."' OR (data = '".$this->datalog."' AND hora >= '".$this->horalog."')))) OR (produto.codproduto IN (SELECT DISTINCT codproduto FROM produtoean WHERE datalog > '".$this->datalog."' OR (datalog = '".$this->datalog."' AND horalog >= '".$this->horalog."'))))";
		}else{
			$query = "";
		}
		return $query;
	}

	function remove_array_format($arr){
		foreach($arr as $i => $row){
			foreach($row as $j => $val){
				$arr[$i][$j] = removespecial(utf8_decode($val));
			}
		}
		return $arr;
	}

	function sql_codproduto(){
		switch($this->frentecaixa->gettipocodproduto()){
			case "E": return "produtoean.codean AS codproduto";
			case "P": return "produto.codproduto";
		}
	}

	function sql_descricao(){
		switch($this->frentecaixa->gettipodescricao()){
			case "A": return "produto.descricao, produto.descricaofiscal";
			case "C": return "produto.descricaofiscal AS descricao, produto.descricaofiscal";
			case "R": return "produto.descricao, produto.descricao AS descricaofiscal";
		}
	}

	function sql_tipopreco(){
		return sql_tipopreco($this->tipopreco);
	}

	function getconnection(){
		return $this->con;
	}

	function getestabelecimento(){
		return $this->estabelecimento;
	}

	function getdatalog(){
		return $this->datalog;
	}

	function getdatalogfim(){
		return $this->datalogfim;
	}

	function getdtmovto(){
		return $this->dtmovto;
	}

	function getfrentecaixa(){
		return $this->frentecaixa;
	}

	function gethoralog(){
		return $this->horalog;
	}

	function gethoralogfim(){
		return $this->horalog;
	}

	function gettipopreco(){
		return $this->tipopreco;
	}

	function geturgente(){
		return $this->urgente;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setdatalog($datalog){
		$this->datalog = value_date($datalog);
	}

	function setdatalogfim($datalogfim){
		$this->datalogfim = value_date($datalogfim);
	}

	function setdtmovto($dtmovto){
		$this->dtmovto = value_date($dtmovto);
	}

	function setfrentecaixa($frentecaixa){
		$this->frentecaixa = $frentecaixa;
	}

	function sethoralog($horalog){
		$this->horalog = value_time($horalog);
	}

	function sethoralogfim($horalogfim){
		$this->horalogfim = value_time($horalogfim);
	}

	function settipopreco($tipopreco){
		if($tipopreco != "A"){
			$tipopreco = "V";
		}
		$this->tipopreco = $tipopreco;
	}

	function seturgente($urgente){
		if(is_bool($urgente)){
			$this->urgente = $urgente;
		}
	}

}
