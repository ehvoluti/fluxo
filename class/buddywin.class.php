<?php
require_once("../def/require_php.php");

class Buddywin{
//	Para pegar um dia e não um mes
//	protected $teste = "S";

	protected $con;

	protected $estabelecimento;

	protected $ano;
	protected $mes;
	protected $dircontabil;

	protected $arr_cliente;
	protected $arr_condpagto;
	protected $arr_estabelecimento;
	protected $arr_fornedor;
	protected $arr_notafiscal;
	protected $arr_operacaonota;
	protected $arr_chavenfe = array(); //numero da nota|serie|tipo movimento|CPF/CNPJ|chavenfe
	protected $arr_natreceita;

	protected $teste;

	function __construct($con){
		$this->con = $con;
	}

	public function setano($ano){
		$this->ano = $ano;
	}

	public function setmes($mes){
		$this->mes = $mes;
	}

	public function setestabelecimento($estabelecimento){
     	$this->estabelecimento = $estabelecimento;
	}

	public function setdircontabil($dircontabil){
     	$this->dircontabil = $dircontabil;
	}

	public function setteste($teste){
		$this->teste = $teste;
	}

	public function gerar(){
		// Busca as naturezas de receita
		setprogress(0,"Carregando naturezas de receita",TRUE);
		$this->arr_natreceita = array();
		$res = $this->con->query("SELECT * FROM natreceita");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_natreceita[$row["tabela"].$row["codigo"]] = $row["natreceita"];
		}



		//**********************************************************************
		//					Layout do arquivo de entradas
		//**********************************************************************
		setprogress(0,"Carregando entradas",TRUE);
		$arr_establecimento = objectbytable("estabelecimento",$this->estabelecimento,$con);
		foreach($arr_establecimento AS $estabelecimento){
			if(strlen($estabelecimento["dircontabil"]) == 0){
				echo messagebox("error", "Diretorio nao informado", "Diretorio nao informado para o estabelecimento ".$estabelecimento["nome"]);
				die();
			}
			else{
				$this->setdircontabil($estabelecimento["dircontabil"]);
			}
		}

