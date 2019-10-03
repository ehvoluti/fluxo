<?php

require_once("../class/consultaproduto.class.php");

class ColetorDados{

	private $con;
	private $estabelecimento;
	private $paramcoletor;

	function __construct($con){
		$this->con = $con;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
		$this->paramcoletor = objectbytable("paramcoletor", $this->estabelecimento->getcodestabelec(), $this->con);
	}

	private function validar(){
		if(!is_object($this->estabelecimento)){
			$_SESSION["ERROR"] = "Informe o estabelecimento antes de efeturar qualquer opera&ccedil;&atilde;o com o coletor de dados.";
			return FALSE;
		}elseif(!$this->estabelecimento->exists()){
			$_SESSION["ERROR"] = "O estabelecimento informado n&atilde;o foi encontrado.";
			return FALSE;
		}elseif(!$this->paramcoletor->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros para coletor de dados n&atilde;o foram informados.<br><a onclick=\"$.messageBox('close'); openProgram('ParamColetor')\">Clique aqui</a> para abrir os par&acirc;metros para coletor de dados.";
			return FALSE;
		}elseif(strlen($this->estabelecimento->getdircoletor()) == 0){
			$_SESSION["ERROR"] = "Diret&oacute;rio de exporta&ccedil;&atilde;o/importa&ccedil;&atilde;o de arquivos do coletor de dados n&atilde;o foi informado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para informar o diret&oacute;rio no cadastro do estabelecimento.";
			return FALSE;
		}elseif(!is_dir($this->estabelecimento->getdircoletor()) && param("SISTEMA", "TIPOSERVIDOR", $this->con) == 0){
			//$_SESSION["ERROR"] = "O diret&oacute;rio informado no estabecimento para exporta&ccedil;&atilde;o/importa&ccedil;&atilde;o de arquivos do coletor de dados n&atilde;o foi encontrado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para corrigir o diret&oacute;rio no cadastro do estabelecimento.";
			return true;
		}else{
			return TRUE;
		}
	}

