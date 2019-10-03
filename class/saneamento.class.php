<?php

class Saneamento{

	private $con; // Conexao com banco de dados
	private $regimetributario; // Regime tributario (1 = simples; 2 = presumido; 3 = real)
	private $uf; // Estado a ser saneado
	private $atualizar_cadastro; // Define se deve atualizar o cadastro ou a tributacao por estado
	private $arr_classfiscal; // Lista de todas as classificacoes fiscais cadastradas
	private $arr_ipi; // Lista de todos os IPIs cadastrados
	private $arr_ncm; // Lista de todos os NCMs cadastrados
	private $arr_piscofins; // Lista de todos os PIS/Cofins cadastrados
	private $autocadsaneamento; // Informa se deve ou nao cadastrar as tributacoes automaticamente (valor booleano)
	private $arr_erro; // Lista de erros para gerar relatorio no final do processo

	public function __construct(Connection $con){
		// Atribui a conexao do banco a classe
		$this->con = $con;

		// Limpa a lista de erros
		$this->arr_erro = array();

		// Verifica se todas as lojas sao do mesmo regime tributario e estado:
		// Caso sim, atualiza direto no cadastro do produto
		// Caso nao, atualiza a tributacao por estado
		$res = $this->con->query("SELECT DISTINCT uf FROM estabelecimento");
		//$res = $this->con->query("SELECT DISTINCT uf, regimetributario FROM estabelecimento");
		$this->atualizar_cadastro = ($res->rowCount() == 1);

		// Carregas todas as classificacoes fiscais cadastradas
		$classfiscal = objectbytable("classfiscal", null, $this->con);
		$this->arr_classfiscal = object_array($classfiscal);

		// Carrega todos os PIS/Cofins cadastrados
		$piscofins = objectbytable("piscofins", null, $this->con);
		$this->arr_piscofins = object_array($piscofins);

		// Carrega todos os IPIs cadastrados
		$ipi = objectbytable("ipi", null, $this->con);
		$this->arr_ipi = object_array($ipi);

		// Carrega todos os NCMs cadastrados
		$ncm = objectbytable("ncm", null, $this->con);
		$this->arr_ncm = object_array($ncm);

		// Carrega o parametro que verifica se deve cadastrar as tributacoes automaticamente
		$this->autocadsaneamento = (param("FISCAL", "AUTOCADSANEAMENTO", $this->con) === "S");
	}

	public function __destruct(){
		// Grava os erros na sessao para serem capturados futuramente
		$_SESSION["SANEAMENTO"]["ERRO"] = $this->arr_erro;
	}

