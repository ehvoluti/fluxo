 var _locate = ({
	element: null,
	afterClose: function(){}
});

$.fn.locate = function(settings){
	settings = $.extend({
		filter: null,
		table: null,
		afterClose: function(){},
		afterOpen: function(){}
	}, settings);
	settings.table = trim(settings.table);

	if(settings.table.length === 0){
		$("[codparceiro='" + $(this).attr("id") + "']").focus();
		return this;
	}

	if(!in_array(settings.table, ["administradora", "cliente", "cupom", "estabelecimento", "fornecedor", "funcionario", "grupoocorrencia", "lancamento", "notafiscal", "orcamento", "pedido", "planocontas", "produto", "simprod", "transportadora", "negociacaopreco", "representante"])){
		return this;
	}

	if($(this).is(":disabled")){
		return this;
	}

	_locate.element = this;
	_locate.afterClose = settings.afterClose;

	var param = new Array();
	if(typeof settings.filter === "string" && settings.filter.length > 0){
		var arr_filter = settings.filter.split(";");
		for(var i in arr_filter){
			if(typeof arr_filter[i] === "string"){
				var v_filter = arr_filter[i].split(":");
				if(v_filter[1].substr(0, 1) === "'"){
					param[param.length] = v_filter[0] + "=" + v_filter[1].substr(1, v_filter[1].length - 2);
				}else{
					var e = $("#" + v_filter[1]);
					var value = "";
					if($(e).is("div") && $(e).find("input:checkbox[key]").length > 0){
						if($(e).find("input:checkbox:checked").length === 1){
							value = $(e).find("input:checkbox:checked").attr("key");
						}
					}else{
						value = $(e).val();
					}
					param[param.length] = v_filter[0] + "=" + value;
				}
			}
		}
	}
	param = param.join("&");

	var elements = $(this).map(function(){
		return "elements[]=" + $(this).attr("id");
	}).get().join("&");

	var title = "Pesquisa de ";
	switch(settings.table){
		case "administradora":
			title += "Administradora";
			break;
		case "cliente":
			title += "Cliente";
			break;
		case "cupom":
			title += "Cupom Fiscal";
			break;
		case "estabelecimento":
			title += "Estabelecimento";
			break;
		case "fornecedor":
			title += "Fornecedor";
			break;
		case "funcionario":
			title += "Funcion&aacute;rio";
			break;
		case "lancamento":
			title += "Lan&ccedil;amento";
			break;
		case "notafiscal":
			title += "Nota Fiscal";
			break;
		case "negociacaopreco":
			title += "Negocia&ccedil;&atilde;o Pre&ccedil;o";
			break;
		case "orcamento":
			title += "Or&ccedil;amento";
			break;
		case "pedido":
			title += "Pedido";
			break;
		case "planocontas":
			title += "Conta Contabil";
			break;
		case "produto":
			title += "Produto";
			break;
		case "simprod":
			title += "Similar";
			break;
		case "grupoocorrencia":
			title += "Grupo de Ocorr&ecirc;ncia";
			break;
		case "transportadora":
			title += "Transportadora";
			break;
	}

	var win = parent.window.$.modalWindow({
		afterClose: settings.afterClose,
		closeButton: true,
		padding: false,
		title: title,
		width: "700px",
		center: ({
			top: false
		}),
		position: ({
			top: "50px"
		})
	});

	var iframe = document.createElement("iframe");
	iframe.scrolling = "no";
	win[0].appendChild(iframe);
	$(iframe).css({
		"height": "100%",
		"width":"100%"
	}).attr({
		"frameborder": "0",
		"src": "../form/locate.php?table=" + settings.table + "&" + elements + "&" + param,
		"locate_iframe": true
	}).bind("load", function(){
		var fbody = $(this).contents().find("body");
		$(win).height($(fbody).css("height"));
		if($.browser.opera){
			$(fbody).css("margin-top", "46px");
		}
		for(var col in settings.values){
			$(this).contents().find("#" + col).val(settings.values[col]);
		}
		settings.afterOpen();
	});

//	Não deixa rolar na tela de etiqueta.
//	$("body").css("overflow", "hidden");

	return this;
};

$.locate = function(action){
	switch(action){
		case "close": // Fecha a janela de pesquisa
			parent._locate.afterClose();
			$.modalWindow("close");
			break;
		case "iframe": // Retorna os elementos dentro da janela da pesquisa);
			return $("iframe[locate_iframe]").contents();
			break;
	}
};

$(document).bind("keydown", function(e){
	if(e.keyCode === 27 && $("iframe[locate_iframe]").length > 0){
		$.locate("close");
	}
});
