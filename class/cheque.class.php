<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Cheque extends Cadastro{ 
	function __construct($codcheque = NULL){
		parent::__construct();
		$this->table = "cheque";
		$this->primarykey = array("codcheque");
		$this->setcodcheque($codcheque);
		if(!is_null($this->getcodcheque())){
			$this->searchbyobject();
		}
	}

    function imprimir(){
        if(!$this->exists()){
            $_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel imprimir um cheque inexistente.";
            return FALSE;
        }
        $estabelecimento = objectbytable("estabelecimento",$this->getcodestabelec(),$this->con);
        $cidade = objectbytable("cidade",$estabelecimento->getcodcidade(),$this->con);
        $banco = objectbytable("banco",$this->getcodbanco(),$this->con);
        $arr_linha = array();
        $dtemissao = convert_date($this->getdtemissao(TRUE),"d/m/Y","d/m/y");

		$arr_linha[] = chr(27).chr(64);
		$arr_linha[] = chr(27).chr(163).$this->getvalorcheque(TRUE).chr(13);
		$arr_linha[] = chr(27).chr(162).$banco->getcodoficial().chr(13);
		$arr_linha[] = chr(27).chr(164).$dtemissao.chr(13);
		$arr_linha[] = chr(27).chr(166).$this->getnominal().chr(13);
		$arr_linha[] = chr(27).chr(167).strtoupper(removespecial($cidade->getnome())).chr(13);
		$arr_linha[] = chr(27).chr(177);
		$arr_linha[] = chr(27).chr(176);

		$x=$estabelecimento->getdirimpcheque();
        write_file($estabelecimento->getdirimpcheque()."cheque".$this->getcodcheque().".chq",$arr_linha,(param("SISTEMA","TIPOSERVIDOR",$this->con) == 0 ? TRUE : FALSE));
		return TRUE;
    }

	function relatorio_cheque($arr_cheque){
        // Cabeçalho PDF
        $pdf = new PDF($this->con,NULL);
        $pdf->SetTitle("Cheque");
        $pdf->SetFillColor(220,220,220);
        $pdf->AddPage();
        $pdf->SetFont("Arial","",9);
		$pdf->Cell(20,8,"Cheque: ".$arr_cheque["numremessa"],0,1,"L");
		$pdf->SetFont("Arial","",7);
        $pdf->Cell(20,4,"Chave",1,0,"L",1);
        $pdf->Cell(95,4,"Favorecido",1,0,"L",1);
        $pdf->Cell(25,4,"Data de Emissao",1,0,"L",1);
        $pdf->Cell(25,4,"Vencimento",1,0,"L",1);
        $pdf->Cell(25,4,"Valor",1,1,"L",1);

        foreach($arr_cheque as $cheque){
            $pdf->Cell(20,4,"",1,0,"L"); //Chave
            $pdf->Cell(95,4,"",1,0,"L"); //Favorecido
            $pdf->Cell(25,4,"",1,0,"C"); //Data de Emissão
            $pdf->Cell(25,4,"",1,0,"C"); //Vencimento
            $pdf->Cell(25,4,"",1,1,"R"); //Valor
        }

        // Gera o arquivo na pasta temp
		$nomearquivo = "../temp/cheque_".$_SESSION["WUser"]."_".$paramarqremessa["banco"]."_".$paramarqremessa["nomebanco"]."_".$paramarqremessa["numremessa"].".pdf";
        $pdf->Output($nomearquivo,"F");
		$_SESSION["arquivoremessa"] = ($nomearquivo);
    }

	function getcodcheque(){
		return $this->fields["codcheque"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodbanco(){
		return $this->fields["codbanco"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"],"Y-m-d","d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtpagto($format = FALSE){
		return ($format ? convert_date($this->fields["dtpagto"],"Y-m-d","d/m/Y") : $this->fields["dtpagto"]);
	}

	function getnumcheque(){
		return $this->fields["numcheque"];
	}

	function getnominal(){
		return $this->fields["nominal"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getvalorcheque($format = FALSE){
		return ($format ? number_format($this->fields["valorcheque"],2,",","") : $this->fields["valorcheque"]);
	}

	function setcodcheque($value){
		$this->fields["codcheque"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodbanco($value){
		$this->fields["codbanco"] = value_numeric($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtpagto($value){
		$this->fields["dtpagto"] = value_date($value);
	}

	function setnumcheque($value){
		$this->fields["numcheque"] = value_string($value,10);
	}

	function setnominal($value){
		$this->fields["nominal"] = value_string($value,100);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setvalorcheque($value){
		$this->fields["valorcheque"] = value_numeric($value);
	}
}
?>