	public function atualizar_produto($dados){
		// Le os dados enviados por parametro
		$codproduto = $dados["codproduto"];
		$classfiscalnfe = $dados["classfiscalnfe"];
		$classfiscalnfs = $dados["classfiscalnfs"];
		$classfiscalpdv = $dados["classfiscalpdv"];
		$piscofinsent = $dados["piscofinsent"];
		$piscofinssai = $dados["piscofinssai"];
		$ipi = $dados["ipi"];
		$aliqiva = $dados["aliqiva"];
		$aliqfcp = $dados["aliqfcp"];
		$natreceita = $dados["natreceita"];
		$cest = $dados["cest"];
		$ncm = $dados["ncm"];

		// NAO SANEAR IPI
		$ipi = null;

		// Caso nao tenho o IVA informado no produto, captura da classificacao fiscal
		if(strlen($aliqiva) === 0){
			$aliqiva = $dados["classfiscalnfe"]["aliqiva"];
		}

		// Caso o NCM estja informado no produto e nao no NCM
		if(!isset($ncm["codigoncm"])){
			$ncm["codigoncm"] = $dados["codigoncm"];
		}

		// Carrega o produto para atualizar a data de saneamento
		$produto = objectbytable("produto", $codproduto, $this->con, true);

		// Verifica se o produto existe
		if(!$produto->exists()){
			return true;
		}

		// Verifica se deve sanear
		if($produto->getsanear() == "N"){
			return true;
		}

		// Inicia uma transacao no banco de dados
		$this->con->start_transaction();

		// Identifica se ocorreu algum erro
		$erro = false;

		// Atualiza a data de saneamento e outros dados
		$produto->setdtsaneamento(date("Y-m-d"));
		$produto->setatualizancm("N");

		// Verifica se deve atualizar o cadastro ou a tributacao por estado
		if($this->atualizar_cadastro){
			$object = $produto;
		}else{
			$object = objectbytable("estadotributo", array($this->uf, $this->regimetributario, $codproduto), $this->con, true);
		}

		// Verifica a classificacao fiscal de entrada
		if(!is_null($classfiscalnfe)){
			$classfiscalnfe = $this->verificar_classfiscal($classfiscalnfe, $produto);
			if($classfiscalnfe === false){
				$erro = true;
			}else{
				$object->setcodcfnfe($classfiscalnfe->getcodcf());
			}
		}

		// Verifica a classificacao fiscal de saida
		if(!is_null($classfiscalnfs)){
			$classfiscalnfs = $this->verificar_classfiscal($classfiscalnfs, $produto);
			if($classfiscalnfs === false){
				$erro = true;
			}else{
				$object->setcodcfnfs($classfiscalnfs->getcodcf());
			}
		}

		// Verifica a classificacao fiscal para PDV
		if(!is_null($classfiscalpdv)){
			$classfiscalpdv = $this->verificar_classfiscal($classfiscalpdv, $produto);
			if($classfiscalpdv === false){
				$erro = true;
			}else{
				$object->setcodcfpdv($classfiscalpdv->getcodcf());
			}
		}

		// Verifica o PIS/Cofins de entrada
		if(!is_null($piscofinsent)){
			$piscofinsent = $this->verificar_piscofins($piscofinsent, $produto);
			if($piscofinsent === false){
				$erro = true;
			}else{
				$object->setcodpiscofinsent($piscofinsent->getcodpiscofins());
			}
		}

		// Verifica o PIS/Cofins de saida
		if(!is_null($piscofinssai)){
			$piscofinssai = $this->verificar_piscofins($piscofinssai, $produto);
			if($piscofinssai === false){
				$erro = true;
			}else{
				$object->setcodpiscofinssai($piscofinssai->getcodpiscofins());
			}
		}

		// Verifica a natureza da receita
		if(strlen($natreceita) > 0){
			$natreceita = str_pad($natreceita, 3, "0", STR_PAD_LEFT);
			$produto->setnatreceita($natreceita);
		}

		// Verifica o CEST
		if(strlen(removeformat($cest)) === 7){
			$cest = removeformat($cest);
			$cest = substr($cest, 0, 2).".".substr($cest, 2, 3).".".substr($cest, 5, 2);
			$produto->setcest($cest);
		}

		// Verifica o IPI de da
		if(!is_null($ipi)){
			$ipi = $this->verificar_ipi($ipi, $produto);
			if($ipi === false){
				$erro = true;
			}else{
			//	$object->setcodipi($ipi->getcodipi());
			}
		}

		// Verifica a aliquota de IVA
		if(!is_null($aliqiva)){
			$object->setaliqiva($aliqiva);
		}

		// Verifica NCM
		if(strlen($ncm["codigoncm"]) > 0){
			$ncm = $this->verificar_ncm($ncm, $object);
			if($ncm === false){
				$erro = true;
			}else{
				$produto->setidncm($ncm->getidncm());
			}
		}

		// Verifica fundo de combate a pobreza
		if(!is_null($aliqfcp)){
			if(!$this->verificar_aliqfcp($aliqfcp, $produto)){
				$erro = true;
			}
		}

		// Verifica se houve algum erro
		if($erro){
			$this->con->rollback();
			return true;
		}

		// Grava os dados alterados no objeto produto/estadotributo
		if(!$object->save()){
			$this->con->rollback();
			return false;
		}

		// Se nao foi feita as mudancas no produto, grava o produto
		if(get_class($object) != "Produto"){
			if(!$produto->save()){
				$this->con->rollback();
				return false;
			}
		}

		// Confirma a transacao no banco de dados
		$this->con->commit();
		return true;
	}

