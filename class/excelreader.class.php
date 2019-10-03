<?php

require_once("../class/reader.class.php");

class ExcelReader{

	private $reader;
	private $sheet;
	public $data;

	function __construct($file_name){
		$this->reader = new Spreadsheet_Excel_Reader();
		$this->reader->setUTFEncoder("iconv");
		$this->reader->setOutputEncoding("UTF-8");
		$this->reader->read($file_name);
	}

	function setsheet($sheet){
		$this->data = array(array());
		$this->sheet = $sheet;
		$this->reader->boundsheets[$this->sheet];
		$table = $this->reader->sheets[$this->sheet]["cells"];
		foreach($table as $i => $row){
			$arr = array();
			foreach($row as $j => $cell){
				$arr[$j] = $cell;
			}
			$this->data[$i] = $arr;
		}
	}

}