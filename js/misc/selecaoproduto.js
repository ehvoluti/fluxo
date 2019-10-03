$.selecaoProduto = function(settings){

	settings = $.extend({
		data: {
			tabela: null, // Nome da tabela (ex: cotacao, pedido)
			codestabelec: null, // Codigo de estabelecimento (array se for mais de um)
			codfornec: null // Codigo do fornecedor
		}, // Dados a serem enviados para a janela
		success: function(){}, // Em caso de sucesso
		fail: function(){} // Em caso de falha
	}, settings);

	var value = null;
	var dataUrl = [];
	for(var i in settings.data){
		value = settings.data[i];
		if(typeof value === "object"){
			value = value.join(",");
		}
		dataUrl.push(i + "=" + value);
	}
	dataUrl = dataUrl.join("&");

	$.selecaoProduto.window = window.open("../form/selecaoproduto.php?" + dataUrl);

	$.messageBox({
		text: "A tela de seleção de produtos está aberta, você precisa concluír a seleção antes de continuar.",
		buttons: {
			"Ir para a seleção de produtos": function(){
				$.selecaoProduto.window.focus();
			}
		}
	});

	if(!$(window).is("[selecaoProduto]")){
		$(window).bind("unload", function(){
			if($.selecaoProduto.estaAberto()){
				$.selecaoProduto.window.close();
			}
		}).attr("selecaoProduto", true);
	}

	$.selecaoProduto.iniciarVerificacao();

	$.selecaoProduto.settings = settings;
};

$.selecaoProduto.settings = null; // Parametros enviados para a funcao
$.selecaoProduto.window = null; // Janela da selacao de produto

$.selecaoProduto.estaAberto = function(){
	return ($.selecaoProduto.window.location.href !== undefined);
};

$.selecaoProduto.iniciarVerificacao = function(){
	setTimeout(function(){
		if($.selecaoProduto.estaAberto()){
			$.selecaoProduto.iniciarVerificacao();
		}else{
			$.messageBox("close");
		}
	}, 1000);
};
