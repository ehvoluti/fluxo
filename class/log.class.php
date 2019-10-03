<?php

class Log{

	private $file;
	private $filename;

	function __construct($name){
		$this->filename = "../temp/{$name}.log";
		$this->open();
	}

	function __destruct(){
		$this->close();
	}

	function clear(){
		$this->close();
		if(file_exists($this->filename)){
			unlink($this->filename);
		}
		$this->open();
	}

	private function close(){
		fclose($this->file);
		if(file_exists($this->filename)){
			chmod($this->filename, 0777);
		}
	}

	private function open(){
		$this->file = fopen($this->filename, "a+");
	}

	function write($text){
		fwrite($this->file, date("d/m/Y H:i:s")." {$text}\r\n");
	}

	static function fastClear($name){
		$log = new Log($name);
		$log->clear();
		$log->close();
	}

	static function fastWrite($name, $text){
		$log = new Log($name);
		$log->write($text);
		$log->close();
	}

}
