<?php
class Zebra{
	private $lines;
	private $density;
	private $height;
	private $width;

	function __construct(){
		$this->lines = array();
	}

	function addbarcode($posX, $posY, $rotation, $barcode, $narrow, $wide, $height, $readable, $data){
		/*
		$posX			Posicao horizontal em pontos
		$posY			Posicao vertical em pontos
		$rotation		Rotacao da imagem (0: 0º, 1: 90º, 2: 180º, 3: 270º)
		$barcode		Selecao de codigo de barras RSS-14 (R14, RL, RS, RT)
		$narrow			Largura das barras (1-10)
		$wide			Valor obrigatorio: 2
		$height			Altura do codigo de barras em pontos
		$readable		Imprime o codigo de barras com os numeros em baixo (B: Sim, N: Nao)
		$data			Codigo de barras
		*/
		$this->lines[] = "B".$posX.",".$posY.",".$rotation.",".$barcode.",".$narrow.",".$wide.",".$height.",".$readable.",\"".$data."\"";
	}
	function addline($posX, $posY, $width, $height){
		//$this->lines[] = "LO50,200,400,20";
		//$this->lines[] = "LO200,50,20,400";
		$this->lines[] = "LO".$posX.",".$posY.",".$width.",".$height;
	}

	function addbox($posX,$posY,$line,$width,$height){
		//$text  = "N\r\n"; // Limpa o buffer
		$text = "X".$posX.",".$posY.",".$line.",".$width.",".$height;
		//$text = "X50,200,5,400,20";
		//$text .= "P1";
		$this->lines[] = $text;
		//$this->lines[] = "X50,200,5,400,20";
	}

	function addtext($posX, $posY, $rotation, $font, $multhor, $multver, $reverse, $data){
		/*
		$posX			Posicao horizontal em pontos
		$posY			Posicao vertical em pontos
		$rotation	Rotacao da imagem (0: 0º, 1: 90º, 2: 180º, 3: 270º)
		$font			Selecao de fonte (1-7)
		$multhor		Multiplicador de largura
		$multver		Multiplicador de altura
		$reverse		Inverte a imagem/texto (N: Normal, R: Inverso)
		$data			Texto
		*/
		$this->lines[] = "A".$posX.",".$posY.",".$rotation.",".$font.",".$multhor.",".$multver.",".$reverse.",\"".$data."\"";
	}

	function getall(){
		$text  = "N\r\n"; // Limpa o buffer
//		$text .= "Q".str_pad($this->width,4,"0",STR_PAD_LEFT).",".str_pad($this->height,4,"0",STR_PAD_LEFT)."\r\n"; // Largura e altura do formulario
		$text .= "R0001,0001\r\n"; // Ponto de referencia
		$text .= "D".$this->density."\r\n"; // Temperatura da impressao
		$text .= "S4\r\n"; // Velocidade
/*		if(isset($this->width)){
			$text .= "q".$this->width."\r\n"; // Largura da etiqueta
		}*/
		$text .= implode("\r\n",$this->lines)."\r\n";
		$text .= "P1";
		return $text;
	}

	function setdensity($density){
		/*
		$density		Temperatura da impressao
		*/
		$this->density = $density;
	}

	function setheight($height){
		/*
		$height		Altura em pontos da etiqueta (area a ser impressa)
		*/
		$this->height = $height;
	}

	function setwidth($width){
		/*
		$width		Largura em pontos da etiqueta (area a ser impressa)
		*/
		$this->width = $width;
	}
}
?>
