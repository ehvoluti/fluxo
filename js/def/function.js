// Pesquisa se existe um valor dentro de um array
var num = "";
function array_search(value, array){
	for(var i = 0; i < array.length; i++){
		if(array[i] == value){
			return i;
		}
	}
	return null;
}

function alteracor_over(x){
	if($(x).attr("alterar") == "true"){
		$(x).css("background", "#E0E0E5");
	}
}

function alteracor_out(x){
	if($(x).attr("alterar") == "true"){
		cor = $(x).attr("cor");
		$(x).css("background", cor);
	}
}

function alteracor_click(x){
	if($(x).attr("alterar") == "true"){
		if(num.length > 0){
			cor = $('tr[num="' + num + '"]').attr("cor");
			$('tr[num="' + num + '"]').css("background", cor);
		}
		$('.row').attr("alterar", "true");
		num = $(x).attr("num");
		$(x).css("background", "#E0E0E5");
		$(x).attr("alterar", "false");
	}else{
		cor = $('tr[num="' + num + '"]').attr("cor");
		$('tr[num="' + num + '"]').css("background", cor);
		$('.row').attr("alterar", "true");
	}
}

// NOVAS FUNCOES DO CADASTRO
function cad_actionlabel(texto){
	if(texto !== null){
		$("#lblAcaoCadastro").html(texto);
	}else{
		$("#lblAcaoCadastro").html("");
	}
}

//retornar digito do modulo 10
function calcula_digito_mod10(NumDado){
	var digito;
	$.ajax({
		async: false,
		url: "../ajax/calcula_digito_mod10.php",
		data: ({
			numdado: NumDado
		}),
		success: function(html){
			digito = html;
		}
	});
	return digito;
}

//retornar digoto do modulo 11
function calcula_digito_mod11(NumDado, NumDig, LimMult){
	NumDig = (typeof (NumDig) == "undefined" ? 1 : NumDig);
	LimMult = (typeof (LimMult) == "undefined" ? 9 : LimMult);
	var digito;
	$.ajax({
		async: false,
		url: "../ajax/calcula_digito_mod11.php",
		data: ({
			numdado: NumDado,
			numdig: NumDig,
			limmult: LimMult
		}),
		success: function(html){
			digito = html;
		}
	});
	return digito;
}

function checkrequired(elem){ // Verifica se existe campos requiridos em branco
	if(elem != undefined){
		var elements = $("#" + elem + " [required]");
	}else{
		var elements = $("[required]");
	}
	for(var i = 0; i < elements.length; i++){
		if($(elements[i]).val().length == 0){
			$.messageBox({
				type: "error",
				text: "O campo <b>" + elements[i].getAttribute("required") + "</b> &eacute; de preenchimento obrigat&oacute;rio.",
				buttons: ({
					"Ok": function(){
						$.messageBox("close");
						$(elements[i]).focus();
					}
				})
			});
			return false;
		}
	}
	return true;
}

// Fecha uma janela apartir do seu id (idtable)
function closeProgram(idtable){
	if(typeof (indexWindow.childWindows) != "undefined"){
		for(var i = 0; i < indexWindow.childWindows.length; i++){
			var win = indexWindow.childWindows[i];
			if(!win.closed && $(win).attr("name") == idtable){
				win.close();
			}
		}
	}
}

// Retorna a diferenca em dias entre duas datas
// Exemplo: diasDecorridos(new Date(1990,8,3),new Date(2012,8,21))
function diasDecorridos(dt1, dt2){
	// Variaveis auxiliares
	var minuto = 60000;
	var dia = minuto * 60 * 24;
	var horarioVerao = 0;

	// Ajusta o horario de cada objeto Date
	dt1.setHours(0);
	dt1.setMinutes(0);
	dt1.setSeconds(0);
	dt2.setHours(0);
	dt2.setMinutes(0);
	dt2.setSeconds(0);

	// Determina o fuso horario de cada objeto Date
	var fh1 = dt1.getTimezoneOffset();
	var fh2 = dt2.getTimezoneOffset();

	// Retira a diferenca do horario de verao
	if(dt2 > dt1){
		horarioVerao = (fh2 - fh1) * minuto;
	}else{
		horarioVerao = (fh1 - fh2) * minuto;
	}

	var dif = Math.abs(dt2.getTime() - dt1.getTime()) - horarioVerao;
	return Math.ceil(dif / dia);
}

