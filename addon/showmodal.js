var closeModal_ = null;

$.showModal = function(settings){
	/*
	id - id da janela
	data - dados que serao passados por variavel de cabecario
	dataType - tipo de dados que serao passados por variavel de cabecario
	height - altura da janela
	url - localizacao da pagina que sera aberta
	width - largura da janela
	
	beforeOpen - evento que sera executado antes de abrir a janela
	afterOpen - evento que sera executado depois de abrir a janela
	beforeClose - evento que sera executado antes de fechar a janela
	afterClose - evento que sera executado depois de fechar a janela
	
	Para fechar a janela tecla 'ESC'
	*/
	settings = $.extend({
		id:"modalwindow",
		data:({}),
		dataType:"html",
		height:null,
		frame:true,
		url:"",
		width:"600px",
		beforeOpen:function(){},
		afterOpen:function(){},
		beforeClose:function(){},
		afterClose:function(){}
	},settings);
	
	// Esconde todos os elementos da tag <object> (elements Flash)
	$("object").hide();
	
	// Bloqueia rolagem de tela
	$("body").css("overflow","hidden");
	
	$.background(true);
	
	var iframe = document.createElement("iframe");
	iframe.id = settings.id;
	document.body.appendChild(iframe);
	$(iframe).css({
		"background-color":"#FFFFFF",
		"border":"1px solid #8899AA",
		"margin-top":"20px",
		"padding":"0px",
		"position":"absolute",
		"width":settings.width
	}).attr({
		"frameborder":"0",
		"scrolling":"no",
		"src":settings.url,// + "?" + settings.data,
		"cwModalWindow":true
	}).load(function(){
		var fBody = $(this).contents().find("body");
		fBody.css({
			"background-color":"#FFFFFF",
			"background-image":"url(../img/background.jpg)",
			"background-repeat":"no-repeat",
			"background-position":"bottom left"
		});
		$(this).height(fBody.height());
		$(this).animate({
			opacity:"show"
		},"fast");
	}).hide().center();

	closeModal_ = function(){
		settings.beforeClose();
		$("[cwModalWindow]").animate({
			opacity:"hide"
		},"fast",function(){
			$.background(false);
			$(this).remove();
			settings.afterClose();
		});
	}
}

// Se a funcao for executada de um frame, sera executada a funcao criada na funcao $.showModal
closeModal = function(){
	if(typeof(closeModal_) == "function"){
		closeModal_();
	}else if(isFrame()){
		parent.closeModal();
	}
	$("body").css("overflow","auto");
	$("object").show();
}