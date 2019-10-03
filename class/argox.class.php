<?php

class Argox{

	private $lines;
	private $density;
	private $backfeed = 220;

	function __construct(){
		$this->lines = array();
	}

	function addbarcode($orientation, $width, $height, $posX, $posY, $data, $simples = false){
		/*
		  $width		Multiplicador de largura (00, 12, 13, 25, 37, 4A, 5C, 6F, 7H, 8K)
		  $height		Multiplicador de altura (0-999)
		  $posX		Posicao horizontal em pontos
		  $posY		Posicao vertical em pontos
		  $data		Numero do codigo de barras
		  orientation Orientacao do texto
		  possiveis valores:
		  1 - Normal
		  2 - Texto virado 90 graus anti-horario
		  3 - Texto virado 180 graus anti-horario
		  4 - Texto virado 270 graus anti-horario
		  valor padrao:
		  1 - Normal
		 */
		// Faz tratamento especifico para argox
		if(strlen(ltrim($data, "0")) < 8 && !$simples){
			$data = strrev(substr(strrev($data), 1));
		}
		$orientation = str_pad(substr($orientation, 0, 1), 1, "1", STR_PAD_LEFT);
		$width = str_pad(substr($width, 0, 2), 2, "0", STR_PAD_LEFT);
		$height = str_pad(substr($height, 0, 3), 3, "0", STR_PAD_LEFT);
		$posX = str_pad(substr($posX, 0, 4), 4, "0", STR_PAD_LEFT);
		$posY = str_pad(substr($posY, 0, 4), 4, "0", STR_PAD_LEFT);
		if($simples == "cpfcnpj"){
			$format = "E";
		}elseif(!$simples){
			if(strlen($data) > 8){
				$format = "F";
				$data = str_pad($data, 13, "0", STR_PAD_LEFT);
			}else{
				$format = "G";
			}
		}else{
			if(strlen($data) == 13){
				$format = "E";
			}else{
				$format = "O";
			}
		}

		$this->lines[] = $orientation.$format.$width.$height.$posY.$posX.$data;
	}

	function addbox($posX, $posY, $width, $height, $widX, $widY){
		/*
		  posX - posicao horizontal do texto
		  posiveis valores:
		  de 0 a 9999
		  posY - posicao vertical do texto
		  posiveis valores:
		  de 0 a 9999
		  width - largura do texto
		  possiveis valores:
		  de 0 a 999
		  height - altura do texto
		  possiveis valores:
		  de 0 a 999
		  widX - largura das linhas horizontais
		  possiveis valores:
		  de 0 a 999
		  widY - largura das linhas verticais
		  possiveis valores:
		  de 0 a 999
		 */
		$posX = str_pad(substr($posX, 0, 4), 4, "0", STR_PAD_LEFT);
		$posY = str_pad(substr($posY, 0, 4), 4, "0", STR_PAD_LEFT);
		$width = str_pad(substr($width, 0, 3), 3, "0", STR_PAD_LEFT);
		$height = str_pad(substr($height, 0, 3), 3, "0", STR_PAD_LEFT);
		$widX = str_pad(substr($widX, 0, 3), 3, "0", STR_PAD_LEFT);
		$widY = str_pad(substr($widY, 0, 3), 3, "0", STR_PAD_LEFT);
		$this->lines[] = "1X11000".$posY.$posX."B".$width.$height.$widX.$widY;
	}

	function addimage($posX, $posY, $file_name){
		/*
		  posX - posicao horizontal do texto
		  posiveis valores:
		  de 0 a 9999
		  posY - posicao vertical do texto
		  posiveis valores:
		  de 0 a 9999
		  file_name - nome do arquivo da imagem
		 */
		$posX = str_pad(substr($posX, 0, 4), 4, "0", STR_PAD_LEFT);
		$posY = str_pad(substr($posY, 0, 4), 4, "0", STR_PAD_LEFT);
		$this->lines[] = "1X11000".$posY.$posX.$file_name;
	}

