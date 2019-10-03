<?php

require_file("lib/fpdf-1.8.1/fpdf.php");

if(strpos($_SERVER["SCRIPT_FILENAME"], "/mobile/") === FALSE){
	define("FPDF_FONTPATH", "../lib/fpdf-1.8.1/font/");
}else{
	define("FPDF_FONTPATH", "../../lib/fpdf-1.8.1/font/");
}

class PDF extends FPDF{

	private $con;
	private $codestabelec;
	private $header = TRUE;
	private $footer = TRUE;
	private $report;
	private $orientation;

	function __construct($con = NULL, $codestabelec = NULL, $orientation = "P", $header = TRUE, $footer = TRUE){
		session_start();
		$this->footer = $footer;
		$this->orientation = $orientation;
		if(is_object($con)){
			$this->con = $con;
		}else{
			$this->con = new Connection();
		}
		$this->codestabelec = value_numeric($codestabelec);
		if(is_bool($header)){
			$this->header = $header;
		}
		return parent::__construct($orientation, "mm", "A4");
	}

	function Cell($w, $h = 0, $txt = "", $border = 0, $ln = 0, $align = "", $fill = FALSE, $link = ""){
		if($w > 0){
			$txt = $this->WordWrap($txt, $w);
		}
		return parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
	}

	function Footer(){
		if($this->footer == TRUE){
			$this->AliasNbPages("{nb}");
			$this->SetFont($font, "", 7);
			$this->SetY(-15);
			$this->Cell(0, 10, "Página ".$this->PageNo()." de {nb}", 0, 0, "C");
		}
	}

	function Header($force_report = FALSE){
		if($this->header){
			$param_sistema_logotipo = param("SISTEMA", "LOGOTIPO", $this->con);
			$param_sistema_dadosimppdf = param("SISTEMA", "DADOSIMPPDF", $this->con);
			$param_relatorio_modelocabecalho = param("RELATORIO", "MODELOCABECALHO", $this->con);

			$usuario = objectbytable("usuario", $_SESSION["WUser"], $this->con);

			if(is_null($this->codestabelec)){
				$query = "SELECT codemitente ";
				$query .= "FROM usuaestabel ";
				$query .= "INNER JOIN estabelecimento ON (usuaestabel.codestabelec = estabelecimento.codestabelec) ";
				$query .= "WHERE usuaestabel.login = '".$usuario->getlogin()."' ";
				$query .= "LIMIT 1 ";

				$res = $this->con->query($query);
				$emi = $res->fetch();

				$e = objectbytable("emitente", $emi["codemitente"], $this->con);
			}else{
				$e = objectbytable("estabelecimento", $this->codestabelec, $this->con);
			}
			$cidade = objectbytable("cidade", $e->getcodcidade(), $this->con);

			if(strlen($param_sistema_logotipo) == 0){
				$param_sistema_logotipo = $e->getlocallogotipo();
			}

			switch($param_relatorio_modelocabecalho){
				case 2:
					$font_family = "Arial";
					$font_size = 7;
					$font_size_title = 9;
					$logo_size = 12;
					$logo_border = 2;
					$height = 3;
					$margem_top = 0;
					break;
				default :
					$font_family = "Arial";
					$font_size = 8;
					$font_size_title = 14;
					$logo_size = 25;
					$logo_border = 5;
					$height = 4;
					$margem_top = 4;
			}

			$this->SetFont($font_family, "B", 14);
			$this->SetTextColor(0, 0, 0);
			$y = $this->GetY();
			if(file_exists($param_sistema_logotipo)){
				$this->Image($param_sistema_logotipo, NULL, NULL, $logo_size, $logo_size);
			}

			$this->SetY($y + $margem_top);
			$this->SetFont($font_family, "B", $font_size_title);
			$this->Cell($logo_size + $logo_border, 3);
			$this->Cell(120, 3, $e->getnome());
			$this->SetFont($font_family, "", $font_size);
			$this->Cell(0, 3, date("d/m/Y"), 0, 1, "R");

			$this->SetFont($font_family, "", $font_size);
			$this->Cell($logo_size + $logo_border, 4);
			$this->Cell(0, $height, $e->getendereco().", ".$e->getnumero()."  -  ".$e->getbairro()."  -  ".$cidade->getnome()."  -  ".$cidade->getuf()."  -  CEP: ".$e->getcep(), 0, 1);

			$this->Cell($logo_size + $logo_border, 4);
			$this->Cell(50, $height, "Tel: ".$e->getfone1());
			$this->Cell(0, $height, "Fax: ".$e->getfax(), 0, 1);

			$this->Cell($logo_size + $logo_border, 4);
			$this->Cell(100, $height, "E-mail: ".($param_sistema_dadosimppdf == 0 ? $e->getemail() : $usuario->getemail()));
			$this->Cell(0, $height, $usuario->getnome(), 0, 1, "R");

			$this->SetY($logo_size + 13);
		}

		if($this->PageNo() == 1){
			$relatorio = objectbytable("relatorio", $_REQUEST["codrelatorio"], $this->con);
			$instrucaocabecalho = $relatorio->getinstrucaocabecalho();
			if(strlen($relatorio->getinstrucaocabecalho()) > 0){
				$filtro = $_REQUEST["filtro"];
				foreach($filtro AS $i => $f){
					if(strlen($f) < 1)
						break;
					$instrucaocabecalho = str_replace('@'.$i, $f, $instrucaocabecalho);
				}
				if(strlen($f) > 0){
					$res = $this->con->query($instrucaocabecalho);
					$_arr = $res->fetchAll(2);
					$texto = "";
					foreach($_arr[0] AS $row){
						$texto .= $row."\n";
					}

					$this->Cell(0, 8, NULL, 0, 1);
					$this->SetTextColor(0, 0, 0);
					$this->SetFont("Arial");
					$this->MultiCell(0, 4, $texto);
					$this->Cell(0, 8, NULL, 0, 1);
				}
			}
		}

		if((is_object($this->report) && in_array($this->report->getformat(), array("pdf1", "pdf2"))) || $force_report){
			$this->SetFillColor(255, 255, 255);
			$this->SetTextColor(0, 0, 0);

			// Titulo do relatorio
			$this->SetFont("Arial", "", 10);
			$this->Cell(0, 4, $this->report->gettitle(), 0, 1);

			// Subtitulo do relatorio
			if(strlen($this->report->getsubtitle()) > 0){
				$this->SetFont("Arial", "", 8);
				$this->MultiCell(0, 4, $this->report->getsubtitle(), 0);
			}

			// Data de emissao do relatorio
			$this->SetY($this->GetY() - 4);
			$this->SetFont("Arial", "", 6);
			$this->Cell(0, 4, "(Emitido em ".date("d/m/Y")." as ".date("H:i:s").")", 0, 1, "R");

			// Percorre uma vez as colunas para criao dos titulos
			$bgcolor = $this->report->getbgcolor("label");
			$fontcolor = $this->report->getfontcolor("label");
			$this->report->html .= "<tr style=\"background:".stringrgb($bgcolor)."; color:".stringrgb($fontcolor)."\">";
			$this->SetFont("Arial", "B", $this->fontsize_pdf);
			$this->SetFillColor($bgcolor[0], $bgcolor[1], $bgcolor[2]);
			$this->SetTextColor($fontcolor[0], $fontcolor[1], $fontcolor[2]);
			$table = $this->report->gettable();
			$arr_column = $this->report->getcolumns();
			foreach($table[0] as $col => $val){
				if($arr_column[$col]->getvisible()){
					$label = $arr_column[$col]->getlabel();
					$width = $arr_column[$col]->getwidth();
					$this->report->html .= "<td style=\"overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:0px ".$this->report->getpaddingcolumn()."px; width:".$width."\"><b>".$label."</b></td>";
					$this->Cell(pdfwidth($this->report->getorientation(), $width), $this->report->getrowheight(), $label, 1, 0, "L", TRUE);
				}
			}
			$this->report->html .= "</tr>";
			$this->SetY($this->GetY() + $this->report->getrowheight());
		}

		return TRUE;
	}

