<?php

class TemporaryLocalFile{

	private $id;
	private $filename;

	function __construct($id){
		$this->id = $id;
		$dirname = __DIR__."/../temp/localfile";
		if(!is_dir($dirname)){
			mkdir($dirname);
		}
		$this->filename = "{$dirname}/{$this->id}_{$_SESSION["WUser"]}.tfl";
	}

	function clear(){
		if(file_exists($this->filename)){
			unlink($this->filename);
		}
		return true;
	}

	function load(){
		if(file_exists($this->filename)){
			$content = file_get_contents($this->filename);
		}else{
			$content = null;
		}
		return unserialize($content);
	}

	function save($content){
		$file = fopen($this->filename, "w+");
		fwrite($file, serialize($content));
		fclose($file);
		return true;
	}

	static function fastClear($id){
		$temporarylocalfile = new TemporaryLocalFile($id);
		return $temporarylocalfile->clear();
	}

	static function fastLoad($id){
		$temporarylocalfile = new TemporaryLocalFile($id);
		return $temporarylocalfile->load();
	}

	static function fastSave($id, $content){
		$temporarylocalfile = new TemporaryLocalFile($id);
		return $temporarylocalfile->save($content);
	}

}