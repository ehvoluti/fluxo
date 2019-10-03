<?php

require_once("websac/require_file.php");
require_file("def/function.php");

class LogError{

	static function register($log){
		// Verifica se os diretorios estao criados
		self::verifydir();

		// Remove os logs antigos
		self::removeold();

		// Guarda o nome do arquivo em uma variavel para previnir a mudanca do nome
		$filename = self::filename();

		// Abre o arquivo
		$file = fopen($filename, "w+");

		// Escreve o log
		fwrite($file, $log);

		// Escreve o backtrace
		$arr_backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$arr_backtrace = array_reverse($arr_backtrace);
		fwrite($file, "\r\n\r\n\r\n");
		fwrite($file, "-------------------- BACKTRACE --------------------\r\n\r\n");
		foreach($arr_backtrace as $backtrace){
			foreach($backtrace as $name => $value){
				fwrite($file, "{$name} => {$value}\r\n");
			}
			fwrite($file, "\r\n");
		}

		// Encerra o arquivo
		fclose($file);

		// Da permissoes no arquivo
		chmod($filename, 0777);
	}

	static private function dirname(){
		return __DIR__."/../temp/log/".date("Y-m-d")."/";
	}

	static private function filename(){
		$microtime = microtime(true) * 10000;
		return self::dirname().date("H.i.s")."-".$microtime.".log";
	}

	static private function removeold(){
		$basedir = dirname(self::dirname());
		$arr_dirname = scandir($basedir);
		foreach($arr_dirname as $dirname){
			if(in_array($dirname, array(".", ".."))){
				continue;
			}
			if(strlen($dirname) !== 10){
				continue;
			}
			if(compare_date($dirname, date("Y-m-d", strtotime("-7 days")), "Y-m-d", "<")){
				$arr_filename = scandir("{$basedir}/{$dirname}");
				foreach($arr_filename as $filename){
					if(in_array($filename, array(".", ".."))){
						continue;
					}
					unlink("{$basedir}/{$dirname}/{$filename}");
				}
				rmdir("{$basedir}/{$dirname}");
			}
		}
	}

	static private function verifydir(){
		if(!is_dir(self::dirname())){
			self::mkdir(self::dirname(), 0777, true);
		}
	}

	static private function mkdir($dirname, $mode = 0777, $recursive = true){
		if(is_null($dirname) || $dirname === ""){
			return false;
		}
		if(is_dir($dirname) || $dirname === "/"){
			return true;
		}
		if(self::mkdir(dirname($dirname), $mode, $recursive)){
			if(mkdir($dirname, $mode)){
				chmod($dirname, 0777);
				return true;
			}else{
				return false;
			}
		}
		return false;
	}

}