	function setreport($report){
		$this->report = $report;
	}

	// Funcao criada para nao o texto nao estourar o tamanho da celula
	function WordWrap($text, $width){
		$aux = "";
		$total = 0;
		for($i = 0; $i < strlen($text); $i++){
			$char = substr($text, $i, 1);
			$total += $this->GetStringWidth($char);
			if($total < $width){
				$aux .= $char;
			}else{
				break;
			}
		}
		return $aux;
	}

	function RoundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));

        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));
        if (strpos($corners, '2')===false)
            $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k,($hp-$y)*$k ));
        else
            $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        if (strpos($corners, '3')===false)
            $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        if (strpos($corners, '4')===false)
            $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        if (strpos($corners, '1')===false)
        {
            $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$y)*$k ));
            $this->_out(sprintf('%.2F %.2F l',($x+$r)*$k,($hp-$y)*$k ));
        }
        else
            $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
	}

	function Rotate($angle,$x=-1,$y=-1)
	{
		if($x==-1)
			$x=$this->x;
		if($y==-1)
			$y=$this->y;
		if($this->angle!=0)
			$this->_out('Q');
		$this->angle=$angle;
		if($angle!=0)
		{
			$angle*=M_PI/180;
			$c=cos($angle);
			$s=sin($angle);
			$cx=$x*$this->k;
			$cy=($this->h-$y)*$this->k;
			$this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
		}
	}

	function _endpage()
	{
		if($this->angle!=0)
		{
			$this->angle=0;
			$this->_out('Q');
		}
		parent::_endpage();
	}

	function RotatedText($x,$y,$txt,$angle)
	{
		//Text rotated around its origin
		$this->Rotate($angle,$x,$y);
		$this->Text($x,$y,$txt);
		$this->Rotate(0);
	}

	function RotatedImage($file,$x,$y,$w,$h,$angle)
	{
		//Image rotated around its upper-left corner
		$this->Rotate($angle,$x,$y);
		$this->Image($file,$x,$y,$w,$h);
		$this->Rotate(0);
	}

}