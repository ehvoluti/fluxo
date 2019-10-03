<?php
require_once("../class/argox.class.php");
require_once("../class/zebra.class.php");
require_once("../class/zebras4m.class.php");
require_once("../class/datamax.class.php");

class Etiqueta{
	private $printer_name; // Nome da impressora (argox,zebra)
	private $printer; // Classe da impressora (Argox, Zebra)

	function __construct($printer_name){
		$this->printer_name = strtolower($printer_name);
		switch($this->printer_name){
			case "argox":
				$this->printer = new Argox();
				break;
			case "datamax":
				$this->printer = new DataMax();
				break;
			case "zebra":
				$this->printer = new Zebra();
				break;
			case "zebras4m":
				$this->printer = new ZebraS4M();
				break;
		}
	}

    function addbox($posX, $posY, $width, $height, $widX = 0, $widY = 0){
		switch($this->printer_name){
			// A R G O X
			case "argox":
			case "datamax":
                $this->printer->addbox($posX,$posY,$width,$height,$widX,$widY);
                break;
			case "zebra":
				$this->printer->addbox($posX,$posY,2,$width,$height);
				break;
			case  "zebras4m":
				$this->printer->addbox($posX,$posY,2,$width,$height);
				break;
        }
    }

	function addbarcode($orientation,$width,$height,$posX,$posY,$data,$simples=false,$barcode="E30"){
		switch($this->printer_name){
			// A R G O X
			case "argox":
			case "datamax":
				switch($width){
					case 0: $width = "00"; break;
					case 1: $width = "12"; break;
					case 2: $width = "13"; break;
					case 3: $width = "25"; break;
					case 4: $width = "37"; break;
					case 5: $width = "4A"; break;
					case 6: $width = "5C"; break;
					case 7: $width = "6F"; break;
					case 8: $width = "7H"; break;
					case 9: $width = "8K"; break;
				}
				$this->printer->addbarcode(($orientation + 1),$width,$height,$posX,$posY,$data,$simples);

				break;
			// Z E B R A
			case "zebra":
			case "zebras4m":
				if(strlen($data) <= 8 && $barcode == "E30"){
					$barcode = "E80";
				}
				$this->printer->addbarcode($posX,$posY,$orientation,$barcode,$width,"2",$height,"B",$data,$simples);
				break;
		}
	}

    function addline($posX,$posY,$width,$height){
        switch($this->printer_name){
			// A R G O X
			case "argox":
			case "datamax":
                $this->printer->addline($posX,$posY,$width,$height);
                break;

			case "zebra":
				$this->printer->addline($posX,$posY,$width,$height);
				break;
        }
    }

	function addtext($orientation,$font,$width,$height,$posX,$posY,$text){
		switch($this->printer_name){
			// A R G O X
			case "argox":
			case "datamax":
				$arrWH = array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O");
				$width = $arrWH[$width];
				$height = $arrWH[$height];
				$this->printer->addtext(($orientation + 1),$font,$width,$height,$posX,$posY,$text);
				break;

			// Z E B R A
			case "zebra":
			case "zebras4m":
				$this->printer->addtext($posX,$posY,$orientation,$font,$width,$height,"N",$text);
				break;
		}
	}

	function getprintername(){
		return $this->printer_name;
	}

    function setbackfeed($backfeed){
        if(in_array($this->printer_name,array("argox"))){
            $this->printer->setbackfeed($backfeed);
        }
    }

	function setmargin_left($margin_left){
        if(in_array($this->printer_name,array("datamax"))){
            $this->printer->setmargin_left($margin_left);
        }
    }

	function setdensity($density){ // Temperatura
		if(in_array($this->printer_name,array("argox","zebra","datamax"))){
			$this->printer->setdensity($density);
		}
	}

	function setheight($height){
		if(in_array($this->printer_name,array("zebra"))){
			$this->printer->setheight($height);
		}
	}

	function setwidth($width){
		if(in_array($this->printer_name,array("zebra"))){
			$this->printer->setwidth($width);
		}
	}

	function getall(){
		return $this->printer->getall();
	}
}
?>