	function limpar_dtsaneamento(){
		$this->con->start_transaction();
		if(!$this->con->query("ALTER TABLE produto DISABLE TRIGGER USER")){
			$this->con->rollback();
			return false;
		}
		if(!$this->con->query("UPDATE produto SET dtsaneamento = NULL WHERE dtsaneamento IS NOT NULL")){
			$this->con->rollback();
			return false;
		}
		if(!$this->con->query("ALTER TABLE produto ENABLE TRIGGER USER")){
			$this->con->rollback();
			return false;
		}
		$this->con->commit();
		return true;
	}

	function setconnection(Connection $con){
		unset($this->con);
		$this->con = $con;
	}

	private function descobrir_classfiscal_tptribicms($codcst){
		switch(substr($codcst, -2)){
			case "00": return "T";
			case "10": return "F";
			case "20": return "R";
			case "40": return "I";
			case "41": return "N";
			case "60": return "F";
			case "70": return "F";
			default: return "N";
		}
	}

	private function descobrir_piscofins_tipo($codcst){
		switch($codcst){
			case "01": return "T";
			case "02": return "T";
			case "03": return "T";
			case "04": return "M";
			case "05": return "F";
			case "06": return "Z";
			case "07": return "I";
			case "08": return "I";
			case "09": return "I";
			case "49": return "I";
			case "50": return "T";
			case "51": return "T";
			case "52": return "T";
			case "53": return "T";
			case "54": return "T";
			case "55": return "T";
			case "56": return "T";
			case "60": return "T";
			case "61": return "T";
			case "62": return "T";
			case "63": return "T";
			case "64": return "T";
			case "65": return "T";
			case "66": return "T";
			case "67": return "T";
			case "70": return "M";
			case "71": return "I";
			case "72": return "M";
			case "73": return "Z";
			case "74": return "F";
			case "75": return "F";
			case "98": return "I";
			case "99": return "I";
			default: return "I";
		}
	}

	public function erro_contador(){
		return count($this->arr_erro);
	}

	private function erro_incluir(Produto $produto, $dados){
		$key = json_encode($dados);
		$this->arr_erro[$key]["dados"] = $dados;
		$this->arr_erro[$key]["produtos"][] = array(
			"codproduto" => $produto->getcodproduto(),
			"descricaofiscal" => $produto->getdescricaofiscal()
		);
	}

	public function setregimetributario($regimetributario){
		$this->regimetributario = $regimetributario;
	}

	public function setuf($uf){
		$this->uf = $uf;
	}

	private function verificar_aliqfcp($aliqfcp, Produto $produto){
		if(strlen($aliqfcp) > 0 && strlen($produto->getidncm()) > 0){
			$ncmestado = objectbytable("ncmestado", array($produto->getidncm(), "SP"), $this->con);
			$ncmestado->setaliqfcp($aliqfcp);
			if(!$ncmestado->save()){
				return false;
			}
		}
		return true;
	}

