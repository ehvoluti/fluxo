<?php
class Chart{
	private $height;
	private $width;
	private $keys;
	private $labels;
	private $values;

	function __construct($height = "",$width = ""){
		$this->height = $height;
		$this->width = $width;
	}
	
	function setlabels($labels){
		if(is_array($labels)){
			$this->labels = $labels;
			foreach($this->labels as $i => $label){
				$this->labels[$i] = "\"".$label."\"";
			}
		}
	}
	
	function setvalues($key,$values){
		if(is_array($values)){
			foreach($values as $i => $value){
				$values[$i] = "\"".$value."\"";
			}
			$this->values[$key] = $values;
		}
	}
	
	function draw(){
		$keys = array();
		foreach($this->values as $key => $values){
			$keys[] = "\"".$key."\":[".implode(",",$values)."]";
		}
		$id = "__canvas_chart_".rand(0,1000);
		$html  = "<canvas id=\"".$id."\" height=\"".$this->height."\" width=\"".$this->width."\"></canvas> ";
		$html .= "<script type=\"text/javascript\"> ";
		$html .= "$(\"#".$id."\").chart({ ";
		$html .= "	labels:[".implode(",",$this->labels)."], ";
		$html .= "	values:({".implode(",",$keys)."}) ";
		$html .= "}); ";
		$html .= "</script> ";
		return $html;
	}
}
?>