	function addline($posX, $posY, $width, $height){
		/*
		  posX - posicao horizontal do texto
		  posiveis valores:
		  de 0 a 9999
		  posY - posicao vertical do texto
		  posiveis valores:
		  de 0 a 9999
		  width - largura do texto
		  possiveis valores:
		  de 0 a 999
		  height - altura do texto
		  possiveis valores:
		  de 0 a 999
		 */
		$posX = str_pad(substr($posX, 0, 4), 4, "0", STR_PAD_LEFT);
		$posY = str_pad(substr($posY, 0, 4), 4, "0", STR_PAD_LEFT);
		$width = str_pad(substr($width, 0, 3), 3, "0", STR_PAD_LEFT);
		$height = str_pad(substr($height, 0, 3), 3, "0", STR_PAD_LEFT);
		$this->lines[] = "1X11000".$posY.$posX."L".$width.$height;
	}

	function addtext($orientation, $font, $width, $height, $posX, $posY, $text){
		/*
		  text - Texto que sera impresso
		  width - largura do texto
		  possiveis valores:
		  de 0 a O (letra o)
		  height - altura do texto
		  possiveis valores:
		  {igual a largura do texto (width)}
		  posX - posicao horizontal do texto
		  posiveis valores:
		  de 0 a 9999
		  posY - posicao vertical do texto
		  posiveis valores:
		  {igual a posisao horizontal do texto (posX)}
		  orientation - Orientacao do texto
		  possiveis valores:
		  1 - Normal
		  2 - Texto virado 90 graus anti-horario
		  3 - Texto virado 180 graus anti-horario
		  4 - Texto virado 270 graus anti-horario
		  font - Tipo de fonte a ser usada
		  possiveis valores:
		  0, 1, 2, 3, 4, 5, 6, 7, 8
		  valor padrao:
		  NULL - Pegara da variavel font (da classe), onde pode ser atribuida atravez da funcao setfont()
		 */
		$orientation = str_pad(substr($orientation, 0, 1), 1, "1", STR_PAD_LEFT);
		$font = str_pad(substr($font, 0, 1), 1, "0", STR_PAD_LEFT);
		$width = str_pad(substr($width, 0, 1), 1, "0", STR_PAD_LEFT);
		$height = str_pad(substr($height, 0, 1), 1, "0", STR_PAD_LEFT);
		$posX = str_pad(substr($posX, 0, 4), 4, "0", STR_PAD_LEFT);
		$posY = str_pad(substr($posY, 0, 4), 4, "0", STR_PAD_LEFT);
		$this->lines[] = $orientation.$font.$width.$height."000".$posY.$posX.$text;
	}

	function getall(){
		$text = array();
		$text[] = chr("002")."f".$this->backfeed; // avanco para corte da etiqueta
		$text[] = chr("002")."KI"; // modo de impressao
		$text[] = chr("002")."L"; // comando de entrada de etiqueta
		$text[] = "D11"; // largura e altura do pixel
		$text[] = "H".str_pad(substr($this->density, 0, 2), 2, "0", STR_PAD_LEFT); // temperatura de impressao
		$text[] = "PC"; // velocidade de impressao (A,B,C,D)
		$text[] = "Q0001"; // numero de copias
		foreach($this->lines as $line){ // percorre todas a linhas da etiqueta
			$text[] = $line;
		}
		$text[] = "E"; // encerra o arquivo
		return implode("\r\n", $text); // retorna o texto que compoe a etiqueta
	}

	function setbackfeed($backfeed){
		/*
		  $backfeed		Avanco para corte da etiqueta (para evitar disperdicio)
		 */
		$this->backfeed = $backfeed;
	}

	function setdensity($density){
		/*
		  $density		Temperatura da impressao
		 */
		$this->density = $density;
	}

}