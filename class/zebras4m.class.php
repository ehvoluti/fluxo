<?php
class ZebraS4M{
	private $lines;
	private $density;
	private $height;
	private $width;

	function __construct(){
		$this->lines = array();
	}

	function addbarcode($posX, $posY, $rotation, $barcode, $narrow, $wide, $height, $readable, $data, $more){
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
		// Remove o digito verificador quando for um PLU
		$data_aux = $data;
		while(substr($data_aux,0,1) == "0"){
			$data_aux = substr($data_aux,1);
		}
		if(strlen($data_aux) < 8){
			$data = strrev(substr(strrev($data),1));
		}

		if($more == "S"){
			$definition = "BCN";
		}elseif(strlen($data) > 8){
			$definition = "BEN";
		}else{
			$definition = "B8N";
		}

		$line  = "^FO{$posX},{$posY}"; // Posicao
		$line .= "^BY{$wide},{$narrow},{$height}"; // Definicao do codigo de barras
		$line .= "^{$definition},{$height},Y,N"; // Definicao das linas do codigo de barras
		$line .= "^FD{$data}^FS"; // Codigo de barras
		$this->lines[] = $line;
	}

	function addtext($posX, $posY, $rotation, $font, $multhor, $multver, $reverse, $data){
		/*
		$posX			Posicao horizontal em pontos
		$posY			Posicao vertical em pontos
		$rotation		Rotacao da imagem (N: 0º, R: 90º, I: 180º, B: 270º)
		$font			Selecao de fonte (1-7)
		$multhor		Multiplicador de largura
		$multver		Multiplicador de altura
		$reverse		Inverte a imagem/texto (N: Normal, R: Inverso)
		$data			Texto
		*/
		switch($rotation){
			case "0": $rotation = "N"; break;
			case "1": $rotation = "R"; break;
			case "2": $rotation = "I"; break;
			case "3": $rotation = "B"; break;
		}
		$line  = "^FO".$posX.",".$posY; // Posicao
		$line .= "^A".$font.$rotation.",".$multver.",".$multhor; // Definicao do texto
		$line .= "^FD".$data."^FS"; // Texto
		$this->lines[] = $line;
	}

	function getall(){
		$lines = array();
		$lines[] = "^XA"; // Inicia a etiqueta
		$lines[] = "^PW".$this->width; // Largura da etiqueta
		$lines[] = "^PRB^FS"; // Velocidade de impressao
		$lines[] = "^CF0,100,100";
		$lines = array_merge($lines,$this->lines);
		$lines[] = "^XZ"; // Encerra a etiqueta
		return implode("\r\n",$lines);
	}

	function addbox($posX,$posY,$linha,$width,$height){
		$line  = "^FO".$posX.",".$posY; // Posicao
		$line .= "^GB".$width.",".$height.",".$linha.",B,0^FS";
		$this->lines[] = $line;
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