<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class MapaResumoImposto extends Cadastro{
	function __construct($codmaparesumo = NULL, $tptribicms = NULL, $aliqicms = NULL){
		parent::__construct();
		$this->table = "maparesumoimposto";
		$this->primarykey = array("codmaparesumo", "tptribicms", "aliqicms");
		$this->setcodmaparesumo($codmaparesumo);
		$this->settptribicms($tptribicms);
		$this->setaliqicms($aliqicms);
		if(!is_null($this->getcodmaparesumo()) && !is_null($this->gettptribicms()) && !is_null($this->getaliqicms())){
			$this->searchbyobject();
		}
	}

	function getcodmaparesumo(){
		return $this->fields["codmaparesumo"];
	}

	function gettptribicms(){
		return $this->fields["tptribicms"];
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"],4,",","") : $this->fields["aliqicms"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"],2,",","") : $this->fields["totalliquido"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"],2,",","") : $this->fields["totalicms"]);
	}

	function setcodmaparesumo($value){
		$this->fields["codmaparesumo"] = value_numeric($value);
	}

	function settptribicms($value){
		$this->fields["tptribicms"] = value_string($value,1);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}
}
?>