	private function verificar_classfiscal($saneamento, Produto $produto){
		// Colunas comparativas da classificacao fiscal
		$arr_classfiscal_column = array("codcst", "tptribicms", "aliqicms", "aliqredicms", "valorpauta");
		if($this->regimetributario === 1){
			$arr_classfiscal_columnp[] = "csosn";
		}

		// Preenche o objeto a ser comparado com os valores informados
		$classfiscalsan = objectbytable("classfiscal", null, $this->con);
		foreach($saneamento as $column => $value){
			call_user_func(array($classfiscalsan, "set".$column), $value);
		}

		// Verifica se o tipo de tributacao foi preenchido
		if(strlen($classfiscalsan->gettptribicms()) === 0){
			$classfiscalsan->settptribicms($this->descobrir_classfiscal_tptribicms($classfiscalsan->getcodcst()));
		}

		// Verifica as reducoes invalidas
		$this->verificar_classfiscal_aliqredicms($classfiscalsan, 41.67);
		$this->verificar_classfiscal_aliqredicms($classfiscalsan, 61.11);

		// Verifica as aliquotas de ICMS
		$this->verificar_classfiscal_aliqicms($classfiscalsan);

		// Verifica o tamanho do CST
		if(strlen($classfiscalsan->getcodcst()) < 3){
			$classfiscalsan->setcodcst(str_pad($classfiscalsan->getcodcst(), 3, "0", STR_PAD_LEFT));
		}

		// Verifica o tamanho do CSOSN
		if(strlen($classfiscalsan->getcsosn()) > 0 && strlen($classfiscalsan->getcsosn()) < 3){
			$classfiscalsan->setcsosn(str_pad($classfiscalsan->getcsosn(), 3, "0", STR_PAD_LEFT));
		}

		// Verifica se a classificacao fiscal ja existe, caso nao, cadastra
		$found = false;
		foreach($this->arr_classfiscal as $classfiscal){
			$found = true;
			foreach($arr_classfiscal_column as $column){
				if($column === "csosn" && strlen($classfiscalsan->getcsosn()) === 0){
					continue;
				}
				if(call_user_func(array($classfiscal, "get".$column)) != call_user_func(array($classfiscalsan, "get".$column))){
					$found = false;
					continue;
				}
			}
			if($found){
				$classfiscalsan = $classfiscal;
				break;
			}
		}
		if(!$found){
			if($this->autocadsaneamento){
				if(!$classfiscalsan->save()){
					return false;
				}
			}else{
				$this->erro_incluir($produto, array(
					"Tabela" => "Classificação Fiscal",
					"CST" => $classfiscalsan->getcodcst(),
					"Tipo de Tributação" => $classfiscalsan->gettptribicms(),
					"Alíquota de ICMS" => $classfiscalsan->getaliqicms(true),
					"Redução BC ICMS" => $classfiscalsan->getaliqredicms(true),
					"Valor de Pauta" => $classfiscalsan->getvalorpauta(true),
					"CSOSN" => $classfiscalsan->getcsosn()
				));
				return false;
			}
			$this->arr_classfiscal[] = $classfiscalsan;
		}
		return $classfiscalsan;
	}

	private function verificar_classfiscal_aliqicms(ClassFiscal $classfiscal){
		switch($classfiscal->getaliqredicms()){
			case 33.33:
				$classfiscal->setaliqicms(18);
				break;
			case 41.67:
				$classfiscal->setaliqicms(12);
				break;
			case 52.00:
				$classfiscal->setaliqicms(25);
				break;
			case 61.11:
				$classfiscal->setaliqicms(18);
				break;
		}
	}

	private function verificar_classfiscal_aliqredicms(ClassFiscal $classfiscal, $aliqredicms){
		if(abs($classfiscal->getaliqredicms() - $aliqredicms) < 1){
			$classfiscal->setaliqredicms($aliqredicms);
		}
	}

	private function verificar_ipi($saneamento, Produto $produto){
		$arr_ipi_column = array("codcstsai", "codcstent", "tptribipi", "aliqipi");

		$ipisan = objectbytable("ipi", null, $this->con);
		foreach($saneamento as $column => $value){
			call_user_func(array($ipisan, "set".$column), $value);
		}

		if(strlen($ipisan->getcodcstsai()) === 0){
			$ipisan->setcodcstsai("99");
		}
		if(strlen($ipisan->getcodcstent()) === 0){
			$ipisan->setcodcstent("49");
		}

		$found = false;
		foreach($this->arr_ipi as $ipi){
			$found = true;
			foreach($arr_ipi_column as $column){
				if(call_user_func(array($ipi, "get".$column)) != call_user_func(array($ipisan, "get".$column))){
					$found = false;
					continue;
				}
			}
			if($found){
				$ipisan = $ipi;
				break;
			}
		}
		if(!$found){
			if($this->autocadsaneamento){
				if(!$ipisan->save()){
					return false;
				}
			}else{
				$this->erro_incluir($produto, array(
					"Tabela" => "IPI",
					"CST Entrada" => $ipisan->getcodcstent(),
					"CST Saída" => $ipisan->getcodcstsai(),
					"Tipo de Tributação" => $ipisan->gettptribipi(),
					"Alíquota de IPI" => $ipisan->getaliqipi(true)
				));
				return false;
			}
			$this->arr_ipi[] = $ipisan;
		}
		return $ipisan;
	}