		$query  = "SELECT ";
		$query .= "(CASE ";
		$query .= "		WHEN nf.tipoparceiro = 'F' THEN (SELECT cpfcnpj FROM fornecedor WHERE codfornec = nf.codparceiro) ";
		$query .= "		WHEN nf.tipoparceiro = 'C' THEN (SELECT cpfcnpj FROM cliente WHERE codcliente = nf.codparceiro) ";
		$query .= "		WHEN nf.tipoparceiro = 'E' THEN (SELECT cpfcnpj FROM estabelecimento WHERE codcliente = nf.codparceiro) ";
		$query .= "END) AS cpfcnpj, ";
		$query .= "nf.codparceiro, nf.tipoparceiro, nf.numnotafis, nf.serie, it.natoperacao, it.aliqicms, ";
		$query .= "nf.especie, nf.codcondpagto, nf.dtentrega, nf.dtemissao, nf.observacaofiscal, nf.chavenfe, ";
		$query .= "CASE WHEN it.totalicmssubst > 0 THEN (SELECT aliqicms FROM classfiscal WHERE pd.codcfnfe = codcf) ELSE 0.000 END AS aliqicmsst, ";
		$query .= "SUM(nf.totaldesconto) AS totaldesconto, SUM(nf.totalfrete) AS totalfrete, ";
		$query .= "SUM(it.totalbruto) AS totalbruto, SUM(it.totalbaseicms) AS totalbaseicms, SUM(it.totalicms) AS totalicms, ";
		$query .= "SUM(it.totalliquido) AS totalliquido, SUM(it.totalacrescimo) AS totalacrescimo, SUM(it.totalbaseisento) AS totalbaseisento, ";
		$query .= "SUM(it.totalipi) AS totalipi, SUM(it.totalbaseicmssubst) AS totalbaseicmssubst, SUM(it.totalicmssubst) AS totalicmssubst, ";
		$query .= "SUM(it.percipi) AS percipi, SUM(it.valipi) AS valipi ";
		$query .= "FROM itnotafiscal it ";
		$query .= "INNER JOIN notafiscal nf ON (it.idnotafiscal = nf.idnotafiscal) ";
		$query .= "INNER JOIN operacaonota op ON (nf.operacao = op.operacao) ";
		$query .= "INNER JOIN produto pd ON (it.codproduto = pd.codproduto) ";
		$query .= "WHERE op.tipo = 'E' AND nf.gerafiscal = 'S' AND nf.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM nf.dtentrega) = ".$this->ano." AND EXTRACT(MONTH FROM nf.dtentrega) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM nf.dtentrega) = 10 ";
		}
		$query .= "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14 ";
		$query .= "ORDER BY 1,2,3,4,5,6 ";

		$arr_linha_ENT_MMAA = array();
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach ($arr as $row){
			if($row["tipoparceiro"] == "F"){
				$parceiro = objectbytable("fornecedor",$row["codparceiro"],$this->con);
				$cidade = objectbytable("cidade",$parceiro->getcodcidade(),$this->con);
			}else if($row["tipoparceiro"] == "C"){
				$parceiro = objectbytable("cliente",$row["codparceiro"],$this->con);
				$cidade = objectbytable("cidade",$parceiro->getcodcidadefat(),$this->con);
			}else if($row["tipoparceiro"] == "E"){
				$parceiro = objectbytable("estabelecimento",$row["codparceiro"],$this->con);
				$cidade = objectbytable("cidade",$parceiro->getcodcidade(),$this->con);
			}

			if($row["aliqicms"] == 0) continue;
			$linha  = $this->valor_texto("NFE",3)." "; // Especie da nota fiscal
			$linha .= $this->valor_texto($row["serie"],3)." "; // Serie da nota fiscal
			$linha .= $this->valor_numerico($row["numnotafis"],0,6)." "; // Numero da nota fiscal
			$linha .= $this->valor_numerico(($row["codcondpagto"] == 2 ? 1 : 2),0,1)." "; // Forma de pagamento
			$linha .= $this->valor_data($row["dtentrega"])." "; // Data de entrada DDMMAAAA
			$linha .= $this->valor_data($row["dtemissao"])." "; // Data de emissao DDMMAAAA
			$linha .= $this->valor_numerico(0,0,5)." "; // emitente
			$linha .= $this->valor_numerico(removeformat($row["natoperacao"]),0,4)." "; // CFOP
			$linha .= $this->valor_texto("N",1)." "; // Incluir base de crédito S/N
			$linha .= $this->valor_texto($row["tipoparceiro"] == "C" ? $parceiro->getuffat() : $parceiro->getuf(),2)." "; // Sigla do estado
			$linha .= $this->valor_numerico(0,0,4); // Classi. contabil
			$linha .= $this->valor_numerico(0,0,4)." "; // Comple. contabil
			$linha .= $this->valor_numerico($row["totalliquido"],2,14)." "; // Valor contabil
			$linha .= $this->valor_numerico($row["totalbaseicms"],2,14)." "; // Base ICMS
			$linha .= $this->valor_numerico($row["aliqicms"],2,5)." "; // Aliquota de ICMS
			$linha .= $this->valor_numerico($row["totalicms"],2,14)." "; // Valor do ICMS
			$linha .= $this->valor_texto(" ",92)." "; // Reservado
			$linha .= $this->valor_numerico($row["totalacrescimo"],2,14)." "; // Valor Acrescimo
			$linha .= $this->valor_numerico($row["totalbaseisento"],2,14)." "; // Isentos de ICMS
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros ICMS
			$linha .= $this->valor_numerico($row["totalbruto"] + $row["totalacrescimo"] - $row["totaldesconto"],2,14)." "; // Base para o IPI
			$linha .= $this->valor_numerico($row["percipi"],2,5)." "; // Aliquota de IPI
			$linha .= $this->valor_numerico($row["valipi"],2,14)." "; // Valor do IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Isentos do IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros de IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // ICMS Retido
			$linha .= $this->valor_numerico(0,2,14)." "; // IPI Incluso
			$linha .= $this->valor_numerico(0,2,14)." "; // Outro IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros Valores
			$linha .= $this->valor_texto($row["observacaofiscal"],50)." "; // Observações
			$linha .= $this->valor_texto(" ",1)." "; // Tipo IPI
			$linha .= $this->valor_numerico(0,2,10)." "; // Pis aduaneiro
			$linha .= $this->valor_numerico(0,2,10)." "; // Cofins aduaneiro
			$linha .= $this->valor_numerico(0,2,6)." "; // Reducao PIS/COFINS
			$linha .= $this->valor_numerico(0,0,3)." "; // Receita super simples
			$linha .= $this->valor_numerico($row["totalbaseicmssubst"],2,14)." "; // Base ICMS de substituicao tributaria
			$linha .= $this->valor_numerico($row["aliqicmsst"],2,5)." "; // Aliquota ICMS de substituicao tributaria
			$linha .= $this->valor_numerico($row["totalicmssubst"],2,14)." "; // Valor ICMS de substituicao tributaria
			$linha .= $this->valor_texto("P",1)." "; // Emitente da nota fisacal P-proprio, T-terceiros
			$linha .= $this->valor_texto('55',2)." "; // Codigo do modelo do documento fiscal conforme SPED EFD
			$linha .= $this->valor_texto("00",2)." "; // Codigo da situacao do documento fiscal conforme SPED EFD
			$linha .= $this->valor_texto("0",1)." "; // Via de Transporte 0-rodoviario, 1-ferroviario, ...
			$linha .= $this->valor_texto("9",1)." "; // Tipo de operacao 9-outros, 0-venda para a concessionaria, ...
			$linha .= $this->valor_texto(" ",14)." "; // CNPJ da Concessionaria
			$linha .= $this->valor_texto(" ",2)." "; // Estado da Concessionaria
			$linha .= $this->valor_texto(" ",1)." "; // Modalidade frete
			$linha .= $this->valor_texto(" ",1); // Reservado

			//***************Informacoes sobre o emitente da nota***************

			$linha .= $this->valor_texto($parceiro->getrazaosocial(),50)." "; // Razao social
			$linha .= $this->valor_texto(removeformat($parceiro->getcpfcnpj()),14)." "; //CPF ou CNPJ do emitente da nota
			$linha .= $this->valor_texto(str_replace(".","",$parceiro->getrgie()),20)." "; // Numero da inscricao estadual do emitente da nota fiscal
			$linha .= $this->valor_numerico(0,0,2)." "; // Codigo para lancamento do Dipam-B
			$linha .= $this->valor_numerico(0,0,4)." "; // Codigo do municipio que se refere o lancamento do Dipam-B
			$linha .= $this->valor_numerico(0,0,1)." "; // Informar municipio 1-sao paulo, 2-outros
			$linha .= $this->valor_texto(" ",1)." "; // Excluir da DIPI
			$linha .= $this->valor_texto($row["tipoparceiro"] == "C" ? $parceiro->getenderfat() : $parceiro->getendereco(),50)." "; // Endereco do emitente da nota fiscal
			$linha .= $this->valor_numerico(str_replace("-","",$row["tipoparceiro"] == "C" ? $parceiro->getcepfat() : $parceiro->getcep()),0,8)." "; // Cep do emitente da nota fiscal
			$linha .= $this->valor_texto($row["tipoparceiro"] == "C" ? $parceiro->getbairrofat() : $parceiro->getbairro(),50)." "; // Bairro do emitente da nota fiscal
			$linha .= $this->valor_texto($cidade->getnome(),50)." "; // Cidade do emitente da nota fiscal
			$linha .= $this->valor_texto($row["tipoparceiro"] == "C" ? $parceiro->getuffat() : $parceiro->getuf(),2)." "; // Sigla do estado do emitente
			$linha .= $this->valor_numerico(0,0,10)." "; // Numero da incricao municipal do emitente na nota fiscal
			$linha .= $this->valor_numerico(0,0,2)." "; // Digito da incricao municipal do emitente
			$linha .= $this->valor_numerico(0,0,2)." "; // Cidade GISS
			$linha .= $this->valor_numerico($row["totalfrete"],2,10)." "; // Valor do Frete
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor do Seguro
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor outras despesas
			$linha .= $this->valor_numerico($row["totaldesconto"],2,10)." "; // Valor desconto
			$linha .= $this->valor_texto(01058,5)." "; // Codigo do pais conforme SPED EFD
			$linha .= $this->valor_texto(" ",15)." "; // Tipo de logradouro
			$linha .= $this->valor_numerico($row["tipoparceiro"] == "C" ? $parceiro->getnumerofat() : $parceiro->getnumero(),0,6)." "; // Numero do endereco
			$linha .= $this->valor_texto(" ",30)." "; // Complemento do endereco
			$linha .= $this->valor_texto(" ",17)." "; // Chassi do veiculo
			$linha .= $this->valor_numerico(0,0,3)." "; // Codigo de observacao negativa
			$linha .= $this->valor_texto(" ",9); // Numero da nota devolvida
			$linha .= $this->valor_texto(" ",1)." "; // Tipo de ICMS 1-Substituto, 2-Substituido
			$linha .= $this->valor_texto(" ",1)." "; // Origem mercadoria
			$linha .= $this->valor_texto(" ",2)." "; // Situacao tributaria
			$linha .= $this->valor_texto(" ",1)." "; // Tipo isencao do super simples 0-ICMS normas, 1-isencao de ICMS, ...
			$linha .= $this->valor_numerico(0,0,5)." "; // Reducao aliquota ICMS super simples
			$linha .= $this->valor_texto(" ",2)." "; // Reservado - Classe de consumo de agua energia eletrica etc ...
			$linha .= $this->valor_texto(" ",1)." "; // Tipo de ligacao energia eletrica
			$linha .= $this->valor_texto(" ",2)." "; // Grupo de tensao energia eletrica
			$linha .= $this->valor_texto(" ",1)." "; // Tipo de assinante do servico de comunicacao ou telecomunicacao
			$linha .= $this->valor_numerico($row["numnotafis"],0,9)." "; // Numero da nota fiscal (opcional)
			$linha .= $this->valor_texto(" ",16)." "; // Reservado
			$arr_linha_ENT_MMAA[] = $linha;

			if(strlen($row["chavenfe"]) > 0){
				$this->arr_chavenfe[] = $row["numnotafis"]."|".$row["serie"]."|E|".removespecial($parceiro->getcpfcnpj())."|".$row["chavenfe"];
			}
		}
		$arquivo = fopen($this->dircontabil."ENT_".str_pad($this->mes, 2, "0",STR_PAD_LEFT).str_replace("20","",$this->ano).".txt","w");
		foreach($arr_linha_ENT_MMAA as $linha){
			fwrite($arquivo,$linha."\r\n");
		}
		unset($arr_linha_ENT_MMAA);
		$this->arr_chavenfe = array_unique($this->arr_chavenfe);
		fwrite($arquivo,"FIM DE ARQUIVO");
		fclose($arquivo);

		//**********************************************************************
		//						Layout do arquivo de saidas
		//**********************************************************************
		setprogress(0,"Carregando saidas",TRUE);


		$query  = "(SELECT nf.codparceiro, nf.numnotafis, 0 AS numnotafisfinal, nf.serie, it.natoperacao, it.aliqicms, nf.codcondpagto, ";
		$query .= "nf.dtentrega, nf.dtemissao, nf.observacaofiscal, nf.chavenfe, 'NFE' AS tipovenda, ";
		$query .= "CASE WHEN it.totalicmssubst > 0 THEN (SELECT aliqicms FROM classfiscal WHERE pd.codcfnfe = codcf) ELSE 0.000 END AS aliqicmsst, ";
		$query .= "SUM(nf.totaldesconto) AS totaldesconto, SUM(nf.totalfrete) AS totalfrete, ";
		$query .= "SUM(it.totalbruto) AS totalbruto, SUM(it.totalbaseicms) AS totalbaseicms, SUM(it.totalicms) AS totalicms, ";
		$query .= "SUM(it.totalliquido) AS totalliquido, SUM(it.totalacrescimo) AS totalacrescimo, SUM(it.totalbaseisento) AS totalbaseisento, ";
		$query .= "SUM(it.totalipi) AS totalipi, SUM(it.totalbaseicmssubst) AS totalbaseicmssubst, SUM(it.totalicmssubst) AS totalicmssubst, ";
		$query .= "SUM(it.percipi) AS percipi, SUM(it.valipi) AS valipi ";
		$query .= "FROM itnotafiscal it ";
		$query .= "INNER JOIN notafiscal nf ON (it.idnotafiscal = nf.idnotafiscal) ";
		$query .= "INNER JOIN operacaonota op ON (nf.operacao = op.operacao) ";
		$query .= "INNER JOIN produto pd ON (it.codproduto = pd.codproduto) ";
		$query .= "WHERE op.tipo = 'S' AND nf.gerafiscal = 'S' AND nf.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM nf.dtentrega) = ".$this->ano." AND EXTRACT(MONTH FROM nf.dtentrega) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM nf.dtentrega) = 10 ";
		}
		$query .= "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13 ";
		$query .= "ORDER BY 2,3,4 ";

		$query .= ")UNION ALL(";

		$query .= "SELECT null::integer AS codparceiro, ";
		$query .= "(SELECT MIN(cupom::integer) FROM cupom WHERE dtmovto = cp.dtmovto AND caixa = cp.caixa AND codestabelec = cp.codestabelec) AS numnotafis, ";
		$query .= "(SELECT MAX(cupom::integer) FROM cupom WHERE dtmovto = cp.dtmovto AND caixa = cp.caixa AND codestabelec = cp.codestabelec) AS numnotafisfinal, ";
		$query .= "cp.caixa::character varying AS serie, ";
		$query .= "CASE WHEN it.tptribicms = 'F' THEN (SELECT natoperacaosubst FROM natoperacao WHERE natoperacao = '5.102') ELSE '5.102' END AS natoperacao, ";
		$query .= "it.aliqicms, 2 AS codcondpagto, cp.dtmovto AS dtentrega, cp.dtmovto AS dtemissao, NULL AS observacaofiscal, NULL AS chavenfe, 'ECF' AS tipovenda, ";
		$query .= "0.000 AS aliqicmsst, ";
		$query .= "SUM(it.desconto) AS totaldesconto, 0.000 AS totalfrete, SUM(it.valortotal) AS totalbruto, ";
		$query .= "CASE WHEN it.aliqicms > 0 THEN SUM(it.valortotal) END AS totalbaseicms, CASE WHEN it.aliqicms > 0 THEN SUM(it.valortotal)*(it.aliqicms/100) END AS totalicms, ";
		$query .= "SUM(it.valortotal + it.acrescimo - it.desconto) AS totalliquido, SUM(it.acrescimo) AS totalacrescimo, 0.000 AS totalbaseisento, ";
		$query .= "0.000 AS totalipi, 0.000 AS totalbaseicmssubst, 0.000 AS totalicmssubst, 0 AS percipi, 0.000 AS valipi ";
		$query .= "FROM itcupom it ";
		$query .= "INNER JOIN cupom cp ON (it.idcupom = cp.idcupom) ";
		$query .= "INNER JOIN maparesumo mp ON (mp.dtmovto = cp.dtmovto AND cp.numeroecf = mp.numeroecf) ";
		$query .= "WHERE cp.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM cp.dtmovto) = ".$this->ano."  AND EXTRACT(MONTH FROM cp.dtmovto) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM cp.dtmovto) = 10 ";
		}
		$query .= "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12  ORDER BY 2,3)";


		$arr_linha_SAI_MMAA = array();
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$arquivo = fopen($this->dircontabil."SAI_".str_pad($this->mes, 2, "0",STR_PAD_LEFT).str_replace("20","",$this->ano).".txt","w");

		foreach ($arr as $row){
			$tem_cliente = TRUE;
			if($row["codparceiro"] > 0){
				$cliente = objectbytable("cliente",$row["codparceiro"],$this->con);
				$cidade = objectbytable("cidade",$cliente->getcodcidadeent(),$this->con);
			}else{
				$tem_cliente = FALSE;
			}

			$linha  = $this->valor_texto($row["tipovenda"],3)." "; // Especie da nota fiscal
			$linha .= $this->valor_texto($row["tipovenda"] == "ECF" ? str_pad($row["serie"],3,"0",STR_PAD_LEFT) : $row["serie"],3)." "; // Serie da nota fiscal
			$linha .= $this->valor_numerico($row["numnotafis"],0,6)." "; // Preencher apenas o campo nota inicial
			$linha .= $this->valor_numerico($row["numnotafisfinal"],0,6)." "; // ultilizado quando o registro de notas e agrupado
			$linha .= $this->valor_texto("SP",2)." "; // Sigla do estado
			$linha .= $this->valor_data($row["dtemissao"])." "; // Data de emissao do no formato DDMMAAAA
			$linha .= $this->valor_numerico(($row["codcondpagto"] == 2) ? 1 : 2,0,1)." "; // Forma de pagamento 1 avista 2 aprazo
			$linha .= $this->valor_numerico(removeformat($row["natoperacao"]),0,4)." "; // Codigo de CFOP
			$linha .= $this->valor_numerico(!$tem_cliente ? "" : $cliente->getcodcliente(),0,5)." "; // Cliente
			$linha .= $this->valor_numerico(0,0,1)." "; // Tipo de PIS
			$linha .= $this->valor_numerico(0,0,4); // Clas. contabil
			$linha .= $this->valor_numerico(0,0,4)." "; // Comple. contabil
			$linha .= $this->valor_numerico($row["totalliquido"],2,14)." "; // Valor total da nota fiscal
			$linha .= $this->valor_numerico($row["totalbaseicms"],2,14)." "; // Base ICMS
			$linha .= $this->valor_numerico($row["aliqicms"],2,5)." "; // Aliquota de ICMS
			$linha .= $this->valor_numerico($row["totalicms"],2,14)." "; // Valor do ICMS
			$linha .= $this->valor_texto(" ",92)." "; // Reservado
			$linha .= $this->valor_numerico($row["totalacrescimo"],2,14)." "; // Valor Acrescimo
			$linha .= $this->valor_numerico($row["totalbaseisento"],2,14)." "; // Isentos de ICMS
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros ICMS
			$linha .= $this->valor_numerico(0,2,14)." "; // Base para o IPI
			$linha .= $this->valor_numerico(0,2,5)." "; // Aliquota de IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Isentos do IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros de IPI
			$linha .= $this->valor_numerico(0,0,1)." "; //Excecoes com aliq. diferenciadas PIS/COFINS
			$linha .= $this->valor_numerico(0,2,14)." "; // ICMS retido
			$linha .= $this->valor_numerico(0,2,14)." "; // IPI incluso que nao sera utilizado como debito
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros valores de IPI
			$linha .= $this->valor_numerico(0,2,14)." "; // Outros valores
			$linha .= $this->valor_texto($row["observacaofiscal"],50)." "; // Observacao fiscal
			$linha .= $this->valor_texto(" ",1)." "; // Tipo de IPI: D-Decimal, M-Mensal, " "-Branco
			$linha .= $this->valor_numerico(0,2,10)." "; // PIS retido nas vendas
			$linha .= $this->valor_numerico(0,2,10)." "; // COFINS retido nas vendas
			$linha .= $this->valor_texto("S",1)." "; // Informar a base do PIS/COFINS: S-Sim, N-Nao
			$linha .= $this->valor_numerico($row["totalliquido"],2,14)." "; // Total da base do PIS/COFINS
			$linha .= $this->valor_texto("N",1)." "; // Informar a base do IRPJ/CSLL: S-Sim, N-Nao
			$linha .= $this->valor_numerico(0,2,14)." "; //  Total da base do IRPJ/CSLL
			$linha .= $this->valor_numerico(0,0,3)." "; // Tipo receita super simples
			$linha .= $this->valor_numerico($row["totalbaseicmssubst"],2,14)." "; // Base ICMS de substituicao tributaria
			$linha .= $this->valor_numerico($row["aliqicmsst"],2,5)." "; // Aliquota ICMS de substituicao tributaria
			$linha .= $this->valor_numerico($row["totalicmssubst"],2,14)." "; // Valor ICMS de substituicao tributaria
			$linha .= $this->valor_texto($row["tipovenda"] == "ECF" ? "2D" : "55",2)." "; // Codigo do modelo do documento fiscal conforme SPED EFD
			$linha .= $this->valor_texto("00",2)." "; // Codigo da situacao do documento fiscal conforme SPED EFD
			$linha .= $this->valor_texto(" ",1)." "; // Via de transporte: 0-Rodoviario, 1-Ferroviario... 5-Aereo
			$linha .= $this->valor_texto(" ",1)." "; // Indicacao de operacao com veiculos: 0-Venda para concessionaria... 9-Outros
			$linha .= $this->valor_texto(" ",14)." "; // CNPJ da Concessionaria
			$linha .= $this->valor_texto(" ",1); // Modalidade de frete: 1-CIF, 2-FOB
			//***************Informacoes sobre o cliente da nota**************//

			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cliente->getrazaosocial(),50)." "; // Razao social
			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cliente->getufent(),2)." "; // Sigla do estado
			$linha .= $this->valor_numerico(0,0,5)." "; // Municipio ZFM/ALC
			$linha .= $this->valor_texto(removeformat(!$tem_cliente ? "" : $cliente->getcpfcnpj()),14)." "; // CPF ou CNPJ
			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cliente->getrgie(),20)." "; // Numero da inscricao estadual
			$linha .= $this->valor_numerico(0,0,2)." "; // Codigo DIPAM-B
			$linha .= $this->valor_numerico(0,0,4)." "; // Municipio DIPAM-B
			$linha .= $this->valor_texto("N",1)." "; // Excluir da DIPI
			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cliente->getufent(),50)." "; // Endereco
			$linha .= $this->valor_numerico(!$tem_cliente ? "" : $cliente->getcepent(),0,8)." "; // CEP
			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cliente->getbairroent(),50)." "; // Bairro
			$linha .= $this->valor_texto(!$tem_cliente ? "" : $cidade->getnome(),50)." "; // Cidade
			$linha .= $this->valor_numerico(0,0,10); // Inscricao municipal
			$linha .= $this->valor_numerico(0,0,2)." "; // Digito municipal
			$linha .= $this->valor_numerico(0,0,2)." "; // Cidade GISS
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor do frete
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor do seguro
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor de outras despesas
			$linha .= $this->valor_numerico(0,2,10)." "; // Valor de descontos
			$linha .= $this->valor_texto(" ",9)." "; // Codigo do SUFRAMA
			$linha .= $this->valor_texto(" ",5)." "; // Codigo do Pais conforme SPED EFD
			$linha .= $this->valor_texto(" ",15)." "; // Tipo de gradouro
			$linha .= $this->valor_numerico(!$tem_cliente ? "" : $cliente->getnumeroent(),0,6)." "; // Numero do endereco
			$linha .= $this->valor_texto(" ",30)." "; // Complemento do enderecao
			$linha .= $this->valor_texto(" ",2)." "; // Estado da concessionaria
			$linha .= $this->valor_texto(" ",17)." "; // Chassi do veiculo
			$linha .= $this->valor_numerico(0,0,3)." "; // Codigo da observacao negativa
			$linha .= $this->valor_texto(" ",9); // Numero da nota devolvida
			$linha .= $this->valor_texto(" ",1)." "; // Informar tipo de ICMS Substituido: 1-ICMS Substituido, 2-ICMS Substituto
			$linha .= $this->valor_texto(" ",1)." "; // Origem da mercadoria
			$linha .= $this->valor_texto(" ",2)." "; // Situacao tributaria
			$linha .= $this->valor_texto(" ",1)." "; // Tipo isencao SS
			$linha .= $this->valor_numerico(0,2,5)." "; // Reducao aliquota ICMS Super Simples
			$linha .= $this->valor_numerico(0,0,9)." "; // Numero da nota fiscal inicial
			$linha .= $this->valor_numerico(0,0,9); // Numero da nota fiscal final
			$linha .= $this->valor_numerico(0,0,1); // Brancos
			$linha .= $this->valor_numerico(0,0,2) ; // Delimitador
			$linha .= $this->valor_texto(" ",479); // Brancos
			fwrite($arquivo,$linha."\r\n");

			if($row["tipovenda"] == "NFE"){
				$this->arr_chavenfe[] = $row["numnotafis"]."|".$row["serie"]."|S|".$parceiro->getcpfcnpj()."|".$row["chavenfe"];
			}
		}
		$this->arr_chavenfe = array_unique($this->arr_chavenfe);

		fwrite($arquivo,"FIM DE ARQUIVO");
		fclose($arquivo);

		//**********************************************************************
		//						Layout da chave NFE
		//**********************************************************************
		setprogress(0,"Carregando chaves NFE",TRUE);
		$arr_linha_NFE_MMAA = array();
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($this->arr_chavenfe as $chavenfe){
			$chavenfe = explode("|",$chavenfe);
			$linha  = $this->valor_texto($chavenfe[0],6)." ";
			$linha .= $this->valor_texto($chavenfe[1],3)." "; //serie
			$linha .= $this->valor_texto($chavenfe[2],1)." ";
			$linha .= $this->valor_texto(removeformat($chavenfe[3]),14)." ";
			$linha .= $this->valor_texto($chavenfe[4],44)." ";
			$linha .= $this->valor_numerico($chavenfe[0],0,9)." ";
			$linha .= $this->valor_texto(" ",814)." ";

			$arr_linha_NFE_MMAA[] = $linha;
		}

		$arquivo = fopen($this->dircontabil."NFE_".str_pad($this->mes, 2, "0",STR_PAD_LEFT).str_replace("20","",$this->ano).".txt","w");
		foreach($arr_linha_NFE_MMAA as $linha){
			fwrite($arquivo,$linha."\r\n");
		}
		fwrite($arquivo,"FIM DE ARQUIVO");
		fclose($arquivo);

		//**********************************************************************
		//				Layout do arquivo de resumos do PDV
		//**********************************************************************
		setprogress(0,"Carregando resumos do PDV",TRUE);
		$query  = "SELECT DISTINCT maparesumo.dtmovto, maparesumo.numeroecf, maparesumo.operacaoini, maparesumo.operacaofim, maparesumo.numeroreducoes, maparesumo.gtinicial, ";
		$query .= "(maparesumo.totalitemcancelado + maparesumo.totalcupomcancelado) AS totalcancelado, (maparesumo.totaldescontocupom + totaldescontoitem) AS totaldesconto, maparesumo.gtfinal, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='T' AND maparesumoimposto.aliqicms=7 AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valoraliq1, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='T' AND maparesumoimposto.aliqicms=12 AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valoraliq2, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='T' AND maparesumoimposto.aliqicms=18 AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valoraliq3, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='T' AND maparesumoimposto.aliqicms=25 AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valoraliq4, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='F' AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valorsubst, ";
		$query .= "(SELECT maparesumoimposto.totalliquido FROM maparesumoimposto WHERE maparesumoimposto.tptribicms='I' AND maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) AS valorisento ";
		$query .= "FROM maparesumo INNER JOIN maparesumoimposto ON (maparesumo.codmaparesumo = maparesumoimposto.codmaparesumo) ";
		$query .= "WHERE maparesumoimposto.tptribicms = 'T' AND maparesumo.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM maparesumo.dtmovto) = ".$this->ano." AND EXTRACT(MONTH FROM maparesumo.dtmovto) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM maparesumo.dtmovto) = 10 ";
		}
		$query .= "ORDER BY dtmovto DESC ";

		$arr_linha_RESU_PDV = array();
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach ($arr as $row){
			$linha  = $this->valor_data($row["dtmovto"])." "; // Data de emissao do PDV no formato DDMMAAAA
			$linha .= $this->valor_numerico($row["numeroecf"],0,3)." "; // Codigo do equipamento emissor do PDV
			$linha .= $this->valor_numerico($row["operacaoini"],0,6)." "; // Numero do documento inicial
			$linha .= $this->valor_numerico($row["operacaofim"],0,6)." "; // Numero do documento final
			$linha .= $this->valor_numerico(" ",0,6)." "; // Contador de ordem de operacao
			$linha .= $this->valor_numerico($row["numeroreducoes"],0,6)." "; // Numero de leitura ou redutor Z do PDV
			$linha .= $this->valor_numerico($row["gtinicial"],0,16)." "; // Valor total inicial do dia
			$linha .= $this->valor_numerico($row["totalcancelado"],0,16)." "; // Valor total de cancelamentos
			$linha .= $this->valor_numerico($row["totaldesconto"],0,16)." "; // Valor total de descontos
			$linha .= $this->valor_numerico($row["gtfinal"],0,16)." "; // Valor total final do dia
			$linha .= $this->valor_numerico(7,2,5)." "; // 1 aliquota do PDV
			$linha .= $this->valor_numerico($row["valoraliq1"],2,12)." "; // Valor da 1 aliquota do PDV
			$linha .= $this->valor_numerico(12,2,5)." "; // 2 aliquota do PDV
			$linha .= $this->valor_numerico($row["valoraliq2"],2,12)." "; // Valor da 2 aliquota do PDV
			$linha .= $this->valor_numerico(18,2,5)." "; // 3 aliquota do PDV
			$linha .= $this->valor_numerico($row["valoraliq3"],2,12)." "; // Valor da 3 aliquota do PDV
			$linha .= $this->valor_numerico($row["valoraliq4"] == 0 ? 0 : 25,2,5)." "; // 4 aliquota do PDV
			$linha .= $this->valor_numerico($row["valoraliq4"],2,12)." "; // Valor da 4 aliquota do PDV
			$linha .= $this->valor_numerico(0,2,5)." "; // 5 aliquota do PDV
			$linha .= $this->valor_numerico(0,2,12)." "; // Valor da 5 aliquota do PDV
			$linha .= $this->valor_numerico(0,2,7)." "; // 6 aliquota do PDV
			$linha .= $this->valor_numerico(0,2,12)." "; // Valor da 6 aliquota do PDV
			$linha .= $this->valor_numerico($row["valorsubst"],2,16)." "; // Valor de substituicao tributaria
			$linha .= $this->valor_numerico($row["valorisento"],2,16)." "; // Valor isento
			$linha .= $this->valor_numerico(0,2,16)." "; // Valor de serviço constante no PDV
			$linha .= $this->valor_numerico(0,0,9)." "; // Numero de resumo do PDV
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do PIS
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do COFINS
			$linha .= $this->valor_numerico(0,0,3)." "; // Contador de reinicio de operacao
			$linha .= $this->valor_numerico(0,0,9)." "; // Documento inicial (opcional)
			$linha .= $this->valor_numerico(0,0,9)." "; // Documento final (opcional)
			$linha .= $this->valor_texto(" ",557)."  "; // Reservado
			$arr_linha_RESU_PDV[] = $linha;
		}

		$arquivo = fopen($this->dircontabil."RESU_PDV.txt","w");
		foreach($arr_linha_RESU_PDV as $linha){
			fwrite($arquivo,$linha."\r\n");
		}
		fwrite($arquivo,"FIM DE ARQUIVO");
		fclose($arquivo);

		//**********************************************************************
		//					Layout do arquivo de itens da nota
		//**********************************************************************
		setprogress(0,"Carregando itens da nota",TRUE);

		$query  = "(SELECT nf.codparceiro, nf.numnotafis, nf.serie, it.natoperacao, it.aliqicms, nf.codcondpagto, ";
		$query .= "nf.dtentrega, nf.dtemissao, nf.observacaofiscal, nf.chavenfe, ";
		$query .= "CASE WHEN it.totalicmssubst > 0 THEN (SELECT aliqicms FROM classfiscal WHERE pd.codcfnfe = codcf) ELSE 0.000 END AS aliqicmsst, ";
		$query .= "op.tipo, it.codproduto, pd.descricaofiscal, un.sigla AS prodsigla, it.csticms AS csticms, ";
		$query .= "nf.totaldesconto AS totaldesconto, nf.totalfrete AS totalfrete, it.totalbruto AS totalbruto, ";
		$query .= "it.totalbaseicms AS totalbaseicms, it.totalicms AS totalicms, ";
		$query .= "it.totalliquido AS totalliquido, it.totalacrescimo AS totalacrescimo, it.totalbaseisento AS totalbaseisento, ";
		$query .= "it.totalipi AS totalipi, it.totalbaseicmssubst AS totalbaseicmssubst, it.totalicmssubst AS totalicmssubst, it.percipi AS percipi, it.valipi AS valipi, ";
		$query .= "it.totalpis, it.totalcofins, it.totalbasepis, it.totalbasecofins, it.aliqcofins, it.aliqpis, ncm.codigoncm, ";
		$query .= "CASE WHEN op.tipo = 'E' THEN (SELECT codcst FROM piscofins WHERE codpiscofins = pd.codpiscofinsent) ELSE (SELECT codcst FROM piscofins WHERE codpiscofins = pd.codpiscofinssai) END AS cstpiscofins , ";
		$query .= "CASE WHEN op.tipo = 'E' THEN ipi.codcstent ELSE NULL END AS codcstipi, it.quantidade, it.preco ";
		$query .= "FROM itnotafiscal it ";
		$query .= "INNER JOIN notafiscal nf ON (it.idnotafiscal = nf.idnotafiscal) ";
		$query .= "INNER JOIN operacaonota op ON (nf.operacao = op.operacao) ";
		$query .= "INNER JOIN produto pd ON (it.codproduto = pd.codproduto) ";
		$query .= "INNER JOIN unidade un ON (it.codunidade = un.codunidade) ";
		$query .= "INNER JOIN ncm ON (pd.idncm = ncm.idncm) ";
		$query .= "INNER JOIN ipi ON (pd.codipi = ipi.codipi) ";
		$query .= "WHERE nf.gerafiscal = 'S' AND nf.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM nf.dtentrega) = ".$this->ano." AND EXTRACT(MONTH FROM nf.dtentrega) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM nf.dtentrega) = 10 ";
		}
		$query .= "ORDER BY 12,1,2,3,4,5,6 ";
		$query .= ")UNION ALL( ";
		$query .= "SELECT cp.codcliente AS codparceiro, ";
		$query .= "(SELECT MIN(cupom::integer) FROM cupom WHERE dtmovto = cp.dtmovto AND caixa = cp.caixa AND codestabelec = cp.codestabelec) AS numnotafis, ";
		$query .= " cp.numeroecf::character AS serie, '5.102' AS natoperacao, it.aliqicms, 2 AS codcondpagto, ";
		$query .= "cp.dtmovto AS dtentrega, cp.dtmovto AS dtemissao, NULL AS observacaofiscal, NULL AS chavenfe, ";
		$query .= "0.000 AS aliqicmsst, ";
		$query .= "'S' AS tipo, it.codproduto, pd.descricaofiscal, un.sigla AS prodsigla, cf.codcst  AS csticms, ";
		$query .= "it.desconto AS totaldesconto, 0.000 AS totalfrete, it.valortotal AS totalbruto, ";
		$query .= "CASE WHEN it.aliqicms > 0 THEN it.valortotal END AS totalbaseicms, CASE WHEN it.aliqicms > 0 THEN it.valortotal*(it.aliqicms/100) END AS totalicms, ";
		$query .= "it.valortotal + it.acrescimo - it.desconto AS totalliquido, it.acrescimo AS totalacrescimo, 0.000 AS totalbaseisento, ";
		$query .= "0.000 AS totalipi, 0.000 AS totalbaseicmssubst, 0.000 AS totalicmssubst, 0 AS percipi, 0.000 AS valipi, ";
		$query .= "it.totalpis, it.totalcofins, it.totalbasepis, it.totalbasecofins, it.aliqcofins, it.aliqpis, ncm.codigoncm, ";
		$query .= "(SELECT codcst FROM piscofins WHERE codpiscofins = pd.codpiscofinssai) AS cstpiscofins, ";
		$query .= "NULL AS codcstipi, it.quantidade, it.preco ";
		$query .= "FROM itcupom it ";
		$query .= "INNER JOIN cupom cp ON (it.idcupom = cp.idcupom) ";
		$query .= "INNER JOIN produto pd ON (it.codproduto = pd.codproduto) ";
		$query .= "INNER JOIN embalagem emb ON (pd.codembalvda = emb.codembal) ";
		$query .= "INNER JOIN unidade un ON (emb.codunidade = un.codunidade) ";
		$query .= "INNER JOIN classfiscal cf ON (pd.codcfpdv = cf.codcf) ";
		$query .= "INNER JOIN ncm ON (pd.idncm = ncm.idncm) ";
		$query .= "WHERE cp.codestabelec = ".$this->estabelecimento." AND EXTRACT(YEAR FROM cp.dtmovto) = ".$this->ano."  AND EXTRACT(MONTH FROM cp.dtmovto) = ".$this->mes." ";
		if($this->teste == "S"){
			$query .= "AND EXTRACT(DAY FROM cp.dtmovto) = 10 ";
		}
		$query .= "ORDER BY 12,1,2,3,4,5,6) ";

		unset($arr_linha_RESU_PDV);
		unset($arr_linha_SAI_MMAA);
		unset($arr_linha_ENT_MMAA);
		$arr = 0;
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$arquivo = fopen($this->dircontabil."DEMO_IPI.txt","w");
		foreach ($arr as $row){
			$linha  = $this->valor_numerico($row["numnotafis"],0,6)." "; // Numero da nota fiscal
			$linha .= $this->valor_texto(convert_date($row["dtentrega"],"Y-m-d","dm"),4)." "; // Data no formato DiaMes
			$linha .= $this->valor_texto($row["tipo"],1)." "; // Especifica se a nota e de: E-entrada, S-saida
			$linha .= $this->valor_numerico($row["codparceiro"],0,5)." "; // Sequencial para identificacao do emitente
			$linha .= $this->valor_numerico(removeformat($row["codigoncm"]),0,11)." "; // Codigo de classificacao fiscal do produto
			$linha .= $this->valor_numerico(($row["csticms"] == "ITCUPOM" ? csticms($row["tptribicms"],$row["aliqicms"],0,0,0) : $row["csticms"]),0,3)." ";	//	Codigo da situacao tributaria do produto
			$linha .= $this->valor_numerico($row["quantidade"],4,10)." "; // Quantidade de intens informado na nota
			$linha .= $this->valor_numerico($row["preco"],2,12)." "; // Valor unitario
			$linha .= $this->valor_numerico($row["totalliquido"],2,14)." "; // Valor total do item
			$linha .= $this->valor_numerico($row["totaldesconto"],2,14)." "; // Valor do desconto
			$linha .= $this->valor_numerico($row["tipo"] == "E" ? $row["totalbruto"] : 0,2,14)." "; // Base de calculo do IPI
			$linha .= $this->valor_numerico($row["tipo"] == "E" ? $row["percipi"] : 0,2,5)." "; // Aliquota do IPI
			$linha .= $this->valor_numerico($row["tipo"] == "E" ? $row["valipi"] : 0,0,12)." "; // Valor do IPI
			$linha .= $this->valor_numerico($row["totalbaseicms"],0,14)." "; // Base de calculo do ICMS
			$linha .= $this->valor_numerico($row["aliqicms"],2,5)." "; // Aliquota do ICMS
			$linha .= $this->valor_numerico($row["totalicms"],2,12)." "; // Valor do ICMS
			$linha .= $this->valor_texto($row["prodsigla"],3)." "; // Unidade de medida de acordo com a NF
			$linha .= $this->valor_texto($row["temsubs"],1)." "; // Tem substituicao: S-sim, N-nao
			$linha .= $this->valor_numerico(str_replace(".","",$row["natoperacao"]),0,4)." "; // Natureza CFOP
			$linha .= $this->valor_numerico(0,0,10)." "; // Numero do registro de exportacao
			$linha .= $this->valor_texto(" ",4)." "; // Reservado
			$linha .= $this->valor_numerico(0,0,10)." "; // Numero de declaracao de importacao
			$linha .= $this->valor_texto(0,1)." "; // Tipo do documento de importacao
			$linha .= $this->valor_texto(" ",2)." "; // Reservado
			$linha .= $this->valor_texto(" ",8)." "; // Data do embarque
			$linha .= $this->valor_texto(" ",14)." "; // Reservado
			$linha .= $this->valor_texto(" ",3)." "; // Reservado
			$linha .= $this->valor_texto(" ",3)." "; // Reservado
			$linha .= $this->valor_texto(" ",334)." "; // Reservado
			$linha .= $this->valor_texto($row["codproduto"],15)." ";// Codigo do produto
			$linha .= $this->valor_texto($row["descricaofiscal"],59)." ";// Descricao do produto
			$linha .= $this->valor_texto($row["prodsigla"],3)." "; // Unidade de medida do produto
			$linha .= $this->valor_texto("N",1)." "; // Gerar DNF
			$linha .= $this->valor_texto(" ",2)." "; // Unidade de medida DNF
			$linha .= $this->valor_texto(" ",3)." "; // Especie da DNF
			$linha .= $this->valor_numerico(0,2,5)." "; // Capacidade volumetrica
			$linha .= $this->valor_texto($row["tipo"] == "E" ? "999" : " ",3)." "; // Enquadramento do IPI
			$linha .= $this->valor_texto($row["tipo"] == "E" ? $row["codcstipi"] : " ",2)." "; // Situacao tributaria do IPI
			$linha .= $this->valor_numerico($row["totalbaseicmssubst"],2,14)." "; // Base de calculo do ICMS de Substituicao tributaria
			$linha .= $this->valor_numerico($row["totalicmssubst"] > 0 ? $row["aliqicmsst"] : 0,2,5)." "; // Aliquota do ICMS de Substituicao tributaria
			$linha .= $this->valor_numerico($row["totalicmssubst"],2,12)." "; // Valor do ICMS de Substituicao tributaria
			$linha .= $this->valor_numerico(0,0,2)." "; // Tipo do item
			$linha .= $this->valor_numerico(substr($row["codigoncm"],0,2),0,2)." "; // Genero do item
			$linha .= $this->valor_texto(" ",9)." "; // Codigo ANP
			$linha .= $this->valor_numerico($row["cupom"],0,6)." "; // Codigo do cupom fiscal
			$linha .= $this->valor_texto($row["codproduto"],60)." "; // Codigo do produto no SPED
			$linha .= $this->valor_texto($row["prodsigla"],3)." "; // Unidade de comercializacao
			$linha .= $this->valor_numerico(0,6,12)." "; // Fator de conversao
			$linha .= $this->valor_texto($row["cstpiscofins"],2)." "; // Situacao tributaria do PIS
			$linha .= $this->valor_texto($row["cstpiscofins"],2)." "; // Situacao tributaria do COFINS
			$linha .= $this->valor_texto(" ",3)." "; // Serie do item
			$linha .= $this->valor_numerico(0,0,3)." "; // Situacao tributaria simples nacional
			$linha .= $this->valor_numerico(0,2,5)." "; // Aliquota de credito simples nacional conforme ajuste SINEF 3
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do calculo de credito simples nacional
			$linha .= $this->valor_texto("S",1)." "; // Informa se a base do PIS/COFINS sera informada pelo usuario: S-sim, N-nao
			$linha .= $this->valor_texto(" ",2)." "; // Tabela de aliquota PIS/COFINS
			$linha .= $this->valor_numerico($row["totalbasepis"],2,14)." "; // Valor da base de PIS
			$linha .= $this->valor_numerico($row["totalpis"],2,14)." "; // Valor do imposto PIS
			$linha .= $this->valor_numerico($row["totalbasecofins"],2,14)." "; // Valor da base de COFINS
			$linha .= $this->valor_numerico($row["totalcofins"],2,14)." "; // Valor do imposto COFINS
			$linha .= $this->valor_texto($row["tipo"] == "E" ? "01" : " " ,2)." "; // Codigo base de credito
			$linha .= $this->valor_texto($row["tipo"] == "E" ? "0" : " ",1)." "; // Origem de credito
			$linha .= $this->valor_texto($row["tipo"] == "E" ? "101" : " ",3)." "; // Tipo de credito PIS/COFINS
			$linha .= $this->valor_texto($row["tipo"] == "E" ? "2" : "1",1)." "; // Tipo de geracao do PIS/COFINS
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor da base bruta IRPJ
			$linha .= $this->valor_numerico(0,2,5)." "; // Aliquota  da base de IRPJ
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor da base bruta da CSLL
			$linha .= $this->valor_numerico(0,2,5)." "; // Aliquota da CSLL
			$linha .= $this->valor_numerico($row["valfrete"],2,14)." "; // Valor do frete proporcional por item
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do seguro proporcional por item
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor das despesas e acessórios proporcional por item
			$linha .= $this->valor_texto(" ",9)." "; // Classificacao do produto para a DACON
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor de acrecsimo constante na nota fiscal (se houver)
			$linha .= $this->valor_numerico($row["tipo"] == "E" ? $row["valipi"] : 0 ,2,14)." "; // Valor do IPI incluso
			$linha .= $this->valor_texto(" ",1)." "; // Tipo de geracao do IRPJ/CSLL
			$linha .= $this->valor_numerico(0,0,9)." "; // Numero da nota fiscal
			$linha .= $this->valor_numerico(0,2,14)." "; // Valor do ICMS retido
			$linha .= $this->valor_texto(" ",1)." "; // Pertencente a linha positiva de calculo de PIS/COFINS: S-sim, N-nao
			$linha .= $this->valor_numerico($row["aliqpis"],2,5)." "; // Aliquota de PIS
			$linha .= $this->valor_numerico($row["aliqcofins"],2,5)." "; // Aliquota de COFINS
			$linha .= $this->valor_texto(" ",1)." "; // Excluir icms retido na base de piscofin

			$linha .= $this->valor_texto($this->natreceita($row["codproduto"]),3)." "; // Natureza da receita de Pis
			$linha .= $this->valor_texto($this->natreceita($row["codproduto"]),3)." "; // Natureza da receita de Cofins

			$linha .= $this->valor_texto(" ",403)." "; // Reservado
			fwrite($arquivo,$linha."\r\n");
		}
		fwrite($arquivo,"FIM DE ARQUIVO");
		fclose($arquivo);
	}
	private function valor_data($data){
		$data = value_date($data);
		$data = convert_date($data,"Y-m-d","dmY");
		return $data;
	}

	private function valor_numerico($numero,$decimais,$tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero,$decimais,".","");
		$numero = substr($numero,0,$tamanho);
		$numero = str_pad($numero,$tamanho,"0",STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto,$tamanho){
		$texto = str_replace(array("\r","\n","'","§","º")," ",$texto); //Campo observacao
		$texto = substr($texto,0,$tamanho);
		$texto = str_pad($texto,$tamanho," ",STR_PAD_RIGHT);
		return $texto;
	}

	protected function natreceita($codproduto){
		$produto = objectbytable("produto",$codproduto,$this->con);

		$natreceita = $this->arr_natreceita["P".$produto->getcodproduto()];
		if(strlen($natreceita) == 0){
			$natreceita = $this->arr_natreceita["S".$produto->getcodsubgrupo()];
		}
		if(strlen($natreceita) == 0){
			$natreceita = $this->arr_natreceita["G".$produto->getcodgrupo()];
		}
		if(strlen($natreceita) == 0){
			$natreceita = $this->arr_natreceita["D".$produto->getcoddepto()];
		}
		if(strlen($natreceita) == 0){
			$natreceita = "999";
		}
		return $natreceita;
	}
}
?>