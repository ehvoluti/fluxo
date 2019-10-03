<?php
class MgContabilidade{
	private $con;
	private $estabelecimento;

	private $arr_cliente;
	private $arr_cupom;
	private $arr_fornecedor;
	private $arr_notafiscal;
	private $arr_operacaonota;
	
	function __construct($con){
		$this->con = $con;
		$this->arr_cupom = array();
		$this->arr_notafiscal = array();
		$this->arr_operacaonota = array();
	}
	
	function addnotafiscal($notafiscal){
		if(!is_object($notafiscal)){
			$notafiscal = objectbytable("notafiscal",$notafiscal,$this->con);
		}
		if($notafiscal->exists()){
			$notafiscal->flag_itnotafiscal(TRUE);
			$notafiscal->searchbyobject();
			$this->arr_notafiscal[] = $notafiscal;
		}
	}
	
	function setestabelecimento($estabelecimento){
		if(!is_object($estabelecimento)){
			$estabelecimento = objectbytable("estabelecimento",$estabelecimento,$this->con);
		}
		if($estabelecimento->exists()){
			$this->estabelecimento = $estabelecimento;
		}
	}
	
	function gerar(){
		if(strlen($this->estabelecimento->getdircontabil()) == 0){
			$_SESSION["ERROR"] = "Diret&oacute;rio de integra&ccedil;&atilde;o cont&aacute;bil n&atilde;o foi informado no estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}elseif(!is_dir($this->estabelecimento->getdircontabil())){
			$_SESSION["ERROR"] = "Diret&oacute;rio de integra&ccedil;&atilde;o cont&aacute;bil (<i>".str_replace("\\","\\\\",$this->estabelecimento->getdircontabil())."</i>) n&atilde;o foi encontrado.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}
		$operacaonota = objectbytable("operacaonota",NULL,$this->con);
		$arr_operacaonota = object_array($operacaonota);
		$this->arr_operacaonota = array();
		foreach($arr_operacaonota as $operacaonota){
			$this->arr_operacaonota[$operacaonota->getoperacao()] = $operacaonota;
		}
		$this->gerar_cliente();
		$this->gerar_fornecedor();
		$this->gerar_notafiscal();
		return TRUE;
	}
	
	private function gerar_cliente(){
		$arr_codfornec = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			if($operacaonota->getparceiro() == "C"){
				$arr_codcliente[] = $notafiscal->getcodparceiro();
			}
		}
		$arr_codcliente = array_unique($arr_codcliente);
		$linhas = array();
		foreach($arr_codcliente as $codcliente){
			$cliente = objectbytable("cliente",$codcliente,$this->con);
			$this->arr_cliente[$cliente->getcodcliente()] = $cliente;
			$funcionario = objectbytable("funcionario",$cliente->getcodvendedor(),$this->con);
			$cidaderes = objectbytable("cidade",$cliente->getcodcidaderes(),$this->con);
			$cidadefat = objectbytable("cidade",$cliente->getcodcidadefat(),$this->con);
			$linha  = str_pad($this->valor_codigo($cliente->getcodcliente(),FALSE),6,"0",STR_PAD_LEFT); // Codigo do cliente
			$linha .= str_repeat(" ",5); // Espaco em branco
			$linha .= str_pad(substr($cliente->getrazaosocial(),0,20),20," ",STR_PAD_RIGHT); // Nome do cliente
			$linha .= str_pad(substr($cliente->getnome(),0,40),40," ",STR_PAD_RIGHT); // Nome fantasia do cliente
			$linha .= str_pad(substr($cliente->getenderres(),0,40),40," ",STR_PAD_RIGHT); // Endereco do cliente
			$linha .= str_pad(substr($cliente->getbairrores(),0,25),25," ",STR_PAD_RIGHT); // Bairro do cliente
			$linha .= str_pad(substr($cidaderes->getnome(),0,20),20," ",STR_PAD_RIGHT); // Municipio do cliente
			$linha .= str_pad(substr($cidaderes->getuf(),0,3),3," ",STR_PAD_RIGHT); // Estado do cliente
			$linha .= $this->valor_cep($cliente->getcepres()); // CEP do cliente
			$linha .= $this->valor_telefone($cliente->getfoneres()); // Telefone do cliente
			$linha .= ($cliente->gettppessoa() == "F" ? $this->valor_cpf($cliente->getcpfcnpj()) : $this->valor_cnpj($cliente->getcpfcnpj())); // CPF/CNPJ do cliente
			$linha .= $this->valor_ie($cliente->getrgie()); // Inscricao estadual do cliente
			$linha .= str_repeat(" ",12); // Cadastro do CCM do cliente
			$linha .= str_pad(substr($cliente->getenderfat(),0,40),40," ",STR_PAD_RIGHT); // Endereco de cobranca do cliente
			$linha .= str_pad(substr($cliente->getbairrofat(),0,25),25," ",STR_PAD_RIGHT); // Bairro de cobranca do cliente
			$linha .= str_pad(substr($cidadefat->getnome(),0,20),20," ",STR_PAD_RIGHT); // Municipio de cobranca do cliente
			$linha .= str_pad(substr($cidadefat->getuf(),0,3),3," ",STR_PAD_RIGHT); // Estado de cobranca do cliente
			$linha .= $this->valor_cep($cliente->getcepfat()); // CEP de cobranca do cliente
			$linha .= str_pad(substr($cliente->getenderent(),0,40),40," ",STR_PAD_RIGHT); // Endereco de entrega do cliente
			$linha .= str_repeat(" ",2); // Previsao de entrega
			$linha .= "N"; // Fax 24h (S/N)
			$linha .= str_pad(substr($cliente->getcontato(),0,30),30," ",STR_PAD_RIGHT); // Contato do cliente
			$linha .= str_repeat(" ",20); // Espaco em branco
			$linha .= str_repeat(" ",3); // Espaco em branco
			$linha .= "N"; // Retencao (?)
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Limite de retencao (?)
			$linha .= "N"; // Nota fiscal (?)
			$linha .= str_pad(substr($cliente->getemail(),0,40),40," ",STR_PAD_RIGHT); // E-mail do cliente
			$linha .= str_repeat(" ",5); // Sembra (?)
			$linha .= str_repeat(" ",5); // Sendelta (?)
			$linha .= 'N'; // Seed (?)
			$linha .= str_repeat(" ",5); // Indica (?)
			$linha .= str_repeat(" ",3); // Codigo do vendedor
			$linha .= "A"; // A - Liberacao analitica   |   S - Liberacao sintetica (?)
			$linha .= $this->valor_telefone($cliente->getfaxfat()); // Fax do cliente
			$linha .= str_repeat(" ",15); // Telex do cliente
			$linha .= str_repeat(" ",15); // Segundo telefone do cliente
			$linha .= str_repeat(" ",6); // Conta integrada sintetica (?)
			$linha .= $this->valor_data($cliente->getdtinclusao()); // Data de cadastro do cliente
			$linhas[] = $linha;
		}
		$this->gravar_arquivo("CLI".str_pad($this->estabelecimento->getcodestabelec(),4,"0",STR_PAD_LEFT).".ARQ",$linhas);
	}
		
