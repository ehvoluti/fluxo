<?php

/* ****************************
D E S C O N T I N U A D O
Data	07/12/2011
Autor	Murilo Strohmeier Feres
**************************** */

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class Gdr{
	private $con;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	
	function __construct(){
		$this->limpar_dados();
	}
	
	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}
	
	function exportar_cliente(){
		setprogress(0,"Buscando clientes",TRUE);
		$arr_linha = array();
		$query  = "SELECT cliente.codcliente, cliente.nome, cliente.cpfcnpj, cliente.enderres, cliente.numerores, ";
		$query .= "	cliente.bairrores, cidade.nome AS cidaderes, cliente.ufres, cliente.cepres, cliente.foneres, ";
		$query .= "	statuscliente.bloqueado, cliente.limite1, cliente.debito1, cliente.limite2, cliente.debito2 ";
		$query .= "FROM cliente ";
		$query .= "INNER JOIN cidade ON (cliente.codcidaderes = cidade.codcidade) ";
		$query .= "INNER JOIN statuscliente ON (cliente.codstatus = statuscliente.codstatus) ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			$saldo = ($row["limite1"] - $row["debito1"]) + ($row["limite2"] - $row["debito2"]);
			if($saldo < 0){
				$saldo = 0;
			}
			setprogress(($i + 1) / sizeof($arr) * 100,"Exportando clientes: ".($i + 1)." de ".sizeof($arr));
			$linha  = str_pad(substr(removeformat($row["cpfcnpj"]),0,18),18," ",STR_PAD_RIGHT); // CPF/CNPJ
			$linha .= str_pad(substr($row["nome"],0,40),40," ",STR_PAD_RIGHT); // Nome
			$linha .= str_pad(substr($row["bloqueado"],0,1),1,"S",STR_PAD_RIGHT); // (N = Negativo / S = Liberado)
			$linha .= str_pad(number_format($saldo,2,",",""),12,"0",STR_PAD_LEFT); // Saldo
			$linha .= str_pad(number_format($row["limite2"],2,",",""),12,"0",STR_PAD_LEFT); // Limite cheque
			$linha .= str_pad(substr($row["enderes"].", ".$row["numerores"],0,40),40," ",STR_PAD_RIGHT); // Endereco
			$linha .= str_pad(substr($row["bairrores"],0,25),25," ",STR_PAD_RIGHT); // Bairro
			$linha .= str_pad(substr($row["cidaderes"],0,20),20," ",STR_PAD_RIGHT); // Cidade
			$linha .= str_pad(substr($row["ufres"],0,2),2," ",STR_PAD_RIGHT); // UF
			$linha .= str_pad(substr($row["cepres"],0,9),9," ",STR_PAD_RIGHT); // CEP
			$linha .= str_pad(substr($row["foneres"],0,15),15," ",STR_PAD_RIGHT); // Telefone
			$linha .= "N"; // Cliente com conta corrente (C = Sim / N = Nao)
			$linha .= str_pad(number_format($row["limite1"],2,",",""),12,"0",STR_PAD_LEFT); // Limite conta corrente
			$arr_linha[] = $linha;
		}
		$this->pdvconfig->file_create("CLIENTE.TXT",$arr_linha);
	}
	
	function exportar_produto(){
		setprogress(0,"Buscando produtos",TRUE);
		$linhas = array();
		$query  = "SELECT produto.codproduto, produtoean.codean, produto.coddepto, ".$this->pdvconfig->sql_descricao().", produto.pesado, icmspdv.infpdv, ";
		$query .= "	produtoestab.precoatc, produtoestab.precovrj, produtoestab.precoatcof, produtoestab.precovrjof, unidade.sigla AS unidade ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		$query .= "INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
		$query .= "INNER JOIN icmspdv ON (classfiscal.tptribicms = icmspdv.tipoicms AND classfiscal.aliqicms = icmspdv.aliqicms AND produtoestab.codestabelec = icmspdv.codestabelec) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "WHERE produtoestab.codestabelec = '".$this->pdvconfig->getestabelecimento()->getcodestabelec()."' ";
		$query .= "	AND produto.gerapdv = 'S' ";
		if(param("ESTOQUE","CARGAITEMCOMESTOQ",$this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND produto.codproduto IN (SELECT DISTINCT codproduto FROM logpreco WHERE data > '".$this->pdvconfig->getdatalog()."' OR (data = '".$this->pdvconfig->getdatalog()."' AND hora >= '".$this->pdvconfig->gethoralog()."')) ";
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			if($this->pdvconfig->gettipopreco() == "A" && $row["precoatc"] == 0){
				continue;
			}elseif($this->pdvconfig->gettipopreco() == "V" && $row["precovrj"] == 0){
				continue;
			}
			if($row["precoatcof"] == 0){
				$row["precoatcof"] = $row["precoatc"];
			}
			if($row["precovrjof"] == 0){
				$row["precovrjof"] = $row["precovrj"];
			}
			setprogress(($i + 1) / sizeof($arr) * 100,"Exportando produtos: ".($i + 1)." de ".sizeof($arr));
			$linha  = ($row["pesado"] == "S" ? "03" : "02"); // Status (01 = Sistema / 02 = Codigo de barras fabricante / 03 = Pesado)
			$linha .= str_pad(substr($row["codean"],0,13),13,"0",STR_PAD_LEFT); // Codigo de barras
			$linha .= str_pad(substr($row["descricaofiscal"],0,35),35," ",STR_PAD_RIGHT); // Descricao
			$linha .= str_pad(number_format(($this->tipopreco == "A" ? $row["precoatc"] : $row["precovrj"]),2,"",""),18,"0",STR_PAD_LEFT); // Preco de venda
			$linha .= str_pad(substr($row["infpdv"],0,2),2," ",STR_PAD_RIGHT); // Totalizador fiscal
			$linha .= str_pad($row["codproduto"],8,"0",STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad(substr($row["unidade"],0,2),2," ",STR_PAD_RIGHT); // Unidade de venda
			$linha .= str_pad(date("d/m/Y"),10," ",STR_PAD_RIGHT); // Data de oferta do produto
			$linha .= str_pad(number_format(($this->pdvconfig->gettipopreco() == "A" ? $row["precoatcof"] : $row["precovrjof"]),2,"",""),18,"0",STR_PAD_LEFT); // Preco de venda em oferta
			$linha .= str_pad(substr($row["coddepto"],0,2),2,"0",STR_PAD_LEFT); // Codigo do departamento
			if($this->pdvconfig->produto_parcial()){
				$linha .= "I"; // Quando for arquivo com apenas os produtos alterados, deve manda esse campo com o valor 'I' (incluir e alterar produto)
			}
			$arr_linha[] = $linha;
		}
		if($this->pdvconfig->produto_parcial()){
			$this->pdvconfig->file_create("PLUSATUA.TXT",$arr_linha);
		}else{
			$this->pdvconfig->file_create("PLU.TXT",$arr_linha);
		}
	}

	
	
	private function formatar_data($data){
		if(strlen($data) == 8){
			return substr($data,0,2)."/".substr($data,2,2)."/".substr($data,4);
		}else{
			return $data;
		}
	}
	
	function diretorio_venda($dir_name,$arquivar = FALSE){
		$this->limpar_dados();
		
		// Tenta abrir o diretorio dos arquivos
		if(!($dir = @opendir($dir_name))){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->path;
			return FALSE;
		}
		
		// Acha todos os arquivos gerados pelo GDR
		$arr_file_v = array();
		$arr_file_c = array();
		while($file = readdir($dir)){
			if(strlen($file) == 8 && is_dir($dir_name.$file)){
				$dir2 = opendir($dir_name.$file);
				while($file2 = readdir($dir2)){
					$file2 = strtolower($file2);
					if(strlen($file2) == 15){
						switch(strtolower(substr($file2,-5))){
							case "v.cpm": $arr_file_v[] = $file."\\".$file2; break;
							case "c.cpm": $arr_file_c[] = $file."\\".$file2; break;
						}
					}
				}
			}
		}

		// Percorre todos os arquivos de venda
		foreach($arr_file_v as $i => $file_name){
			setprogress($i / sizeof($arr_file_v) * 100,"Lendo arquivos de venda: ".($i + 1)." de ".sizeof($arr_file_v));
			$arr_linha = read_file($file_name);
			// Captura dados que estao no nome do arquivo
			$data = substr($file_name,0,strpos($file_name,"\\"));
			$dados = substr($file_name,strpos($file_name,"\\") + 1);
			$caixa = substr($dados,0,4);
			$cupom = substr($dados,4,5);
			// Separa os arquivos que serao importados
			if($arquivar){
				if(!is_dir($dir_name."IMPORTADO")){
					mkdir($dir_name."IMPORTADO");
				}
				if(!is_dir($dir_name."IMPORTADO\\".$data)){
					mkdir($dir_name."IMPORTADO\\".$data);
				}
				copy($dir_name.$file_name,$dir_name."IMPORTADO\\".strtoupper($file_name));
				unlink($dir_name.$file_name);
			}
			// Percorre todas as linhas do arquivo
			$pdvvenda = new PdvVenda;
			$pdvvenda->setcaixa($caixa);
			$pdvvenda->setcupom($cupom);
			$pdvvenda->setdata(substr($data,6,2)."/".substr($data,4,2)."/".substr($data,0,4));
			foreach($arr_linha as $j => $linha){
				$valor = explode(" ",$linha);
				$identificador = (integer) $valor[0];
				switch($identificador){
					case 1: // Cabecalho
						
						break;
					case 2: // Item (inclusao/cancelamento)
						if(trim($valor[2]) == "CANCELAMENTO"){
							foreach($pdvvenda->pdvitem as $k => $pdvitem){
								if($pdvitem->getsequencial() == $valor[1]){
									unset($pdvvenda->pdvitem[$k]);
									break;
								}
							}
						}else{
							$pdvitem = new PdvItem;
							$pdvitem->setsequencial($valor[1]);
							$pdvitem->setquantidade($valor[2]);
							$pdvitem->setcodproduto($valor[3],TRUE);
							$pdvitem->setpreco($valor[4]);
							$pdvitem->settotal($valor[7]);
							$pdvvenda->pdvitem[] = $pdvitem;
						}
						break;
					case 3: // Data, hora, cupom e operador
						$pdvvenda->setcodfunc($valor[3]);
						break;
					case 8: // Acrescimo

						break;
					case 12: // Subtotal

						break;
					case 4: // Finalizacao com dinheiro
					case 5: // Finalizacao com ticket
					case 6: // Finalizacao com cartao de credito
					case 7: // Finalizacao com cheque
					case 9: // Finalizacao com contra-vale
					case 10: // Finalizacao com conta-corrente
					case 11: // Finalizacao com TEF
						$pdvfinalizador = new PdvFinalizador;
						$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
						$pdvfinalizador->setdata($pdvvenda->getdata());
						$pdvfinalizador->setcupom($pdvvenda->getcupom());
						$pdvfinalizador->setcodfinaliz($valor[0]);
						$pdvfinalizador->setvalortotal($valor[1]);
						$this->pdvfinalizador[] = $pdvfinalizador;
						break;
				}
			}
			$this->pdvvenda[] = $pdvvenda;
		}
		
		// Percorre todos os arquivos de cancelamento
		foreach($arr_file_c as $i => $file_name){
			setprogress($i / sizeof($arr_file_c) * 100,"Lendo arquivos de cancelamento: ".($i + 1)." de ".sizeof($arr_file_c));
			$arr_linha = read_file($file_name);
			// Captura dados que estao no nome do arquivo
			$data = substr($file_name,0,strpos($file_name,"\\"));
			$dados = substr($file_name,strpos($file_name,"\\") + 1);
			$caixa = substr($dados,0,4);
			$cupom = substr($dados,4,5);
			
			$data = substr($data,6,2)."/".substr($data,4,2)."/".substr($data,0,4);
			
			// Separa os arquivos que serao importados
			if($arquivar){
				if(!is_dir($dir_name."IMPORTADO")){
					mkdir($dir_name."IMPORTADO");
				}
				if(!is_dir($dir_name."IMPORTADO\\".$data)){
					mkdir($dir_name."IMPORTADO\\".$data);
				}
				copy($dir_name.$file_name,$dir_name."IMPORTADO\\".strtoupper($file_name));
				unlink($dir_name.$file_name);
			}
			// Percorre todas as linhas do arquivo
			$f = FALSE;
			foreach($this->pdvvenda as $pdvvenda){
				if($pdvvenda->getdata() == $data && $pdvvenda->getcaixa() == $caixa && $pdvvenda->getcupom() == $cupom){
					$pdvvenda->setstatus("C");
					$f = TRUE;
					break;
				}
			}
			if(!$f){
				$pdvvenda = new PdvVenda;
				$pdvvenda->setcaixa($caixa);
				$pdvvenda->setcupom($cupom);
				$pdvvenda->setdata($data);
				$pdvvenda->setstatus("C");
				$this->pdvvenda[] = $pdvvenda;
			}
		}
		return TRUE;
	}
	
	function getpdvvenda(){
		return $this->pdvvenda;
	}
	
	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}
	
	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

}
?>