	private function verificar_ncm($saneamento, $object_reference){
		// Verifica se o ocidgo do NCM foi preenchido
		$saneamento["codigoncm"] = trim($saneamento["codigoncm"]);
		if(strlen($saneamento["codigoncm"]) === 0){
			$_SESSION["ERROR"] = "C&oacute;digo de NCM deve ser informado antes da verifica&ccedil;&atilde;o.";
			return false;
		}

		// Reformata o NCM
		$saneamento["codigoncm"] = removeformat($saneamento["codigoncm"]);
		$saneamento["codigoncm"] = substr($saneamento["codigoncm"], 0, 4).".".substr($saneamento["codigoncm"], 4, 2).".".substr($saneamento["codigoncm"], 6, 2);

		$found = false;
		foreach($this->arr_ncm as $ncm){
			if($ncm->getcodigoncm() === $saneamento["codigoncm"]){
				$found = true;
				break;
			}
		}
		if(!$found){
			$ncm = objectbytable("ncm", null, $this->con);
			$ncm->setcodigoncm($saneamento["codigoncm"]);
			$ncm->setdescricao(strlen($saneamento["descricao"]) > 0 ? $saneamento["descricao"] : $saneamento["codigoncm"]);
			$ncm->setcodcfnfe($object_reference->getcodcfnfe());
			$ncm->setcodcfnfs($object_reference->getcodcfnfs());
			$ncm->setcodcfpdv($object_reference->getcodcfpdv());
			$ncm->setcodpiscofinsent($object_reference->getcodpiscofinsent());
			$ncm->setcodpiscofinssai($object_reference->getcodpiscofinssai());
			$ncm->setcodipi($object_reference->getcodipi());
			$ncm->setaliqiva($object_reference->getaliqiva());
			if(!$ncm->save()){
				return false;
			}
			$this->arr_ncm[] = $ncm;
		}
		return $ncm;
	}

	private function verificar_piscofins($saneamento, Produto $produto){
		$arr_piscofins_column = array("tipo", "codcst", "aliqpis", "aliqcofins");

		$piscofinssan = objectbytable("piscofins", null, $this->con);
		foreach($saneamento as $column => $value){
			call_user_func(array($piscofinssan, "set".$column), $value);
		}

		if(strlen($piscofinssan->gettipo()) === 0){
			$piscofinssan->settipo($this->descobrir_piscofins_tipo($piscofinssan->getcodcst()));
		}

		if($piscofinssan->gettipo() === "T"){
			if(strlen($piscofinssan->getaliqpis()) === 0 || strlen($piscofinssan->getaliqcofins()) === 0){
				switch($this->regimetributario){
					case "2": // Lucro presumido
						$piscofinssan->setaliqpis(0.65);
						$piscofinssan->setaliqcofins(3);
						break;
					case "3": // Lucro real
						$piscofinssan->setaliqpis(1.65);
						$piscofinssan->setaliqcofins(7.6);
						break;
				}
			}
		}else{
			$piscofinssan->setaliqpis(0);
			$piscofinssan->setaliqcofins(0);
		}

		if(strlen($piscofinssan->getcodccs()) === 0){
			$piscofinssan->setcodccs("01");
		}

		$found = false;
		foreach($this->arr_piscofins as $piscofins){
			$found = true;
			foreach($arr_piscofins_column as $column){
				if(call_user_func(array($piscofins, "get".$column)) != call_user_func(array($piscofinssan, "get".$column))){
					$found = false;
					continue;
				}
			}
			if($found){
				$piscofinssan = $piscofins;
				break;
			}
		}
		if(!$found){
			if($this->autocadsaneamento){
				if(!$piscofinssan->save()){
					var_dump($_SESSION["ERROR"]);
					return false;
				}
			}else{
				$this->erro_incluir($produto, array(
					"Tabela" => "PIS/Cofins",
					"CST" => $piscofinssan->getcodcst(),
					"Tipo" => $piscofinssan->gettipo(),
					"Alíquota de PIS" => $piscofinssan->getaliqpis(true),
					"Alíquota de Cofins" => $piscofinssan->getaliqcofins(true)
				));
				return false;
			}
			$this->arr_piscofins[] = $piscofinssan;
		}
		return $piscofinssan;
	}

}
