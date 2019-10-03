$.fn.valMask = function(value){
//	var dif = value.length - $(this).val().length;
//	var cursor = $(this)[0].selectionStart;
	$(this).val(value);
//	$(this)[0].selectionEnd = cursor + dif;
	return this;
};

var planoconta = "9.99.999";

$.fn.cwSetMask = function(){
	$.mask.rules["%"] = /[0-9,]/;
	$.mask.rules["X"] = /[0-9xX]/;
	$.mask.masks = ({
		"barrasboleto": {mask: "99999.99999.99999.999999.99999.999999.9.99999999999999"},
		"barrasboletosemponto": {mask: "99999999999999999999999999999999999999999999999"},
		"barrasconcessionaria": {mask: "999999999999.999999999999.999999999999.999999999999"},
		"porcent" : {mask: "99.99"},
		"cep": {mask: "99999-999"},
		"cest": {mask: "99.999.99"},
		"cfop": {mask: "9.9999999"},
		"chavenfe": {mask: "99999999999999999999999999999999999999999999"},
		"cnpj": {mask: "99.999.999/9999-99"},
		"codigoncm": {mask: "9999.99.99"},
		"cpf": {mask: "999.999.999-99"},
		"crc": {mask: "***************"},
		"data": {mask: "99/99/9999"},
		"ecf": {mask: "999"},
		"csticms": {mask: "999"},
		"natreceita": {mask: "999"},
		"margem": {mask: '999', type: 'reverse', signal: ''},
		"caixa": {mask: "999"},
		"ean": {mask: ((typeof param_cadastro_eanletras !== 'undefined') ? ((param_cadastro_eanletras == "S") ? "********************" : "99999999999999999") : "99999999999999999")},
		"hora": {mask: "99:99:99"},
		"int2": {mask: "99"},
		"inteiro": {mask: "999999999999999", type: "reverse"},
		"inteiro5": {mask: "999999", type: "reverse"},
		"inteiro2": {mask: "9999999999999999999999999", type: "reverse"},
		"inteiro3": {mask: "999", type: "reverse"},
		//"chavenfe":{mask:"99999999999999999999999999999999999999999999",type:"reverse"},
		"planoconta": {mask: planoconta},
		"rg": {mask: "99.999.999-XX"},
		"senhaativacao": {mask: "**.**.**.**.**.**.**.**.**.**"},
		"senhamensal": {mask: "99.99.99.99.99.99.99.99.99.99.99.99.99.99.99.99.99.99.99.99"},
		"telefone": {mask: "(99) 9999-99999"},
		"telefone2": {mask: "(99) 99999-9999"},
		"telefone3":{mask: "9800 999 9999"},
		"veiculo": {mask: "aaa-9999"},
		"ie": {mask: "999.999.999.999.999.999"},
		"ie-AC": {mask: "99.999.999/999-99"},
		"ie-AL": {mask: "999999999"},
		"ie-AP": {mask: "999999999"},
		"ie-AM": {mask: "99.999.999-9"},
		"ie-BA": {mask: "999999-99"},
		"ie-CE": {mask: "99999999-9"},
		"ie-DF": {mask: "99.999999.999-99"},
		"ie-ES": {mask: "99999999-9"},
		"ie-GO": {mask: "99.999.999-9"},
		"ie-MA": {mask: "999999999"},
		"ie-MT": {mask: "9999999999-9"},
		"ie-MS": {mask: "99999999-9"},
		"ie-MG": {mask: "9999999999999"},
		"ie-PA": {mask: "99-999999-9"},
		"ie-PB": {mask: "99999999-9"},
		"ie-PR": {mask: "99999999-99"},
		"ie-PE": {mask: "9999999-99"},
		"ie-PI": {mask: "999999999"},
		"ie-RJ": {mask: "99.999.99-9"},
		"ie-RN": {mask: "99.9.999.999-9"},
		"ie-RS": {mask: "999/9999999"},
		"ie-RO": {mask: "9999999999999-9"},
		"ie-RR": {mask: "99999999-9"},
		"ie-SC": {mask: "999.999.999"},
		"ie-SP": {mask: "999.999.999.999"},
		"ie-SE": {mask: "99999999-9"},
		"ie-TO": {mask: "99999999999"}
	});

	$(this).filter("input:text[mask]").not("[mask^='decimal']").setMask({
		attr: "mask",
		fixedChars: "[().:/ -]"
	});

	$(this).filter("[mask='data']").each(function(){
		$(this).datepicker();
	});

	$(this).filter("input:text[mask^='decimal']").decimalFormat();

	$(this).filter("input:text[mask='data']").bind("blur", function(){ // Completa a data quando sair do campo
		var data = $(this).val();
		switch(data.length){
			case 1:
				data = "0" + data + "/" + server.month + "/" + server.year;
				break;
			case 2:
				data = data + "/" + server.month + "/" + server.year;
				break;
			case 3:
				data = "/" + server.month + "/" + server.year;
				break;
			case 4:
				data = data.substr(0, 3) + "0" + data.substr(3, 1) + "/" + server.year;
				break;
			case 5:
				data = data + "/" + server.year;
				break;
			case 6:
				data = data + server.year;
				break;
			case 7:
				data = data.substr(0, 6) + "0" + data.substr(6, 1);
				break;
			case 8:
				var ano = data.substr(6, 2);
				ano = (parseInt(ano) <= 60 ? "20" + ano : "19" + ano);
				data = data.substr(0, 6) + ano;
				break;
			case 9:
				data = data.substr(0, 8);
				break;
		}
		$(this).val(data);
	});

	$(this).filter("input:text[mask='hora']").bind("blur", function(){ // Completa a hora quando sair do campo
		var hora = $(this).val();
		switch(hora.length){
			case 1:
				hora = "0" + hora + ":00:00";
				break;
			case 2:
				hora = hora + ":00:00";
				break;
			case 3:
				hora = hora + "00:00";
				break;
			case 4:
				hora = hora.substr(0, 3) + "0" + hora.substr(3, 1) + ":00";
				break;
			case 5:
				hora = hora + ":00";
				break;
			case 6:
				hora = hora + "00";
				break;
			case 7:
				hora = hora.substr(0, 6) + "0" + hora.substr(6, 1);
				break;
		}
		$(this).val(hora);
	});

	$(this).filter("input:text[mask='veiculo']").bind("change", function(){
		$(this).val($(this).val().toUpperCase());
	});

	$(".ui-datepicker-trigger").bind("dblclick", function(){
		var element = $(this).prev();
		if(!element.is(":disabled")){
			if(!serverDate){
				$.ajax({
					async: false,
					url: "../ajax/dataservidor.php",
					success: function(html){
						serverDate = html;
					}
				});
			}
			element.val(serverDate).focus();
		}
	});

	$(this).filter("input:text[mask='ie']").bind("keyup", function(e){
		if(e.keyCode === 73){
			$(this).val("ISENTO");
		}
	});

	// Trata mascara de telefone para aceitar 9 digitos
	$(this).filter("input:text[mask^='telefone']").bind("keyup", function(){
		if($(this).attr("mask") === "telefone" && $(this).val().length === 15){
			$(this).attr("mask", "telefone2").setMask("telefone2");
		}
		if(($(this).attr("mask") == "telefone" || $(this).attr("mask") == "telefone2") && $(this).val() == "(08) 00"){
			$(this).attr("mask", "telefone3").setMask("telefone3");
		}
		if($(this).attr("mask") === "telefone2" && $(this).val().length === 14){
			$(this).attr("mask", "telefone").setMask("telefone");
		}

		if($(this).attr("mask") === "telefone3" && $(this).val().substr(0,1) != "0"){
			$(this).attr("mask", "telefone").setMask("telefone");
		}
	});

	return this;
};

// Colocar virgula no keyup
function keyup_comma(number){
	aux = number.value;	
	aux = aux.replace(/\D/g,"") //permite digitar apenas números	
	aux = aux.replace(/[0-9]{12}/,"inválido") //limita pra máximo 999.999.999,99	
	aux = aux.replace(/(\d{1})(\d{8})$/,"$1.$2") //coloca ponto antes dos últimos 8 digitos		
	aux = aux.replace(/(\d{1})(\d{1,2})$/,"$1,$2") //coloca virgula antes dos últimos 2 digitos	
	number.value = aux;		
}