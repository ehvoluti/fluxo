<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");
require_once("../class/pdvvasilhame.class.php");

class SysPdv{

	private $con;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	private $pdvvasilhame;
	private $pdvrecebepdv;
	private $frentecaixa;
	private $error;
	public $arr_codcliente_sincpdv;
	public $arr_codproduto_sincpdv;

	function __construct(){
		$this->limpar_dados();
	}

	function error(){
		return $this->error;
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
		$this->frentecaixa = objectbytable("frentecaixa", 4, $this->con);
	}

	function exportar_cliente($return = FALSE, $sincpdv = false){
		setprogress(0, "Buscando clientes", TRUE);
		$estabelecimento = $this->pdvconfig->getestabelecimento();
		$arr_linha = array();

		$where = array();

		$query = "SELECT cliente.nome, cliente.cpfcnpj, cliente.enderres, cliente.bairrores, cliente.cepres, cliente.foneres, cliente.rgie, cliente.tppessoa, ";
		$query .= "  cliente.sexo, cliente.numerores, cliente.complementores, cliente.codcliente, cidade.nome AS cidade_nome, statuscliente.codstatus AS status, ";
		$query .= "  cliente.complementoent, cliente.limite1, cliente.debito1, cliente.limite2, cliente.debito2, ";
		$query .= "  empresa.nome AS nomeempresa, ";
		$query .= "  cliente.tipopreco, cliente.descfixo, cliente.ufres, cliente.senha, cidade.codoficial AS cidadeigbe ";
		$query .= "FROM cliente ";
		$query .= "LEFT JOIN cliente empresa ON (empresa.codcliente = cliente.codempresa) ";
		$query .= "LEFT JOIN cidade ON (cliente.codcidaderes = cidade.codcidade) ";
		$query .= "INNER JOIN statuscliente ON (cliente.codstatus = statuscliente.codstatus) ";
		$query .= "LEFT JOIN clienteestab ON (cliente.codcliente = clienteestab.codcliente) ";
		if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
			$where[] = "clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
		}
		if($sincpdv){
			$where[] = "clienteestab.sincpdv IN (0, 1)";
		}else{
			if($this->pdvconfig->cliente_parcial()){
				$where[] = $this->pdvconfig->cliente_parcial_query();
			}
		}
		if(count($where) > 0){
			$query .= "WHERE ".implode(" AND ", $where);
		}