// Extrai e executa o javascript de um texto
function extractScript(script){
	// Verifica se existe um erro do php no texto
	var arr_error = ["Fatal error:", "Parse error:"];
	for(var i in arr_error){
		if(script.indexOf(arr_error[i]) !== -1){
			logError("Erro retornado do PHP ao executar AJAX\r\n\r\n" + script);
			alert(script);
			return false;
		}
	}
	// Inicia a funcao
	var ini, pos_src, fim, codigo;
	var objScript = null;
	ini = script.indexOf("<script", 0);
	while(ini !== -1){
		var objScript = document.createElement("script");
		objScript.type = "text/javascript";
		pos_src = script.indexOf(" src", ini);
		ini = script.indexOf('>', ini) + 1;
		if(pos_src < ini && pos_src >= 0){
			ini = pos_src + 4;
			fim = script.indexOf(".", ini) + 4;
			codigo = script.substring(ini, fim);
			codigo = codigo.replace("=", "").replace(" ", "").replace("\"", "").replace("\"", "").replace("\'", "").replace("\'", "").replace(">", "");
			objScript.src = codigo;
		}else{
			fim = script.indexOf("</script>", ini);
			codigo = script.substring(ini, fim);
			objScript.text = codigo;
		}
		document.body.appendChild(objScript);
		ini = script.indexOf("<script", fim);
		document.body.removeChild(objScript);
	}
}

function filterchild(id, values, cascade){ // Filtra todos os combos que tem como o atributo 'parentId' o valor passado pelo parametro 'id'
	if(cascade === undefined){ // Controla o numero de voltas que ja deu na funcao
		cascade = 0;
	}else{
		cascade++;
	}
	var elem = $("*[parentId='" + id + "']");
	if($(elem).length > 0){
		var width = $(elem).css("width");
		$(elem).html("<option value=''>Carregando...</option>");
		if(navigator.appName === "Microsoft Internet Explorer"){
			$(elem).css("width", width);
		}
		var file_name = "../" + (location.pathname.indexOf("/mobile/") > -1 ? "../" : "") + "ajax/" + $(elem).attr("ajaxFile") + ".php";
		$.ajax({
			async: false,
			url: file_name,
			data: $(elem).attr("parentField") + "=" + $("#" + id).val(),
			success: function(html){
				$(elem).html(html);
				if(values !== undefined){
					if(typeof values === "object"){
						$(elem).val(values[cascade]);
					}else{
						$(elem).val(values);
					}
					if($(elem).val() === null){
						$(elem).val("");
					}
				}
				if($(elem).length > 0){
					filterchild($(elem).attr("id"), values, cascade);
				}
				if($(elem).is("[multiple][gear]")){
					$(elem).find("option[value='']").remove();
					$(elem).multipleSelect("refresh");
				}
			}
		});
	}
}

function filterchildlist(objeto, childlist){
	var valor = new Array();
	objeto.find("[key]:checked").each(function(i){
		valor[i] = ($(this).attr("key"));
	});
	$.ajax({
		async: false,
		url: "../ajax/" + childlist + "list.php",
		data: ({
			valor: valor
		}),
		success: function(html){
			$("#cod" + childlist).html(html);
		}
	});
}

function findFirstWindow(win){
	/*
	 if(typeof(win) == "undefined"){
	 win = window;
	 }
	 return (win.opener ? findFirstWindow(win.opener) : win);
	 */
	return (window.opener ? window.opener : window);
}

function in_array(needle, haystack, argStrict){
	var key = '', strict = !!argStrict;
	if(strict){
		for(key in haystack){
			if(haystack[key] === needle){
				return true;
			}
		}
	}else{
		for(key in haystack){
			if(haystack[key] == needle){
				return true;
			}
		}
	}
	return false;
}

function isFrame(){
	return (window.location != window.parent.location);
}

function keycode(key){
	if(window.event){
		return key.keyCode;
	}else if(key.which){
		return key.which;
	}
}

