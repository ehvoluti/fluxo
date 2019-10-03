<?php

class Update{

	private $con;

	private $git_project = "controlware/websac";
	private $git_user = "controlware1987";
	private $git_pass = "alphadata1987";

	private $dir_websac;
	private $dir_htdocs;

	function __construct($con){
		$this->con = $con;
		$this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1); // Permite multiplos comandos

		$this->dir_websac = dirname(__DIR__);
		$this->dir_htdocs = dirname($this->dir_websac);
	}

	public function atualizar(){
		setprogress(25, "Atualizando banco de dados", true);
		if(!$this->atualizar_banco()){
			return false;
		}
		setprogress(50, "Verificando Git", true);
		if(!$this->verifica_git()){
			return false;
		}
		setprogress(75, "Atualizando arquivos", true);
		if(!$this->atualizar_arquivos()){
			return false;
		}
		setprogress(100, "Atualizando data de atualização", true);
		if(!$this->atualizar_logupdate()){
			return false;
		}

		return true;
	}

	public function atualizar_banco(){
		$param_datains = objectbytable("parametro", array("ATUALIZACAO", "DATAINS"), $this->con);
		$datains = $param_datains->getvalor();
		$param_numeroins = objectbytable("parametro", array("ATUALIZACAO", "NUMEROINS"), $this->con);
		$numeroins = str_pad($param_numeroins->getvalor(), 3, "0", STR_PAD_LEFT);

		$url = "http://update.websac.net?data={$datains}&arquivo={$numeroins}.sql";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$arr_instrucao = json_decode($response, true);

		$this->con->start_transaction();

		$totalins = 0;
		foreach($arr_instrucao as $Iano => $arr_Iano){
			foreach($arr_Iano as $Imes => $arr_Imes){
				foreach($arr_Imes as $Idia => $arr_Idia){
					foreach($arr_Idia AS $arquivo => $sql){
						$totalins++;
					}
				}
			}
		}

		$ult_data = null;
		$ult_numero = null;

		$i = 1;
		foreach($arr_instrucao as $Iano => $arr_Iano){
			foreach($arr_Iano as $Imes => $arr_Imes){
				foreach($arr_Imes as $Idia => $arr_Idia){
					foreach($arr_Idia AS $arquivo => $sql){
						$sql = base64_decode($sql);
						$this->con->exec($sql);
						$error = $this->con->errorInfo();
						if(strlen($error[2]) > 0){
							$this->con->rollback();
							$_SESSION["ERROR"] = "Erro ao tentar executar a instrução: {$Idia}/{$Imes}/{$Iano} - {$arquivo}<br><br>{$error[2]}";
							return false;
						}
						$ult_data = "{$Iano}-{$Imes}-{$Idia}";
						$ult_numero = $arquivo;
						$i++;
					}
				}
			}
		}

		if(strlen($ult_data) > 0 && strlen($ult_numero) > 0){
			$param_datains->setvalor($ult_data);
			$param_numeroins->setvalor($ult_numero);

			if(!$param_datains->save()){
				$this->con->rollback();
				$_SESSION["ERROR"] = "Erro ao tentar gravar a data da última instrução executada.<br>".$_SESSION["ERROR"];
				return false;
			}

			if(!$param_numeroins->save()){
				$this->con->rollback();
				$_SESSION["ERROR"] = "Erro ao tentar gravar o número da última instrução executada.<br>".$_SESSION["ERROR"];
				return false;
			}
		}

		$this->con->commit();

		return true;
	}

	private function atualizar_arquivos(){
		$command = implode(" && ", array(
			"cd {$this->dir_websac}",
			"git config http.sslVerify false",
			"git config user.name = \"{$this->git_user}\"",
			"git config user.password = \"{$this->git_pass}\"",
			"git fetch --all",
			"git reset --hard origin/master"
		));
		var_dump($command);
		$retorno = $this->comando($command);
		var_dump($retorno);
		return true;
	}

	private function comando($comando){
		$comando .= " 2>&1";
		//echo "<br>Executando comando:<br>{$comando}<br><br>";
		$output = null;
		exec($comando, $output);
		$output = implode(" ", $output);
		//echo "Retorno:<br>".var_export($output, true)."<br><br>";
		return $output;
	}

	private function verifica_git(){
		if(is_dir("../.git")){
			return true;
		}else{
			if(strstr(php_uname(), "Linux")){
				$directory = $this->dir_websac;
				$destination = $directory."-old";

				$nome_cliente = basename($directory);

				$htdocs_directory = $this->dir_htdocs;

				$output = $this->comando("git --version");
				if(!strstr($output, "git version")){
					$_SESSION["ERROR"] = $output;
					return false;
				}

				if(strstr($output, "not found")){
					$_SESSION["ERROR"] = "Git não está instalado no servidor.";
					return false;
				}

				$this->comando("mv {$directory} {$destination}");

				$version = trim(file_get_contents("/etc/centos-release"));
				if(strstr($version, "6.")){
					$command = "cd {$htdocs_directory} && git clone https://{$this->git_user}:{$this->git_pass}@github.com/{$this->git_project}.git {$nome_cliente}";
				}else{
					$command = "cd {$htdocs_directory} && git -c http.sslVerify=false clone https://{$this->git_user}:{$this->git_pass}@github.com/{$this->git_project}.git {$nome_cliente}";
				}
				$output = $this->comando($command);

				if(!is_dir($directory)){
					$_SESSION["ERROR"] = "Não foi possível clonar o repositório do WebSac do Git.<br><br>{$output}";
					return false;
				}

				if(strstr($output, "Cloning into")){
					$files_to_copy = array("/support", "/temp", "/lib/nfephp-4.0.0", "/img/logo.jpg");
					foreach($files_to_copy as $file){
						$this->comando("rm -Rf {$directory}/{$file}");
						$this->comando("cp -Rf {$destination}/{$file} {$directory}/{$file}");
					}
					$this->comando("chmod -R 0777 {$this->dir_websac}");
					return true;
				}else{
					$_SESSION["ERROR"] = var_export($output, true);
					return false;
				}
			}else{
				$nome_cliente = explode("\\", __DIR__);

				$nome_cliente = array_reverse($nome_cliente)[1];

				$directory = "C:\\Apache24\\htdocs\\{$nome_cliente}";
				$destination = "C:\\Apache24\\htdocs\\{$nome_cliente}_old";

				$this->comando("ren {$directory} {$destination}");

				$command = "cd ../../ && git clone https://{$this->git_user}:{$this->git_pass}@github.com/{$this->git_project}.git {$nome_cliente}";
				$output = $this->comando($comando);

				if(strstr($output, "Checking out files")){
					$this->comando("xcopy /S /E $destination\\support $directory\\support");
					$this->comando("xcopy /S /E $destination\\temp $directory\\temp");
					$this->comando("xcopy /S /E $destination\\lib\\nfephp-4.0.0 $directory\\lib\\nfephp-4.0.0");
					$this->comando("xcopy /S /E $destination\\img\\logo.jpg $directory\\img\\logo.jpg");

					return true;
				}else{
					$_SESSION["ERROR"] = var_export($output, true);
					return false;
				}
			}
		}
	}

	private function atualizar_logupdate(){
		$log = objectbytable("logupdate",NULL,$con);
		$log->setdata(date("d/m/Y"));
		$log->sethora(date("H:i:s"));
		$log->setip($_SERVER["REMOTE_ADDR"]);
		if(!$log->save()){
			$_SESSION["ERROR"] ="Erro ao tentar gravar o log de atualiza&ccedil;&otilde;es.";
			return false;
		}
		return true;
	}

}