	function exportar(){
		if(!$this->validar()){
			return FALSE;
		}
		$query = "SELECT produto.codproduto, ltrim(produtoean.codean,'0') AS codean, produto.descricaofiscal AS descricao, produtoestab.precovrj AS preco, produtoestab.sldatual AS estoque ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
		$query .= "	AND produtoestab.disponivel = 'S' ";
		$query .= "ORDER BY produto.codproduto ";
		$res = $this->con->query($query);
		$arr_query = $res->fetchAll(2);
		echo $query;
		if(strlen($this->paramcoletor->getexp_pos_codproduto()) > 0){
			$arr_campos[$this->paramcoletor->getexp_pos_codproduto()] = array(
				"nome" => "codproduto",
				"posicao" => $this->paramcoletor->getexp_pos_codproduto(),
				"tamanho" => $this->paramcoletor->getexp_tam_codproduto(),
				"str_pad" => STR_PAD_LEFT
			);
		}
		if(strlen($this->paramcoletor->getexp_pos_codean()) > 0){
			$arr_campos[$this->paramcoletor->getexp_pos_codean()] = array(
				"nome" => "codean",
				"posicao" => $this->paramcoletor->getexp_pos_codean(),
				"tamanho" => $this->paramcoletor->getexp_tam_codean(),
				"str_pad" => STR_PAD_LEFT
			);
		}
		if(strlen($this->paramcoletor->getexp_pos_descricao()) > 0){
			$arr_campos[$this->paramcoletor->getexp_pos_descricao()] = array(
				"nome" => "descricao",
				"posicao" => $this->paramcoletor->getexp_pos_descricao(),
				"tamanho" => $this->paramcoletor->getexp_tam_descricao(),
				"str_pad" => STR_PAD_RIGHT
			);
		}
		if(strlen($this->paramcoletor->getexp_pos_preco()) > 0){
			$arr_campos[$this->paramcoletor->getexp_pos_preco()] = array(
				"nome" => "preco",
				"posicao" => $this->paramcoletor->getexp_pos_preco(),
				"tamanho" => $this->paramcoletor->getexp_tam_preco(),
				"decimal" => $this->paramcoletor->getexp_dec_preco(),
				"str_pad" => STR_PAD_LEFT
			);
		}
		if(strlen($this->paramcoletor->getexp_pos_estoque()) > 0){
			$arr_campos[$this->paramcoletor->getexp_pos_estoque()] = array(
				"nome" => "estoque",
				"posicao" => $this->paramcoletor->getexp_pos_estoque(),
				"tamanho" => $this->paramcoletor->getexp_tam_estoque(),
				"decimal" => $this->paramcoletor->getexp_dec_estoque(),
				"str_pad" => STR_PAD_LEFT
			);
		}
		ksort($arr_campos);
		$linhas = array();
		foreach($arr_query as $row){
			$linha = "";
			foreach($arr_campos as $campo){
				if(strlen($campo["posicao"]) == 0 || strlen($campo["tamanho"]) == 0){
					continue;
				}
				$linha = str_pad(substr($linha, 0, $campo["posicao"] - 1), $campo["posicao"] - 1, " ", STR_PAD_RIGHT);
				$valor = $row[$campo["nome"]];
				if(isset($campo["decimal"])){
					$valor = number_format($valor, $campo["decimal"], $this->paramcoletor->getsepdecimal(), "");
				}
				$linha .= str_pad(substr($valor, 0, $campo["tamanho"]), $campo["tamanho"], " ", $campo["str_pad"]);
			}
			$linhas[] = $linha;
		}
		if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == 1){
			echo write_file($this->estabelecimento->getdircoletor()."\\".$this->paramcoletor->getexp_nomearquivo(), $linhas);
		}else{
			$f = fopen($this->estabelecimento->getdircoletor()."\\".$this->paramcoletor->getexp_nomearquivo(), "w+");
			fwrite($f, implode("\r\n", $linhas)."\r\n");
			fclose($f);
		}
		return TRUE;
	}

	function importar($conteudo = null){
		if(!$this->validar()){
			return FALSE;
		}
		if(is_null($conteudo)){
			$file_name = $this->estabelecimento->getdircoletor().$this->paramcoletor->getimp_nomearquivo();
			if(!file_exists($file_name)){
				$file_name = "../temp/upload/coletor.txt";
				if(!file_exists($file_name)){
					$_SESSION["ERROR"] = "O arquivo n&atilde;o foi encontrado:<br>".str_replace("\\", "\\\\",  $this->estabelecimento->getdircoletor().$this->paramcoletor->getimp_nomearquivo());
					return FALSE;
				}
			}
			$file = fopen($file_name, "r");
			$linhas = array();
			while(!feof($file)){
				$linhas[] = fgets($file, 4096);
			}
			fclose($file);
		}else{
			$linhas = explode("\n", $conteudo);
		}

		$arr_campos = array();
		if(strlen($this->paramcoletor->getimp_pos_codproduto()) > 0){
			$arr_campos["codproduto"] = array(
				"posicao" => $this->paramcoletor->getimp_pos_codproduto(),
				"tamanho" => $this->paramcoletor->getimp_tam_codproduto()
			);
		}
		if(strlen($this->paramcoletor->getimp_pos_codean()) > 0){
			$arr_campos["codean"] = array(
				"posicao" => $this->paramcoletor->getimp_pos_codean(),
				"tamanho" => $this->paramcoletor->getimp_tam_codean()
			);
		}
		if(strlen($this->paramcoletor->getimp_pos_quantidade()) > 0){
			$arr_campos["quantidade"] = array(
				"posicao" => $this->paramcoletor->getimp_pos_quantidade(),
				"tamanho" => $this->paramcoletor->getimp_tam_quantidade(),
				"decimal" => $this->paramcoletor->getimp_dec_quantidade()
			);
		}
		if(strlen($this->paramcoletor->getimp_pos_preco()) > 0){
			$arr_campos["preco"] = array(
				"posicao" => $this->paramcoletor->getimp_pos_preco(),
				"tamanho" => $this->paramcoletor->getimp_tam_preco(),
				"decimal" => $this->paramcoletor->getimp_dec_preco()
			);
		}

		$sepdecimal = $this->paramcoletor->getsepdecimal();
		$arr_valores = array();
		if(strlen($this->paramcoletor->getimp_separador()) > 0){
			$imp_separador = $this->paramcoletor->getimp_separador();
			$aux_valores = array();

			foreach($linhas as $linha){
				if(strlen(trim($linha)) == 0){
					continue;
				}else{
					$aux_linha = explode($imp_separador, $linha);
					if(strlen($aux_linha[0]) < 8){
						$aux_valores["codproduto"] = $aux_linha[0];
					}else{
						$aux_valores["codean"] = $aux_linha[0];
					}
					$aux_valores["quantidade"] = $aux_linha[1];
					$aux_valores["preco"] = $aux_linha[2];
					$arr_valores[] = $aux_valores;
				}
			}
		}else{
			foreach($linhas as $linha){
				if(strlen(trim($linha)) == 0){
					continue;
				}
				$valores = array();
				foreach($arr_campos as $nome => $campo){
					$valor = trim(substr($linha, ($campo["posicao"] - 1), $campo["tamanho"]));
					if(isset($campo["decimal"])){
						if(strlen($sepdecimal) == 0){
							$valor = $valor / pow(10, $campo["decimal"]);
						}elseif(strlen($sepdecimal) > 0 && strstr($valor, $sepdecimal)){
							$valor = number_format(str_replace($sepdecimal, ".", $valor), 4, ".", "");
						}
					}
					$valores[$nome] = $valor;
				}
				$arr_valores[] = $valores;
			}
		}

		$consultaproduto = new ConsultaProduto($this->con);
		foreach($arr_valores as $valores){
			if(strlen($valores["codean"]) > 0){
				$consultaproduto->addcodproduto($valores["codean"]);
			}
		}
		$consultaproduto->consultar(FALSE, TRUE);
		$arr_encontrado = $consultaproduto->getencontrado();
		foreach($arr_encontrado as $codoriginal => $codcorreto){
			foreach($arr_valores as $i => $valores){
				if($valores["codean"] == $codoriginal){
					$arr_valores[$i]["codproduto"] = $codcorreto;
				}
			}
		}

		return $arr_valores;
	}

}
