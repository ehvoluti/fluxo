<?php
class Grid{
	private $gridalign;
	private $gridcolumnswidth;
	private $gridfooteralign;
	private $gridheader;
	private $gridrows;
	private $gridfooter;
	private $bottomfooter;
	private $gridrowsatributes;
	private $gridrowsclasses;
	private $gridrowsid;
	private $gridrowsonclick;
	private $gridwidth;
	private $gridcolumnswidthtitle;
	private $num;

	private $color1 = "#FFFFFF";
	private $color2 = "#F2F5F7";
	private $colorhot = "#E0E0E5";

	function __construct(){
		$this->gridalign = array();
		$this->gridcolumnswidth = array();
		$this->gridfooter = array();
		$this->gridfooteralign = array();
		$this->gridheader = array();
		$this->gridrows = array();
		$this->setwidth("100%");
	}

	function addrow($row, $onclick = null, $id = null, $attributes = null, $class = null){
		if(!is_array($row)){
			return FALSE;
		}else{
			$i = sizeof($this->gridrows);
			foreach($row as $value){
				$this->gridrows[$i][] = $value;
			}
			if(is_string($onclick) || $onclick == NULL){
				$this->gridrowsonclick[$i] = $onclick;
			}
			if(is_string($id) || $id == NULL){
				$this->gridrowsid[$i] = $id;
			}
			if(is_string($attributes) || $attributes == NULL){
				$this->gridrowsattributes[$i] = $attributes;
			}
			if(is_string($class) || $class == NULL){
				$this->gridrowsclasses[$i] = (strlen($class) > 0 ? " ".$class : "");
			}
		}
	}

	function draw(){
		//$html = "<table class=\"grid\"".(isset($this->gridwidth) ? " style=\"width:".$this->gridwidth."\"" : "")." CwRowColor1=\"".$this->color1."\" CwRowColor2=\"".$this->color2."\" CwRowColorHot=\"".$this->colorhot."\">";
		$html = "<table class=\"grid\"".(isset($this->gridwidth) ? " style=\"width:".$this->gridwidth.";".(count($this->gridfooter) > 0 ? "margin-bottom:18px" : "")."\"" : "")." CwRowColor1=\"".$this->color1."\" CwRowColor2=\"".$this->color2."\" CwRowColorHot=\"".$this->colorhot."\">";
		if(sizeof($this->gridheader)){
			$html .= "<thead><tr class=\"title\">";
			// $_find_check = procura se existe chebox no header
			$find_check = array();
			foreach($this->gridheader as $i => $header){
				$html .= "<th ".(strlen($this->gridcolumnswidth[$i]) > 0 ? "style=\"width:".$this->gridcolumnswidth[$i]."\"" : "").">".$header."</th>";
				$find = stripos($header,"checkbox");
				$find_check[] = ($find !== FALSE || strlen($header) == 0 ? "\"B\"" : "\"A\"");
			}
			$html .= "</tr></thead>";
		}
		$html .= "<tbody>";
		foreach($this->gridrows as $i => $row){
			$html .= $this->getrowhtml($i);
		}

		if(count($this->gridfooter) > 0){
			if(strlen($this->bottomfooter) == 0){
				$this->bottomfooter = "73px";
			}
			$html .= "<tfoot style=\"position: absolute; margin-left: -2px; bottom: {$this->bottomfooter} \"><tr class=\"title\">";
			foreach($this->gridfooter as $i => $foot){
				$html .= "<td ".(count($this->gridfooteralign) > 0 ? "align=\"{$this->gridfooteralign[$i]}\" " : "")."><div id=\"div_rp_{$foot}\" style=\"height: 100%; width: 100%\" ></div></td>";
			}
			$html .= "</tr></tfoot>";
		}

		$html .= "</tbody>";
		$html .= "</table>";
		if(sizeof($this->gridheader) > 0){
			// Murilo (04/01/2019): comentado porque estava dando problema em uma grada que vinha dentro do ajax e era executado usando o extractScript
			//$html .= script("ordenar_grid([".implode(",",$find_check)."],\"#divgradepesquisa\");");
		}
		return $html;
	}

	function getrowhtml($row){
		if(array_key_exists($row,$this->gridrows) !== FALSE){
			$color = ($row % 2 == 0 ? $this->color1 : $this->color2);
			$num = $this->num += 1;
			$html = "<tr class=\"row{$this->gridrowsclasses[$row]}\""
				.($this->gridrowsid[$row] != NULL ? " id=\"".$this->gridrowsid[$row]."\" " : " ")
				.($this->gridrowsonclick[$row] != NULL ? " onclick=\"".$this->gridrowsonclick[$row]."\" " : " ")
				."onmouseover=\"javascript: alteracor_over(this);\" "
				."onmouseout=\"javascript: alteracor_out(this);\" "
				."onclick=\"javascript: alteracor_click(this);\" "
				."style=\"".($row > 200 && false ? "display:none; " : "")."background-color:".$color.($this->gridrowsonclick[$row] != NULL ? "; cursor:pointer" : "")."\" "
				.$this->gridrowsattributes[$row]." cor=\"".$color."\" alterar=\"true\" num=\"".$this->num."\">";
			foreach($this->gridrows[$row] as $i => $value){
				$hint = str_replace(array('"',"'","<br>"),array("&quot;","&acute;","\n"),$value);
				if(strpos($hint,"<") !== FALSE && strpos($hint,">") !== FALSE){
					$hint = "";
				}elseif(strlen($hint) > 0){
					$hint = (strlen($this->gridheader[$i]) > 0 ? $this->gridheader[$i].":\n" : "").$hint;
					$hint = str_replace("<br>","\n",$hint);
				}
				$html .= "<td ".(sizeof($this->gridalign) > 0 ? " align=\"".$this->gridalign[$i]."\"" : "")." ".(strlen($this->gridcolumnswidth[$i]) > 0 ? "style=\"width:".$this->gridcolumnswidth[$i]."\"" : "")." alt=\"".$hint."\" title=\"".$hint."\"><div style=\"height:100%; width:100%\">".$value."</div></td>";
			}
			$html .= "</tr>";
		}else{
			$html = "<tr class=\"row\"><td>&nbsp;</td></tr>";
		}
//		$html .= script("$.gear()");
		return $html;
	}

	function rowcount(){
		return count($this->gridrows);
	}

	function setcolumnsalign($align){
		if(is_array($align)){
			$this->gridalign = $align;
		}
	}

	function setcolumnswidth($width){
		if(is_array($width)){
			$this->gridcolumnswidth = $width;
		}
	}

	function setheader($header){
		$this->gridheader = array();
		if(!is_array($header)){
			return FALSE;
		}else{
			$this->gridheader = $header;
		}
	}

	function setfooter($footer, $position){
		$this->gridfooter = array();
		if(!is_array($footer)){
			return FALSE;
		}else{
			$this->gridfooter = $footer;
			$this->bottomfooter = $position;
		}
	}

	function setfooteralign($footeralign){
		if(!is_array($footeralign)){
			return FALSE;
		}else{
			$this->gridfooteralign = $footeralign;
		}
	}

	function setwidth($width){
		$this->gridwidth = $width;
	}
}
?>
