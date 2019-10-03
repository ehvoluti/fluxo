<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Layout extends Cadastro{

	function __construct($codlayout = NULL){
		parent::__construct();
		$this->table = "layout";
		$this->primarykey = array("codlayout");
		$this->setcodlayout($codlayout);
		if(!is_null($this->getcodlayout())){
			$this->searchbyobject();
		}
	}

	function exportar($codestabelec = NULL, $disponivel = FALSE, $codfornec = NULL, $codgrupo = NULL, $codsubgrupo = NULL, $coddepto = NULL, $codfamilia = NULL, $codmarca = NULL, $estoque = FALSE, $foralinha = NULL, $codinventario = NULL, $download = "N"){
		if($this->gettipolayout() != "E"){
			$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel criar arquivo para layout do tipo importa&ccedil;&atilde;o.";
			return FALSE;
		}

		if(strlen($codestabelec) == 0){
			$codestabelec = NULL;
		}
		if(strlen($codfornec) == 0){
			$codfornec = NULL;
		}
		if(strlen($codgrupo) == 0){
			$codgrupo = NULL;
		}
		if(strlen($codsubgrupo) == 0){
			$codsubgrupo = NULL;
		}
		if(strlen($coddepto) == 0){
			$coddepto = NULL;
		}
		if(strlen($codfamilia) == 0){
			$codfamilia = NULL;
		}
		if(strlen($codmarca) == 0){
			$codmarca = NULL;
		}
		if(strlen($codinventario) == 0){
			$codinventario = NULL;
		}

		$aux_query = array();

		$query = "SELECT DISTINCT produtoestab.codestabelec, produto.codproduto, produto.descricaofiscal AS descricao, produto.descricao AS descricaoreduz, ";
		$query .= "produtoestab.custotab AS custo, produtoestab.precovrjof, departamento.nome AS departamento, grupoprod.descricao AS grupo, ";
		$query .= " '".$this->gettextofixo()."' AS textofixo, ";
		$query .= ($this->getprecooferta() == "S" ? sql_tipopreco("A", TRUE, "precoatc").", ".sql_tipopreco("V", TRUE, "precovrj") : "produtoestab.precoatc, produtoestab.precovrj").", ";
		$query .= "	produtoestab.sldatual AS estoque, ";
		if($this->getgerartodoseans() == "S"){
			//$query .= "	trim(both ' ' from produtoean.codean)::numeric(20,0) AS codean ";
			$aux_query[] = "	produtoean.codean, produtoean.codean::bigint AS order_codean  ";
		}else{
			//$query .= "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1)::numeric(20,0) AS codean ";
			$aux_query[] = "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codean, (SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1)::bigint AS order_codean ";
		}

		if($this->getexb_siglaunidadesaida() == "S"){
			$aux  = "(SELECT unidade.sigla ";
			$aux .= "FROM produto as produtoaux ";
			$aux .= "INNER JOIN embalagem ON (embalagem.codembal = produtoaux.codembalvda) ";
			$aux .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
			$aux .= "WHERE codproduto = produto.codproduto) AS siglaunidadesaida ";

			$aux_query[] = $aux;
			unset($aux);
		}

		$query .= implode(",",$aux_query);

		$query .= "FROM produto ";
		$query .= "INNER JOIN departamento ON (produto.coddepto = departamento.coddepto) ";
		$query .= "INNER JOIN grupoprod ON (produto.codgrupo = grupoprod.codgrupo) ";
		$query .= " ";
		if(!is_null($codfornec)){
			$query .= "INNER JOIN prodfornec ON (produto.codproduto = prodfornec.codproduto)";
		}
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		if($this->getgerartodoseans() == "S"){
			$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto AND produtoean.codean ~ '^[-0-9]+$') ";
		}

		if(!is_null($codinventario)){
			$query .= "LEFT JOIN itinventario ON (produto.codproduto = itinventario.codproduto) ";
		}

		if(!is_null($codestabelec)){
			$query .= " WHERE produtoestab.codestabelec = ".$codestabelec." ";
			if(!$disponivel){
				$query .= "	AND produtoestab.disponivel = 'S' ";
			}
		}

		if(!is_null($codfornec)){
			$query .= " AND prodfornec.codfornec = ".$codfornec." ";
		}
		if(!is_null($codgrupo)){
			$query .= " AND produto.codgrupo = ".$codgrupo." ";
		}
		if(!is_null($codsubgrupo)){
			$query .= " AND produto.codsubgrupo = ".$codsubgrupo." ";
		}
		if(!is_null($codfamilia)){
			$query .= " AND produto.codfamilia = ".$codfamilia." ";
		}
		if(!is_null($codmarca)){
			$query .= " AND produto.codmarca = ".$codmarca." ";
		}
		if(!is_null($coddepto)){
			$query .= " AND produto.coddepto = ".$coddepto." ";
		}

		if(!is_null($codinventario)){
			$query .= " AND itinventario.codinventario = ".$codinventario." ";
		}

		if(strlen($foralinha) > 0){
			$query .= " AND produto.foralinha = '".$foralinha."' ";
		}

		if(strlen($estoque) > 0){
			$query .= " AND produtoestab.sldatual > 0 ";
		}

		if(strlen($this->getordem())){
			if($this->getordem() == "13"){
				$query .= "ORDER BY order_codean ";
			}else{
				$query .= "ORDER BY ".$this->getordem()." ";
			}
		}else{
			$query .= "ORDER BY produtoestab.codestabelec, produto.descricaofiscal ";
		}

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$linhas = array();
		foreach($arr as $row){
			$linha = "";
			if($this->gettipotabulacao() == "C"){ // Por caractere
				$colunas = array();
				if($this->getexb_codean() == "S"){
					$colunas[$this->getord_codean()] = $row["codean"];
				}
				if($this->getexb_codestabelec() == "S"){
					$colunas[$this->getord_codestabelec()] = $row["codestabelec"];
				}
				if($this->getexb_codproduto() == "S"){
					$colunas[$this->getord_codproduto()] = $row["codproduto"];
				}
				if($this->getexb_custo() == "S"){
					$colunas[$this->getord_custo()] = number_format($row["custo"], $this->getdec_custo(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_descricao() == "S"){
					$colunas[$this->getord_descricao()] = (strlen($this->gettam_descricao()) > 0 ? substr($row["descricao"], 0, $this->gettam_descricao()) : $row["descricao"]);
				}
				if($this->getexb_descricaoreduz() == "S"){
					$colunas[$this->getord_descricaoreduz()] = (strlen($this->gettam_descricaoreduz()) > 0 ? substr($row["descricaoreduz"], 0, $this->gettam_descricaoreduz()) : $row["descricaoreduz"]);
				}
				if($this->getexb_estoque() == "S"){
					$colunas[$this->getord_estoque()] = number_format($row["estoque"], $this->getdec_estoque(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_quantidade() == "S"){
					$colunas[$this->getord_quantidade()] = number_format($row["estoque"], $this->getdec_quantidade(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_precoatc() == "S"){
					$colunas[$this->getord_precoatc()] = number_format($row["precoatc"], $this->getdec_precoatc(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_precovrj() == "S"){
					$colunas[$this->getord_precovrj()] = number_format($row["precovrj"], $this->getdec_precovrj(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_precovrjof() == "S"){
					$colunas[$this->getord_precovrjof()] = number_format($row["precovrjof"], $this->getdec_precovrjof(), $this->getseparadordecimal(), "");
				}
				if($this->getexb_textofixo() == "S"){
					$colunas[$this->getord_textofixo()] = (strlen($this->gettam_textofixo()) > 0 ? substr($this->gettextofixo(), 0, $this->gettam_textofixo()) : $this->gettextofixo());
				}
				if($this->getexb_departamento() == "S"){
					$colunas[$this->getord_departamento()] = (strlen($this->gettam_departamento()) > 0 ? substr($row["departamento"], 0, $this->gettam_departamento()) : $row["departamento"]);
				}
				if($this->getexb_grupo() == "S"){
					$colunas[$this->getord_grupo()] = (strlen($this->gettam_grupo()) > 0 ? substr($row["grupo"], 0, $this->gettam_grupo()) : $row["grupo"]);
				}
				if($this->getexb_siglaunidadesaida() == "S"){
					$colunas[$this->getord_siglaunidadesaida()] = (strlen($this->gettam_siglaunidadesaida()) > 0 ? substr($row["siglaunidadesaida"], 0, $this->gettam_siglaunidadesaida()) : $row["siglaunidadesaida"]);
				}
				$valores = array();
				for($i = 1; $i <= $this->getnumerocolunas() && $i < 200; $i++){
					$valores[$i] = $colunas[$i];
				}
				$linha = implode($this->getseparadorcoluna(), $valores);
			}elseif($this->gettipotabulacao() == "P"){ // Por posicao
				$colunas = array();
				if($this->getexb_codean() == "S"){
					$colunas[$this->getpos_codean()] = "codean";
				}
				if($this->getexb_codestabelec() == "S"){
					$colunas[$this->getpos_codestabelec()] = "codestabelec";
				}
				if($this->getexb_codproduto() == "S"){
					$colunas[$this->getpos_codproduto()] = "codproduto";
				}
				if($this->getexb_custo() == "S"){
					$colunas[$this->getpos_custo()] = "custo";
				}
				if($this->getexb_descricao() == "S"){
					$colunas[$this->getpos_descricao()] = "descricao";
				}
				if($this->getexb_descricaoreduz() == "S"){
					$colunas[$this->getpos_descricaoreduz()] = "descricaoreduz";
				}
				if($this->getexb_estoque() == "S"){
					$colunas[$this->getpos_estoque()] = "estoque";
				}
				if($this->getexb_quantidade() == "S"){
					$colunas[$this->getpos_quantidade()] = "quantidade";
				}
				if($this->getexb_precoatc() == "S"){
					$colunas[$this->getpos_precoatc()] = "precoatc";
				}
				if($this->getexb_precovrj() == "S"){
					$colunas[$this->getpos_precovrj()] = "precovrj";
				}
				if($this->getexb_precovrjof() == "S"){
					$colunas[$this->getpos_precovrjof()] = "precovrjof";
				}
				if($this->getexb_textofixo() == "S"){
					$colunas[$this->getpos_textofixo()] = "textofixo";
				}
				if($this->getexb_departamento() == "S"){
					$colunas[$this->getpos_departamento()] = "departamento";
				}
				if($this->getexb_grupo() == "S"){
					$colunas[$this->getpos_grupo()] = "grupo";
				}
				if($this->getexb_siglaunidadesaida() == "S"){
					$colunas[$this->getpos_siglaunidadesaida()] = "siglaunidadesaida";
				}
				ksort($colunas);
				$linha = "";
				foreach($colunas as $pos => $coluna){
					$linha = str_pad(substr($linha, 0, ($pos - 1)), ($pos - 1), " ", STR_PAD_RIGHT);
					$valor = $row[$coluna];
					if(method_exists($this, "getdec_".$coluna) && in_array($coluna, array("precovrj","precoatc","precovrjof","estoque"))){
						$valor = number_format($valor, call_user_func(array($this, "getdec_".$coluna)), (string) $this->getseparadordecimal(), "");
					}
					$linha .= str_pad(substr($valor, 0, call_user_func(array($this, "gettam_".$coluna))), call_user_func(array($this, "gettam_".$coluna)), call_user_func(array($this, "getcom_".$coluna)), (call_user_func(array($this, "getali_".$coluna)) == "L" ? STR_PAD_RIGHT : STR_PAD_LEFT));
				}
			}
			$linhas[] = $linha;
		}
		if($download == "S"){
			echo write_file($this->getnomearquivo(), $linhas, TRUE);
		}else{
			if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"){
				write_file($this->getnomearquivo(), $linhas, TRUE);
				@chmod($this->getnomearquivo(), 0777);
			}else{
				echo write_file($this->getnomearquivo(), $linhas, FALSE);
			}
		}
		return TRUE;
	}

	function importar(){
		$this->searchbyobject();
		$dados = array();
		if($this->fields["tipotabulacao"] == "C"){
			$arquivo = $this->getnomearquivo();
			if(file_exists($arquivo)){
				$handle = fopen($arquivo, "r");
				if($handle){
					while(!feof($handle)){
						$buffer = fgets($handle);
						$aux = explode($this->getseparadorcoluna(), $buffer);
						if($this->getexb_codean() == "S"){
							$dados["codean"][] = $aux[$this->getord_codean()];
						}
						if($this->getexb_codestabelec() == "S"){
							$dados["codestabelec"][] = $aux[$this->getord_codestabelec()];
						}
						if($this->getexb_codproduto() == "S"){
							$dados["codproduto"][] = $aux[$this->getord_codproduto()];
						}
						if($this->getexb_descricao() == "S"){
							$dados["descricao"][] = $aux[$this->getord_descricao()];
						}
						if($this->getexb_descricaoreduz() == "S"){
							$dados["descricaoreduz"][] = $aux[$this->getord_descricaoreduz()];
						}
						if($this->getexb_custo() == "S"){
							$dados["custo"][] = $aux[$this->getord_custo()];
						}
						if($this->getexb_precoatc() == "S"){
							$dados["precoatc"][] = $aux[$this->getord_precoatc()];
						}
						if($this->getexb_precovrj() == "S"){
							$dados["precovrj"][] = $aux[$this->getord_precovrj()];
						}
						if($this->getexb_precovrjof() == "S"){
							$dados["precovrjof"][] = $aux[$this->getord_precovrjof()];
						}
						if($this->getexb_estoque() == "S"){
							$dados["estoque"][] = $aux[$this->getord_estoque()];
						}
						if($this->getexb_quantidade() == "S"){
							$dados["quantidade"][] = $aux[$this->getord_quantidade()];
						}
					}
					fclose($handle);
				}
			}else{
				echo messagebox("error", "", "Arquivo não encontrado.");
			}
		}else{
			$arquivo = $this->getnomearquivo();
			if(file_exists($arquivo)){
				$handle = fopen($arquivo, "r");
				if($handle){
					while(!feof($handle)){
						$buffer = fgets($handle);
						if($this->getexb_codean() == "S"){
							$dados["codean"][] = substr($buffer, $this->getpos_codean(), $this->gettam_codean());
						}
						if($this->getexb_codestabelec() == "S"){
							$dados["codestabelec"][] = substr($buffer, $this->getpos_codestabelec(), $this->gettam_codestabelec());
						}
						if($this->getexb_codproduto() == "S"){
							$dados["codproduto"][] = substr($buffer, $this->getpos_codproduto(), $this->gettam_codproduto());
						}
						if($this->getexb_descricao() == "S"){
							$dados["descricao"][] = substr($buffer, $this->getpos_descricao(), $this->gettam_descricao());
						}
						if($this->getexb_descricaoreduz() == "S"){
							$dados["descricaoreduz"][] = substr($buffer, $this->getpos_descricaoreduz(), $this->gettam_descricaoreduz());
						}
						if($this->getexb_custo() == "S"){
							$dados["custo"][] = substr($buffer, $this->getpos_custo(), $this->gettam_custo());
						}
						if($this->getexb_precoatc() == "S"){
							$dados["precoatc"][] = substr($buffer, $this->getpos_precoatc(), $this->gettam_precoatc());
						}
						if($this->getexb_precovrj() == "S"){
							$dados["precovrj"][] = substr($buffer, $this->getpos_precovrj(), $this->gettam_precovrj());
						}
						if($this->getexb_precovrjof() == "S"){
							$dados["precovrjof"][] = substr($buffer, $this->getpos_precovrjof(), $this->gettam_precovrjof());
						}
						if($this->getexb_estoque() == "S"){
							$dados["estoque"][] = substr($buffer, $this->getpos_estoque(), $this->gettam_estoque());
						}
						if($this->getexb_quantidade() == "S"){
							$dados["quantidade"][] = substr($buffer, $this->getpos_quantidade(), $this->gettam_quantidade());
						}
					}
					fclose($handle);
				}
			}else{
				echo messagebox("error", "", "Arquivo não encontrado.");
			}
		}
		return $dados;
	}

	function getcodlayout(){
		return $this->fields["codlayout"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getnomearquivo(){
		return $this->fields["nomearquivo"];
	}

	function gettipolayout(){
		return $this->fields["tipolayout"];
	}

	function gettipotabulacao(){
		return $this->fields["tipotabulacao"];
	}

	function getseparadordecimal(){
		return $this->fields["separadordecimal"];
	}

	function getseparadorcoluna(){
		return $this->fields["separadorcoluna"];
	}

	function getgerartodoseans(){
		return $this->fields["gerartodoseans"];
	}

	function getselecionarestab(){
		return $this->fields["selecionarestab"];
	}

	function getexb_codestabelec(){
		return $this->fields["exb_codestabelec"];
	}

	function getpos_codestabelec(){
		return $this->fields["pos_codestabelec"];
	}

	function gettam_codestabelec(){
		return $this->fields["tam_codestabelec"];
	}

	function getali_codestabelec(){
		return $this->fields["ali_codestabelec"];
	}

	function getcom_codestabelec(){
		return $this->fields["com_codestabelec"];
	}

	function getord_codestabelec(){
		return $this->fields["ord_codestabelec"];
	}

	function getexb_codproduto(){
		return $this->fields["exb_codproduto"];
	}

	function getpos_codproduto(){
		return $this->fields["pos_codproduto"];
	}

	function gettam_codproduto(){
		return $this->fields["tam_codproduto"];
	}

	function getali_codproduto(){
		return $this->fields["ali_codproduto"];
	}

	function getcom_codproduto(){
		return $this->fields["com_codproduto"];
	}

	function getord_codproduto(){
		return $this->fields["ord_codproduto"];
	}

	function getexb_descricao(){
		return $this->fields["exb_descricao"];
	}

	function getpos_descricao(){
		return $this->fields["pos_descricao"];
	}

	function gettam_descricao(){
		return $this->fields["tam_descricao"];
	}

	function getali_descricao(){
		return $this->fields["ali_descricao"];
	}

	function getcom_descricao(){
		return $this->fields["com_descricao"];
	}

	function getord_descricao(){
		return $this->fields["ord_descricao"];
	}

	function getexb_codean(){
		return $this->fields["exb_codean"];
	}

	function getpos_codean(){
		return $this->fields["pos_codean"];
	}

	function gettam_codean(){
		return $this->fields["tam_codean"];
	}

	function getali_codean(){
		return $this->fields["ali_codean"];
	}

	function getcom_codean(){
		return $this->fields["com_codean"];
	}

	function getord_codean(){
		return $this->fields["ord_codean"];
	}

	function getexb_custo(){
		return $this->fields["exb_custo"];
	}

	function getpos_custo(){
		return $this->fields["pos_custo"];
	}

	function gettam_custo(){
		return $this->fields["tam_custo"];
	}

	function getdec_custo(){
		return $this->fields["dec_custo"];
	}

	function getali_custo(){
		return $this->fields["ali_custo"];
	}

	function getcom_custo(){
		return $this->fields["com_custo"];
	}

	function getord_custo(){
		return $this->fields["ord_custo"];
	}

	function getexb_precoatc(){
		return $this->fields["exb_precoatc"];
	}

	function getpos_precoatc(){
		return $this->fields["pos_precoatc"];
	}

	function gettam_precoatc(){
		return $this->fields["tam_precoatc"];
	}

	function getdec_precoatc(){
		return $this->fields["dec_precoatc"];
	}

	function getali_precoatc(){
		return $this->fields["ali_precoatc"];
	}

	function getcom_precoatc(){
		return $this->fields["com_precoatc"];
	}

	function getord_precoatc(){
		return $this->fields["ord_precoatc"];
	}

	function getexb_precovrj(){
		return $this->fields["exb_precovrj"];
	}

	function getpos_precovrj(){
		return $this->fields["pos_precovrj"];
	}

	function gettam_precovrj(){
		return $this->fields["tam_precovrj"];
	}

	function getdec_precovrj(){
		return $this->fields["dec_precovrj"];
	}

	function getali_precovrj(){
		return $this->fields["ali_precovrj"];
	}

	function getcom_precovrj(){
		return $this->fields["com_precovrj"];
	}

	function getord_precovrj(){
		return $this->fields["ord_precovrj"];
	}

	function getexb_estoque(){
		return $this->fields["exb_estoque"];
	}

	function getpos_estoque(){
		return $this->fields["pos_estoque"];
	}

	function gettam_estoque(){
		return $this->fields["tam_estoque"];
	}

	function getdec_estoque(){
		return $this->fields["dec_estoque"];
	}

	function getali_estoque(){
		return $this->fields["ali_estoque"];
	}

	function getcom_estoque(){
		return $this->fields["com_estoque"];
	}

	function getord_estoque(){
		return $this->fields["ord_estoque"];
	}
	function getexb_quantidade(){
		return $this->fields["exb_quantidade"];
	}

	function getpos_quantidade(){
		return $this->fields["pos_quantidade"];
	}

	function gettam_quantidade(){
		return $this->fields["tam_quantidade"];
	}

	function getdec_quantidade(){
		return $this->fields["dec_quantidade"];
	}

	function getali_quantidade(){
		return $this->fields["ali_quantidade"];
	}

	function getcom_quantidade(){
		return $this->fields["com_quantidade"];
	}

	function getord_quantidade(){
		return $this->fields["ord_quantidade"];
	}

	function getnumerocolunas(){
		return $this->fields["numerocolunas"];
	}

	function getexb_descricaoreduz(){
		return $this->fields["exb_descricaoreduz"];
	}

	function getpos_descricaoreduz(){
		return $this->fields["pos_descricaoreduz"];
	}

	function gettam_descricaoreduz(){
		return $this->fields["tam_descricaoreduz"];
	}

	function getali_descricaoreduz(){
		return $this->fields["ali_descricaoreduz"];
	}

	function getcom_descricaoreduz(){
		return $this->fields["com_descricaoreduz"];
	}

	function getord_descricaoreduz(){
		return $this->fields["ord_descricaoreduz"];
	}

	function getprecooferta(){
		return $this->fields["precooferta"];
	}

	function getexb_precovrjof(){
		return $this->fields["exb_precovrjof"];
	}

	function getpos_precovrjof(){
		return $this->fields["pos_precovrjof"];
	}

	function gettam_precovrjof(){
		return $this->fields["tam_precovrjof"];
	}

	function getali_precovrjof(){
		return $this->fields["ali_precovrjof"];
	}

	function getcom_precovrjof(){
		return $this->fields["com_precovrjof"];
	}

	function getord_precovrjof(){
		return $this->fields["ord_precovrjof"];
	}

	function gettextofixo(){
		return $this->fields["textofixo"];
	}

	function getexb_textofixo(){
		return $this->fields["exb_textofixo"];
	}

	function getpos_textofixo(){
		return $this->fields["pos_textofixo"];
	}

	function gettam_textofixo(){
		return $this->fields["tam_textofixo"];
	}

	function getali_textofixo(){
		return $this->fields["ali_textofixo"];
	}

	function getcom_textofixo(){
		return $this->fields["com_textofixo"];
	}

	function getord_textofixo(){
		return $this->fields["ord_textofixo"];
	}

	function getdec_precovrjof(){
		return $this->fields["dec_precovrjof"];
	}

	function getordem($format = FALSE){
		return ($format ? number_format($this->fields["ordem"], 0, ",", "") : $this->fields["ordem"]);
	}

	function getexb_departamento(){
		return $this->fields["exb_departamento"];
	}

	function getpos_departamento(){
		return $this->fields["pos_departamento"];
	}

	function gettam_departamento(){
		return $this->fields["tam_departamento"];
	}

	function getdec_departamento(){
		return $this->fields["dec_departamento"];
	}

	function getali_departamento(){
		return $this->fields["ali_departamento"];
	}

	function getcom_departamento(){
		return $this->fields["com_departamento"];
	}

	function getord_departamento(){
		return $this->fields["ord_departamento"];
	}

	function getexb_grupo(){
		return $this->fields["exb_grupo"];
	}

	function getpos_grupo(){
		return $this->fields["pos_grupo"];
	}

	function gettam_grupo(){
		return $this->fields["tam_grupo"];
	}

	function getdec_grupo(){
		return $this->fields["dec_grupo"];
	}

	function getali_grupo(){
		return $this->fields["ali_grupo"];
	}

	function getcom_grupo(){
		return $this->fields["com_grupo"];
	}

	function getord_grupo(){
		return $this->fields["ord_grupo"];
	}

	function getautointervalo(){
		return $this->fields["autointervalo"];
	}

	function getautodtultgeracao($format = FALSE){
		return ($format ? convert_date($this->fields["autodtultgeracao"], "Y-m-d", "d/m/Y") : $this->fields["autodtultgeracao"]);
	}

	function getautohrultgeracao(){
		return substr($this->fields["autohrultgeracao"], 0, 8);
	}

	function getautofilcodestabelec(){
		return $this->fields["autofilcodestabelec"];
	}

	function getautofilforalinha(){
		return $this->fields["autofilforalinha"];
	}

	function getexb_siglaunidadesaida(){
		return $this->fields["exb_siglaunidadesaida"];
	}

	function getpos_siglaunidadesaida(){
		return $this->fields["pos_siglaunidadesaida"];
	}

	function gettam_siglaunidadesaida(){
		return $this->fields["tam_siglaunidadesaida"];
	}

	function getali_siglaunidadesaida(){
		return $this->fields["ali_siglaunidadesaida"];
	}

	function getcom_siglaunidadesaida(){
		return $this->fields["com_siglaunidadesaida"];
	}

	function getord_siglaunidadesaida(){
		return $this->fields["ord_siglaunidadesaida"];
	}

	function setcodlayout($value){
		$this->fields["codlayout"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 100);
	}

	function setnomearquivo($value){
		$this->fields["nomearquivo"] = value_string($value, 300);
	}

	function settipolayout($value){
		$this->fields["tipolayout"] = value_string($value, 1);
	}

	function settipotabulacao($value){
		$this->fields["tipotabulacao"] = value_string($value, 1);
	}

	function setseparadordecimal($value){
		$this->fields["separadordecimal"] = value_string($value, 1);
	}

	function setseparadorcoluna($value){
		$this->fields["separadorcoluna"] = value_string($value, 1);
	}

	function setgerartodoseans($value){
		$this->fields["gerartodoseans"] = value_string($value, 1);
	}

	function setselecionarestab($value){
		$this->fields["selecionarestab"] = value_string($value, 1);
	}

	function setexb_codestabelec($value){
		$this->fields["exb_codestabelec"] = value_string($value, 1);
	}

	function setpos_codestabelec($value){
		$this->fields["pos_codestabelec"] = value_numeric($value);
	}

	function settam_codestabelec($value){
		$this->fields["tam_codestabelec"] = value_numeric($value);
	}

	function setali_codestabelec($value){
		$this->fields["ali_codestabelec"] = value_string($value, 1);
	}

	function setcom_codestabelec($value){
		$this->fields["com_codestabelec"] = value_string($value, 1);
	}

	function setord_codestabelec($value){
		$this->fields["ord_codestabelec"] = value_numeric($value);
	}

	function setexb_codproduto($value){
		$this->fields["exb_codproduto"] = value_string($value, 1);
	}

	function setpos_codproduto($value){
		$this->fields["pos_codproduto"] = value_numeric($value);
	}

	function settam_codproduto($value){
		$this->fields["tam_codproduto"] = value_numeric($value);
	}

	function setali_codproduto($value){
		$this->fields["ali_codproduto"] = value_string($value, 1);
	}

	function setcom_codproduto($value){
		$this->fields["com_codproduto"] = value_string($value, 1);
	}

	function setord_codproduto($value){
		$this->fields["ord_codproduto"] = value_numeric($value);
	}

	function setexb_descricao($value){
		$this->fields["exb_descricao"] = value_string($value, 1);
	}

	function setpos_descricao($value){
		$this->fields["pos_descricao"] = value_numeric($value);
	}

	function settam_descricao($value){
		$this->fields["tam_descricao"] = value_numeric($value);
	}

	function setali_descricao($value){
		$this->fields["ali_descricao"] = value_string($value, 1);
	}

	function setcom_descricao($value){
		$this->fields["com_descricao"] = value_string($value, 1);
	}

	function setord_descricao($value){
		$this->fields["ord_descricao"] = value_numeric($value);
	}

	function setexb_codean($value){
		$this->fields["exb_codean"] = value_string($value, 1);
	}

	function setpos_codean($value){
		$this->fields["pos_codean"] = value_numeric($value);
	}

	function settam_codean($value){
		$this->fields["tam_codean"] = value_numeric($value);
	}

	function setali_codean($value){
		$this->fields["ali_codean"] = value_string($value, 1);
	}

	function setcom_codean($value){
		$this->fields["com_codean"] = value_string($value, 1);
	}

	function setord_codean($value){
		$this->fields["ord_codean"] = value_numeric($value);
	}

	function setexb_custo($value){
		$this->fields["exb_custo"] = value_string($value, 1);
	}

	function setpos_custo($value){
		$this->fields["pos_custo"] = value_numeric($value);
	}

	function settam_custo($value){
		$this->fields["tam_custo"] = value_numeric($value);
	}

	function setdec_custo($value){
		$this->fields["dec_custo"] = value_numeric($value);
	}

	function setali_custo($value){
		$this->fields["ali_custo"] = value_string($value, 1);
	}

	function setcom_custo($value){
		$this->fields["com_custo"] = value_string($value, 1);
	}

	function setord_custo($value){
		$this->fields["ord_custo"] = value_numeric($value);
	}

	function setexb_precoatc($value){
		$this->fields["exb_precoatc"] = value_string($value, 1);
	}

	function setpos_precoatc($value){
		$this->fields["pos_precoatc"] = value_numeric($value);
	}

	function settam_precoatc($value){
		$this->fields["tam_precoatc"] = value_numeric($value);
	}

	function setdec_precoatc($value){
		$this->fields["dec_precoatc"] = value_numeric($value);
	}

	function setali_precoatc($value){
		$this->fields["ali_precoatc"] = value_string($value, 1);
	}

	function setcom_precoatc($value){
		$this->fields["com_precoatc"] = value_string($value, 1);
	}

	function setord_precoatc($value){
		$this->fields["ord_precoatc"] = value_numeric($value);
	}

	function setexb_precovrj($value){
		$this->fields["exb_precovrj"] = value_string($value, 1);
	}

	function setpos_precovrj($value){
		$this->fields["pos_precovrj"] = value_numeric($value);
	}

	function settam_precovrj($value){
		$this->fields["tam_precovrj"] = value_numeric($value);
	}

	function setdec_precovrj($value){
		$this->fields["dec_precovrj"] = value_numeric($value);
	}

	function setali_precovrj($value){
		$this->fields["ali_precovrj"] = value_string($value, 1);
	}

	function setcom_precovrj($value){
		$this->fields["com_precovrj"] = value_string($value, 1);
	}

	function setord_precovrj($value){
		$this->fields["ord_precovrj"] = value_numeric($value);
	}

	function setexb_estoque($value){
		$this->fields["exb_estoque"] = value_string($value, 1);
	}

	function setpos_estoque($value){
		$this->fields["pos_estoque"] = value_numeric($value);
	}

	function settam_estoque($value){
		$this->fields["tam_estoque"] = value_numeric($value);
	}

	function setdec_estoque($value){
		$this->fields["dec_estoque"] = value_numeric($value);
	}

	function setali_estoque($value){
		$this->fields["ali_estoque"] = value_string($value, 1);
	}

	function setcom_estoque($value){
		$this->fields["com_estoque"] = value_string($value, 1);
	}

	function setord_estoque($value){
		$this->fields["ord_estoque"] = value_numeric($value);
	}
	function setexb_quantidade($value){
		$this->fields["exb_quantidade"] = value_string($value, 1);
	}

	function setpos_quantidade($value){
		$this->fields["pos_quantidade"] = value_numeric($value);
	}

	function settam_quantidade($value){
		$this->fields["tam_quantidade"] = value_numeric($value);
	}

	function setdec_quantidade($value){
		$this->fields["dec_quantidade"] = value_numeric($value);
	}

	function setali_quantidade($value){
		$this->fields["ali_quantidade"] = value_string($value, 1);
	}

	function setcom_quantidade($value){
		$this->fields["com_quantidade"] = value_string($value, 1);
	}

	function setord_quantidade($value){
		$this->fields["ord_quantidade"] = value_numeric($value);
	}

	function setnumerocolunas($value){
		$this->fields["numerocolunas"] = value_numeric($value);
	}

	function setexb_descricaoreduz($value){
		$this->fields["exb_descricaoreduz"] = value_string($value, 1);
	}

	function setpos_descricaoreduz($value){
		$this->fields["pos_descricaoreduz"] = value_numeric($value);
	}

	function settam_descricaoreduz($value){
		$this->fields["tam_descricaoreduz"] = value_numeric($value);
	}

	function setali_descricaoreduz($value){
		$this->fields["ali_descricaoreduz"] = value_string($value, 1);
	}

	function setcom_descricaoreduz($value){
		$this->fields["com_descricaoreduz"] = value_string($value, 1);
	}

	function setord_descricaoreduz($value){
		$this->fields["ord_descricaoreduz"] = value_numeric($value);
	}

	function setprecooferta($value){
		$this->fields["precooferta"] = value_string($value, 1);
	}

	function setexb_precovrjof($value){
		$this->fields["exb_precovrjof"] = value_string($value, 1);
	}

	function setpos_precovrjof($value){
		$this->fields["pos_precovrjof"] = value_numeric($value);
	}

	function settam_precovrjof($value){
		$this->fields["tam_precovrjof"] = value_numeric($value);
	}

	function setali_precovrjof($value){
		$this->fields["ali_precovrjof"] = value_string($value, 1);
	}

	function setcom_precovrjof($value){
		$this->fields["com_precovrjof"] = value_string($value, 1);
	}

	function setord_precovrjof($value){
		$this->fields["ord_precovrjof"] = value_numeric($value);
	}

	function settextofixo($value){
		$this->fields["textofixo"] = value_string($value, 20);
	}

	function setexb_textofixo($value){
		$this->fields["exb_textofixo"] = value_string($value, 1);
	}

	function setpos_textofixo($value){
		$this->fields["pos_textofixo"] = value_numeric($value);
	}

	function settam_textofixo($value){
		$this->fields["tam_textofixo"] = value_numeric($value);
	}

	function setali_textofixo($value){
		$this->fields["ali_textofixo"] = value_string($value, 1);
	}

	function setcom_textofixo($value){
		$this->fields["com_textofixo"] = value_string($value, 1);
	}

	function setord_textofixo($value){
		$this->fields["ord_textofixo"] = value_numeric($value);
	}

	function setdec_precovrjof($value){
		$this->fields["dec_precovrjof"] = value_numeric($value);
	}

	function setordem($value){
		$this->fields["ordem"] = value_numeric($value);
	}

	function setexb_departamento($value){
		$this->fields["exb_departamento"] = value_string($value, 1);
	}

	function setpos_departamento($value){
		$this->fields["pos_departamento"] = value_numeric($value);
	}

	function settam_departamento($value){
		$this->fields["tam_departamento"] = value_numeric($value);
	}

	function setdec_departamento($value){
		$this->fields["dec_departamento"] = value_numeric($value);
	}

	function setali_departamento($value){
		$this->fields["ali_departamento"] = value_string($value, 1);
	}

	function setcom_departamento($value){
		$this->fields["com_departamento"] = value_string($value, 1);
	}

	function setord_departamento($value){
		$this->fields["ord_departamento"] = value_numeric($value);
	}

	function setexb_grupo($value){
		$this->fields["exb_grupo"] = value_string($value, 1);
	}

	function setpos_grupo($value){
		$this->fields["pos_grupo"] = value_numeric($value);
	}

	function settam_grupo($value){
		$this->fields["tam_grupo"] = value_numeric($value);
	}

	function setdec_grupo($value){
		$this->fields["dec_grupo"] = value_numeric($value);
	}

	function setali_grupo($value){
		$this->fields["ali_grupo"] = value_string($value, 1);
	}

	function setcom_grupo($value){
		$this->fields["com_grupo"] = value_string($value, 1);
	}

	function setord_grupo($value){
		$this->fields["ord_grupo"] = value_numeric($value);
	}

	function setautointervalo($value){
		$this->fields["autointervalo"] = value_numeric($value);
	}

	function setautodtultgeracao($value){
		$this->fields["autodtultgeracao"] = value_date($value);
	}

	function setautohrultgeracao($value){
		$this->fields["autohrultgeracao"] = value_time($value);
	}

	function setautofilcodestabelec($value){
		$this->fields["autofilcodestabelec"] = value_numeric($value);
	}

	function setautofilforalinha($value){
		$this->fields["autofilforalinha"] = value_string($value, 1);
	}

	function setexb_siglaunidadesaida($value){
		$this->fields["exb_siglaunidadesaida"] = value_string($value, 1);
	}

	function setpos_siglaunidadesaida($value){
		$this->fields["pos_siglaunidadesaida"] = value_numeric($value);
	}

	function settam_siglaunidadesaida($value){
		$this->fields["tam_siglaunidadesaida"] = value_numeric($value);
	}

	function setali_siglaunidadesaida($value){
		$this->fields["ali_siglaunidadesaida"] = value_string($value, 1);
	}

	function setcom_siglaunidadesaida($value){
		$this->fields["com_siglaunidadesaida"] = value_string($value, 1);
	}

	function setord_siglaunidadesaida($value){
		$this->fields["ord_siglaunidadesaida"] = value_numeric($value);
	}

}