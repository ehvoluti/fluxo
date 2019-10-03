// Variaveis globais da posicao do mouse
var mouseX = 0;
var mouseY = 0;
document.onmousemove = function(e){
	mouseX = ((window.Event) ? e.pageX : event.clientX);
	mouseY = ((window.Event) ? e.pageY : event.clientY);
};

// Variavel que armazena a janela pricipal do sistema
var indexWindow = findFirstWindow();

// Trata o controle de janelas (todas ligadas a janela pai)
if(window !== indexWindow){
	if(indexWindow.childWindows === undefined){
		indexWindow.childWindows = [];
	}
	indexWindow.childWindows[indexWindow.childWindows.length] = window;
	indexWindow.onunload = function(){
		for(var i = 0; i < indexWindow.childWindows.length; i++){
			if(!indexWindow.childWindows[i].closed){
				indexWindow.childWindows[i].close();
			}
		}
	};
}

// Variavel que armazena o programa
var idtable = window.idtable;
if(idtable === undefined){
	idtable = null;
}

// Marca pagina como Focada/Desfocada
$(window).bind("focus", function(){
	$(this).attr("focused", true);
}).bind("blur", function(){
	$(this).attr("focused", false);
});

$(document).bind("ready", function(){
	// Altera o icone da pagina (favicon)
	if($("link[rel='shortcut icon']").length === 0){
		var imagem = (idtable !== null ? $("#divScreen img:first").attr("src") : "../img/favicon.png");
		$("head").append("<link rel='shortcut icon' href='" + imagem + "'>");
	}

	// Muda a pagina pagina visivel
	$("body").css("display", "block");

	// Aplica a imagem de fundo na pagina
	$("body:has(#mainForm)").css({
		"background-image": "url(../img/websac_bg.png)",
		"background-repeat": "no-repeat",
		"background-position": "bottom right"
	});

	$("#divScreen table.mainform").css("margin-top", "20px"); // Deixa a tela um pouco mais pra baixo

	//$.screenMenu();

	$("#divScreen").center();

	// Atualiza a imagem e o titulo dos programas
	if(idtable !== null){
		$.ajax({
			url: "../ajax/programa_atualizadados.php",
			data: ({
				idtable: idtable,
				titulo: document.title,
				imagem: ($("#divScreen").length > 0 ? $("#divScreen img:first").attr("src") : "")
			}),
			dataType: "html"
		});
	}

	// Cria o caminho da tela abaixo do titulo
	$("li.title > div").each(function(){
		var div = this;
		$.ajax({
			url: "../ajax/idtable_caminho.php",
			data: ({
				idtable: idtable
			}),
			success: function(html){
				html = "<div style='color:#666; font-size:7pt; left:2px; margin-right:2px; position:relative; top:-2px'>" + html + "</div>";
				$(div).append(html).css("top", "-3px");
			}
		});
	});

	// G E A R ! !
	$.gear();

	// Esconde o div do calendario
	$("#ui-datepicker-div").hide();

	// Abre a primeira aba de todos os grupos
	$("ul.tabs").each(function(){
		$(this).children().filter("li[pageid]:first").openTab();
	});

	// Deixa a pagina de todas as abas do mesmo tamanho
	resizeTabPages();

	// Foca o primeiro campo da tela
	$("body").focusFirst();
});