function keypressPk(key, elem){
	if(keycode(key) == 13){
		var val = $(elem).val();
		$.cadastro.limpar();
		$(elem).val(val);
		$.cadastro.pesquisar();
	}
}

function limpar_cache(){
	return true;

	var pagina = window;
	var request = new XMLHttpRequest();
	request.open("GET", pagina, false);
	request.send(null);
	if(!request.getResponseHeader("Date")){
		var cached = request;
		request = new XMLHttpRequest();
		var ifModifiedSince = (cached.getResponseHeader("Last-Modified") || new Date(0));
		request.open("GET", pagina, false);
		request.setRequestHeader("If-Modified-Since", ifModifiedSince);
		request.send("");
		if(request.status == 304){
			request = cached;
		}
	}
}

function logError(log){
	$.ajax({
		url: "../ajax/logerror.php",
		type: "POST",
		data: {
			log: log
		}
	});
}

function number_format(number, decimals, dec_point, thousands_sep){
	var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
	var d = dec_point == undefined ? "," : dec_point;
	var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
	var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
	return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function opendiv(grpDivs, div){ // Abre uma div e esconde todas as outras do seu grupo
	for(var i = 0; i < grpDivs.length; i++){
		$("#" + grpDivs[i]).hide();
	}
	$("#" + div).show();
}

function openProgram(idtable, param){
	var encontrou = false;
	if(idtable == request("idtable")){
		if($("[primarykey]").length > 0){
			$.cadastro.limpar();
			var arr_primarykey = $("[primarykey]").map(function(){
				return $(this).attr("id");
			});
			var arr_param = param.split("&");
			for(var i = 0; i < arr_param.length; i++){
				var valor = arr_param[i].split("=");
				if(in_array(valor[0], arr_primarykey)){
					$("#" + valor[0]).val(valor[1]);
				}
			}
			if($("[primarykey][value='']").length == 0){
				$.cadastro.pesquisar();
				$.modalWindow("close");
			}
		}
		encontrou = true;
	}else if(typeof (indexWindow.childWindows) != "undefined"){
		for(var i = 0; i < indexWindow.childWindows.length; i++){
			var win = indexWindow.childWindows[i];
			if(!win.closed && $(win).attr("name") == idtable){
				encontrou = true;
				if(typeof (param) == "undefined"){
//					win.alert("Voc\u00EA foi redirecionado para: " + indexWindow.childWindows[i].document.title);
					if(indexWindow != window){
						indexWindow.openProgram(idtable, param);
						return true;
					}
					win.focus();
				}else{
					$.messageBox({
						type: "alert",
						title: "Aten&ccedil;&atilde;o",
						text: "A janela aberta carregar&aacute; novos dados.<br>Deseja fechar a janela j&aacute; carregada para o carregamento dos novos dados?",
						buttons: ({
							"Sim": function(){
								closeProgram(idtable);
								_openProgram(idtable, param);
								$.messageBox("close");
							},
							"N\u00E3o": function(){
								$.messageBox("close");
							}
						})
					});
				}
			}
		}
	}
	if(!encontrou){
		_openProgram(idtable, param);
	}
}

function _openProgram(idtable, param){
//	var win = window.open("../form/loading.php",idtable);
	var win = indexWindow.open("", idtable);
	$(win).attr("name", idtable);
	$.ajax({
		url: "../ajax/openprogram.php",
		data: ({
			idtable: idtable
		}),
		dataType: "html",
		success: function(html){
			var url = trim(html);
			if(url.length > 0){
				url = url + (strpos(url, "?", 0) ? "&" : "?") + "idtable=" + idtable;
				if(typeof (param) == "string"){
					url = url + "&" + param;
				}
				win.location.href = url;
			}else{
				win.close();
			}
		}
	});
}

function padrao(variavel, valor){
	return (typeof (variavel) == "undefined" ? valor : variavel);
}

function param(idparam, codparam){
	var r;
	$.ajax({
		async: false,
		url: "../ajax/param.php",
		data: ({
			idparam: idparam,
			codparam: codparam
		}),
		success: function(html){
			r = html;
		}
	});
	return r;
}

// Chama funcoes do support/function.php
function phpFunction(funcao, arr_parametro){
	var retorno;
	$.ajax({
		async: false,
		url: "../ajax/function.php",
		data: ({
			funcao: funcao,
			arr_parametro: arr_parametro
		}),
		success: function(html){
			retorno = html;
		}
	});
	return retorno;
}

// Le um arquivo na maquina local
function read_file(file_name){
	return $("#WSApplet")[0].read_file(file_name);
}

// Deixa a pagina de todas as abas do mesmo tamanho
function resizeTabPages(){
	var ul = $("ul.tabs:not(:first)");
	/*if($("#hdnCadTable").length > 0){
	 ul = ul.filter(":not(:first)");
	 }*/
	$(ul).each(function(){
		var li = $(this).children().filter("li[pageid]");
		if($(li).parents(".tabpage").length > 1){
			return true;
		}
		var height = 0;
		$(li).each(function(){
			var div = $("#" + $(this).attr("pageid"));
			if($(div).height() > height){
				height = $(div).height();
			}
		});
		$(li).each(function(){
			$("#" + $(this).attr("pageid")).height(height);
		});
	});
}

function request(name){
	var arr = window.location.search.substr(1).split("&");
	for(var i in arr){
		if(!isNaN(i) && name === arr[i].split("=")[0]){
			return arr[i].split("=")[1];
		}
	}
	return null;
}

function remove_special(text){
	var specialChars = [
		{val: "a", left: "\u00E0\u00E1\u00E2\u00E3\u00E4"},
		{val: "e", left: "\u00E8\u00E9\u00EA"},
		{val: "i", left: "\u00EC\u00ED\u00EE\u00EF"},
		{val: "o", left: "\u00F2\u00F3\u00F4\u00F5\u00F6"},
		{val: "u", left: "\u00F9\u00FA\u00FB\u00FC"},
		{val: "c", left: "\u00E7"},
		{val: "A", left: "\u00C0\u00C1\u00C2\u00C3\u00C4"},
		{val: "E", left: "\u00C8\u00C9\u00CA\u00CB"},
		{val: "I", left: "\u00CC\u00CD\u00CE\u00CF"},
		{val: "O", left: "\u00D2\u00D3\u00D4\u00D5\u00D6"},
		{val: "U", left: "\u00D9\u00DA\u00DB\u00DC"},
		{val: "C", left: "\u00C7"}
	];
	for(var i = 0; i < specialChars.length; i++){
		var regex = new RegExp("[" + specialChars[i].left + "]", "g");
		text = text.replace(regex, specialChars[i].val);
	}
	return text;
}

function settipopessoa(tipomask, tipopessoa, element){
	$(element).val("");
	$("[alertValid][for='" + $(element).attr("id") + "']").remove();
	switch(tipomask){
		case "cpfcnpj":
			if(tipopessoa === "F"){
				$(element).attr("mask", "cpf").setMask("cpf");
			}else if(tipopessoa === "J"){
				$(element).attr("mask", "cnpj").setMask("cnpj");
			}
			break;
		case "rgie":
			if(tipopessoa === "F"){
				$(element).attr("mask", "rg").setMask("rg");
			}else if(tipopessoa === "J"){
				$(element).attr("mask", "ie").setMask("ie").unbind("keyup").bind("keyup", function(e){
					if(e.keyCode === 73){
						$(this).val("ISENTO");
					}
				});
			}
			break;
	}
}

function shadeColor(color, percent){
	if(color.length === 4){
		color = "#" + color.substr(1,1).repeat(2) + color.substr(2,1).repeat(2) + color.substr(3,1).repeat(2);
	}

    var R = parseInt(color.substring(1,3),16);
    var G = parseInt(color.substring(3,5),16);
    var B = parseInt(color.substring(5,7),16);

    R = parseInt(R * (100 + percent) / 100);
    G = parseInt(G * (100 + percent) / 100);
    B = parseInt(B * (100 + percent) / 100);

    R = (R<255)?R:255;
    G = (G<255)?G:255;
    B = (B<255)?B:255;

    var RR = ((R.toString(16).length===1)?"0"+R.toString(16):R.toString(16));
    var GG = ((G.toString(16).length===1)?"0"+G.toString(16):G.toString(16));
    var BB = ((B.toString(16).length===1)?"0"+B.toString(16):B.toString(16));

    return "#"+RR+GG+BB;
}

function showelementmenu(elemid){
	var element = $("#" + elemid);
	var id = "cwMenuElement_" + elemid;
	$("*[cwMenuElement]").remove();
	var menu = document.createElement("div");
	menu.setAttribute("id", id);
	document.body.appendChild(menu);
	menu = $("#" + id);
	menu.attr("cwMenuElement", "true");
	menu.css("background", "#FFFFFF");
	menu.css("border", "1px solid #999999");
	menu.css("left", mouseX - 2);
	menu.css("position", "absolute");
	menu.css("top", mouseY - 2);

	if(element.attr("foreignkey")){
		var fk = element.attr("foreignkey").split(",");
		menu.append("<div style='padding:2px; padding-bottom:none' onClick='openfkwindow(&quot;" + fk[0] + "&quot;)'><label>Abrir cadastro de " + fk[1] + "</label></div>");
		menu.append("<div style='padding:2px; padding-bottom:none' onClick='$(&quot;#" + elemid + "&quot;).refreshComboBox()'><label>Recarregar lista</label></div>");
	}

	$("#" + id + " > div").mouseover(function(){
		$(this).css("background-color", "#EAE0FF");
	}).mouseout(function(){
		$(this).css("background-color", "#FFFFFF");
	}).click(function(){
		menu.remove();
	}).css({
		"height": "14px"
	});

	$("*").mousemove(function(){
		if(mouseX < parseInt(menu.css("left").replace("px", "") - 10)
				|| mouseX > parseInt(menu.css("left").replace("px", "")) + menu.width() + 10
				|| mouseY < parseInt(menu.css("top").replace("px", "")) - 10
				|| mouseY > parseInt(menu.css("top").replace("px", "")) + menu.height() + 10
				){
			menu.remove();
			$("*").unbind("mousemove");
		}
	});
}

function substr_count(haystack, needle, offset, length){
	var pos = 0;
	var cnt = 0;
	haystack += "";
	needle += "";
	if(isNaN(offset)){
		offset = 0;
	}
	if(isNaN(length)){
		length = 0;
	}
	offset--;
	while((offset = haystack.indexOf(needle, offset + 1)) != -1){
		if(length > 0 && (offset + needle.length) > length){
			return false;
		}else{
			cnt++;
		}
	}
	return cnt;
}

function strpos(haystack, needle, offset){ // haystack = texto || needle = string a ser procurada || offset = posicao inicial da busca
	var i = (haystack + '').indexOf(needle, (offset ? offset : 0));
	return (i === -1 ? false : i);
}

function strtofloat(text, zeroifempty){
	if(zeroifempty == true && text.length == 0){
		text = "0";
	}
	return parseFloat(text.replace(",", "."));
}

function strtoobj(string, changeid){
	var object = {};
	if(typeof (string) == "undefined" || string.length == 0){
		return object;
	}
	if(typeof (changeid) == "undefined" || changeid !== true){
		changeid = false;
	}
	var a = string.split(",");
	for(var i in a){
		if(!isNaN(i) && strpos(a[i], ":") !== false && i != "sum"){
			var b = a[i].split(":");
			if(b.length == 2){
				b[1] = (changeid ? $("#" + b[1]).val() : b[1]);
				var arr = new Array();
				arr[b[0]] = b[1];
				$.extend(object, arr);
			}
		}
	}
	delete object["sum"];
	return object;
}

function str_repeat(input, multiplier){
	return new Array(multiplier + 1).join(input);
}

// Verifica se existe quebra de linha em um texto, e substitue o caracter da quebra por '\\n' (Essa funcao e usado para mandar texto com quebras para variaveis no cabecario)
// (IMPLEMENTACAO) Verifica se existe outros caracteres especiais
function textwarp(text){
	aux = "";
	if(typeof (text) == "string"){
		for(var i = 0; i < text.length; i++){
			switch(text.charCodeAt(i)){
				case 10:
					aux += "%5Cn";
					break; // Enter
				case 34:
					aux += "%22";
					break; // Aspas duplas
				case 37:
					aux += "%25";
					break; // Percentual
				case 38:
					aux += "%26";
					break; // E comercial
				case 39:
					aux += "%27";
					break; // Aspas Simples
				case 92:
					aux += "%5C";
					break; // Verifica barra
				default:
					aux += text.substr(i, 1);
					break;
			}
		}
	}
	aux = decodeURI(aux);
	return aux;
}

function trim(string){ // Limpa os espacos na ponta de uma string
	if(string != null){
		return string.replace(/^\s*/, "").replace(/\s*$/, "");
	}else{
		return "";
	}
}

function urlParams(){
	var search = document.location.search.split("+").join(" ");
	var params = {};
	var tokens = null;
	var re = /[?&]?([^=]+)=([^&]*)/g;
	var val = null;

	while(tokens = re.exec(search)){
		val = decodeURIComponent(tokens[2]);
		if(val === undefined || val === "undefined"){
			val = null;
		}
		params[decodeURIComponent(tokens[1])] = val;
	}

	return params;
}

function usuario_nome(usuario, callback){
	$.ajax({
		url: "../ajax/usuario_nome.php",
		data: ({
			usuario: usuario
		}),
		success: function(html){
			if($.isFunction(callback)){
				callback(html);
			}
		}
	});
}

//validar um codigo de barras
function valid_codigo_barras_boletos(codigobarras, tipo){
	var valido;
	$.ajax({
		async: false,
		url: "../ajax/valida_codigo_barras_boletos.php",
		data: ({
			codigobarras: codigobarras,
			tipo: tipo
		}),
		success: function(html){
			valido = (html == "T");
			//alert(html);
		}
	});
	return valido;
}

function valid_cnpj(cnpj){
	var numeros, digitos, soma, i, resultado, pos, tamanho, digitos_iguais;
	cnpj = cnpj.replace(".", "").replace(".", "").replace("/", "").replace("-", "");
	if(cnpj.split(cnpj.substring(0, 1)).join("").length == 0){
		return false;
	}

	digitos_iguais = 1;
	if(cnpj.length < 14 && cnpj.length < 15){
		return false;
	}
	for(i = 0; i < cnpj.length - 1; i++){
		if(cnpj.charAt(i) != cnpj.charAt(i + 1)){
			digitos_iguais = 0;
			break;
		}
	}
	if(!digitos_iguais){
		tamanho = cnpj.length - 2
		numeros = cnpj.substring(0, tamanho);
		digitos = cnpj.substring(tamanho);
		soma = 0;
		pos = tamanho - 7;
		for(i = tamanho; i >= 1; i--){
			soma += numeros.charAt(tamanho - i) * pos--;
			if(pos < 2){
				pos = 9;
			}
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if(resultado != digitos.charAt(0)){
			return false;
		}
		tamanho = tamanho + 1;
		numeros = cnpj.substring(0, tamanho);
		soma = 0;
		pos = tamanho - 7;
		for(i = tamanho; i >= 1; i--){
			soma += numeros.charAt(tamanho - i) * pos--;
			if(pos < 2){
				pos = 9;
			}
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if(resultado != digitos.charAt(1)){
			return false;
		}
		return true;
	}else{
		return false;
	}
}

function valid_cpf(cpf){
	var numeros, digitos, soma, i, resultado, digitos_iguais;
	digitos_iguais = 1;
	cpf = cpf.replace(".", "").replace(".", "").replace("-", "");
	if(cpf.split(cpf.substring(0, 1)).join("").length === 0){
		return false;
	}
	if(cpf.length !== 11){
		return false;
	}
	for(i = 0; i < cpf.length - 1; i++){
		if(cpf.charAt(i) !== cpf.charAt(i + 1)){
			digitos_iguais = 0;
			break;
		}
	}
	if(!digitos_iguais){
		numeros = cpf.substring(0, 9);
		digitos = cpf.substring(9);
		soma = 0;
		for(i = 10; i > 1; i--){
			soma += numeros.charAt(10 - i) * i;
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if(resultado !== parseInt(digitos.charAt(0))){
			return false;
		}
		numeros = cpf.substring(0, 10);
		soma = 0;
		for(i = 11; i > 1; i--){
			soma += numeros.charAt(11 - i) * i;
		}
		resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
		if(resultado !== parseInt(digitos.charAt(1))){
			return false;
		}
		return true;
	}else{
		return false;
	}
}

function valid_date(date){
	var day = date.substr(0, 2);
	var month = date.substr(3, 2);
	var year = date.substr(6, 4);
	if(isNaN(parseInt(day)) || isNaN(parseInt(month)) || isNaN(parseInt(year))){
		return false;
	}else if(day.length != 2 || month.length != 2 || year.length != 4){
		return false;
	}else if(month < 1 || month > 12){
		return false;
	}else if((day < 1) || (day > 30 && (month == 4 || month == 6 || month == 9 || month == 11)) || (day > 31)){
		return false;
	}else if(month == 2 && (day > 29 || (day > 28 && (Math.floor(year / 4) != year / 4)))){
		return false;
	}else{
		return true;
	}
}

function valid_ean(ean){ // Verifica se um ean e valido
	if(typeof (ean) != "string" || ean.length < 8){
		return false;
	}
	var par = 0;
	var imp = 0;
	var ultE = ean.substr(ean.length - 1, 1);
	for(var i = 0; i < ean.length - 1; i++){
		if(i % 2 == 0){
			par += parseInt(ean.substr(i, 1));
		}else{
			imp += parseInt(ean.substr(i, 1));
		}
	}
	imp = imp * 3;
	var tot = (par + imp) + '';
	var ultT = tot.substr(tot.length - 1, 1);
	var con = 10 - ultT;
	if(con == 10){
		con = 0;
	}
	return (con == ultE);
}

function ordenar_grid(find_check, grid){
	$(".grid").addClass("fancyTable");
	$(function(){
		var header = new Array();
		var h = new Array();
		if(typeof (grid) == "undefined"){
			grid = "#divgradepesquisa";
		}
		if(typeof (find_check) == "undefined"){
			find_check = "";
		}

		for(var x = 0; x < find_check.length; x++){
			if(find_check[x] == "B"){
				header[x] = {sorter: false};
			}
		}
		$(grid + " .grid").tablesorter({
			sortReset: true,
			sortRestart: true,
			sortInitialOrder: "desc",
			headers:
					header
		});

		for(x = 0; x < find_check.length; x++){
			if(find_check[x] == "B"){
				$(grid + " .grid th:eq(" + x + ")").removeClass("header");
			}
		}
	});
	$(grid + " .grid").bind("sortStart", function(){
		$.loading(true);
	}).bind("sortEnd", function(){
		$(grid + " .grid").refreshGridColor();
		$.loading(false);
	});
}

// Grid topo fixo
function fixa_topogrid(grid){
	if(grid === undefined){
		grid = "#divgradepesquisa";
	}


	if($(grid + " table tbody").css("position") !== "absolute"){
		$(grid + " table tbody").css({
			"height": ($(grid).height() - 24),
			"margin-left": "-2px",
			"overflow-x": "auto",
			"overflow-y": "scroll",
			"position": "absolute",
			"width": $(grid).width()
		});
	}

	$(grid + " table thead tr th").each(function(i){
		i++;
		if(i < $(grid + " tbody tr:first td").length){
			$(grid + " table tbody tr td:nth-child(" + (i) + ")").width($(this).width());
			$(grid + " table tbody tr td:nth-child(" + (i) + ") div").width($(this).width() - 1);
		}else{
			$(grid + " table tbody tr td:nth-child(" + (i) + ")").width($(this).width() - 17);
			$(grid + " table tbody tr td:nth-child(" + (i) + ") div").width($(this).width() - 18);
		}
	});
	setTimeout(function(){$(".grid").trigger("update")},3000);
}

// Cria um arquivo na maquina local
function write_file(file_name, arr_line){
	if(typeof arr_line !== "object"){
		arr_line = new Array(arr_line);
	}
	if(!$.browser.webkit){
		var fso = new ActiveXObject("Scripting.FileSystemObject");
		var fh = fso.CreateTextFile(file_name, true);
		fh.WriteLine(arr_line.join("\r\n"));
		fh.Close();
		return true;
	}else{
		if(typeof $("#WSApplet").get(0).write_file !== "function"){
			document.body.appendChild($("#WSApplet").get(0));
		}
		return $("#WSApplet").get(0).write_file(file_name, arr_line);
	}
}

// Verifica o mix de fornecedor
function fornecedor_mix(){
	var fornecedor;
	if($("#codparceiro").length){
		fornecedor = $("#codparceiro");
	}
	if($("#codfornec").length){
		fornecedor = $("#codfornec");
	}
	if($("#bf_codfornec").length){
		fornecedor = $("#bf_codfornec");
	}
	if($("#codestabelec").length == 0){
		return true;
	}

	if(fornecedor.val().length > 0){
		$.ajax({
			async: false,
			url: "../ajax/fornecedor_mix.php",
			data: ({
				codestabelec: $("#codestabelec").val(),
				codfornec: fornecedor.val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	}
	return true;
}

// Define uma fun��o que poder� ser usada para validar e-mails usando regexp
function validaremail(email){
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}

function diretorio(estabelecimento, diretorio){
	var dirname;

	$.ajax({
		async: false,
		url: "../ajax/diretorio_buscar.php",
		data: ({
			codestabelec: estabelecimento,
			diretorio: diretorio
		}),
		success: function(txt){
			dirname = txt;
		}
	});

	return dirname;
}

function filename(primarykey, arquivo){
	var filename;

	$.ajax({
		async: false,
		url: "../ajax/arquivo_buscar.php",
		data: ({
			primarykey: primarykey,
			arquivo: arquivo
		}),
		success: function(txt){
			filename = txt;
		}
	});

	return filename;
}

function proc_oferta(){
	$.ajax({
		url: "../proc/ofertaproduto.php"
	});
	console.log("oferta verificada");
}

// Modifica o :contains do jQuery para ele pesquisar case-insensitive
$.expr[":"].contains = $.expr.createPseudo(function(arg){
	return (function(elem){
		return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
	});
});

function arquivo_deletar(arquivo){
	var resp;
	$.ajax({
		async: false,
		url: "../ajax/arquivo_deletar.php",
		data: ({
			arquivo: arquivo
		}),
		success: function(html){
			resp = html;
		}
	});

	return resp;
}

function verifica_saldo(codestabelec, codproduto, saldo){
	$.messageBox({
		title: "",
		type: "alert",
		text: "O produto <b>"+codproduto+"</b> se encontra com estoque de <b>"+saldo+"</b>, deseja continuar mesmo assim?",
		buttons: {
			"Continuar": function(){
				$.ajax({
					url: "../ajax/mixproduto_alterar.php",
					data: ({
						codestabelec: codestabelec,
						codproduto: codproduto,
						disponivel: "N",
						forcar: "S"
					}),
					success: function(html){
						extractScript(html);
					}
				});
				$.messageBox("close");
			},
			"Cancelar": function(){
				$("[codproduto="+codproduto+"]").checked(true);
				$("[coluna=disponivel]").checked(true);
				$.messageBox("close");
			}
		}
	});
}

function getidtable_notafiscal(operacaonf){
	switch(operacaonf){
		case "AE":
			return "NFAjuste";
		case "AS":
			return "NFAjusteSaida";
		case "CP":
			return "NFEntrada";
		case "DC":
			return "NFDevCliente";
		case "DF":
			return "NFDevFornecedor";
		case "EC":
			return "NFRemessaCliE";
		case "EF":
			return "NFRemessaForE"
		case "EX":
			return "NfExportacao";
		case "IM":
			return "NFImportacao";
		case "PD":
			return "NFPerda";
		case "PR":
			return "NFProdutor";
		case "RC":
			return "NFRemessaCli";
		case "RF":
			return "NFRemessaFor";
		case "TE":
			return "CadNotaFiscalTE";
		case "TS":
			return "CadNotaFiscalTS";
		case "VD":
			return "NFSaida";
		case "NC":
			return "NotaConsumidor";
		case "SS":
			return "NotaServicoSS";
		case "SE":
			return "NotaServicoES";
		default:
			return false;
			break;
	}
}
