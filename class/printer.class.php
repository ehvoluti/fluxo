<?php

class Printer{

	private $con;
	private $codestabelec;
	private $text = "";
	private $arr_text = array();
	private $param_sistema_localetiqueta;

	function __construct($con, $codestabelec){
		$this->con = $con;
		$this->codestabelec = $codestabelec;
		$this->param_sistema_localetiqueta = param("SISTEMA", "LOCALETIQUETA", $this->con);
	}

	function addtext($text, $newline = FALSE){
		if($newline && strlen($this->text) > 0){
			$this->text .= "\r\n";
		}
		$this->text .= $text;
		$this->arr_text[] = $text;
	}

	function gettext(){
		if(strlen(end($this->arr_text)) > 0){
			$this->addtext("\r\n");
		}
		return $this->text;
	}

	function print_on($etiqueta, $printername, $tiposervidor = "N"){
		if(strlen($tiposervidor) == 0){
			$tiposervidor = (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0" ? "L" : "N");
		}

		$extensao = param("SISTEMA", "EXTENSAOETIQUETA", $this->con);
		if(strlen($extensao) == 0){
			switch($etiqueta->getprintername()){
				case "argox": $extensao = "arg";
					break;
				case "zebra": $extensao = "zeb";
					break;
				case "zebras4m": $extensao = "s4m";
					break;
				default: $extensao = "wsp";
					break;
			}
		}

		if(param("INTEGRACAO", "WSFILER", $this->con) === "S"){
			$nome = $printername.uniqid().".".$extensao;
			$conteudo = implode("\r\n", $this->arr_text);
			wsfiler($this->con, $this->codestabelec, $nome, $conteudo);
		}else{
			if($tiposervidor == "L"){
				$arr_parte_text = array_chunk($this->arr_text, 40);
				foreach($arr_parte_text as $x => $parte_text){
					if(count($parte_text) > 0){
						if(strlen(end($parte_text)) > 0){
							$parte_text[] = "";
						}
						if($this->param_sistema_localetiqueta == "0"){
							$dir = dirname(dirname($_SERVER["SCRIPT_FILENAME"]))."/temp/";
							if(!strstr($dir, "usr")){
								$dir = str_replace("/", "\\", $dir);
							}
						}else{
							$dir = $printername;
						}
						$filename = $dir.removeformat(microtime()).$x.".".$extensao;
						$filename = str_replace("\\", "/", $filename);
						$filename = str_replace("[ip_cliente]", $_SERVER["REMOTE_ADDR"], $filename);
						$file = fopen($filename, "w+");
						if($this->param_sistema_localetiqueta == "0"){
							fwrite($file, $printername."\r\n");
						}
						fwrite($file, implode("\r\n", $parte_text));
						fclose($file);
						@chmod($filename, 0777);
					}
				}
			}else{
				$arr_parte_text = array_chunk($this->arr_text, 40);
				foreach($arr_parte_text as $x => $parte_text){
					if(strlen(end($parte_text)) > 0){
						$parte_text[] = "";
					}
					if(strlen(implode("\r\n", $parte_text)) > 0){
						echo write_file($printername."imprimir".$x.".txt", implode("\r\n", $parte_text)."\r\n");
					}
				}
			}
		}
	}

	function savefile(){
		$file_name = "../temp/etiqueta.wsp";
		$file_name = str_replace("/", "\\", $file_name);
		$file = fopen($file_name, "w+");
		fwrite($file, $this->text);
		fclose($file);
		return $file_name;
	}

}
