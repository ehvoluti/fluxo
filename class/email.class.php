<?php
require_file("class/connection.class.php");
require_file("def/function.php");
//require_file("lib/phpmailer-5.2.14/PHPMailerAutoload.php");
require_file("lib/swift-mailer/vendor/autoload.php");

require_file("lib/phpmailer-5.1.0/class.phpmailer.php");

final class Email{
	private $con;
	private $host;
	private $porta;
	private $tipoautenticacao;
	private $usuario;
	private $senha;
	private $remetente;
	private $destinatario;
	private $destinatario_bcc;
	private $assunto;
	private $mensagem;
	private $anexo;

	function __construct($con = NULL){
		$this->destinatario = array();
		$this->anexo = array();
		if(is_object($con)){
			$this->con = $con;
		}else{
			$this->con = new Connection();
		}
	}

	function setanexo($anexo){
		if(!is_array($anexo)){
			$anexo = array($anexo);
		}
		$this->anexo = $anexo;
		return TRUE;
	}

	function settipoautenticacao($tipoautenticacao){
		$this->tipoautenticacao = $tipoautenticacao;
	}

	function setcorpo($corpo){
		$this->mensagem = $corpo;
	}

	function setdestinatario($destinatario){
		if(!is_array($destinatario)){
			$destinatario = explode(";", $destinatario);
		}
		foreach($destinatario as $s){
			if(!is_string($s)){
				$_SESSION["ERROR"] = "Um dos destinat&aacute;rios informado no e-mail &eacute; inv&aacute;lido.";
				return FALSE;
			}
		}
		$this->destinatario = $destinatario;
		return TRUE;
	}

	function setdestinatario_bcc($destinatario_bcc){
		if(!is_array($destinatario_bcc)){
			$destinatario_bcc = explode(";", $destinatario_bcc);
		}
		foreach($destinatario_bcc as $s){
			if(!is_string($s)){
				$_SESSION["ERROR"] = "Um dos destinat&aacute;rios informado no e-mail &eacute; inv&aacute;lido.";
				return FALSE;
			}
		}
		$this->destinatario_bcc = $destinatario_bcc;
		return TRUE;
	}

	function sethost($host){
		$this->host = $host;
	}

	function setporta($porta){
		$this->porta = $porta;
	}

	function setsenha($senha){
		$this->senha = $senha;
	}

	function settitulo($titulo){
		$this->assunto = $titulo;
	}

	function setusuario($usuario){
		$this->usuario = $usuario;
	}

	function enviar(){
		$this->remetente = $this->usuario;
		foreach($this->anexo as $anexo){
			if(!file_exists($anexo)){
				$_SESSION["ERROR"] = "Um dos arquivos anexados ao e-mail n&atilde;o pode ser encontrado.<br>Nome do arquivo: ".$anexo;
				return FALSE;
			}
		}

		$phpmailer = new PHPMailer();

		$phpmailer->Timeout = 20;
		$phpmailer->SetLanguage("br", "../plugin/phpmailer-5.1-0/language/");
		$phpmailer->CharSet = "UTF-8";
		$phpmailer->IsSMTP();
		$phpmailer->Host = "smtplw.com.br";
		$phpmailer->Port = "587";
		$phpmailer->SMTPAuth = TRUE;
		$phpmailer->Username = "controlwareemail";
		$phpmailer->Password = "hdOyAACq7007";
		$phpmailer->IsHTML();
		$phpmailer->From = "websac@controlware.com.br";
		$phpmailer->FromName = $this->remetente;
		foreach($this->destinatario as $destinatario){
			$phpmailer->AddAddress($destinatario);
		}
		if(is_array($this->destinatario_bcc) > 0){
			foreach($this->destinatario_bcc as $destinatario_bcc){
				$phpmailer->AddBCC($destinatario_bcc);
			}
		}

		//echo $this->assunto; exit;

		$phpmailer->addReplyTo($this->remetente);
		$phpmailer->Subject = utf8_encode(mb_decode_mimeheader($this->assunto));
		$phpmailer->Body = $this->mensagem;
		foreach($this->anexo as $anexo){
			$phpmailer->AddAttachment($anexo);
		}

		if($phpmailer->Send()){
			return TRUE;
		}else{
			$_SESSION["ERROR"] = "Houve uma falha ao tentar enviar o e-mail.<br>".$phpmailer->ErrorInfo;
			return FALSE;
		}
	}

	function enviar_proprio(){
		$param_sistema_libemail = param("SISTEMA","LIBEMAIL", $this->con);
		if($param_sistema_libemail == "1"){
			// Create the Transport
			if(true){
				$transport = Swift_SmtpTransport::newInstance($this->host, $this->porta, $this->tipoautenticacao)
					->setAuthMode('login')
					->setUsername($this->usuario)
					->setPassword($this->senha);
			}else{
				$transport = (new Swift_SmtpTransport($this->host, $this->porta))->setUsername($this->usuario)->setPassword($this->senha);
			}

			// Create the Mailer using your created Transport
			$mailer = new Swift_Mailer($transport);

			// Create a message
			$message = (new Swift_Message($this->assunto))->setFrom([$this->usuario => $this->usuario]);
			$message->setTo($this->destinatario);
			$message->setBody($this->mensagem);
			$message->setContentType("text/html");

			foreach($this->anexo as $anexo){
				$message->attach(Swift_Attachment::fromPath($anexo));
			}

			try{
				if($mailer->send($message)){
					return TRUE;
				}else{
					$_SESSION["ERROR"] = "Email não enviado, verifique o SMTP, usuario e senha";
					return FALSE;
				}
			} catch (Exception $e) {
				var_dump($e);
				$_SESSION["ERROR"] = "Email não enviado, verifique o SMTP, usuario e senha";
				return FALSE;
			}
		}else{
			$this->remetente = $this->usuario;
			foreach($this->anexo as $anexo){
				if(!file_exists($anexo)){
					$_SESSION["ERROR"] = "Um dos arquivos anexados ao e-mail n&atilde;o pode ser encontrado.<br>Nome do arquivo: ".$anexo;
					return FALSE;
				}
			}

			$mail = new PHPMailer();
			$mail->Timeout = 20;
			$mail->SetLanguage("br", "../plugin/phpmailer-5.1-0/language/");
			$mail->CharSet = "utf-8";
			$mail->IsSMTP();
			$mail->SMTPAuth = TRUE;
			$mail->isHTML(true);
			$mail->Host = $this->host; // Servidor SMTP
			$mail->Port = $this->porta;
			$mail->Username = $this->usuario;
			$mail->Password = $this->senha;
			$mail->From = $this->remetente; // Define o Remetente
			$mail->FromName = $this->remetente;
			foreach($this->destinatario as $destinatario){
				$mail->AddAddress($destinatario);
			}
			if(count($this->destinatario_bcc) > 0){
				foreach($this->destinatario_bcc as $destinatario_bcc){
					$mail->AddBCC($destinatario_bcc);
				}
			}
			$mail->Subject = $this->assunto; // Define o Assunto
			$mail->Body = $this->mensagem;
			foreach($this->anexo as $anexo){
				$mail->AddAttachment($anexo);
			}
			if($mail->Send()){
				return TRUE;
			}else{
				$_SESSION["ERROR"] = "Houve uma falha ao tentar enviar o e-mail.<br>".$mail->ErrorInfo;
				return FALSE;
			}
		}
	}
}