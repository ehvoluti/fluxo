<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Usuario extends Cadastro{
	private $flag_usuaestabel = FALSE;
	private $usuaestabel = array();
	private $validchars = array("0","1","2","3","4","5","6","7","8","9","a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J","k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T","u","U","v","V","w","W","x","X","y","Y","z","Z");

	function __construct($login = NULL){
		parent::__construct();
		$this->table = "usuario";
		$this->primarykey = array("login");
		$this->setlogin($login);
		if(!is_null($this->getlogin())){
			$this->searchbyobject();
		}
	}

	function cript($pass){
		return $pass;

		$aux = "";
		for($i = 0; $i < strlen($pass); $i++){
			$aux .= chr(255 - ord(substr($pass, $i, 1)));
		}
		if(database_encoding() == "UTF8"){
		//	$aux = win1252_to_utf8($aux);
		}
		return $aux;
	}

	function descript($pass){
		return $pass;

		if(database_encoding() == "UTF8"){
		//	$pass = utf8_to_win1252($pass);
		}
		$aux = "";
		for($i = 0; $i < strlen($pass); $i++){
			$aux .= chr(255 - ord(substr($pass, $i, 1)));
		}
		$pass = $aux;
		$aux = "";
		for($i = 0; $i < strlen($pass); $i++){
			if(in_array(substr($pass,$i,1),$this->validchars)){
				$aux .= substr($pass,$i,1);
			}
		}
		return $aux;
	}

	function flag_usuaestabel($b){
		if(is_bool($b)){
			$this->flag_usuaestabel = $b;
		}
	}

	function save($object = NULL){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_usuaestabel){
				$usuaestabel = objectbytable("usuaestabel",NULL,$this->con);
				$usuaestabel->setlogin($this->getlogin());
				$arr_usuaestabel = object_array($usuaestabel);
				foreach($arr_usuaestabel as $usuaestabel){
					if(!$usuaestabel->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->usuaestabel as $usuaestabel){
					$usuaestabel->setlogin($this->getlogin());
					if(!$usuaestabel->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function searchatdatabase($query,$fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && !$fetchAll){
			if($this->flag_usuaestabel){
				$usuaestabel = objectbytable("usuaestabel",NULL,$this->con);
				$usuaestabel->setlogin($this->getlogin());
				$this->usuaestabel = object_array($usuaestabel);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$temporary = new Temporary("usuario_usuaestabel",FALSE);
		$this->usuaestabel = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$usuaestabel = objectbytable("usuaestabel",NULL,$this->con);
			$usuaestabel->setlogin($this->getlogin());
			$usuaestabel->setcodestabelec($temporary->getvalue($i,"codestabelec"));
			$this->usuaestabel[] = $usuaestabel;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();
		$temporary = new Temporary("usuario_usuaestabel",TRUE);
		$temporary->setcolumns(array("codestabelec"));
		foreach($this->usuaestabel as $usuaestabel){
			$temporary->append();
			$temporary->setvalue("last","codestabelec",$usuaestabel->getcodestabelec());
		}
		$temporary->save();
		return $html;
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getsenha(){
		return $this->descript($this->fields["senha"]);
	}

	function getemail(){
		return $this->fields["email"];
	}

	function getbloqueado(){
		return $this->fields["bloqueado"];
	}

	function getdtultacesso($format = FALSE){
		return ($format ? convert_date($this->fields["dtultacesso"],"Y-m-d","d/m/Y") : $this->fields["dtultacesso"]);
	}

	function gethrultacesso(){
		return $this->fields["hrultacesso"];
	}

	function getipacesso(){
		return $this->fields["ipacesso"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcodgrupo(){
		return trim($this->fields["codgrupo"]);
	}

	function getsupervenda(){
		return $this->fields["supervenda"];
	}

	function getsupercompra(){
		return $this->fields["supercompra"];
	}

	function getsuperfinanceiro(){
		return $this->fields["superfinanceiro"];
	}

	function getnotificacao(){
		return $this->fields["notificacao"];
	}

	function getpermiteabrisessao(){
		return $this->fields["permiteabrisessao"];
	}

	function getpermiteacrescimo(){
		return $this->fields["permiteacrescimo"];
	}

	function getpermiteacresctotal(){
		return $this->fields["permiteacresctotal"];
	}

	function getpermiteaferir(){
		return $this->fields["permiteaferir"];
	}

	function getpermitebloqcupom(){
		return $this->fields["permitebloqcupom"];
	}

	function getpermitecanccupom(){
		return $this->fields["permitecanccupom"];
	}

	function getpermitecancitem(){
		return $this->fields["permitecancitem"];
	}

	function getdescmaxitem(){
		return $this->fields["descmaxitem"];
	}

	function getdescmaxvenda(){
		return $this->fields["descmaxvenda"];
	}

	function getpermitefechasessao(){
		return $this->fields["permitefechasessao"];
	}

	function getpermiteabrigaveta(){
		return $this->fields["permiteabrigaveta"];
	}

	function getpermiteutimod(){
		return $this->fields["permiteutimod"];
	}

	function getpermiteimpleiturax(){
		return $this->fields["permiteimpleiturax"];
	}

	function getvalormaximomovcaixa(){
		return $this->fields["valormaximomovcaixa"];
	}

	function getpermiteimpleituraz(){
		return $this->fields["permiteimpleituraz"];
	}

	function getpermitemovvalorcaixa(){
		return $this->fields["permitemovvalorcaixa"];
	}

	function getmensagemlogin(){
		return $this->fields["mensagemlogin"];
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}
	
	function setlogin($value){
		$this->fields["login"] = value_string($value,20);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setsenha($value){
		$this->fields["senha"] = $this->cript(value_string($value,20));
	}

	function setemail($value){
		$this->fields["email"] = value_string($value,60);
	}

	function setbloqueado($value){
		$this->fields["bloqueado"] = value_string($value,1);
	}

	function setdtultacesso($value){
		$this->fields["dtultacesso"] = value_date($value);
	}

	function sethrultacesso($value){
		$this->fields["hrultacesso"] = value_string($value,8);
	}

	function setipacesso($value){
		$this->fields["ipacesso"] = value_string($value,40);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setsupervenda($value){
		$this->fields["supervenda"] = value_string($value,1);
	}

	function setsupercompra($value){
		$this->fields["supercompra"] = value_string($value,1);
	}

	function setsuperfinanceiro($value){
		$this->fields["superfinanceiro"] = value_string($value,1);
	}

	function setnotificacao($value){
		$this->fields["notificacao"] = value_string($value,1);
	}

	function setpermiteabrisessao($value){
		$this->fields["permiteabrisessao"] = value_string($value,1);
	}

	function setpermiteacrescimo($value){
		$this->fields["permiteacrescimo"] = value_string($value,1);
	}

	function setpermiteacresctotal($value){
		$this->fields["permiteacresctotal"] = value_string($value,1);
	}

	function setpermiteaferir($value){
		$this->fields["permiteaferir"] = value_string($value,1);
	}

	function setpermitebloqcupom($value){
		$this->fields["permitebloqcupom"] = value_string($value,1);
	}

	function setpermitecanccupom($value){
		$this->fields["permitecanccupom"] = value_string($value,1);
	}

	function setpermitecancitem($value){
		$this->fields["permitecancitem"] = value_string($value,1);
	}

	function setdescmaxitem($value){
		$this->fields["descmaxitem"] = value_string($value,1);
	}

	function setdescmaxvenda($value){
		$this->fields["descmaxvenda"] = value_string($value,1);
	}

	function setpermitefechasessao($value){
		$this->fields["permitefechasessao"] = value_string($value,1);
	}

	function setpermiteabrigaveta($value){
		$this->fields["permiteabrigaveta"] = value_string($value,1);
	}

	function setpermiteutimod($value){
		$this->fields["permiteutimod"] = value_string($value,1);
	}

	function setpermiteimpleiturax($value){
		$this->fields["permiteimpleiturax"] = value_string($value,1);
	}

	function setvalormaximomovcaixa($value){
		$this->fields["valormaximomovcaixa"] = value_string($value,1);
	}

	function setpermiteimpleituraz($value){
		$this->fields["permiteimpleituraz"] = value_string($value,1);
	}

	function setpermitemovvalorcaixa($value){
		$this->fields["permitemovvalorcaixa"] = value_string($value,1);
	}

	function setmensagemlogin($value){
		$this->fields["mensagemlogin"] = value_string($value);
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}	
}
