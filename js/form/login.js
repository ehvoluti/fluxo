// Limpa o cache do navegador
limpar_cache();

$(document).bind("ready", function(){

	// Centraliza a tela do login
	$(".login").center({
		left: true,
		top: true
	});

	// Ajusta o player de video
	$("#div_video video").bind("loadstart", function(){
		$(this).parent().parent().width($(this).width() + 20).center();
		$(this).parent().width($(this).width() + 20);
	}).bind("canplay", function(){
		$(this).trigger("loadstart");
		this.play();
	});

	// Ao pressionar ENTER no usuario ou senha
	$("#usuario, #senha").bind("keypress", function(e){
		if(e.keyCode === 13){
			login_efetuar();
		}
	});

	// Ao pressionar ENTER na senha mensal
	$("#senhamensal").bind("keypress", function(e){
		if(e.keyCode === 13){
			senhamensal_validar();
		}
	});

	// Verifica o status da senha mensal e ja joga o foco no campo correto
	if(parseInt($("#senhamensal-status").val()) > 0){
		$("#senhamensal-invalida").show();
		$("#senhamensal").focus();
	}else{
		$("#senhamensal-invalida").hide();
		$("#usuario").focus();
	}

	// Verifica se libera o login de acordo com o status da senha mensal
	if(parseInt($("#senhamensal-status").val()) > 1){
		$(".login-block").show();
	}else{
		$(".login-block").hide();
	}

	// Verifica a senha de ativacao
	if($("#senhaativacao-status").val() !== "0"){
		$.senhaAtivacao();
	}

	// Inicia o alerta da senha mensal
	senhamensal_alertar();
});

function atualizacao_novidade(){
	var win = $.modalWindow({
		closeButton: true,
		height: 400,
		padding: false,
		title: "&Uacute;ltimas Novidades",
		width: 550
	});
	var iframe = document.createElement("iframe");
	$(win).get(0).appendChild(iframe);
	$(iframe).attr({
		frameborder: "0",
		src: "../form/news.php?data=" + $("#logupdate-data").val() + "&hora=" + $("#logupdate-hora").val()
	}).css({
		height: "365px",
		margin: "0px",
		padding: "0px",
		width: "100%"
	});
}

function atualizacao_verificar(){
	if($("#atualizacao-senha-correta").val().length > 0){
		$.messageBox({
			type: "info",
			title: "Senha para Atualiza&ccedil;&atilde;o",
			text: "Informe a senha para prosseguir com a atualiza&ccedil;&atilde;o do sistema:<br><input type=\"password\" id=\"atualizacao-senha\" class=\"field_m\" style=\"margin:10px 0px 0px 10px\">",
			buttons: ({
				"Atualizar": function(){
					atualizacao_verificar_btn();
				},
				"Cancelar": function(){
					$.messageBox("close");
				}
			}),
			afterOpen: function(){
				$("#atualizacao-senha").bind("keypress", function(e){
					if(e.keyCode === 13){
						atualizacao_verificar_btn();
					}
				});
			}
		});
	}else{
		verificaAtualizacao_();
	}
}

function atualizacao_verificar_(){
	$.messageBox({
		type: "alert",
		title: "Atualiza&ccedil;&atilde;o do Sistema",
		text: "<b>Aten&ccedil;&atilde;o!</b><br>A atualização pode demorar alguns minutos e, para isso, &eacute; necess&aacute;rio que todos os usu&aacute;rios fiquem deslogados do WebSac at&eacute; que a atualiza&ccedil;&atilde;o seja conclu&iacute;da. &Eacute; recomendado a atualiza&ccedil;&atilde;o do sistema ap&oacute;s o expediente de trabalho.<br><br>Deseja iniciar a atualiza&ccedil;&atilde;o agora?",
		buttons: ({
			"Sim": function(){
				$.messageBox("close");
				$.ajaxProgress({
					url: "../ajax/login_update.php",
					success: function(html){
						extractScript(html);
					}
				});
			},
			"N\u00E3o": function(){
				$.messageBox("close");
			}
		})
	});
}

function atualizacao_verificar_btn(){
	var senha = $("#atualizacao-senha").val();
	$.messageBox("close");
	if(senha === $("#atualizacao-senha-correta").val()){
		atualizacao_verificar_();
	}else{
		$.messageBox({
			type: "error",
			text: "Senha informada n&atilde;o &eacute; v&aacute;lida.",
			focusOnClose: $("#atualizacao-senha")
		});
		atualizacao_verificar();
	}
}

function login_efetuar(){
	if($("#usuario").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o usu&aacute;rio.",
			focusOnClose: $("#usuario")
		});
		return false;
	}
	if($("#senha").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a senha.",
			focusOnClose: $("#senha")
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/login_login.php",
		data: ({
			usuario: $("#usuario").val(),
			senha: $("#senha").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function login_sucesso(){
	if(closeAfterLogin){
		window.close();
	}else{
		window.location.href = "../form/index.php";
	}
}

function login_esqueceusenha(){
	$.messageBox({
		type: "info",
		title: "Recuperar senha",
		text: "Informe o seu email abaixo para enviarmos sua senha:<br><input id=\"usuario_email\" style=\"width: 200px;\" class=\"field_m\">",
		buttons: ({
			"Enviar": function(){
				enviar_senha();
				$.messageBox("close");
			},
			"Cancelar": function(){
				$.messageBox("close");
			}
		}),
		afterOpen: function(){
			$("#usuario_email").bind("keypress", function(e){
				if(e.keyCode === 13){
					enviar_senha();
					$.messageBox("close");
				}
			});
			$("#usuario_email").focus();
		}
	});
}

function login_esqueceusenha_enviar(){
	if($("#usuario_email").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe o e-mail do usuário para onde a senha deve ser enviada.",
			afterClose: function(){
				login_esqueceusenha();
			}
		});
	}else{
		$.loading(true);
		$.ajax({
			url: "../ajax/usuario_senha.php",
			data: ({
				usuario_email: $("#usuario_email").val()
			}),
			success: function(html){
				$.loading(false);
				extractScript(html);
			}
		});
	}
}

function senhamensal_alertar(){
	var element = $("#senhamensal-status-texto");
	if($(element).attr("speed") !== undefined){
		$(element).animate({
			opacity: "toggle"
		}, "slow", function(){
			senhamensal_alertar();
		});
	}
}

function senhamensal_sucesso(){
	$.messageBox({
		type: "success",
		text: "Senha mensal atualizada com sucesso!<br>Para atualiza&ccedil;&atilde;o entrar em vigor, a p&aacute;gina ser&aacute; recarregada.",
		afterClose: function(){
			location.reload(true);
		}
	});
}

function senhamensal_validar(){
	if($("#senhamensal").val().length === 0){
		$.messageBox({
			type: "error",
			text: "Informe a senha mensal antes de validar.",
			focusOnClose: $("#senhamensal")
		});
		return false;
	}
	$.loading(true);
	$.ajax({
		url: "../ajax/login_senhamensal.php",
		data: ({
			senhamensal: $("#senhamensal").val()
		}),
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function senhamensal_validaronline(){
	$.loading(true);
	$.ajax({
		url: "../ajax/login_validacaoonline.php",
		success: function(html){
			$.loading(false);
			extractScript(html);
		}
	});
}

function video(title, url){
	$("#div_video video").attr({
		src: url
	}).css("visibility", "visible");
	$.modalWindow({
		content: $("#div_video"),
		height: "90%",
		title: title,
		position: ({
			top: "5%"
		})
	});
}