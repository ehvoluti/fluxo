<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ParamPontoVenda extends Cadastro{

   public $arr_parampontovendausuario;
   protected $flag_parampontovendausuario = false;

   function __construct($codestabelec = NULL){
      parent::__construct();
      $this->table = "parampontovenda";
      $this->primarykey = array("codestabelec");
      $this->setcodestabelec($codestabelec);
      if(!is_null($this->getcodestabelec())){
         $this->searchbyobject();
      }
   }

   function flag_parampontovendausuario($value){
      if(is_bool($value)){
         $this->flag_parampontovendausuario = $value;
      }
   }

   function save($object = null){
      $this->connect();
      $this->con->start_transaction();

      if(!parent::save($object)){
         $this->con->rollback();
         return false;
      }

      if($this->flag_parampontovendausuario){
         $parampontovendausuario = objectbytable("parampontovendausuario", null, $this->con);
         $parampontovendausuario->setcodestabelec($this->getcodestabelec());
         $arr_parampontovendausuario = object_array($parampontovendausuario);
         foreach($arr_parampontovendausuario as $parampontovendausuario){
            if(!$parampontovendausuario->delete()){
               $this->con->rollback();
               return false;
            }
         }
         foreach($this->arr_parampontovendausuario as $parampontovendausuario){
            $parampontovendausuario->setcodestabelec($this->getcodestabelec());
            if(!$parampontovendausuario->save()){
               $this->con->rollback();
               return false;
            }
         }
      }

      $this->con->commit();
      return true;
   }

   function searchatdatabase($query, $fetchAll = FALSE){
      $return = parent::searchatdatabase($query, $fetchAll);
      if($return !== false && count($return) === 1 && !$fetchAll){
         if($this->flag_parampontovendausuario){
            $parampontovendausuario = objectbytable("parampontovendausuario", NULL, $this->con);
            $parampontovendausuario->setcodestabelec($this->getcodestabelec());
            $this->arr_parampontovendausuario = object_array($parampontovendausuario);
         }
      }
      return $return;
   }

   function getfieldvalues(){
		parent::getfieldvalues();

		$temporary = new Temporary("parampontovenda_parampontovendausuario", false);
		$this->arr_parampontovendausuario = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$parampontovendausuario = objectbytable("parampontovendausuario", NULL, $this->con);
			$parampontovendausuario->setcodestabelec($this->getcodestabelec());
			$parampontovendausuario->setlogin($temporary->getvalue($i, "login"));
			$parampontovendausuario->setlocalimpressora($temporary->getvalue($i, "localimpressora"));
			$this->arr_parampontovendausuario[] = $parampontovendausuario;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		$temporary = new Temporary("parampontovenda_parampontovendausuario", true);
		$temporary->setcolumns(array("login", "localimpressora"));
		foreach($this->arr_parampontovendausuario as $parampontovendausuario){
			$temporary->append();
			$temporary->setvalue("last", "login", $parampontovendausuario->getlogin());
			$temporary->setvalue("last", "localimpressora", $parampontovendausuario->getlocalimpressora());
		}
		$temporary->save();

		return $html;
	}

   function getbloqvalunitario(){
      return $this->fields["bloqvalunitario"];
   }

   function getcodestabelec(){
      return $this->fields["codestabelec"];
   }

   function gettipovenda(){
      return $this->fields["tipovenda"];
   }

   function getnatoperacao(){
      return $this->fields["natoperacao"];
   }

   function getlocalimpressora(){
      return $this->fields["localimpressora"];
   }

   function gettextovias(){
      return $this->fields["textovias"];
   }

   function gettextoviasorc(){
      return $this->fields["textoviasorc"];
   }

   function gettituloimpresso(){
      return $this->fields["tituloimpresso"];
   }

   function getsaltofinal(){
      return $this->fields["saltofinal"];
   }

   function getimpendereco(){
      return $this->fields["impendereco"];
   }

   function getcodtabela(){
      return $this->fields["codtabela"];
   }

   function getimprimedocumento(){
      return $this->fields["imprimedocumento"];
   }

   function gettiposervidor(){
      return $this->fields["tiposervidor"];
   }

   function getimpnomecliente(){
      return $this->fields["impnomecliente"];
   }

   function getimpcpfcnpj(){
      return $this->fields["impcpfcnpj"];
   }

   function getimptelefonecli(){
      return $this->fields["imptelefonecli"];
   }

   function getimprimeorcamento(){
      return $this->fields["imprimeorcamento"];
   }

   function gettiporecibo(){
      return $this->fields["tiporecibo"];
   }

   function getgeraprevenda(){
      return $this->fields["geraprevenda"];
   }

   function getgerafinanceiro(){
      return $this->fields["gerafinanceiro"];
   }

   function getgeratroco(){
      return $this->fields["geratroco"];
   }

   function getsolicitavendedor(){
      return $this->fields["solicitavendedor"];
   }

   function getimpestabelecimento(){
      return $this->fields["impestabelecimento"];
   }

   function getimpusuariologado(){
      return $this->fields["impusuariologado"];
   }

   function getprodsemlimite(){
      return $this->fields["prodsemlimite"];
   }

   function getsupervisor(){
      return $this->fields["supervisor"];
   }

   function getindpres(){
      return $this->fields["indpres"];
   }

   function getordemitem(){
      return $this->fields["ordemitem"];
   }

   function getdectovalorperc(){
      return $this->fields["dectovalorperc"];
   }

   function getmostrarendereco(){
      return $this->fields["mostrarendereco"];
   }

   function getenviapesadoprevenda(){
      return $this->fields["enviapesadoprevenda"];
   }

   function getorcgeraatendido(){
      return $this->fields["orcgeraatendido"];
   }

   function getpercdescsemsuper($format = true){
	  return ($format ? number_format($this->fields["percdescsemsuper"],2,",","") : $this->fields["percdescsemsuper"]);
   }

   function getdadosfornec(){
      return $this->fields["dadosfornec"];
   }

   function getobservacaopedorc(){
       return $this->fields["observacaopedorc"];
   }

   function getformapagto(){
       return $this->fields["formapagto"];
   }

   function getcondpagto(){
       return $this->fields["condpagto"];
   }

   function getparcelafin(){
		return $this->fields["parcelafin"];
   }

   function setimprimedocumento($value){
      $this->fields["imprimedocumento"] = value_string($value, 1);
   }

   function setbloqvalunitario($value){
      $this->fields["bloqvalunitario"] = value_string($value, 1);
   }

   function setcodestabelec($value){
      $this->fields["codestabelec"] = value_numeric($value);
   }

   function settipovenda($value){
      $this->fields["tipovenda"] = value_string($value, 5);
   }

   function setnatoperacao($value){
      $this->fields["natoperacao"] = value_string($value, 9);
   }

   function setlocalimpressora($value){
      $this->fields["localimpressora"] = value_string($value, 200);
   }

   function settextovias($value){
      $this->fields["textovias"] = value_string($value, 200);
   }

   function settextoviasorc($value){
      $this->fields["textoviasorc"] = value_string($value, 200);
   }

   function settituloimpresso($value){
      $this->fields["tituloimpresso"] = value_string($value, 39);
   }

   function setsaltofinal($value){
      $this->fields["saltofinal"] = value_numeric($value);
   }

   function setimpendereco($value){
      $this->fields["impendereco"] = value_string($value, 1);
   }

   function setcodtabela($value){
      $this->fields["codtabela"] = value_numeric($value);
   }

   function settiposervidor($value){
      $this->fields["tiposervidor"] = value_string($value, 1);
   }

   function setimprimeorcamento($value){
      $this->fields["imprimeorcamento"] = value_string($value, 1);
   }

   function setimpnomecliente($value){
      $this->fields["impnomecliente"] = value_string($value, 1);
   }

   function setimpcpfcnpj($value){
      $this->fields["impcpfcnpj"] = value_string($value, 1);
   }

   function setimptelefonecli($value){
      $this->fields["imptelefonecli"] = value_string($value, 1);
   }

   function settiporecibo($value){
      $this->fields["tiporecibo"] = value_string($value, 1);
   }

   function setgeraprevenda($value){
      $this->fields["geraprevenda"] = value_string($value, 1);
   }

   function setgerafinanceiro($value){
      $this->fields["gerafinanceiro"] = value_string($value, 1);
   }

   function setgeratroco($value){
      $this->fields["geratroco"] = value_string($value, 1);
   }

   function setsolicitavendedor($value){
      $this->fields["solicitavendedor"] = value_string($value, 1);
   }

   function setimpestabelecimento($value){
      $this->fields["impestabelecimento"] = value_string($value, 1);
   }

   function setimpusuariologado($value){
      $this->fields["impusuariologado"] = value_string($value, 1);
   }

   function setprodsemlimite($value){
      $this->fields["prodsemlimite"] = value_string($value, 1);
   }

   function setsupervisor($value){
      $this->fields["supervisor"] = value_string($value, 1);
   }

   function setindpres($value){
      $this->fields["indpres"] = value_numeric($value);
   }

   function setordemitem($value){
      $this->fields["ordemitem"] = value_string($value, 1);
   }

   function setdectovalorperc($value){
      $this->fields["dectovalorperc"] = value_string($value, 1);
   }

   function setmostrarendereco($value){
      $this->fields["mostrarendereco"] = value_string($value, 1);
   }

   function setenviapesadoprevenda($value){
      $this->fields["enviapesadoprevenda"] = value_string($value, 1);
   }

   function setorcgeraatendido($value){
      $this->fields["orcgeraatendido"] = value_string($value, 1);
   }

   function setpercdescsemsuper($value){
      $this->fields["percdescsemsuper"] = value_numeric($value);
   }

   function setdadosfornec($value){
      $this->fields["dadosfornec"] = value_string($value, 1);
   }

   function setobservacaopedorc($value){
       return $this->fields["observacaopedorc"] = value_string($value, 1);
   }

   function setformapagto($value){
       $this->fields["formapagto"] = value_string($value, 1);
   }

    function setcondpagto($value){
       $this->fields["condpagto"] = value_string($value, 1);
   }

   function setparcelafin($value){
	   $this->fields["parcelafin"] = value_string($value, 1);
   }
}