		$this->arr_codcliente_sincpdv = array();

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$arr = $this->pdvconfig->remove_array_format($arr);

		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando clientes: ".($i + 1)." de ".sizeof($arr));
			$linha = str_pad($row["codcliente"], 15, "0", STR_PAD_LEFT); // Codigo do cliente
			$linha .= str_pad(substr(removespecial($row["nome"]), 0, 40), 40, " ", STR_PAD_RIGHT); // Nome do cliente
			$linha .= str_pad(substr(removeformat($row["cpfcnpj"]), 0, 14), 14, " ", STR_PAD_RIGHT); // CPF ou CGC
			$linha .= str_pad(substr(removespecial($row["enderres"]), 0, 45), 45, " ", STR_PAD_RIGHT); // Endereco
			$linha .= str_pad(substr(removespecial($row["bairrores"]), 0, 15), 15, " ", STR_PAD_RIGHT); // Bairro
			$linha .= str_pad(substr(removespecial($row["cidade_nome"]), 0, 20), 20, " ", STR_PAD_RIGHT); // Cidade
			$linha .= str_pad(substr($row["ufres"], 0, 2), 2, " ", STR_PAD_RIGHT); // Estado (sigla)
			$linha .= str_pad(substr(removeformat($row["cepres"]), 0, 8), 8, " ", STR_PAD_RIGHT); // CEP
			$linha .= str_pad(substr(removeformat($row["foneres"]), 0, 12), 12, " ", STR_PAD_RIGHT); // Telefone
			$linha .= str_pad(number_format($row["limite1"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Limite de credito
			$linha .= str_pad(number_format($row["debito1"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Limite de credito utilizado
			$linha .= str_pad(substr($row["status"], 0, 2), 2, "0", STR_PAD_LEFT); // Codigo do status
			$linha .= str_pad("PRZ", 3, " ", STR_PAD_RIGHT); // Tabela de prazo
			$linha .= str_pad("0", 3, "0", STR_PAD_RIGHT); // Prazo
			$linha .= str_pad(substr(removespecial($row["nome"]), 0, 25), 25, " ", STR_PAD_RIGHT); // Nome fantasia do cliente
			$linha .= str_pad(substr(removeformat($row["rgie"]), 0, 20), 20, " ", STR_PAD_RIGHT); // CPF ou CGC
			$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // Data de cadastro
			$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // Data de nascimento
			$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // Data de bloqueio
			$linha .= str_pad("", 30, " ", STR_PAD_RIGHT); // Nome do pai
			$linha .= str_pad("", 30, " ", STR_PAD_RIGHT); // Nome da mae
			$linha .= str_pad(substr($row["tppessoa"], 0, 1), 1, " ", STR_PAD_RIGHT); // Tipo de pessoa (F ou J)
			$linha .= str_pad(substr(removeformat($row["foneres"]), 0, 12), 12, " ", STR_PAD_RIGHT); // Telefone
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Fax
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Nome da pessoa para contato
			$linha .= str_pad("", 45, " ", STR_PAD_RIGHT); // Endereco para cobranca
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Bairro para cobranca
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // CEP para cobranca
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Cidade para cobranca
			$linha .= str_pad("", 2, " ", STR_PAD_RIGHT); // Estado para cobranca
			$linha .= str_pad($row["descfixo"], 13, " ", STR_PAD_RIGHT); // Desconto
			$linha .= str_pad("", 255, " ", STR_PAD_RIGHT); // Observacao
			$linha .= str_pad("", 255, " ", STR_PAD_RIGHT); // Restricoes
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Administrador do cartao de credito
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Numero do cartao de credito
			$linha .= str_pad("", 5, " ", STR_PAD_RIGHT); // Validade do cartao de credito
			$linha .= str_pad("", 70, " ", STR_PAD_RIGHT); // Correio eletronico
			$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // Data da ultima alteracao
			$linha .= str_pad("", 7, " ", STR_PAD_RIGHT); // Codigo de atividade eletronica
			$linha .= str_pad(substr($row["sexo"], 0, 1), 1, " ", STR_PAD_RIGHT); // Sexo (M ou F)
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Tipo de resisdencia
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Tempo de residencia
			$linha .= str_pad("", 50, " ", STR_PAD_RIGHT); // Veiculo
			$linha .= str_pad($row["complementoent"], 50, " ", STR_PAD_RIGHT); // Ponto de referencia
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Comprovante de endereco
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Comprovante de renda
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Comprovante de renda conjuge
			$tamlinha = strlen($linha);
			$linha .= str_pad($row["nomeempresa"], 40, " ", STR_PAD_RIGHT); // Empresa de trabalho
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone de trabalho
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Cargo na empresa
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Tempo de empresa
			$linha .= str_pad("", 50, " ", STR_PAD_RIGHT); // Endereco de trabalho
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Nome do chefe de trabalho
			$linha .= str_pad(number_format(0, 2, ".", ""), 13, " ", STR_PAD_LEFT); // Salario
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Outras rendas
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Estado civil
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome do conjuge
			$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // Data de nascimento do conjuge
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome da empresa onde conjuge trabalha
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone do trabalho do conjuge
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Cargo do conjuge
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Nome do chefe do conjuge
			$linha .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Salario do conjuge
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome da referencia 1
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone da referencia 1
			$linha .= str_pad("", 50, " ", STR_PAD_RIGHT); // Endereco da referencia 1
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome da referencia 2
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone da referencia 2
			$linha .= str_pad("", 50, " ", STR_PAD_RIGHT); // Endereco da referencia 2
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome da referencia comercial 1
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone da referencia comercial 1
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome da referencia comercial 2
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone da referencia comercial 2
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria 1
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria agencia 1
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria conta  1
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Tipo de conta bancaria 1
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria 2
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria agencia 2
			$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Referencia bancaria conta 2
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Tipo de conta bancaria 2
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Ticket
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome do dependente 1
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Grau de parentesco do dependente 1
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone do dependente 1
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Nome do dependente 2
			$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Grau de parentesco do dependente 2
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone do dependente 2
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Situacao no SPC
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Nome da pessoa de contato no SPC
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Situacao no tele-cheque
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Nome da pessoa de contato no tele-cheque
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Observacao da situacao
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Situacao de aprovacao do cadastro
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Pessoa que autorizou o cadastro
			$linha .= str_pad("", 2, " ", STR_PAD_RIGHT); // Dia de fechamento da fatura
			$linha .= str_pad("", 20, " ", STR_PAD_RIGHT); // Naturalidade
			$linha .= str_pad("", 6, " ", STR_PAD_RIGHT); // Orgao expedidor da RG
			$linha .= str_pad($row["tipopreco"], 1, " ", STR_PAD_RIGHT); // Tipo de preco
			$linha .= str_pad("", 3, "0", STR_PAD_RIGHT); // Ramo de atividade
			$linha .= str_pad("", 5, " ", STR_PAD_RIGHT); // Complemento bairro
			$linha .= str_pad("", 5, " ", STR_PAD_RIGHT); // Complemento bairro do endereco de cobranca
			$linha .= str_pad(substr($row["numerores"], 0, 6), 6, " ", STR_PAD_LEFT); // Numero do endereco
			$linha .= str_pad("", 6, " ", STR_PAD_RIGHT); // Numero do endereco de combranca
			$linha .= str_pad(substr($row["complementores"], 0, 12), 12, " ", STR_PAD_RIGHT); // Complemento do endereco
			$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Complemento de cobranca
			$linha .= str_pad("", 4, "0", STR_PAD_RIGHT); // Codigo do vendedor (4 digitos)
			$linha .= str_pad(number_format($row["limite2"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Limite  de credito 2
			$linha .= str_pad(number_format($row["debito2"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Limite  de credito utilizado 2
			$linha .= str_pad($row["codcliente"], 6, "0", STR_PAD_LEFT); // Codigo interno
			$linha .= str_pad("", 6, "0", STR_PAD_RIGHT); // codigo do vendedor (6 digitos)
			$linha .= str_pad($row["nome"], 60, " ", STR_PAD_RIGHT); // descricao do cliente
			$linha .= str_pad($row["nome"], 60, " ", STR_PAD_RIGHT); // nome fantasia do cliente
			$linha .= str_pad($row["senha"], 12, " ", STR_PAD_LEFT); // senha do cliente
			$linha .= str_pad($row["cidadeigbe"], 7, "0", STR_PAD_RIGHT); // Codigo do IBGE
			$linha .= str_pad("1058", 5, "0", STR_PAD_RIGHT); // Codigo do Pais

			$arr_linha[] = $linha;

			$this->arr_codcliente_sincpdv[] = $row["codcliente"];
		}
		if($return){
			return array($this->pdvconfig->file_create("SYSPCLI.TXT", $arr_linha, "w+", TRUE));
		}else{
			$this->pdvconfig->file_create("SYSPCLI.TXT", $arr_linha);
		}
		return true;
	}

	function exportar_finalizadora(){
		// Administradora
		setprogress(0, "Buscando administradoras", 0);
		$arr_linha = array();
		$res = $this->con->query("SELECT codadminist, nome FROM administradora");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$linha = str_pad($row["codadminist"], 2, "0", STR_PAD_LEFT); // Codigo da administradora
			$linha .= str_pad(substr($row["nome"], 0, 35), 35, " ", STR_PAD_RIGHT); // Nome da administradora
			$arr_linha[] = $linha;
		}
		$this->pdvconfig->file_create("SYSPADM.TXT", $arr_linha);

		// Finalizadoras
		setprogress(0, "Buscando finalizadoras", 0);
		$arr_linha = array();
		$query = "SELECT finalizadora.codfinaliz, finalizadora.descricao, especie.especie, ";
		$query .= "	(CASE WHEN finalizadora.tipoparceiro = 'A' THEN finalizadora.codparceiro ELSE NULL END) AS codadminist, ";
		$query .= "	(CASE WHEN condpagto.dia1 = 0 AND condpagto.percdia1 = 100 THEN 'V' ELSE 'P' END) AS tipo, ";
		$query .= "	(CASE WHEN condpagto.dia2 - condpagto.dia1 > 0 THEN condpagto.dia2 - condpagto.dia1 ELSE 0 END) AS prazo ";
		$query .= "FROM finalizadora ";
		$query .= "INNER JOIN condpagto ON (finalizadora.codcondpagto = condpagto.codcondpagto) ";
		$query .= "INNER JOIN especie ON (finalizadora.codespecie = especie.codespecie) ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			switch($row["especie"]){
				case "BL": $row["especie"] = "0";
					break; // Boleto bancario
				case "CC": $row["especie"] = "2";
					break; // Cartao de credito
				case "CD": $row["especie"] = "2";
					break; // Cartao de debito
				case "CH": $row["especie"] = "1";
					break; // Cheque
				case "DC": $row["especie"] = "2";
					break; // Debito em conta
				case "DH": $row["especie"] = "0";
					break; // Dinheiro
				case "DR": $row["especie"] = "0";
					break; // Deposito em conta corrente
				case "OT": $row["especie"] = "0";
					break; // Outro
				default : $row["especie"] = "0";
					break;
			}
			$linha = str_pad($row["codfinaliz"], 3, "0", STR_PAD_LEFT); // Codigo da finalizadora
			$linha .= str_pad(substr($row["descricao"], 0, 20), 20, " ", STR_PAD_RIGHT); // Descricao da finalizadora
			$linha .= $row["tipo"]; // Tipo (V = a vista; P = a prazo)
			$linha .= "S"; // Consulta cliente (S/N)
			$linha .= "0"; // Verifica limite de credito (0 = nao verifica; 1 = limite 1; 2 = limite 2)
			$linha .= "N"; // Atualiza limite de credito (S/N)
			$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor para indicar ponto de sangria
			$linha .= "S"; // Permite troco (S/N)
			$linha .= "N"; // Solicita quantidade de documentos (S/N)
			$linha .= "000"; // Numero de autenticacoes
			$linha .= "N"; // Imprime documento ou comprovante vinculado (S/N)
			$linha .= "S"; // Permite recebimento (S/N)
			$linha .= "S"; // Permite pagamento (S/N)
			$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor maximo de troco
			$linha .= "N"; // Gera conta corrente (S/N)
			$linha .= str_pad($row["prazo"], 3, "0", STR_PAD_LEFT); // Prazo entre as parcelas
			$linha .= "S"; // Permite encerrar venda no preco 1 (S/N)
			$linha .= "S"; // Permite encerrar venda no preco 2 (S/N)
			$linha .= "S"; // Permite encerrar venda no preco 3 (S/N)
			$linha .= "N"; // Solicita plano de pagamento (S/N)
			$linha .= "S"; // Permite troca (S/N)
			$linha .= "N"; // Imprime cheque (S/N)
			$linha .= "N"; // Sangria automatica (S/N)
			$linha .= "N"; // Cheque bom para (S/N)
			$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor minimo
			$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor maximo
			$linha .= "N"; // Imprime segunda via do documento (S/N)
			$linha .= str_repeat(" ", 15); // Texto livre 1
			$linha .= " "; // Tipo texto livre 1
			$linha .= "00"; // Tamanho texto livre 1
			$linha .= "N"; // Texto livre 1 obrigatorio (S/N)
			$linha .= str_repeat(" ", 15); // Texto livre 2
			$linha .= " "; // Tipo texto livre 2
			$linha .= "00"; // Tamanho texto livre 2
			$linha .= "N"; // Texto livre 2 obrigatorio (S/N)
			$linha .= str_repeat(" ", 15); // Texto livre 3
			$linha .= " "; // Tipo texto livre 3
			$linha .= "00"; // Tamanho texto livre 3
			$linha .= "N"; // Texto livre 3 obrigatorio (S/N)
			$linha .= str_repeat(" ", 15); // Texto livre 4
			$linha .= " "; // Tipo texto livre 4
			$linha .= "00"; // Tamanho texto livre 4
			$linha .= "N"; // Texto livre 4 obrigatorio (S/N)
			$linha .= "S"; // Gera contas a receber (S/N)
			$linha .= str_pad($row["codadminist"], 2, "0", STR_PAD_LEFT); // Codigo da administradora
			$linha .= $row["especie"]; // Especie da finalizadora
			$linha .= "00"; // Posicao da finalizadora na ECF
			$arr_linha[] = $linha;
		}
		//$this->pdvconfig->file_create("SYSPFZD.TXT",$arr_linha);
	}

	// Parametro "sincpdv" determina se deve usar o campo "sincpdv" ou o data e hora de alteracao
	// Caso o "sincpdv" seja "true", a variavel "arr_codproduto_sincpdv" da classe sera preenchida
	function exportar_produto($return = false, $sincpdv = false){
		setprogress(0, "Buscando produtos", true);

		$this->arr_codproduto_sincpdv = array();

		$arr_linha_produto = array();
		$arr_linha_produtoean = array();
		$arr_linha_estoque = array();
		$arr_linha_composicao = array();
		$arr_linha_syspimpfed = array();
		$arr_linha_syspimppro = array();
		$arr_linha_syspsec = array();
		$arr_linha_syspgrp = array();

		// Busca os produtos
		$query = "SELECT DISTINCT {$this->pdvconfig->sql_codproduto()}, {$this->pdvconfig->sql_descricao()}, classfiscal.tptribicms, classfiscal.aliqicms, ";
		$query .= "  classfiscal.aliqredicms, produtoestab.precoatc, produtoestab.precoatcof, produtoestab.precovrj, produtoestab.precovrjof, ";
		$query .= "  produtoestab.qtdatacado, produto.pesado, produto.pesounid, produto.precovariavel, unidade.sigla AS unidade, produtoestab.sldatual, ";
		$query .= "  COALESCE((SELECT composicao.explosaoauto FROM composicao WHERE composicao.codproduto = produto.codproduto LIMIT 1),'N') AS explosao, ";
		$query .= "  ncm.codigoncm, produto.multiplicado, produto.codetiqgondola, produto.natreceita, produto.codproduto AS codprodutooriginal, ";
		$query .= "  produto.coddepto, produto.codgrupo, produto.vasilhame, produto.codvasilhame, ";
		$query .= "  COALESCE(produto.cest, cest.cest) AS cest ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "INNER JOIN ( ";
		$query .= "  SELECT produtoestab.codproduto, produtoestab.codestabelec, classfiscal.* ";
		$query .= "  FROM produtoestab ";
		$query .= "  INNER JOIN estabelecimento ON (produtoestab.codestabelec = estabelecimento.codestabelec) ";
		$query .= "  INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "  LEFT JOIN estadotributo ON (estabelecimento.uf = estadotributo.uf AND estabelecimento.regimetributario = estadotributo.regimetributario AND produto.codproduto = estadotributo.codproduto) ";
		$query .= "  INNER JOIN classfiscal ON (COALESCE(estadotributo.codcfpdv,produto.codcfpdv) = classfiscal.codcf) ";
		$query .= ") AS classfiscal ON (produtoestab.codestabelec = classfiscal.codestabelec AND produto.codproduto = classfiscal.codproduto) ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "LEFT JOIN cest ON (ncm.idcest = cest.idcest) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produtoestab.disponivel = 'S' ";
		$query .= "	AND produto.gerapdv = 'S' ";
		if(param("ESTOQUE", "CARGAITEMCOMESTOQ", $this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}
		if(param("FRENTECAIXA", "PERMITICARGAZERADO", $this->con) != "S"){
			$query .= "	AND ".sql_tipopreco($this->pdvconfig->gettipopreco(), TRUE, NULL)." > 0 ";
		}
		if($sincpdv){
			$query .= "	AND produtoestab.sincpdv IN (0, 1) ";
		}else{
			if($this->pdvconfig->produto_parcial()){
				$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
			}
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$res = $this->con->query($query);
		$arr_pro = $res->fetchAll(2);
		$arr_codproduto = array();
		$arr_coddepto = array();
		$arr_codgrupo = array();
		foreach($arr_pro as $row_pro){
			$arr_codproduto[] = $row_pro["codprodutooriginal"];
			$arr_coddepto[] = $row_pro["coddepto"];
			$arr_codgrupo[] = $row_pro["codgrupo"];
		}

		if(count($arr_codproduto) === 0 && !$sincpdv){
			$this->error = "Nenhum produto encontrado.";
			return false;
		}

		$arr_coddepto = array_unique($arr_coddepto);
		$arr_codgrupo = array_unique($arr_codgrupo);

		if($this->pdvconfig->getfrentecaixa()->getversao() == "16.2.2"){
			// Gera o cadastro de departamento
			$res = $this->con->query("SELECT departamento.coddepto, departamento.nome FROM departamento WHERE departamento.coddepto IN (".implode(",", $arr_coddepto).") ");
			$arr_departamento = $res->fetchAll(2);
			foreach($arr_departamento AS $departamento){
				$linha = str_pad($departamento["coddepto"], 2, "0", STR_PAD_LEFT);
				$linha .= str_pad(substr($departamento["nome"], 0, 30), 30, " ", STR_PAD_LEFT);
				$arr_linha_syspsec[] = $linha;
			}

			// Gera o cadastro de grupo
			$res = $this->con->query("SELECT grupoprod.coddepto, grupoprod.codgrupo, grupoprod.descricao FROM grupoprod WHERE grupoprod.codgrupo IN (".implode(",", $arr_codgrupo).") ");
			$arr_grupoprod = $res->fetchAll(2);
			foreach($arr_grupoprod AS $grupo){
				$linha = str_pad($grupo["coddepto"], 2, "0", STR_PAD_LEFT);
				$linha .= str_pad($grupo["codgrupo"], 3, "0", STR_PAD_LEFT);
				$linha .= str_pad(substr($grupo["descricao"], 0, 30), 30, " ", STR_PAD_LEFT);
				$arr_linha_syspgrp[] = $linha;
			}
		}

		// Busca os codigos de barras
		if($this->frentecaixa->gettipocodproduto() !== "E"){
			$where = array();
			if($this->pdvconfig->getfrentecaixa()->getbalancaean() == "N"){
				$where[] = "produto.pesado = 'N'";
			}
			if(count($arr_codproduto) > 0){
				$where[] = "produto.codproduto IN (".implode(", ", $arr_codproduto).")";

				$res = $this->con->query("SELECT produtoean.codproduto, produtoean.codean FROM produtoean INNER JOIN produto ON (produtoean.codproduto = produto.codproduto)".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : ""));
				$arr_ean = $res->fetchAll(2);
			}else{
				if($sincpdv){
					$arr_ean = array();
				}else{
					$this->error = "Nenhum produto encontrado.";
					return false;
				}
			}
		}else{
			$arr_ean = array();
		}

		// Busca as informacoes de tributacoes
		foreach($arr_pro as $i => $row_pro){
			setprogress(($i + 1) / sizeof($arr_pro) * 100, "Exportando produtos: ".($i + 1)." de ".sizeof($arr_pro));

			// Acha a tributacao certa do produto
			$query = "SELECT * ";
			$query .= "FROM icmspdv ";
			$query .= "WHERE codestabelec = {$this->pdvconfig->getestabelecimento()->getcodestabelec()} ";
			$query .= "  AND tipoicms = '{$row_pro["tptribicms"]}' ";
			$query .= "  AND aliqicms = {$row_pro["aliqicms"]} ";
			$query .= "  AND redicms = {$row_pro["aliqredicms"]} ";
			$res = $this->con->query($query);
			$arr_icmspdv = $res->fetchAll(2);
			if(count($arr_icmspdv) === 0){
				$this->error = "Não encontrado informações tributarias para o PDV do produto <b>".$row_pro["codproduto"]."</b>:<br><br><b>Tipo de Tributação</b> = ".$row_pro["tptribicms"]."<br><b>Aliquota </b> = ".$row_pro["aliqicms"]."<br><b>Aliquota de Redução</b> = ".$row_pro["aliqredicms"]."<br><br><a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributação do PDV.";
				return false;
			}else{
				$row_icmspdv = array_shift($arr_icmspdv);
			}

			// Inclui o codigo do produto na lista de codigo exportados
			$this->arr_codproduto_sincpdv[] = $row_pro["codproduto"];

			// Cria linha do produto
			$linha_produto = str_pad($row_pro["codproduto"], 14, "0", STR_PAD_LEFT); // Codigo do produto
			$linha_produto .= str_pad(substr(removespecial($row_pro["descricaofiscal"]), 0, 45), 45, " ", STR_PAD_RIGHT); // Descricao completa
			$linha_produto .= str_pad(substr(removespecial($row_pro["descricao"]), 0, 20), 20, " ", STR_PAD_RIGHT); // Descricao reduzida
			if($this->pdvconfig->getfrentecaixa()->getversao() == "16.2.2"){
				$linha_produto .= str_pad($row_pro["coddepto"], 2, "0", STR_PAD_LEFT);
				$codgrupo = substr($row_pro["codgrupo"], -3);
			}else{
				$linha_produto .= "99"; // Codigo da secao (Padrao)
				$codgrupo = "";
			}

			$linha_produto .= "S"; // Produto paga comissao
			$linha_produto .= str_pad(substr($row_icmspdv["infpdv"], 0, 3), 3, " ", STR_PAD_RIGHT); // Tributacao (T07, T12, T18, F18, I00)
			$linha_produto .= ($row_pro["pesounid"] == "P" ? "S" : (($row_pro["multiplicado"] == "N") ? "U" : "N")); // Peso variavel
			$linha_produto .= $this->valor_numerico($row_pro["codetiqgondola"], 0, 2); // Codigo do local para impressao remota
			$linha_produto .= "00.00"; // Percentual de comissao 1
			$linha_produto .= "00.00"; // Percentual de comissao 2
			$linha_produto .= "00.00"; // Percentual de comissao 3
			$linha_produto .= "99.99"; // Desconto maximo
			$linha_produto .= str_pad(number_format($row_pro["precovrj"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de venda
			$linha_produto .= str_pad(number_format($row_pro["precovrjof"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de oferta
			$linha_produto .= "000"; // Dias de validade
			$linha_produto .= str_pad($row_pro["precovariavel"], 1, "N", STR_PAD_RIGHT); // Produto com preco variavel
			$linha_produto .= "S"; // Lista para frente de caixa
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Estoque minimo
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Estoque maximo
			$linha_produto .= str_repeat(" ", 4); // Codigo do fornecedor
			$linha_produto .= str_pad(number_format($row_pro["precoatc"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de venda 2
			$linha_produto .= str_pad(number_format($row_pro["precoatcof"], 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de oferta 2
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de venda 3
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco de oferta 3
			$linha_produto .= str_repeat("0", 1); // Tabela A (?)
			$linha_produto .= "P"; // Tipo de bonificacao (P = Preco; Q = Quantidade)
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Fator de bonificacao
			$linha_produto .= str_repeat(" ", 8); // Data de alteracao do produto (AAAAMMDD)
			$linha_produto .= "0"; // Quantidade de etiquetas para impressao
			$linha_produto .= str_pad(substr($row_pro["unidade"], 0, 3), 3, " ", STR_PAD_LEFT); // Unidade de venda (UN, CX, FD, KG)
			$linha_produto .= "N"; // Identificacao de produto alterado
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Custo do produto
			$linha_produto .= "N"; // Controla numero de serie
			$linha_produto .= "S"; // Controla estoque
			$linha_produto .= "S"; // Permite desconto
			$linha_produto .= "O"; // Especializacao (P = Prato; G = Guarnicao; O = Outros)
			$linha_produto .= ($row_pro["explosao"] == "S" ? "K" : "N"); // Composicao (S = Sim; N = Nao; K = Kit de Produto; C = Componente)
			$linha_produto .= ($row_pro["pesado"] == "S" ? "S" : "N"); // Envia para balanca
			$linha_produto .= "N"; // Controla validade
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 7, "0", STR_PAD_LEFT); // Margem de venda 1
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 7, "0", STR_PAD_LEFT); // Margem de venda 2
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 7, "0", STR_PAD_LEFT); // Margem de venda 3
			$linha_produto .= str_repeat(" ", 1); // Mix do Produto (?)
			$linha_produto .= str_repeat(" ", 8); // Data de inclusao (AAAAMMDD)
			$linha_produto .= str_repeat(" ", 8); // Data de quando o produto ficou fora de linha (AAAAMMDD)
			$linha_produto .= str_repeat(" ", 8); // Data do ultimo reajuste do preco 1 (AAAAMMDD)
			$linha_produto .= str_repeat(" ", 8); // Data do ultimo reajuste do preco 2 (AAAAMMDD)
			$linha_produto .= str_repeat(" ", 8); // Data do ultimo reajuste do preco 3 (AAAAMMDD)
			$linha_produto .= "N"; // Permite alterar a descricao do produto no frente de caixa
			$linha_produto .= str_repeat(" ", 20); // Endereco de estoque onde esta o produto
			$linha_produto .= str_pad(number_format($row_pro["qtdatacado"], 2, ".", ""), 9, "0", STR_PAD_LEFT); // Quantidade minima para vender com o preco 2
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 9, "0", STR_PAD_LEFT); // Quantidade minima para vender com o preco 3
			$linha_produto .= str_pad($codgrupo, 3, "0", STR_PAD_LEFT); // Codigo do grupo de produtos
			$linha_produto .= str_repeat(" ", 3); // Codigo do subgrupo de produtos
			$linha_produto .= str_pad(number_format(1, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Quantidade na embalagem de compra
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 9, "0", STR_PAD_LEFT); // Quantidade maxima para produtos em oferta
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 9, "0", STR_PAD_LEFT); // Peso bruto
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 9, "0", STR_PAD_LEFT); // Peso liquido
			$linha_produto .= str_repeat(" ", 3); // Unidade de referencia
			$linha_produto .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Medidade de referencia
			$linha_produto .= "0".str_pad(substr($row_pro["codigoncm"], 0, 2), 2, " ", STR_PAD_LEFT); // Codigo do genero
			$linha_produto .= $this->valor_texto(" ", 35); // Complemento de descricao do produto
			$linha_produto .= $this->valor_texto(" ", 20); // NCM
			$linha_produto .= str_repeat(" ", 3); // Unidade de compra
			$linha_produto .= str_repeat(" ", 3); // Reservado
			$linha_produto .= str_pad(substr($row_pro["natreceita"], 0, 3), 3, " ", STR_PAD_LEFT); // Natureza dos impostos federais
			$linha_produto .= $this->valor_texto(str_replace(".", "", $row_pro["codigoncm"]), 8); // NCM
			$linha_produto .= str_repeat(" ", 2); // Codigo excecao NCM
			$linha_produto .= str_pad(substr($row_pro["unidade"], 0, 3), 3, " ", STR_PAD_LEFT); // Unidade de referencia
			$linha_produto .= str_pad(number_format(1, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Medida de referencia
			if(strlen($this->pdvconfig->getfrentecaixa()->getversao()) > 0){
				$linha_produto .= str_pad(removeformat($row_pro["cest"]), 7, "0", STR_PAD_LEFT); // CEST
				$linha_produto .= "1"; // Finalidade (1: Comercializacao)
				$linha_produto .= "A"; // Arredontar ou Truncar
				$linha_produto .= "T"; // Producao de produtos (Proprio, Terceiros)
				$linha_produto .= str_pad($row_pro["vasilhame"], 1, " ", STR_PAD_LEFT); // Produto vasilhame
				if(strlen($row_pro["codvasilhame"]) > 0){
					$linha_produto .= str_pad($row_pro["codvasilhame"], 14, "0", STR_PAD_LEFT); // Codigo do vasilhame
				}else{
					$linha_produto .= str_pad(" ", 14, " ", STR_PAD_LEFT); // Codigo do vasilhame
				}
			}
			$arr_linha_produto[] = $linha_produto;

			// Estoque do produto
			$linha_estoque = str_pad($row_pro["codproduto"], 14, "0", STR_PAD_LEFT); // Codigo do produto
			$linha_estoque .= str_pad(number_format($row_pro["sldatual"], 2, ".", ""), 15, " ", STR_PAD_LEFT); // Saldo de estoque
			$linha_estoque .= date("dmY"); // Data da ultima entrada
			$linha_estoque .= date("dmY"); // Data da ultima saida
			$arr_linha_estoque[] = $linha_estoque;
		}

		foreach($arr_ean as $i => $row_ean){
			setprogress(($i + 1) / sizeof($arr_ean) * 100, "Exportando codigos de barras: ".($i + 1)." de ".sizeof($arr_ean));
			$linha_produtoean = str_pad($row_ean["codproduto"], 14, "0", STR_PAD_LEFT); // Codigo do produto
			$linha_produtoean .= str_pad($row_ean["codean"], 20, " ", STR_PAD_LEFT); // Ean do produto
			$linha_produtoean .= str_pad(number_format($row_ean["quantidade"], 2, ",", ""), 13, "0", STR_PAD_LEFT); // Ean do produto
			$linha_produtoean .= str_pad("", 46, " ", STR_PAD_LEFT); // espacamento
			$linha_produtoean .= "P"; // espacamento
			$arr_linha_produtoean[] = $linha_produtoean;
		}

		// Codigo do estabelecimento
		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();

		// Carregando arquivos da lei de olho no imposto
		$abc = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");

		$query = "SELECT DISTINCT piscofins.codpiscofins, '98' AS codcstent, ";
		$query .= " (CASE WHEN estabelecimento.regimetributario = '1' THEN '49' ELSE piscofins.codcst END) AS codcst, ";
		$query .= " (CASE WHEN estabelecimento.regimetributario = '1' THEN '0.0000' ELSE piscofins.aliqpis END) AS aliqpis ";
		$query .= "FROM piscofins ";
		$query .= "INNER JOIN produto ON (piscofins.codpiscofins = produto.codpiscofinssai) ";
		$query .= "INNER JOIN estabelecimento ON (estabelecimento.codestabelec = {$codestabelec}) ";
		$query .= "WHERE  codcst < '50' ";
		$query .= "ORDER BY piscofins.codpiscofins ";

		$res = $this->con->query($query);
		$arr_aliqpis = $res->fetchAll(2);

		$query = "SELECT DISTINCT piscofins.codpiscofins, '98' AS codcstent, ";
		$query .= " (CASE WHEN estabelecimento.regimetributario = '1' THEN '49' ELSE piscofins.codcst END) AS codcst, ";
		$query .= " (CASE WHEN estabelecimento.regimetributario = '1' THEN '0.0000' ELSE piscofins.aliqcofins END) AS aliqcofins ";
		$query .= "FROM piscofins ";
		$query .= "INNER JOIN produto ON (piscofins.codpiscofins = produto.codpiscofinssai) ";
		$query .= "INNER JOIN estabelecimento ON (estabelecimento.codestabelec = {$codestabelec}) ";
		$query .= "WHERE  codcst < '50' ";
		$query .= "ORDER BY piscofins.codpiscofins ";

		$res = $this->con->query($query);
		$arr_aliqcofins = $res->fetchAll(2);

		$contabc = 0;
		$arr_abc = array();

		foreach($arr_aliqpis as $aliqpis){
			$linha = $abc[$contabc]; // Código do imposto
			$linha .= $this->valor_texto("PIS", 20); // Descrição do imposto
			$linha .= "A"; // E-Entrada; S-Saída; A-Ambas
			$linha .= $this->valor_numerico($aliqpis["aliqpis"], 4, 8); // Alíquota percentual do imposto
			$linha .= $this->valor_numerico(0, 4, 8); // Percentual de retenção do imposto
			$linha .= $this->valor_texto(" ", 50); // Observação
			$linha .= "P"; // Valido P-pis ou C-Cofins
			$linha .= $this->valor_numerico($aliqpis["aliqpis"], 4, 8); // Alíquota percentual do imposto Saida
			$linha .= $this->valor_numerico($aliqpis["codcstent"], 0, 2); // CST Entrada do PIS\COFINS
			$linha .= $this->valor_numerico($aliqpis["codcst"], 0, 2); // 	CST Saida do PIS\COFINS
			$linha .= $this->valor_numerico(($contabc + 1), 0, 2); // Valores válidos: 01 a 99

			$arr_linha_syspimpfed[] = $linha;
			$arr_abc["PIS-".$aliqpis["codpiscofins"]] = array(
				"letra" => $abc[$contabc],
				"numero" => ($contabc + 1)
			);

			$contabc++;
		}

		foreach($arr_aliqcofins as $aliqcofins){
			$linha = $abc[$contabc]; // Código do imposto
			$linha .= $this->valor_texto("COFINS", 20); // Descrição do imposto
			$linha .= "A"; // E-Entrada; S-Saída; A-Ambas
			$linha .= $this->valor_numerico($aliqcofins["aliqcofins"], 4, 8); // Alíquota percentual do imposto
			$linha .= $this->valor_numerico(0, 4, 8); // Percentual de retenção do imposto
			$linha .= $this->valor_texto(" ", 50); // Observação
			$linha .= "C"; // Valido P-pis ou C-Cofins
			$linha .= $this->valor_numerico($aliqcofins["aliqcofins"], 4, 8); // Alíquota percentual do imposto Saida
			$linha .= $this->valor_numerico($aliqcofins["codcstent"], 0, 2); // CST Entrada do PIS\COFINS
			$linha .= $this->valor_numerico($aliqcofins["codcst"], 0, 2); // 	CST Saida do PIS\COFINS
			$linha .= $this->valor_numerico(($contabc + 1), 0, 2); // Valores válidos: 01 a 99

			$arr_linha_syspimpfed[] = $linha;
			$arr_abc["COFINS-".$aliqcofins["codpiscofins"]] = array(
				"letra" => $abc[$contabc],
				"numero" => ($contabc + 1)
			);

			$contabc++;
		}

		$query = "SELECT DISTINCT {$this->pdvconfig->sql_codproduto()}, piscofins.codpiscofins, piscofins.aliqcofins, piscofins.aliqpis, piscofins.codcst ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec();
		$query .= "	AND produtoestab.disponivel = 'S' ";
		if(count($arr_codproduto) > 0){
			$query .= " AND produtoestab.codproduto IN (".implode(",", $arr_codproduto).") ";
		}else{
			$query .= " AND FALSE ";
		}
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}
		$query .= "	AND ".sql_tipopreco($this->pdvconfig->gettipopreco(), TRUE, NULL)." > 0 ";
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$res = $this->con->query($query);
		$arr_aliq_produto_abc = $res->fetchAll(2);

		foreach($arr_aliq_produto_abc as $aliq_produto_abc){

			$abc_pis = $arr_abc["PIS-".$aliq_produto_abc["codpiscofins"]];
			$linha = $this->valor_numerico($aliq_produto_abc["codproduto"], 0, 14);
			$linha .= $abc_pis["letra"];
			$linha .= $this->valor_numerico($abc_pis["numero"], 0, 2);
			$arr_linha_syspimppro[] = $linha;

			$abc_cofins = $arr_abc["COFINS-".$aliq_produto_abc["codpiscofins"]];
			$linha = $this->valor_numerico($aliq_produto_abc["codproduto"], 0, 14);
			$linha .= $abc_cofins["letra"];
			$linha .= $this->valor_numerico($abc_cofins["numero"], 0, 2);
			$arr_linha_syspimppro[] = $linha;
		}

		// Carrega as composicoes
		$query = "SELECT DISTINCT composicao.codcomposicao, {$this->pdvconfig->sql_codproduto()}, produtoestab.precovrj ";
		$query .= "FROM composicao ";
		$query .= "INNER JOIN produto ON (composicao.codproduto = produto.codproduto) ";
		$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "INNER JOIN produtoestab ON (composicao.codproduto = produtoestab.codproduto AND produtoestab.codestabelec = {$this->pdvconfig->getestabelecimento()->getcodestabelec()}) ";
		$query .= "WHERE composicao.tipo IN ('V','A') ";
		$query .= "	AND composicao.explosaoauto = 'S' ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Composicoes ".($i + 1)." de ".count($arr));
			$linha = "C"; // Texto fixo "C"
			$linha .= " "; // Reservado
			$linha .= str_pad($row["codproduto"], 14, "0", STR_PAD_LEFT); // Codigo do produto pai
			$linha .= str_pad(number_format(0, 3, ".", ""), 9, "0", STR_PAD_LEFT); // Quantidade do produto composto
			$linha .= "V"; // Indica onde deve ocorrer a baixa de estoque (P - no momento da produto; V - no momento da venda)
			$linha .= "1"; // Nivel de custo (?)
			$linha .= "K"; // Tipo de composicao (N - normal; K - kit)
			$linha .= "S"; // Trabalha com preco fixo (S - Sim, N - Não)
			$arr_linha_composicao[] = $linha;


			$query = "SELECT DISTINCT {$this->pdvconfig->sql_codproduto()}, itcomposicao.quantidade, (CASE WHEN produtoestab.precovrjof > 0 THEN produtoestab.precovrjof ELSE produtoestab.precovrj END) AS precovrj ";
			$query .= "FROM itcomposicao ";
			$query .= "INNER JOIN produto ON (itcomposicao.codproduto = produto.codproduto) ";
			$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
			$query .= "INNER JOIN produtoestab ON (itcomposicao.codproduto = produtoestab.codproduto AND produtoestab.codestabelec = {$this->pdvconfig->getestabelecimento()->getcodestabelec()}) ";
			$query .= "WHERE itcomposicao.codcomposicao = {$row["codcomposicao"]} ";
			$res = $this->con->query($query);
			$arr2 = $res->fetchAll(2);
			$total_precovrj = 0;
			foreach($arr2 as $row2){
				$total_precovrj += $row2["precovrj"] * $row2["quantidade"];
			}
			$totalfilho = 0;

			$arr2 = array_sort($arr2, "quantidade", SORT_DESC);
			$cont = 0;
			foreach($arr2 as $row2){
				$cont++;
				if($total_precovrj == 0){
					continue;
				}
				$fator = ($row["precovrj"] / $total_precovrj);
				$precofilho = round($row2["precovrj"] * $fator, 2);
				$totalfilho += $precofilho * $row2["quantidade"];

				if(count($arr2) == $cont && $row2["quantidade"] == 1){
					$dif = round($row["precovrj"] - $totalfilho, 2);
					$precofilho += $dif;
				}

				$linha = "I"; // Texto fixo "I"
				$linha .= str_pad($row2["codproduto"], 14, "0", STR_PAD_LEFT); // Codigo do produto pai
				$linha .= str_pad(number_format($row2["quantidade"], 3, ".", ""), 9, "0", STR_PAD_LEFT); // Quantidade do produto filho da composicao
				$linha .= str_pad(number_format(($row2["precovrj"] / $total_precovrj), 5, ".", ""), 7, "0", STR_PAD_LEFT); // Fator de participacao do produto filho em relacao ao preco final do produto pai
				$linha .= str_pad(number_format(0, 5, ".", ""), 7, "0", STR_PAD_LEFT); // Fator 2
				$linha .= str_pad(number_format(0, 5, ".", ""), 7, "0", STR_PAD_LEFT); // Fator 3
				$linha .= str_pad(number_format($precofilho, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco fixo 1
				$linha .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco fixo 2
				$linha .= str_pad(number_format(0, 2, ".", ""), 13, "0", STR_PAD_LEFT); // Preco fixo 3

				$arr_linha_composicao[] = $linha;
			}
		}

		$this->pdvconfig->atualizar_precopdv($arr_codproduto);

		if($return){
			return array(
				$this->pdvconfig->file_create("SYSPPRO.TXT", $arr_linha_produto, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPEST.TXT", $arr_linha_estoque, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPAUX.TXT", $arr_linha_produtoean, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPCMP.TXT", $arr_linha_composicao, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPIMPFED.TXT", $arr_linha_syspimpfed, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPIMPPRO.TXT", $arr_linha_syspimppro, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPSEC.TXT", $arr_linha_syspsec, "w+", TRUE),
				$this->pdvconfig->file_create("SYSPGRP.TXT", $arr_linha_syspgrp, "w+", TRUE)
			);
		}else{
			$this->pdvconfig->file_create("SYSPPRO.TXT", $arr_linha_produto);
			$this->pdvconfig->file_create("SYSPEST.TXT", $arr_linha_estoque);
			$this->pdvconfig->file_create("SYSPAUX.TXT", $arr_linha_produtoean);
			$this->pdvconfig->file_create("SYSPCMP.TXT", $arr_linha_composicao);
			$this->pdvconfig->file_create("SYSPIMPFED.TXT", $arr_linha_syspimpfed);
			$this->pdvconfig->file_create("SYSPIMPPRO.TXT", $arr_linha_syspimppro);
			$this->pdvconfig->file_create("SYSPSEC.TXT", $arr_linha_syspsec);
			$this->pdvconfig->file_create("SYSPGRP.TXT", $arr_linha_syspgrp);
		}

		return true;
	}

	function exportar_vendedor(){
		setprogress(0, "Buscando vendedores", TRUE);
		$arr_linha = array();
		$query = "SELECT * FROM funcionario ORDER BY codfunc";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		if(is_array($arr)){
			// Percorre todos os funcionario
			foreach($arr as $i => $row){
				setprogress(($i + 1) / sizeof($arr) * 100, "Exportando vendedores: ".($i + 1)." de ".sizeof($arr));
				$linha = ""; // Linha do arquivo de funcionario
				$linha .= str_pad($row["codfunc"], 4, "0", STR_PAD_LEFT); // Codigo interno do funcionario (com 4 digitos)
				$linha .= str_pad(substr($row["nome"], 0, 15), 15, " ", STR_PAD_RIGHT); // Apelido do funcionario
				$linha .= str_pad("", 5, " ", STR_PAD_RIGHT); // Reservado
				$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Cargo
				$linha .= str_pad(number_format($row["comissao"], 2, ".", ""), 15, "0", STR_PAD_LEFT); // Percentual de comissao 1
				$linha .= str_pad("", 15, " ", STR_PAD_LEFT); // Percentual de comissao 2
				$linha .= str_pad("", 15, " ", STR_PAD_LEFT); // Percentual de comissao 3
				$linha .= str_pad(substr($row["nome"], 0, 30), 30, " ", STR_PAD_RIGHT); // Apelido do funcionario
				$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Nivel de acesso
				$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Senha de acesso
				$linha .= str_pad($row["codfunc"], 6, "0", STR_PAD_LEFT); // Codigo interno do funcionario (com 6 digitos)
				$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Limite de desconto sobre saldo
				$arr_linha[] = $linha;
			}
		}
		//$this->pdvconfig->file_create("SYSPFUN.TXT",$arr_linha);
	}

	function importar_maparesumo($nome_arquivo, $arquivo_obrigatorio = FALSE){
		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();
		$paramfiscal = objectbytable("paramfiscal", $codestabelec);
		if(!file_exists($nome_arquivo)){
			if($arquivo_obrigatorio){
				$_SESSION["ERROR"] = "Arquivo do mapa resumo do ECF n&atilde;o foi encontrado.";
				return FALSE;
			}else{
				return TRUE;
			}
		}

		$res = $this->con->query("SELECT DISTINCT dtmovto FROM maparesumo WHERE codestabelec = ".$codestabelec);
		$arr = $res->fetchAll(2);
		$arr_dtmovto = array();
		foreach($arr as $row){
			$arr_dtmovto[] = convert_date($row["dtmovto"], "Y-m-d", "d/m/Y");
		}

		$arr_ecf = array();
		$ecf = objectbytable("ecf", NULL, $this->con);
		$arr_ecf = object_array($ecf);

		$this->con->start_transaction();

		/*
		  // Tratamento para remover linhas repetidas
		  $arr_linha_aux = array_reverse(read_file($nome_arquivo));
		  $arr_linha = array();
		  $arr_caixadatareduc = array();
		  foreach($arr_linha_aux as $linha){
		  $caixadatareduc = substr($linha,6,3).substr($linha,30,8);
		  if(!in_array($caixadatareduc,$arr_caixadatareduc)){
		  $arr_caixadatareduc[] = $caixadatareduc;
		  $arr_linha[] = $linha;
		  }
		  }
		  $arr_linha = array_reverse($arr_linha);
		 */
		$arr_linha = read_file($nome_arquivo, TRUE, "\n");

		$res = $this->con->query("SELECT codestabelec, caixa, dtmovto, codecf FROM maparesumo WHERE codestabelec = ".$codestabelec);
		$arr_maparesumo = $res->fetchAll(2);

		foreach($arr_linha as $i => $linha){
			setprogress(($i + 1) / sizeof($arr_linha) * 100, "Importando mapa resumo: ".($i + 1)." de ".sizeof($arr_linha));
			$registro = substr($linha, 0, 2); // Tipo de registro (03 = mapa resumo)
			//$codestabelec = substr($linha,2,4); // Codigo do estabelecimento [NAO TRATAR O ESTABELECIMENTO]
			if($registro == "03"){
				$caixa = substr($linha, 6, 3); // Numero do caixa
				$sequencial = substr($linha, 9, 6); // Sequencial interno do sistema
				$numeroecf = substr($linha, 15, 3); // Numero do ECF
				$contordem = substr($linha, 18, 6); // Contador de ordem de operacao
				$contreduc = substr($linha, 24, 6); // Contador de reducoes Z
				$datareduc = substr($linha, 30, 8); // Data da reducao Z
				$totalbruto = substr($linha, 38, 12); // Valor total da venda bruta
				$totalcanc = substr($linha, 50, 12); // Valor total de cancelamentos
				$totaldescto = substr($linha, 62, 12); // Valor total de descontos
				$totalliquido = substr($linha, 74, 12); // Valor total da venda liquida
				$totalbaseicmssubst = substr($linha, 86, 12); // Valor total da base de calculo de ST
				$totalbaseisento = substr($linha, 98, 12); // Valor total da base de calculo nao tributada
				$seqecf = substr($linha, 284, 12); // Sequencial do equipamento ECF
				$hremissao = substr($linha, 290, 4); // Hora da emissao da reducao
				$gtinicial = substr($linha, 294, 18); // GT inicial
				$gtfinal = substr($linha, 312, 18); // GT final
//				$cupominicial = substr($linha,330,6); // Cupom inicial
				$cupominicial = substr($linha, 502, 6); // Cupom inicial
				if($cupominicial == 0 || strlen($cupominicial) <= 0){
					$cupominicial = substr($linha, 330, 6); // Cupom inicial
				}
				$cupomfinal = substr($linha, 336, 6); // Cupom final
				$totalisento = substr($linha, 342, 12); // Valor total de isentos
				$totalservico = substr($linha, 354, 12); // Valor total de servicos
				$serieecf = substr($linha, 366, 20); // Numero de serie do equipamento
				$seqicinial = substr($linha, 502, 6); // Sequencial inicial do dia
				$contreinicio = substr($linha, 508, 6); // Contador de reinicio de operacao

				$dtmovto = convert_date($datareduc, "dmY", "Y-m-d");
				$datareduc = convert_date($datareduc, "dmY", "d/m/Y");

				$query = "SELECT COUNT(cupom) FROM cupom where dtmovto = '$dtmovto' AND status = 'C' ";
				$res = $this->con->query($query);
				$cuponscancelados = $res->fetchColumn();

				$query = "SELECT COUNT(codproduto) FROM itcupom where idcupom IN (SELECT idcupom FROM cupom where dtmovto = '$dtmovto') AND status = 'C' ";
				$res = $this->con->query($query);
				$itenscancelados = $res->fetchColumn();

				$arr_tributacao = array(
					array(
						"tptribicms" => "F",
						"aliqicms" => 0,
						"totalliquido" => $totalbaseicmssubst,
						"totalicms" => 0
					),
					array(
						"tptribicms" => "I",
						"aliqicms" => 0,
						"totalliquido" => $totalisento,
						"totalicms" => 0
					),
					array(
						"tptribicms" => "N",
						"aliqicms" => 0,
						"totalliquido" => $totalbaseisento,
						"totalicms" => 0
					)
				);
				for($i = 0; $i < 10; $i++){
					if($i < 6){
						$pos = 110 + 29 * $i;
					}else{
						$pos = 386 + 29 * ($i - 6);
					}
					$aliqicms = substr($linha, $pos, 5); // Aliquota de ICMS
					$totalbaseicms = substr($linha, $pos + 5, 12); // Total de base de calculo de ICMS
					$totalicms = substr($linha, $pos + 17, 12); // Total de ICMS
					if($totalbaseicms > 0){
						$arr_tributacao[] = array(
							"tptribicms" => "T",
							"aliqicms" => $aliqicms,
							"totalliquido" => $totalbaseicms,
							"totalicms" => $totalicms
						);
					}
				}

				$serieecf = trim($serieecf);
				if(strlen($serieecf) == 0){
					continue;
				}

				$achou_ecf = FALSE;
				foreach($arr_ecf as $ecf){
					if($ecf->getnumfabricacao() == $serieecf){
						$achou_ecf = TRUE;
						break;
					}
				}
				if($ecf->getequipamentofiscal() == "ECF" && $ecf->getgeramapa() == "N"){
					continue;
				}

				if(!$achou_ecf){
					$ecf = objectbytable("ecf", NULL, $this->con);
					$ecf->setcodestabelec($codestabelec);
					$ecf->setnumfabricacao($serieecf);
					$ecf->setcaixa($caixa);
					$ecf->setequipamentofiscal("ECF");
					if(!$ecf->save()){
						$this->con->rollback();
						return FALSE;
					}
					$arr_ecf[] = $ecf;
				}

				// Se for SAT nao deve puxar o mapa resumo
				if($ecf->getequipamentofiscal() != "ECF"){
					continue;
				}

				// Verifica se ja existe o mapa resumo
				$count_maparesumo = $this->con->query("SELECT * FROM maparesumo WHERE codestabelec = ".$codestabelec." AND caixa = ".$caixa." AND dtmovto = '".convert_date($datareduc, "d/m/Y", "Y-m-d")."' AND codecf = ".$ecf->getcodecf());
				if($count_maparesumo->rowCount() > 0){
					continue;
				}

				$maparesumo = objectbytable("maparesumo", NULL, $this->con);
				$maparesumo->setcodestabelec($codestabelec);
				$maparesumo->setcaixa($caixa);
				$maparesumo->setnumeroecf($numeroecf);
				$maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
				$maparesumo->setdtmovto($datareduc);
				$maparesumo->setcodecf($ecf->getcodecf());
				if($cupominicial == 0){
					$sql_max = "SELECT MAX(operacaofim) as operacao FROM maparesumo WHERE caixa=".$caixa;
					$sql_max .= " AND codestabelec = ".$codestabelec;
					$qr = $this->con->query($sql_max);
					$arr_max = $qr->fetchAll(2);
					foreach($arr_max as $max){
						$cupominicial = $max["operacao"] + 1;
					}
				}
				$maparesumo->setoperacaoini($cupominicial);
				$maparesumo->setoperacaofim($contordem);
				$maparesumo->setgtinicial($gtinicial);
				$maparesumo->setgtfinal($gtfinal);
				$maparesumo->settotalbruto($totalbruto);
				$maparesumo->settotalcupomcancelado($totalcanc);
				$maparesumo->setcuponscancelados($cuponscancelados);
				$maparesumo->setitenscancelados($itenscancelados);
				$maparesumo->settotaldescontocupom($totaldescto);
				$maparesumo->settotalliquido($totalbruto - $totalcanc - $totaldescto);
				$maparesumo->setnumseriefabecf($serieecf);
				$maparesumo->setreiniciofim($contreinicio);
				$maparesumo->setnumeroreducoes($contreduc);

				setprogress(($i + 1) / sizeof($arr_linha) * 100, "Importando mapa resumo: ".($i + 1)." de ".sizeof($arr_linha));

				$arr_maparesumo[] = array(
					"codestabelec" => $maparesumo->getcodestabelec(),
					"caixa" => $maparesumo->getcaixa(),
					"dtmovto" => $maparesumo->getdtmovto(),
					"codecf" => $maparesumo->getcodecf()
				);


				if(!$maparesumo->save()){
					$this->con->rollback();
					return FALSE;
				}
				foreach($arr_tributacao as $tributacao){
					$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
					$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
					$maparesumoimposto->settptribicms($tributacao["tptribicms"]);
					$maparesumoimposto->setaliqicms($tributacao["aliqicms"]);
					$maparesumoimposto->settotalliquido($tributacao["totalliquido"]);
					$maparesumoimposto->settotalicms($tributacao["totalicms"]);
					if(!$maparesumoimposto->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}
		$paramfiscal->setnummaparesumo($paramfiscal->getnummaparesumo() + 1);
		if(!$paramfiscal->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	function arquivo_venda($file_name){
		/*
		 * Murilo S Feres - 03/01/2018
		 *
		 * Tratamento criado para tentar identificar a diferenca na leitura online.
		 *
		 * Nesse caso, cada arquivo lido sera criado uma copia no diretorio temp
		 * do WebCac, assim podemos comparar os arquivos originais com as copias
		 * feitas no momento da leitura.
		 */
		/*
		if(file_exists($file_name) && in_array(substr(basename($file_name), 0, 2), array("LG", "TP"))){
			$dirname = __DIR__."/../temp/log-syspdv/";
			if(!is_dir($dirname)){
				mkdir($dirname);
				chmod($dirname, 0777);
			}
			file_put_contents($dirname."/".basename($file_name), file_get_contents($file_name));
		}
		*/

		$this->limpar_dados();
		$linhas = array();
		if(file_exists($file_name)){
			$file = fopen($file_name, "r");
			while(!feof($file)){
				$linhas[] = fgets($file, 4096);
			}
			fclose($file);
		}
		$totalvenda = 0;
		$tem_item = "N";
		$arr_chavecfe = array();
		$param_pontovenda_syspdvvasilhame = param("PONTOVENDA", "SYSPDVVASILHME", $this->con);
		$param_frentecaixa_syspdvrecebimento = param("FRENTECAIXA", "SYSPDVRECEBIMENTO", $this->con);
		$recebimento = false;
		$arr_cupom_recebimento = array();
		foreach($linhas as $i => $linha){
			setprogress($i / sizeof($linhas) * 100, "Lendo arquivo de vendas: ".($i + 1)." de ".sizeof($linhas));
			$registro = substr($linha, 0, 2);
			$codestabelec = substr($linha, 2, 4); // Codigo do estabelecimento
			$caixa = substr($linha, 6, 3); // Numero do caixa
			$codoperador = substr($linha, 9, 4); // Codigo do operador
			$cupom = substr($linha, 13, 6); // Sequencial interno do sistema
			$numfabricacao = "";
			$chavecfe = "";

			if($tem_item == "S" && (!in_array($registro, array("01", "02", "04", "09")))){
				$tem_item = "N";
			}

			if($param_pontovenda_syspdvvasilhame == "N" && in_array($registro, array("23", "24", "25"))){
				continue;
			}

			switch($registro){
				// I T E M   V E N D I D O
				case "01":
					$tem_item = "S";
					$codsecao = substr($linha, 19, 2); // Codigo da secao
					$tributacao = substr($linha, 21, 3); // Tributacao (formato: A00; exemplo: F18,T07)
					$aliqicms = substr($linha, 24, 5); // Aliquota de ICMS (formato: 99.99)
					$aliqredicms = substr($linha, 29, 6); // Aliquota de reducao na base do ICMS (formato: 99.999)
					$data = substr($linha, 35, 8); // Data da venda (formato: DDMMAAAA)
					$codproduto = substr($linha, 43, 14); // Codigo do produto
					$quantidade = substr($linha, 57, 9); // Quantidade de venda (formato: 99999.999)
					$precounit = substr($linha, 66, 12); // Valor unitario do produto (formato: 999999999.99)
					$desconto = substr($linha, 78, 12); // Valor do desconto (formato: 999999999.99)
					$acrescimo = substr($linha, 90, 12); // Valor do acrescimo (formato: 999999999.99)
					$codfunc = substr($linha, 102, 4); // Codigo do funcionario (vendedor)
					$comissao = substr($linha, 106, 5); // Percentual de comissao do vendedor no final da venda (formato: 99.99)
					$codfuncpre = substr($linha, 111, 4); // Codigo do funcionario na pre-venda
					$comissaopro = substr($linha, 115, 5); // Percentual de comissao do vendedor na pre-venda (formato: 99.99)
					$codfuncpro = substr($linha, 120, 4); // Codigo do funcionario na producao
					$comissaopro = substr($linha, 124, 5); // Percentual de comissao do vendedor na producao (formato: 99.99)
					$tipopreco = substr($linha, 129, 1); // Tipo de preco praticado (1, 2 ou 3)
					$totalitem = substr($linha, 130, 12); // Valor total do item vendido (999999999.99)
					$seqecf = substr($linha, 142, 6); // Sequencial do equipamento fiscal (ECF)
					$numecf = substr($linha, 148, 3); // Numero do equipamento fiscal (ECF)
					$hora = substr($linha, 151, 4); // Hora da transacao de venda (formato: HHMM)
					$preco = substr($linha, 155, 12); // Preco de venda do produto (formato: 999999999.99)
					$bonificado = substr($linha, 167, 1); // Bonificacao (S ou N)
					$fatorbonif = substr($linha, 168, 9); // Fator de bonificacao (formato: 9999.9999)
					$redcomissao = substr($linha, 177, 9); // Percentual de reducao na comissao (formato: 9999.9999)
					$codimpressora = substr($linha, 186, 2); // Codigo da impressora
					$imprnfbascf = substr($linha, 188, 1); // Impressora nota fiscal baseada no cupom fiscal (S ou N)
					$codfunccanc = substr($linha, 189, 4); // Codigo do funcionario que autorizou o cancelamento
					$tiporegistro = substr($linha, 193, 1); // Venda (1) ou cancelado (2)
					$custo = substr($linha, 194, 12); // Preco de custo do produto
					$serie = substr($linha, 206, 20); // Numero de serie do produto
					$cartaofid = substr($linha, 226, 19); // Numero do cartao fidelidade
					$serieecf = trim(substr($linha, 245, 20)); // Numero de serie do equipamento
					$numprevenda = substr($linha, 265, 9); // Numero da pre-venda
					$cpfcnpj = trim(substr($linha, 274, 14)); // CPF/CNPJ do consumidor
					$chavecfe = trim(substr($linha, 335, 50)); // Chave do cupom fiscal eletronico
					// Tratamento para nao importar o pai de composicao (explosao automatica)


					if(strlen(trim($chavecfe,0)) > 0){
						$mod = substr($chavecfe, 20,2);
						if($mod == 65){
							$numfabricacao = "";
						}else{
							$numfabricacao = ltrim(substr($chavecfe, 22, 9), "0");
						}
					}elseif(strlen(trim($serieecf,0)) > 0){
						$numfabricacao = trim($serieecf);
					}else{
						$numfabricacao = "";
					}

					if(value_numeric($totalitem) === 0){
						continue;
					}

					$codproduto = $this->codproduto($codproduto);

					$data = substr($data, 0, 2)."/".substr($data, 2, 2)."/".substr($data, 4);
					$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":00";


					if(!valid_cnpj($cpfcnpj) && strlen(trim($cpfcnpj)) > 11){
						$cpfcnpj = substr($cpfcnpj, -11);
					}

					$tptribicms = substr($tributacao, 0, 1);
					if($tptribicms === "R"){
						$tptribicms = "T";
					}

					if(strlen($chavecfe) > 0){
						$seqecf = substr($chavecfe, 31, 6);
						$arr_chavecfe[$cupom][$caixa] = $seqecf;
					}

					$pdvitem = new PdvItem();
					$pdvitem->setstatus("A");
					$pdvitem->setcodproduto($codproduto);
					$pdvitem->setquantidade($quantidade);
					$pdvitem->setpreco($precounit);
					$pdvitem->setdesconto($desconto);
					$pdvitem->setacrescimo($acrescimo);
					$pdvitem->settotal($totalitem);
					$pdvitem->settptribicms($tptribicms);
					$pdvitem->setaliqicms($aliqicms);

					$found = FALSE;
					foreach(array_reverse($this->pdvvenda) as $pdvvenda){
						if($pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $caixa){
							$pdvvenda->pdvitem[] = $pdvitem;
							$found = TRUE;
							break;
						}
					}
					if(!$found){
						$pdvvenda = new PdvVenda();
						$pdvvenda->setcupom($cupom);
						$pdvvenda->setcaixa($caixa);
						$pdvvenda->setnumeroecf($numecf);
						$pdvvenda->setdata($data);
						$pdvvenda->sethora($hora);
						$pdvvenda->setcpfcnpj($cpfcnpj);
						$pdvvenda->setcodfunc($codfunc);
						$pdvvenda->setcodorcamento($numprevenda);
						$pdvvenda->setseqecf($seqecf);
						$pdvvenda->setchavecfe($chavecfe);
						$pdvvenda->setoperador($codoperador);
						$pdvvenda->setnumfabricacao($numfabricacao);
						$pdvvenda->setarquivo($file_name);
						$pdvvenda->pdvitem[] = $pdvitem;
						$this->pdvvenda[] = $pdvvenda;
					}
					break;
				// F I N A L I Z A C A O
				case "02":
					$data = substr($linha, 19, 8); // Data da venda (formato: DDMMAAAA)
					$codfinaliz = substr($linha, 27, 3); // Codigo da finalizadora
					$tipofinaliz = substr($linha, 30, 1); // Tipo de finalizacao (V = Vista; P = Prazo)
					$valortotal = str_replace(",", ".", substr($linha, 31, 12)); // Valor da finalizacao (formato: 999999999.99)
					$codcliente = trim(substr($linha, 43, 15)); // Codigo do cliente
					$tipopreco = substr($linha, 58, 1); // Tipo de preco praticado (1, 2 ou 3)
					$seqecf = substr($linha, 59, 6); // Sequencial do equipamento fiscal (ECF)
					$datavencto = substr($linha, 65, 8); // Data de vencimento (formato: DDMMAAAA)
					$textolivre1 = substr($linha, 73, 15); // Texto livre
					$textolivre2 = substr($linha, 88, 15); // Texto livre
					$textolivre3 = substr($linha, 103, 15); // Texto livre
					$textolivre4 = substr($linha, 118, 15); // Texto livre
					$numecf = substr($linha, 133, 3); // Numero do equipamento fiscal (ECF)
					$hora = substr($linha, 136, 4); // Hora da transacao da venda (formato: ?)
					$numcartaofid = substr($linha, 140, 19); // Numero do cartao fidelidade
					$codagente = substr($linha, 160, 4); // Codigo do agente
					$desctomoeda = str_replace(",", ".", substr($linha, 163, 12)); // Desconto moeda
					$solicplano = substr($linha, 175, 1); // Solicitou plano (S ou N)
					$especfinaliz = substr($linha, 176, 3); // Especializacao da finalizadora
					$reservado1 = substr($linha, 179, 3); // Reservado
					$emitecheque = substr($linha, 182, 15); // Emite cheque
					$valorrecebido = str_replace(",", ".", substr($linha, 197, 12)); // Valor recebido (formato: 999999999.99)
					$valortroco = str_replace(",", ".", substr($linha, 209, 12)); // Valor do troco (formato: 999999999.99)
					$tipotroco = substr($linha, 221, 1); // Tipo do troco (C = Contra-vale; T = Troco)
					$cmc7 = substr($linha, 223, 1); // Ler CMC7 (S ou N)
					$numcartaopror = substr($linha, 223, 19); // Numero do cartoa proprio
					$codplanopagto = substr($linha, 242, 2); // Codigo do plano de pagamento
					$numvalecompra = substr($linha, 244, 10); // Numero do vale-compra
					$codfuncauto = substr($linha, 254, 4); // Codigo do funcionario que autorizou
					$cpfcnpj = substr($linha, 300, 14);

					$totalvenda += $valortotal;

					$data = substr($data, 0, 2)."/".substr($data, 2, 2)."/".substr($data, 4);
					$datavencto = substr($datavencto, 0, 2)."/".substr($datavencto, 2, 2)."/".substr($datavencto, 4);
					$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":00";

					if(!valid_cnpj($cpfcnpj) && strlen(trim($cpfcnpj)) > 11){
						$cpfcnpj = substr($cpfcnpj, -11);
					}

					if(in_array($caixa."|".$cupom."|".$data, $arr_cupom_recebimento)){
						$recebimento = true;
					}

					if(!$recebimento && $tem_item == "N"){
						continue;
					}

					$textolivre1 = trim($textolivre1);
					$textolivre2 = trim($textolivre2);
					$textolivre3 = trim($textolivre3);
					$textolivre4 = trim($textolivre4);

					if(count($arr_chavecfe) > 0 && strlen($arr_chavecfe[$cupom][$caixa]) > 0){
						$seqecf = $arr_chavecfe[$cupom][$caixa];
					}

					$finalizador = new PdvFinalizador();
					$finalizador->setstatus("A");
					$finalizador->setcupom($cupom);
					$finalizador->setcaixa($caixa);
					$finalizador->setcodcliente($codcliente);
					$finalizador->setdata($data);
					$finalizador->sethora($hora);
					$finalizador->setcodfinaliz($codfinaliz);
					$finalizador->setcpfcliente($cpfcnpj);
					$finalizador->setvalortotal($valortotal - $desctomoeda);
					$finalizador->setcodbanco($textolivre1);
					$finalizador->setnumagenciacheq($textolivre2);
					$finalizador->setnumcheque($textolivre3);
					if(strlen($textolivre4) == 10){
						$finalizador->setdatavencto($textolivre4);
					}else{
						$finalizador->setdatavencto($datavencto);
					}

					if($recebimento){
						$recebepdv = $this->pdvrecebepdv[$caixa."|".$cupom."|".$data];
						$recebepdv->setcodfinaliz($codfinaliz);
						$this->pdvrecebepdv[$caixa."|".$cupom."|".$data] = $recebepdv;

						$finalizador->setrecebepdv(true);
						$this->pdvfinalizador[] = $finalizador;
						$recebimento = false;
						continue;
					}

					// Preenche o CPF na venda e o codigo do vendedor na finalizadora
					$achou = FALSE;
					foreach($this->pdvvenda as $pdvvenda){
						if($pdvvenda->getdata() == $finalizador->getdata() && $pdvvenda->getcaixa() == $finalizador->getcaixa() && $pdvvenda->getcupom() == $finalizador->getcupom()){
							$achou = TRUE;
							$finalizador->setcodfunc($pdvvenda->getcodfunc());
							if(strlen($pdvvenda->getcpfcnpj()) == 0 && strlen($finalizador->getcpfcliente()) > 0){
								$pdvvenda->setcpfcnpj($finalizador->getcpfcliente());
							}elseif(strlen($pdvvenda->getcodcliente()) == 0 && strlen($finalizador->getcodcliente()) > 0){
								$pdvvenda->setcodcliente($finalizador->getcodcliente());
							}elseif(strlen($pdvvenda->getcpfcnpj()) > 0 && strlen($finalizador->getcpfcliente()) == 0){
								$finalizador->setcpfcliente($pdvvenda->getcpfcnpj());
							}
						}
					}

					if($achou){
						$this->pdvfinalizador[] = $finalizador;
					}

					break;
				// I T E M   D E   T R O C A
				case "04":
					$data = substr($linha, 35, 8); // Data da venda
					$codproduto = substr($linha, 43, 14); // Codigo do produto
					$quantidade = substr($linha, 57, 9); // Quantidade de venda (formato: 99999.999)
					$precounit = substr($linha, 66, 12); // Valor unitario do produto (formato: 999999999.99)

					$codproduto = $this->codproduto($codproduto);

					$f = FALSE;
					foreach(array_reverse($this->pdvvenda) as $pdvvenda){
						if($pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $caixa){
							$f = TRUE;
							break;
						}
					}
					if(!$f){
						$pdvvenda = new PdvVenda;
						$pdvvenda->setcupom($cupom);
						$pdvvenda->setcaixa($caixa);
						$pdvvenda->setdata($data);
						$pdvvenda->setarquivo($file_name);
						$this->pdvvenda[] = $pdvvenda;
					}
					$f = FALSE;
//					foreach(array_reverse($pdvvenda->pdvitem) as $pdvitem){
//						if($pdvitem->getcodproduto() == $codproduto){
//							$pdvitem->setquantidade($pdvitem->getquantidade() - $quantidade);
//							$f = TRUE;
//							break;
//						}
//					}
					if(!$f){
						$pdvitem = new PdvItem;
						$pdvitem->setcodproduto($codproduto);
						$pdvitem->setquantidade($quantidade);
						$pdvitem->setpreco($precounit);
						$pdvitem->setstatus("T");
						$pdvvenda->pdvitem[] = $pdvitem;
					}
					break;
				// C A N C E L A M E N T O   D E   I T E M   V E N D I D O
				case "06":
					$tributacao = substr($linha, 21, 3); // Tributacao (formato: A00; exemplo: F18,T07)
					$aliqicms = substr($linha, 24, 5); // Aliquota de ICMS (formato: 99.99)
					$aliqredicms = substr($linha, 29, 6); // Aliquota de reducao na base do ICMS (formato: 99.999)
					$data = substr($linha, 35, 8); // Data da venda
					$codproduto = substr($linha, 43, 14); // Codigo do produto
					$quantidade = substr($linha, 57, 9); // Quantidade de venda (formato: 99999.999)
					$precounit = substr($linha, 66, 12); // Valor unitario do produto (formato: 999999999.99)
					$desconto = substr($linha, 78, 12); // Valor do desconto (formato: 999999999.99)
					$acrescimo = substr($linha, 90, 12); // Valor do acrescimo (formato: 999999999.99)
					$totalitem = substr($linha, 130, 12); // Valor total do item vendido (999999999.99)
					$seqecf = substr($linha, 142, 6); // Sequencial do equipamento fiscal (ECF)
					$hora = substr($linha, 151, 4); // Hora da transacao de venda (formato: HHMM)
					$serieecf = trim(substr($linha, 245, 20)); // Numero de serie do equipamento
					$cpfcnpj = trim(substr($linha, 274, 14)); // CPF/CNPJ do consumidor
//					$chavecfe = trim(substr($linha, 341, 44)); // Chave do cupom fiscal eletronico
					$chavecfe = trim(substr($linha, 335, 44)); // Chave do cupom fiscal eletronico

					if($serieecf){
						$numfabricacao = $serieecf;
					}else{
						if(strlen(trim($chavecfe,0)) > 0){
							$mod = substr($chavecfe, 20,2);
							if($mod == 65){
								$numfabricacao = "";
							}else{
								$numfabricacao = ltrim(substr($chavecfe, 22, 9),"0");
							}
						}
					}
					$codproduto = $this->codproduto($codproduto);

					$data = substr($data, 0, 2)."/".substr($data, 2, 2)."/".substr($data, 4);
					$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":00";

					$tptribicms = substr($tributacao, 0, 1);
					if($tptribicms == "R"){
						$tptribicms = "T";
					}

					//$len = strlen($seqecf);
					//$seqecf = str_pad(--$seqecf,$len,"0",STR_PAD_LEFT);

					$found = FALSE;
					foreach(array_reverse($this->pdvvenda) as $pdvvenda){
						if($pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $caixa){
							$pdvvenda->setnumfabricacao($numfabricacao);
							$found = TRUE;
							break;
						}
					}
					if(!$found){
						if(strlen($serieecf) < 10 && strlen($chavecfe) === 0){
							continue 2;
						}
						$pdvvenda = new PdvVenda;
						$pdvvenda->setseqecf($seqecf);
						$pdvvenda->setcupom($cupom);
						$pdvvenda->setcaixa($caixa);
						$pdvvenda->setdata($data);
						$pdvvenda->sethora($hora);
						$pdvvenda->setnumeroecf($numecf);
						$pdvvenda->setcpfcnpj($cpfcnpj);
						$pdvvenda->setchavecfe($chavecfe);
						$pdvvenda->setnumfabricacao($numfabricacao);
						$pdvvenda->setarquivo($file_name);
						$this->pdvvenda[] = $pdvvenda;
					}
					$found = FALSE;
					/*
					foreach(array_reverse($pdvvenda->pdvitem) as $pdvitem){
//						if($pdvitem->getcodproduto() == $codproduto){ Alteracao na OS
						if($pdvitem->getcodproduto() == $codproduto && $pdvitem->getstatus() != "C"){
							$pdvitem->setstatus("C");
							$f = TRUE;
							break;
						}
					}
					*/
					if(!$found){
						$pdvitem = new PdvItem;
						$pdvitem->setcodproduto($codproduto);
						$pdvitem->setstatus("C");
						$pdvitem->setquantidade($quantidade);
						$pdvitem->setpreco($precounit);
						$pdvitem->setdesconto($desconto);
						$pdvitem->setacrescimo($acrescimo);
						$pdvitem->settotal($totalitem);
						$pdvitem->settptribicms($tptribicms);
						$pdvitem->setaliqicms($aliqicms);
						$pdvvenda->pdvitem[] = $pdvitem;
					}
					break;
				// C A N C E L A M E N T O   D E   F I N A L I Z A C A O
				case "07":
					//$seqecf = substr($linha, 59, 6); // Sequencial do equipamento fiscal (ECF)
					foreach($this->pdvfinalizador as $i => $pdvfinalizador){
						//if($pdvfinalizador->getcaixa() == $caixa && $pdvfinalizador->getcupom() == $seqecf){
						if($pdvfinalizador->getcaixa() == $caixa && $pdvfinalizador->getcupom() == $cupom){
							unset($this->pdvfinalizador[$i]);
							break;
						}
					}
					break;

				//R E C E B I M E N T O S   D I V E R S O S
				case "09":
					if($param_frentecaixa_syspdvrecebimento === "S"){
						$codfinaliz = substr($linha, 27, 3); // Codigo da finalizadora
						$codestabelec = value_numeric(substr($linha, 2, 4));
						$dtmovto = substr($linha, 19, 8);
						$dtmovto = substr($dtmovto, 0, 2)."/".substr($dtmovto, 2, 2)."/".substr($dtmovto, 4);
						$valortotal = substr($linha, 48, 12);
						$codcliente = value_numeric(substr($linha, 130, 15));

						$recebepdv = objectbytable("recebepdv", null, $this->con);
						$recebepdv->setcodestabelec($codestabelec);
						$recebepdv->setcupom($cupom);
						$recebepdv->setdtmovto($dtmovto);
						$recebepdv->settotalliquido($valortotal);
						$recebepdv->setcodfinaliz($codfinaliz);
						$recebepdv->setcaixa($caixa);
						$recebepdv->settiporecebimento("C");
						$recebepdv->settipoparceiro("C");
						$recebepdv->setcodparceiro($codcliente);

						$this->pdvrecebepdv[$caixa."|".$cupom."|".$dtmovto] = $recebepdv;

						$arr_cupom_recebimento[] = $caixa."|".$cupom."|".$dtmovto;
					}
					break;

				// R E S E R V A D O    ( C A N C E L A M E N T O   D E   C U P O M )
				case "13":
					$data = substr($linha, 19, 8);
					$cupom = substr($linha, 27, 6);
					$modelo = substr($linha, 72, 2);

					// Caso seja SAT pega o cancelamento de outro campo
					if($modelo == "59"){
						$cupom = substr($linha, 13, 6);
						$cupom--;
						$cupom = str_pad($cupom,6,"0",STR_PAD_LEFT);
					}
					$data = substr($data, 0, 2)."/".substr($data, 2, 2)."/".substr($data, 4);

					$chavecfe = trim(substr($linha, 58, 44)); // Chave do cupom fiscal eletronico
					$serieecf = "";

					$numfabricacao = "";
					if(strlen(trim($chavecfe,0)) > 0){
						$mod = substr($chavecfe, 20,2);
						if($mod == 65){
							$numfabricacao = "";
						}else{
							$numfabricacao = ltrim(substr($chavecfe, 22, 9),"0");
						}
					}elseif(strlen(trim($serieecf,0)) > 0){
						$numfabricacao = $serieecf;
					}

					$f = FALSE;
					foreach(array_reverse($this->pdvvenda) as $pdvvenda){
						if($pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $caixa){
							$pdvvenda->setstatus("C");
							$pdvvenda->setchavecfe($chavecfe);
							$f = TRUE;
							break;
						}
					}
					if(!$f){
						$pdvvenda = new PdvVenda;
						$pdvvenda->setcupom($cupom);
						$pdvvenda->setcaixa($caixa);
						$pdvvenda->setdata($data);
						$pdvvenda->setnumfabricacao($numfabricacao);
						$pdvvenda->setstatus("C");
						$pdvvenda->setarquivo($file_name);
						$this->pdvvenda[] = $pdvvenda;
					}
					break;
				// Vasilhame
				case "23":
					$codestabelec = value_numeric(substr($linha, 2, 4));
					$numrecepcao = value_numeric(substr($linha, 6, 10));
					$dtrecepcao = substr($linha, 16, 8);
					$dtrecepcao = substr($dtrecepcao, 0, 2)."/".substr($dtrecepcao, 2, 2)."/".substr($dtrecepcao, 4);
					$codcliente = value_numeric(substr($linha, 27, 15));
					$caixa = value_numeric(substr($linha, 26, 15));
					$dtvenda = substr($linha, 44, 8);
					$dtvenda = substr($dtvenda, 0, 2)."/".substr($dtvenda, 2, 2)."/".substr($dtvenda, 4);
					$codfunc = value_numeric(substr($linha, 58, 10));
					$tipo = substr($linha, 47, 1);
					$pdvvasilhame = new PdvVasilhame;
					$pdvvasilhame->setcodestabelec($codestabelec);
					$pdvvasilhame->setnumrecepcao($numrecepcao);
					$pdvvasilhame->setdtrecepcao($dtrecepcao);
					$pdvvasilhame->setcodcliente($codcliente);
					$pdvvasilhame->setcaixa($caixa);
					$pdvvasilhame->setdtvenda($dtvenda);

					$this->pdvvasilhame[$numrecepcao] = array();
					$this->pdvvasilhame[$numrecepcao][0] = $pdvvasilhame;
					break;
				// ... de Vasilhame
				case "24":
					$pdvvasilhame = new PdvVasilhame;

					$numrecepcao = value_numeric(substr($linha, 2, 10));
					$codproduto = value_numeric(substr($linha, 12, 14));
					$quantidade = value_numeric(substr($linha, 26, 9));

					$pdvvasilhame->setcodestabelec($this->pdvvasilhame[$numrecepcao][0]->getcodestabelec());
					$pdvvasilhame->setnumrecepcao($this->pdvvasilhame[$numrecepcao][0]->getnumrecepcao());
					$pdvvasilhame->setdtrecepcao($this->pdvvasilhame[$numrecepcao][0]->getdtrecepcao());
					$pdvvasilhame->setcodcliente($this->pdvvasilhame[$numrecepcao][0]->getcodcliente());
					$pdvvasilhame->setcaixa($this->pdvvasilhame[$numrecepcao][0]->getcaixa());
					$pdvvasilhame->setdtvenda($this->pdvvasilhame[$numrecepcao][0]->getdtvenda());

					$pdvvasilhame->setquantidade($quantidade);
					$pdvvasilhame->setcodvasilhame($codproduto);
					if($tipo == "E"){
						$pdvvasilhame->setentsai("DE");
					}else{
						$pdvvasilhame->setentsai("E");
					}
					$this->pdvvasilhame[$numrecepcao][] = $pdvvasilhame;
					break;
				// ... de Vasilhame
				case "25":
					$pdvvasilhame = new PdvVasilhame;

					$numrecepcao = value_numeric(substr($linha, 2, 10));
					$codproduto = value_numeric(substr($linha, 12, 14));
					$quantidade = value_numeric(substr($linha, 26, 9));

					$pdvvasilhame->setcodestabelec($this->pdvvasilhame[$numrecepcao][0]->getcodestabelec());
					$pdvvasilhame->setnumrecepcao($this->pdvvasilhame[$numrecepcao][0]->getnumrecepcao());
					$pdvvasilhame->setdtrecepcao($this->pdvvasilhame[$numrecepcao][0]->getdtrecepcao());
					$pdvvasilhame->setcodcliente($this->pdvvasilhame[$numrecepcao][0]->getcodcliente());
					$pdvvasilhame->setcaixa($this->pdvvasilhame[$numrecepcao][0]->getcaixa());
					$pdvvasilhame->setdtvenda($this->pdvvasilhame[$numrecepcao][0]->getdtvenda());

					$pdvvasilhame->setquantidade($quantidade);
					$pdvvasilhame->setcodvasilhame($codproduto);
					if($tipo == "E"){
						$pdvvasilhame->setentsai("DS");
					}else{
						$pdvvasilhame->setentsai("S");
					}
					$this->pdvvasilhame[$numrecepcao][] = $pdvvasilhame;
					break;
			}
		}

		// Verifica se o total dos itens bate com o total do cupom
		if(substr(basename($file_name), 0, 2) == "LG" || substr(basename($file_name), 0, 2) == "TP"){
			$troca = false;
			$totalitens = 0;
			$itemativo = false;
			foreach($this->pdvvenda as $pdvvenda){
				if($pdvvenda->getstatus() == "A"){
					foreach($pdvvenda->pdvitem as $pdvitem){
						if($pdvitem->getstatus() == "A"){
							$itemativo = true;
							$totalitens += $pdvitem->gettotal();
						}elseif($pdvitem->getstatus() == "T"){
							$troca = true;
						}
					}
				}
			}
			if($itemativo && round($totalitens, 2) != round($totalvenda, 2) && count($this->pdvrecebepdv) == 0 && !$troca){
				$this->pdvfinalizador = array_unique_multi($this->pdvfinalizador);
				if(count($this->pdvfinalizador) == 0 || (string) $this->pdvfinalizador[0]->getvalortotal() != (string) $totalitens){
					$processo = objectbytable("processo", "LEITURAONLINE", $this->con);
					$processo->erro("O total do cupom {$cupom} não confere com o total dos itens. (Arquivo: ".basename($file_name).") ".((string) $t." - ".(string) $totalvenda));
					return false;
				}
			}
		}

		return true;
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

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

	private function codproduto($codproduto){
		$codproduto = trim(ltrim($codproduto, "0"));
		if(strlen($codproduto) == 0){
			$codproduto = 0;
		}
		return $codproduto;
	}

	private function valor_numerico($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, ".", "");
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto, $tamanho){
		$texto = substr($texto, 0, $tamanho);
		$texto = str_pad($texto, $tamanho, " ", STR_PAD_RIGHT);
		return $texto;
	}

}
