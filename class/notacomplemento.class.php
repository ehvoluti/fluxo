<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class NotaComplemento extends Cadastro{
	function __construct($idnotacomplemento = NULL){
		parent::__construct();
		$this->table = "notacomplemento";
		$this->primarykey = array("idnotacomplemento");
		$this->setidnotacomplemento($idnotacomplemento);
		if(!is_null($this->getidnotacomplemento())){
			$this->searchbyobject();
		}
	}

	function calcular_chavenfe(){
		$arr_ini = array(1,2,3,4,5,6);
		$natoperacao = objectbytable("natoperacao",$this->getnatoperacao(),$this->con);
		if($natoperacao->getgerafiscal() == "S"){

			$notafiscal = objectbytable("notafiscal",$this->getidnotafiscal(),$this->con);
			$operacaonota = objectbytable("operacaonota",$notafiscal->getoperacao(),$this->con);

			if($operacaonota->gettipo() == "E" && !in_array($operacaonota->getoperacao(),array("DC","PR"))){
				switch($operacaonota->getparceiro()){
					case "C":
					case "E":
						$emitente = objectbytable("estabelecimento",$notafiscal->getcodparceiro(),$this->con);
						break;
					case "F":
						$emitente = objectbytable("fornecedor",$notafiscal->getcodparceiro(),$this->con);
						break;
				}
			}else{
				$emitente = objectbytable("estabelecimento",$this->getcodestabelec(),$this->con);
			}

			$estado = objectbytable("estado",$emitente->getuf(),$this->con);

			$chavenfe = str_pad(substr($estado->getcodoficial(),-2),2,"0",STR_PAD_LEFT); // Codigo oficial do estado do emitente
			$chavenfe .= convert_date($this->getdtemissao(),"Y-m-d","ym"); // Ano e mes da data de emissao (formato: AAMM)
			$chavenfe .= str_pad(substr(removeformat($emitente->getcpfcnpj()),0,14),14,"0",STR_PAD_LEFT); // CNPJ do emitente
			$chavenfe .= "55"; // Modelo da nota fiscal
			$chavenfe .= str_pad(substr($this->getserie(),0,3),3,"0",STR_PAD_LEFT); // Serie da nota fiscal
			$chavenfe .= str_pad($this->getnumnotafis(),9,"0",STR_PAD_LEFT); // Numero da nota fiscal
			$chavenfe .= "1"; // Forma de emissao da nota fiscal
			//$chavenfe .= str_pad($this->getnumnotafis(),8,"0",STR_PAD_LEFT); // Codigo numerico (enviar numero da nota fiscal)

			if(strlen(trim($this->getnumerodocumento())) == 0){
				$cNFAuxiliar = md5($this->getnumnotafis());
				$cNFAuxiliar = sprintf("%.0f", hexdec($cNFAuxiliar));
				$posIni = $arr_ini[$this->weekOfMonth($this->getdtemissao()) - 1];
				$cNFAuxiliar = str_pad(substr($cNFAuxiliar, $posIni * 5, 8) , 8, "0", STR_PAD_LEFT);
			}else{
				$cNFAuxiliar = $this->getnumerodocumento();
			}
			$chavenfe .= $cNFAuxiliar;
			$chavenfe_rev = strrev($chavenfe);
			$ponderacao = 0;
			$peso = 2;
			for($i = 0; $i < strlen($chavenfe_rev); $i++){
				$ponderacao += substr($chavenfe_rev,$i,1) * $peso;
				if(++$peso > 9){
					$peso = 2;
				}
			}
			$digito = 11 - ($ponderacao % 11);
			if($digito > 9){
				$digito = 0;
			}
			$chavenfe .= $digito;
		}else{
			$chavenfe = NULL;
		}

		$this->setchavenfe($chavenfe);
		$this->setnumerodocumento($cNFAuxiliar);
		return $chavenfe;
	}

	function weekOfMonth($date) {
		// estract date parts
		list($y, $m, $d) = explode('-', date('Y-m-d', strtotime($date)));

		// current week, min 1
		$w = 1;

		// for each day since the start of the month
		for ($i = 1; $i <= $d; ++$i) {
			// if that day was a sunday and is not the first day of month
			if ($i > 1 && date('w', strtotime("$y-$m-$i")) == 0) {
				// increment current week
				++$w;
			}
		}

		// now return
		return $w;
	}
    function save(){
        $this->connect();

        // Verifica se os parametros fiscais foram informados
		$paramfiscal = objectbytable("paramfiscal",$this->getcodestabelec(),$this->con);
		if(!$paramfiscal->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros fiscais n&atilde;o informados.<br><a onclick=\"openProgram('CadParamFiscal','codestabelec=".$this->getcodestabelec()."')\">Clique aqui</a> para abrir o par&acirc;metros fiscais.";
			return FALSE;
		}

        // Gera um numero de nota fiscal quando for uma nova nota fiscal de complemento
        if(strlen($this->getnumnotafis()) == 0){
            $natoperacao = objectbytable("natoperacao",$this->getnatoperacao(),$con);
            // Verifica se CFOP esta para gerar fiscal, se sim pega o sequencial da nf fiscal caso contrario pega o sequencial nao fiscal
            if($natoperacao->getgerafiscal() == "S"){
                $this->setnumnotafis($paramfiscal->getnumnotafis());
                $paramfiscal->setnumnotafis($paramfiscal->getnumnotafis() + 1);
            }else{
                $this->setnumnotafis($paramfiscal->getnumnotanfis());
				$this->setserie($paramfiscal->getserienfis());
                $paramfiscal->setnumnotanfis($paramfiscal->getnumnotanfis() + 1);
            }
            if(!$paramfiscal->save()){
                $this->con->rollback();
                return FALSE;
            }
        }
        if(strlen($this->getserie()) == 0){
            $this->setserie($paramfiscal->getserie());
        }

//		if($this->getfinalidade() == "3"){
//			$this->settipoparceiro("E");
//			$this->setcodparceiro($this->getcodestabelec());
//		}

        // Grava a nota fiscal de complemento
        return parent::save();
    }

	function getidnotacomplemento(){
		return $this->fields["idnotacomplemento"];
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"],2,",","") : $this->fields["totalipi"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"],2,",","") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"],2,",","") : $this->fields["totalicms"]);
	}

	function gettotalbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubst"],2,",","") : $this->fields["totalbaseicmssubst"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"],2,",","") : $this->fields["totalicmssubst"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"],"Y-m-d","d/m/Y") : $this->fields["datalog"]);
	}

	function gethoralog(){
		return substr($this->fields["horalog"],0,8);
	}

	function getchavenfe(){
		return $this->fields["chavenfe"];
	}

	function getfinalidade(){
		return $this->fields["finalidade"];
	}

	function gettextonota(){
		return $this->fields["textonota"];
	}

	function getcodigostatus(){
		return $this->fields["codigostatus"];
	}

	function getxmotivo(){
		return $this->fields["xmotivo"];
	}

	function getprotocolonfe(){
		return $this->fields["protocolonfe"];
	}

	function getdataautorizacao(){
		return $this->fields["dataautorizacao"];
	}

	function getdatacancelamento(){
		return $this->fields["datacancelamento"];
	}

	function getprotocolocanc(){
		return $this->fields["protocolocanc"];
	}

	function getxmlnfe(){
		return $this->fields["xmlnfe"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"],"Y-m-d","d/m/Y") : $this->fields["dtentrega"]);
	}

	function getrecibonfe(){
		return $this->fields["recibonfe"];
	}

	function getemissaopropria(){
		return $this->fields["emissaopropria"];
	}

	function getcsticms(){
		return $this->fields["csticms"];
	}

	function getorigem(){
		return $this->fields["origem"];
	}

	function getnumerodocumento(){
		return $this->fields["numerodocumento"];
	}

//	function gettipoparceiro(){
//		return $this->fields["tipoparceiro"];
//	}
//
//	function getcodparceiro(){
//		return $this->fields["codparceiro"];
//	}

	function setidnotacomplemento($value){
		$this->fields["idnotacomplemento"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value,3);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value,9);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function settotalbaseicms($value){
		$this->fields["totalbaseicms"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalbaseicmssubst($value){
		$this->fields["totalbaseicmssubst"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}

	function setchavenfe($value){
		$this->fields["chavenfe"] = value_string($value,44);
	}

	function setfinalidade($value){
		$this->fields["finalidade"] = value_numeric($value);
	}

	function settextonota($value){
		$this->fields["textonota"] = value_string($value,50);
	}

	function setcodigostatus($value){
		$this->fields["codigostatus"] = value_numeric($value);
	}

	function setxmotivo($value){
		$this->fields["xmotivo"] = value_string($value);
	}

	function setprotocolonfe($value){
		$this->fields["protocolonfe"] = value_string($value);
	}

	function setdataautorizacao($value){
		$this->fields["dataautorizacao"] = value_date($value);
	}

	function setdatacancelamento($value){
		$this->fields["datacancelamento"] = value_date($value);
	}

	function setprotocolocanc($value){
		$this->fields["protocolocanc"] = value_string($value);
	}

	function setxmlnfe($value){
		$this->fields["xmlnfe"] = value_string($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setrecibonfe($value){
		$this->fields["recibonfe"] = value_string($value, 15);
	}

	function setemissaopropria($value){
		$this->fields["emissaopropria"] = value_string($value, 1);
	}

	function setcsticms($value){
		$this->fields["csticms"] = value_string($value, 3);
	}

	function setorigem($value){
		$this->fields["origem"] = value_string($value, 1);
	}

	function setnumerodocumento($value){
		$this->fields["numerodocumento"] = value_string($value, 8);
	}
//	function setsettipoparceiro($value){
//		$this->fields["tipoparceiro"] = value_string($value, 1);
//	}
//	function setcodparceiro($value){
//		$this->fields["codparceiro"] = value_string($value, 1);
//	}
}
