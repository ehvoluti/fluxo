var _messageBoxArray = new Array(); // Fila de mensagens

var _messageBox = ({
	text:null,

	focusOnClose:null,
	afterClose:function(){},
	beforeClose:function(){},

	buttonFocus:null, // Botao a ser focado na janela
	originalBody:null, // Corpo original onde estao os elementos (iframe)
	parentBody:null, // Corpo usado para criacao do mensagem (iframe -> parent)

	outerElements:null // Todos os elementos da tela com excessao dos elementos da caixa de mensagem
});

$.messageBox = function(settings,settings2){
	var borderRadius = "5px";

	// Comando para fechar a caixa de mensagem ativa
	if(settings == "close"){
		// Execulta a funcao antes de fechar a caixa de mensagem
		if(typeof(_messageBox.beforeClose) == "function"){
			_messageBox.beforeClose();
		}
		// Libera todos os campos da tela para ser acessado usando TAB
		$(_messageBox.outerElements).attr("tabindex","0");
		// Se o texto veio de um elemento, passa ele para o elemento pai de onde ele veio, para nao perder os dados
		if(_messageBox.text != null){
			while($("[messageBox_textBox]").find("*").length > 0){
				_messageBox.text.appendChild($("[messageBox_textBox]").find("*").first()[0]);
			}
		}
		// Remove o fundo, caixa de mensagem e libera a barra de rolagem da tela
		$(_messageBox.parentBody).find("[messageBox],[messageBox_background]").remove();
		$("body").css("overflow","auto");
		// Executa a funcao depois de fechar a caixa de mensagem
		if(typeof(_messageBox.afterClose) == "function"){
			_messageBox.afterClose();
		}
		// Foca o campo passado por parametro
		if(_messageBox.focusOnClose != null && $(_messageBox.focusOnClose).length > 0){
			$(_messageBox.focusOnClose).focus();
		}
		// Verifica se existe mais caixas de mensagem na fila
		if(_messageBoxArray.length > 0){
			var settings = _messageBoxArray.shift();
			$.messageBox(settings);
		}
		return true;
	}

	settings = $.extend({
		focusOnClose:$("body").firstElement(), // Elemento a ser focado apos fechar a mensagem
		text:"",
		title:"",
		type:"info", // alert, error, info, success
		buttons:({
			"Ok":function(){
				$.messageBox("close");
			}
		}),
		afterOpen:function(){},
		afterClose:function(){},
		beforeClose:function(){}
	},settings);

	if($("[messageBox]").length > 0){
		_messageBoxArray.push(settings);
		return true;
	}

	_messageBox.focusOnClose = settings.focusOnClose;
	_messageBox.afterClose = settings.afterClose;
	_messageBox.beforeClose = settings.beforeClose;
	_messageBox.originalBody = document.body;
	_messageBox.parentBody = document.body;// $.parentSelector("body")[0];

	if(settings.type == "alert"){
		var shadowColor = "#BBBB88";
		var imageColor = "#EEEEAA";
		var imageFile = "../img/msg_alert.png";
	}else if(settings.type == "error"){
		var shadowColor = "#BB9988";
		var imageColor = "#EEDDCC";
		var imageFile = "../img/msg_error.png";
	}else if(settings.type == "success"){
		var shadowColor = "#99BB88";
		var imageColor = "#DDEECC";
		var imageFile = "../img/msg_success.png";
	}else{
		var shadowColor = "#CCCCCC";
		var imageColor = "#DDDDDD";
		var imageFile = "../img/msg_info.png";
	}

	var background = document.createElement("div");
	_messageBox.parentBody.appendChild(background);
	$(background).attr({
		"messageBox_background":true
	}).css({
		"background-color":"RGBA(255,255,255,0.75)",
		"height":"100%",
		"left":"0px",
		"position":"fixed",
		"top":"0px",
		"width":"100%"
	});

	var mainBox = document.createElement("div");
	_messageBox.parentBody.appendChild(mainBox);
	$(mainBox).attr({
		"messageBox":true
	}).css({
		"background-color":"#FFFFFF",
		"border":"3px solid #FFFFFF",
		"left":"200px",
		"position":"absolute",
		"width":"600px"
	}).css3({
		"border-radius":borderRadius,
		"box-shadow":"0px 0px 250px 50px " + shadowColor
	});

	var imageBox = document.createElement("div");
	mainBox.appendChild(imageBox);
	$(imageBox).attr({
		"messageBox_imageBox":true
	}).css({
		"background-color":imageColor,
		"height":"100%",
		"text-align":"center",
		"width":"80px"
	}).css3({
		"border-top-left-radius":parseInt(borderRadius) - 4,
		"border-bottom-left-radius":parseInt(borderRadius) - 4
	});

	var image = document.createElement("img");
	imageBox.appendChild(image);
	$(image).attr({
		"src":imageFile
	}).css({
		"height":"48px",
		"margin-top":"15px",
		"width":"48px"
	});

	var title = document.createElement("div");
	mainBox.appendChild(title);
	$(title).css({
		"font-size":"13pt",
		"left":parseInt($(imageBox).width()) + 20,
		"position":"absolute",
		"top":"15px"
	}).html(settings.title);

	var text = document.createElement("div");
	mainBox.appendChild(text);
	$(text).attr({
		"messageBox_textBox":true
	}).css({
		"font-size":"10pt",
		"left":parseInt($(imageBox).width()) + 20,
		"padding-right":"20px",
		"position":"absolute",
		"top":parseInt($(title).position().top) + parseInt($(title).height()) + 10,
		"max-height": "400px",
		"width": "470px",
		"overflow-y":"auto"
	});
	_messageBox.text = null;
	if(typeof(settings.text) == "string"){
		$(text).html(settings.text);
	}else{
		_messageBox.text = $(settings.text)[0];
		while($(settings.text).find("*").length > 0){
			text.appendChild($(settings.text).find("*").first()[0]);
		}
	}

	$(text).children().css("font-size",$(text).css("font-size"));

	var buttonsBox = document.createElement("div");
	mainBox.appendChild(buttonsBox);
	$(buttonsBox).css({
		"height":"30px",
		"left":parseInt($(imageBox).width()),
		"position":"absolute",
		"text-align":"center",
		"top":parseInt($(text).height()) + parseInt($(text).position().top) + 20,
		"width":parseInt($(mainBox).width()) - parseInt($(imageBox).width())
	});

	$(mainBox).height(parseInt($(buttonsBox).height()) + parseInt($(buttonsBox).position().top) + 8);
/*	$(mainBox).css({
		"top":parseInt($(window).scrollTop()) + parseInt($(document).height()) / 2 - parseInt($(mainBox).height()) / 2 - 50
	});*/
	$(mainBox).center({
		left:true,
		top:true,
		onRezise:true
	});

	var buttonFocus = null;
	for(var index in settings.buttons){
		var button = document.createElement("input");
		button.type = "button";
		buttonsBox.appendChild(button);
		$(button).css({
			"margin-right":"5px"
		}).val(index).bind("click",settings.buttons[index]);
		if(buttonFocus == null){
			buttonFocus = button;
		}
	}
	_messageBox.buttonFocus = buttonFocus;

	$("body").css("overflow","hidden");

	$.gear();

	_messageBox.outerElements = $("input,select,textarea").not($(mainBox).find("*")).not("[tabindex='-1']");
	$(_messageBox.outerElements).attr("tabindex","-1");

	$(mainBox).focusFirst();
	settings.afterOpen();
	if($("#erro-tecnico").length > 0){
		$("#erro-tecnico").hide();
		$("[messagebox] input").parent().css("top","100px");
		$("[messagebox]").css("height","138px");
	}

}