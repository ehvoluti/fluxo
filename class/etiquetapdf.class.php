<?php

require_once("../fpdf/fpdf.php");

class EtiquetaPdf{

	private $pdf;
	private $heigth;
	private $width;
	private $text = array();
	private $barcode = array();

	function __construct(){

	}

	function addtext($size, $posX, $posY, $text){
		$this->text[] = array(
			"size" => $size,
			"posX" => $posX,
			"posY" => $posY,
			"text" => $text
		);
	}

	function addbarcode($width, $height, $posX, $posY, $barcode){
		$this->barcode[] = array(
			"width" => $width,
			"height" => $height,
			"posX" => $posX,
			"posY" => $posY,
			"barcode" => $barcode
		);
	}

	function setwidth($value){
		$this->width = $value;
	}

	function setheight($value){
		$this->height = $value;
	}

	function getall(){
		$this->pdf = new FPDF("P", "mm", array($this->width, $this->height));
		$this->pdf->SetTitle("Etiquetas");
		$this->pdf->SetMargins(0, 0, 0);
		$this->pdf->SetFont("Arial", "B", 16);
		$this->pdf->SetTextColor(0, 0, 0);
		foreach($this->text as $text){
			$this->pdf->SetFontSize($text["size"]);
			$this->pdf->SetX($text["posX"]);
			$this->pdf->Write($text["posY"] + $text["size"] / 3.4, $text["text"]);
		}
		foreach($this->barcode as $barcode){
			$this->EAN13($barcode["posX"], $barcode["posY"], $barcode["barcode"], $barcode["height"], $barcode["width"]);
		}
		$this->pdf->Output("Etiquetas.pdf", "I");
	}

	/*	 * *********************************
	  F U N C O E S   D A   I N T E R N E T
	 * ********************************* */

	function EAN13($x, $y, $barcode, $h = 16, $w = .35){
		$this->Barcode($x, $y, $barcode, $h, $w, 13);
	}

	function UPC_A($x, $y, $barcode, $h = 16, $w = .35){
		$this->Barcode($x, $y, $barcode, $h, $w, 12);
	}

	function GetCheckDigit($barcode){
		$sum = 0;
		for($i = 1; $i <= 11; $i += 2){
			$sum += 3 * $barcode{$i};
		}
		for($i = 0; $i <= 10; $i+=2){
			$sum += $barcode{$i};
		}
		$r = $sum % 10;
		if($r > 0){
			$r = 10 - $r;
		}
		return $r;
	}

	function TestCheckDigit($barcode){
		$sum = 0;
		for($i = 1; $i <= 11; $i += 2){
			$sum += 3 * $barcode{$i};
		}
		for($i = 0; $i <= 10; $i += 2){
			$sum += $barcode{$i};
		}
		return ($sum + $barcode{12}) % 10 == 0;
	}

	function Barcode($x, $y, $barcode, $h, $w, $len){
		$barcode = str_pad($barcode, $len - 1, "0", STR_PAD_LEFT);
		if($len == 12){
			$barcode = "0".$barcode;
		}
		if(strlen($barcode) == 12){
			$barcode .= $this->GetCheckDigit($barcode);
		}elseif(!$this->TestCheckDigit($barcode)){
			$this->Error("Incorrect check digit");
		}
		$codes = array(
			"A" => array(
				"0" => "0001101", "1" => "0011001", "2" => "0010011", "3" => "0111101", "4" => "0100011",
				"5" => "0110001", "6" => "0101111", "7" => "0111011", "8" => "0110111", "9" => "0001011"
			),
			"B" => array(
				"0" => "0100111", "1" => "0110011", "2" => "0011011", "3" => "0100001", "4" => "0011101",
				"5" => "0111001", "6" => "0000101", "7" => "0010001", "8" => "0001001", "9" => "0010111"
			),
			"C" => array(
				"0" => "1110010", "1" => "1100110", "2" => "1101100", "3" => "1000010", "4" => "1011100",
				"5" => "1001110", "6" => "1010000", "7" => "1000100", "8" => "1001000", "9" => "1110100"
			)
		);
		$parities = array(
			"0" => array("A", "A", "A", "A", "A", "A"),
			"1" => array("A", "A", "B", "A", "B", "B"),
			"2" => array("A", "A", "B", "B", "A", "B"),
			"3" => array("A", "A", "B", "B", "B", "A"),
			"4" => array("A", "B", "A", "A", "B", "B"),
			"5" => array("A", "B", "B", "A", "A", "B"),
			"6" => array("A", "B", "B", "B", "A", "A"),
			"7" => array("A", "B", "A", "B", "A", "B"),
			"8" => array("A", "B", "A", "B", "B", "A"),
			"9" => array("A", "B", "B", "A", "B", "A")
		);
		$code = "101";
		$p = $parities[$barcode{0}];
		for($i = 1; $i <= 6; $i++){
			$code .= $codes[$p[$i - 1]][$barcode{$i}];
		}
		$code.="01010";
		for($i = 7; $i <= 12; $i++){
			$code.=$codes["C"][$barcode{$i}];
		}
		$code.="101";
		for($i = 0; $i < strlen($code); $i++){
			if($code{$i} == "1"){
				$this->pdf->Rect($x + $i * $w, $y, $w, $h, "F");
			}
			$this->pdf->SetFont("Arial", "", 12);
			$this->pdf->Text($x, $y + $h + 11 / $this->pdf->k, substr($barcode, -$len));
		}
	}

}