	private function gerar_fornecedor(){
		$arr_codfornec = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			if($operacaonota->getparceiro() == "F"){
				$arr_codfornec[] = $notafiscal->getcodparceiro();
			}
		}
		$arr_codfornec = array_unique($arr_codfornec);
		$linhas = array();
		foreach($arr_codfornec as $codfornec){
			$fornecedor = objectbytable("fornecedor",$codfornec,$this->con);
			$this->arr_fornecedor[$fornecedor->getcodfornec()] = $fornecedor;
			$cidade = objectbytable("cidade",$fornecedor->getcodcidade(),$this->con);
			$linha  = str_pad($this->valor_codigo($fornecedor->getcodfornec(),FALSE),6,"0",STR_PAD_LEFT); // Codigo do fornecedor
			$linha .= str_repeat(" ",5); // Espaco em branco
			$linha .= str_pad(substr($fornecedor->getnome(),0,40),40," ",STR_PAD_RIGHT); // Nome do fornecedor
			$linha .= str_pad(substr($fornecedor->getendereco(),0,40),40," ",STR_PAD_RIGHT); // Endereco do fornecedor
			$linha .= str_pad(substr($fornecedor->getbairro(),0,25),25," ",STR_PAD_RIGHT); // Bairro do fornecedor
			$linha .= str_pad(substr($cidade->getnome(),0,20),20," ",STR_PAD_RIGHT); // Municipio do fornecedor
			$linha .= str_pad(substr($cidade->getuf(),0,3),3," ",STR_PAD_RIGHT); // Estado do fornecedor
			$linha .= $this->valor_cep($fornecedor->getcep()); // CEP do fornecedor
			$linha .= $this->valor_telefone($fornecedor->getfone1()); // Telefone do fornecedor
			$linha .= $this->valor_cnpj($fornecedor->getcpfcnpj()); // CNPJ do fornecedor
			$linha .= $this->valor_ie($fornecedor->getrgie()); // Inscricao estadual do fornecedor
			$linha .= str_pad(substr($fornecedor->getendereco(),0,40),40," ",STR_PAD_RIGHT); // Endereco de pagamento do fornecedor
			$linha .= str_pad(substr($cidade->getnome(),0,20),20," ",STR_PAD_RIGHT); // Municipio de pagamento do fornecedor
			$linha .= str_pad(substr($cidade->getuf(),0,3),3," ",STR_PAD_RIGHT); // Estado de pagamento do fornecedor
			$linha .= str_pad(substr($fornecedor->getendereco(),0,40),40," ",STR_PAD_RIGHT); // Endereco de retirada do fornecedor
			$linha .= str_pad(substr($fornecedor->getdiasentrega(),0,2),2," ",STR_PAD_LEFT); // Previsao de entrega do fornecedor
			$linha .= str_pad(substr($fornecedor->getcontato1(),0,30),30," ",STR_PAD_RIGHT); // Contato do fornecedor
			$linha .= str_repeat(" ",3); // Codigo do comprador
			$linha .= str_repeat(" ",30); // Nome do comprador
			$linha .= "A"; // A - Liberacao analitica   |   S - Liberacao sintetica
			$linha .= $this->valor_telefone($fornecedor->getfax()); // Fax do fornecedor
			$linha .= str_repeat(" ",15); // Telex do fornecedor
			$linha .= str_repeat(" ",15); // Segundo telefone do fornecedor
			$linha .= str_repeat(" ",6); // Conta integrada sintetica
			$linha .= $this->valor_data($fornecedor->getdatainclusao()); // Data de cadastro do fornecedor
			$linhas[] = $linha;
		}
		$this->gravar_arquivo("FOR".str_pad($this->estabelecimento->getcodestabelec(),4,"0",STR_PAD_LEFT).".ARQ",$linhas);
	}
	
	private function gerar_lancamento($notafiscal){
		$lancamento = objectbytable("lancamento",NULL,$this->con);
		$lancamento->setidnotafiscal($notafiscal->getidnotafiscal());
		$arr_lancamento = object_array($lancamento);
		$linhas = array();
		$dtemissao = explode("-",$notafiscal->getdtemissao());
		$mes = $dtemissao[1];
		foreach($arr_lancamento as $lancamento){
			$linha  = "VEN"; // Identificador
			$linha .= str_repeat("0",5); // Numero do lancamento (preencher com zeros)
			$linha .= str_pad($mes,2,"0",STR_PAD_LEFT); // Mes do lancamento da nota fiscal
			$linha .= $this->valor_data($lancamento->getdtvencto()); // Data de vencimento
			$linha .= str_pad($this->valor_decimal($lancamento->getvalorliquido(),2),16," ",STR_PAD_LEFT); // Valor do lancamento
			$linha .= str_pad(substr($notafiscal->getnatoperacao(),0,6),6," ",STR_PAD_RIGHT); // Natureza de operacao
			$linhas[] = $linha;
		}
		return $linhas;
	}

	private function gerar_notafiscal(){
		$linhas = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			switch($operacaonota->getparceiro()){
				case "C":
					$cliente = $this->arr_cliente[$notafiscal->getcodparceiro()];
					$nome_parceiro = $cliente->getnome();
					$cpfcnpj_parceiro = ($cliente->gettppessoa() == "F" ? $this->valor_cpf($cliente->getcpfcnpj()) : $this->valor_cnpj($cliente->getcpfcnpj()));
					$uf_parceiro = $cliente->getufres();
					break;
				case "F":
					$fornecedor = $this->arr_fornecedor[$notafiscal->getcodparceiro()];
					$nome_parceiro = $fornecedor->getnome();
					$cpfcnpj_parceiro = ($fornecedor->gettppessoa() == "F" ? $this->valor_cpf($fornecedor->getcpfcnpj()) : $this->valor_cnpj($fornecedor->getcpfcnpj()));
					$uf_parceiro = $fornecedor->getuf();
					break;
			}

			$notafiscalimposto = objectbytable("notafiscalimposto",NULL,$this->con);
			$notafiscalimposto->setidnotafiscal($notafiscal->getidnotafiscal());
			$arr_notafiscalimposto_aux = object_array($notafiscalimposto);
			$arr_notafiscalimposto = array();
			$b_icmsst = FALSE;
			foreach($arr_notafiscalimposto_aux as $i => $notafiscalimposto){
				if(substr($notafiscalimposto->gettipoimposto(),0,4) == "ICMS"){
					$arr_notafiscalimposto[$notafiscalimposto->getaliquota()]["base"] += $notafiscalimposto->getbase();
					$arr_notafiscalimposto[$notafiscalimposto->getaliquota()]["total"] += $notafiscalimposto->getvalorimposto();
					$arr_notafiscalimposto[$notafiscalimposto->getaliquota()]["isento"] += $notafiscalimposto->getisento();
				}
				if($notafiscalimposto->gettipoimposto() == "ICMS_F"){
					$b_icmsst = TRUE;
				}
			}

			$linha  = "LCT"; // Identificador
			$linha .= "00000"; // Numero do lancamento
			$linha .= str_pad($notafiscal->getnumnotafis(),10," ",STR_PAD_LEFT); // Numero da nota fiscal
			$linha .= str_repeat(" ",10); // Numero da nota fiscal (para trabalhar com sequencia de numero)
			$linha .= str_pad("NF",5," ",STR_PAD_RIGHT); // Especie da nota fiscal]
			$linha .= str_pad($notafiscal->getserie(),3," ",STR_PAD_RIGHT); // Serie da nota fiscal
			$linha .= $this->valor_data($notafiscal->getdtemissao()); // Data de emissao
			$linha .= $this->valor_data($notafiscal->getdtentrega()); // Data de entrada
			$linha .= str_pad(substr(removeformat($notafiscal->getnatoperacao()),0,4),6," ",STR_PAD_RIGHT); // Natureza de operacao
			$linha .= str_repeat(" ",5).str_pad($this->valor_codigo($notafiscal->getcodparceiro(),TRUE),7,"0",STR_PAD_LEFT); // Codigo do cliente/fornecedor
			$linha .= (substr($notafiscal->getnatoperacao(),0,1) == "6" ? "T" : "F"); // Venda a consumidor final
			$linha .= ($b_icmsst ? "T" : "F"); // Substituicao tributaria
			$linha .= str_pad(substr($cpfcnpj_parceiro,0,18),18," ",STR_PAD_RIGHT); // CPF/CNPJ do cliente/fornecedor
			$linha .= str_repeat(" ",5); // Conta para debito
			$linha .= str_repeat(" ",5); // Conta para credito
			$linha .= str_repeat(" ",6); // Centro de custo para debito
			$linha .= str_repeat(" ",6); // Centro de custo para credito
			$linha .= str_repeat(" ",6); // Setor para debito
			$linha .= str_repeat(" ",6); // Setor para credito
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalliquido(),2),16," ",STR_PAD_LEFT); // Valor total da nota fiscal
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalliquido(),2),16," ",STR_PAD_LEFT); // Valor contabil da nota fiscal
			$linha .= str_pad(substr($uf_parceiro,0,3),3," ",STR_PAD_RIGHT); // UF do cliente/fornecedor
			$linha .= str_repeat(" ",9); // Codigo do servico
			$linha .= str_repeat(" ",16); // Valor dos materiais (servico)
			$linha .= str_repeat(" ",16); // Valor da sub-empreitada (servico)
			// Prenche com os impostos da nota
			foreach($arr_notafiscalimposto as $aliqicms => $notafiscalimposto){
				$linha .= str_repeat("0",5); // Rotina de calculo (preencher com zeros)
				$linha .= str_pad($this->valor_decimal($notafiscalimposto["base"],2),16," ",STR_PAD_LEFT); // Total da base de ICMS
				$linha .= str_pad($this->valor_decimal($aliqicms,2),5," ",STR_PAD_LEFT); // Aliquota de ICMS
				$linha .= str_pad($this->valor_decimal($notafiscalimposto["total"],2),16," ",STR_PAD_LEFT); // Total de ICMS
				$linha .= str_pad($this->valor_decimal($notafiscalimposto["isento"],2),16," ",STR_PAD_LEFT); // Total isento de ICMS
				$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Outras despesas de ICMS
			}
			// Preenche com espaco em branco quando nao completa 5 impostos
			for($i = sizeof($arr_notafiscalimposto); $i < 5; $i++){
				$linha .= str_repeat("0",5); // Rotina de calculo (preencher com zeros)
				$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total da base de ICMS
				$linha .= str_pad($this->valor_decimal(0,2),5," ",STR_PAD_LEFT); // Aliquota de ICMS
				$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total de ICMS
				$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total isento de ICMS
				$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Outras despesas de ICMS
			}
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalbruto() - $notafiscal->gettotaldesconto() + $notafiscal->gettotalacrescimo(),2),16," ",STR_PAD_LEFT); // Base de IPI
			$linha .= str_pad($this->valor_decimal(0,2),5," ",STR_PAD_LEFT); // Percentual de IPI
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalipi(),2),16," ",STR_PAD_LEFT); // Total de IPI
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total isento de IPI
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Outras despesas de IPI
			$linha .= str_pad($this->valor_decimal(0,2),5," ",STR_PAD_LEFT); // Percentual de imposto de renda sob servico
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalarecolher(),2),16," ",STR_PAD_LEFT); // ICMS retido ou imposto de renda retido sobre servico
			$linha .= str_repeat(" ",16); // Observacao do IPI
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Base calculo de INSS
			$linha .= str_pad($this->valor_decimal(0,2),5," ",STR_PAD_LEFT); // Percentual de INSS
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total de INSS
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalbaseicmssubst(),2),16," ",STR_PAD_LEFT); // Total da base de ICMS ST
			$linha .= str_pad($this->valor_decimal($notafiscal->gettotalicmssubst(),2),16," ",STR_PAD_LEFT); // Total de ICMS ST
			$linha .= str_repeat(" ",5); // Codigo da ZFM
			$linha .= str_repeat(" ",40); // Observacoes necessarias
			$linha .= "F"; // Flag de atualizacao
			$linha .= "001"; // Numero da estacao
			$linha .= str_repeat(" ",40); // Observacao 2
			$linha .= str_repeat(" ",40); // Observacao 3
			$linha .= ($notafiscal->getmodfrete() == "1" ? "2" : "1"); // Tipo de frete
			$linha .= "T"; // Situacao da nota
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Base calculo de ISS retido
			$linha .= str_pad($this->valor_decimal(0,2),16," ",STR_PAD_LEFT); // Total de ISS retido
			$linha .= str_pad($this->valor_decimal(0,2),5," ",STR_PAD_LEFT); // Aliquota de ISS retido
			$linhas[] = $linha;
			$linhas = array_merge($linhas,$this->gerar_lancamento($notafiscal));
		}
		$this->gravar_arquivo("MV".str_pad($this->estabelecimento->getcodestabelec(),4,"0",STR_PAD_LEFT)."01.TXT",$linhas);
	}
		
	private function gravar_arquivo($nome,$linhas){
		$file = fopen($this->estabelecimento->getdircontabil().$nome,"w+");
		fwrite($file,implode("\r\n",$linhas)."\r\n");
		fclose($file);
	}
		
	private function valor_cep($cep){ // Formato: 99999-999
		$cep = removeformat($cep);
		if(strlen($cep) > 0){
			return substr($cep,0,5)."-".substr($cep,5,3);
		}else{
			return str_repeat(" ",9);
		}
	}
	
	private function valor_cnpj($cnpj){ // Formato: 99.999.999/9999-99
		$cnpj = removeformat($cnpj);
		if(strlen($cnpj) > 0){
			return str_pad(substr($cnpj,0,2).".".substr($cnpj,2,3).".".substr($cnpj,5,3)."/".substr($cnpj,8,4)."-".substr($cnpj,12,2),18," ",STR_PAD_RIGHT);
		}else{
			return str_repeat(" ",18);
		}
	}
	
	private function valor_codigo($codigo,$hifen = TRUE){
		$codigo = str_pad($codigo,5,"0",STR_PAD_LEFT);
		$total  = substr($codigo,0,1) * 10;
		$total += substr($codigo,0,2) * 9;
		$total += substr($codigo,0,3) * 8;
		$total += substr($codigo,0,4) * 7;
		$total += substr($codigo,0,5) * 6;
		$digito = $total % 11;
		if($digito == 0 || $digito > 9){
			$digito = 1;
		}
		return $codigo.($hifen ? "-" : "").$digito;
	}
	
	private function valor_cpf($cpf){
		$cpf = removeformat($cpf);
		if(strlen($cpf) > 0){
			return str_pad(substr($cpf,0,3).".".substr($cpf,3,3).".".substr($cpf,6,3)."-".substr($cpf,9,2),18," ",STR_PAD_RIGHT);
		}else{
			return str_repeat(" ",18);
		}
	}
	
	private function valor_data($data){ // Formato: AAAAMMDD
		if(strpos($data,"/") !== FALSE){
			return implode("",array_reverse(explode("/",$data)));
		}else if(strpos($data,"-") !== FALSE){
			return implode("",explode("-",$data));
		}
	}
	
	private function valor_decimal($valor,$casas_decimais){
		$valor = value_numeric($valor);
		return number_format($valor,$casas_decimais,".","");
	}
	
	private function valor_ie($ie){ // Formato: ???
		return str_pad(substr($ie,0,20),20," ",STR_PAD_RIGHT);
	}
	
	private function valor_telefone($telefone){ // Formato: (0099) 9999-9999
		$telefone = removeformat($telefone);
		if(strlen($telefone) > 0){
			return "(00".substr($telefone,0,2).")".substr($telefone,2,4)."-".substr($telefone,6,4);
		}else{
			return str_repeat(" ",15);
		}
	}
}
?>