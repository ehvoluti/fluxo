$.calculadora = function(settings){
	if(settings === true){
		if($("#__calculadora").length == 0){
			$.ajax({
				async:false,
				url:"../form/calculadora.php",
				success:function(html){
					$("body").append(html);
				}
			});
		}
		$("#__calculadora").show().focus();
	}else if(settings == false){
		$("#__calculadora").hide();
	}
}

$.calculadora.calcular = function(){
	var resultado = $("#__calculadora [display]").val();
	var vet = resultado.split(" ");
	switch(vet[1]){
		case "x":
			resultado = vet[0] * vet[2];
			break;
		case "÷":
			resultado = vet[0] / vet[2];
			break;
		case "+":
			resultado = parseFloat(vet[0]) + parseFloat(vet[2]);
			break;
		case "-":
			resultado = vet[0] - vet[2];
			break;
		case "v":
			resultado = Math.sqrt(vet[0]);
			break;
		case "^":
			resultado = Math.pow(vet[0],vet[2]);
			break;
	}
	resultado = String(resultado);
	var p_pos = strpos(resultado,".");
	if(p_pos !== false && resultado.substr(p_pos).length > 8){
		resultado = number_format(resultado,8,".","");
	}
	$("#__calculadora [display]").val(resultado);
}

$.calculadora.desfazer = function(){
	var vet = $("#__calculadora [display]").val().split(" ");
	switch(vet.length){
		case 1:
			vet[0] = vet[0].substr(0,vet[0].length - 1);
			break;
		case 3:
			if(vet[2].length == 0){
				vet.pop();
				vet.pop();
			}else{
				vet[2] = vet[2].substr(0,vet[2].length - 1);
			}
			break;
	}
	$("#__calculadora [display]").val(vet.join(" "));
}

$.calculadora.limpar = function(){
	$("#__calculadora [display]").val("");
}

$.calculadora.movervalor = function(){
	var vet = $("#__calculadora [display]").val().split(" ");
	if(vet.length > 1){
		$.calculadora.calcular();
	}
	var val = $("#__calculadora [display]").val();
	var e = $("[focused='true']").filter("input:text,textarea");
	if($(e).length > 0){
		switch($(e).attr("mask")){
			case "decimal2": $(e).val(number_format(val,2,",","")); break;
			case "decimal3": $(e).val(number_format(val,3,",","")); break;
			case "decimal4": $(e).val(number_format(val,4,",","")); break;
			case "inteiro": $(e).val(number_format(val,0,",","")); break;
			default: $(e).val($(e).val() + val.replace(".",",")); break;
		}
		$(e).trigger("change");
	}
}

$.calculadora.numero = function(v){
	var vet = $("#__calculadora [display]").val().split(" ");
	switch(v){
		case ".":
			if((vet.length == 1 && vet[0].length == 0) || (vet.length == 3 && vet[2].length == 0)){
				v = "0" + v;
			}else if((vet.length == 1 && strpos(vet[0],".") !== false) || (vet.length == 3 && strpos(vet[2],".") !== false)){
				v = "";
			}
			break;
	}
	$("#__calculadora [display]").val($("#__calculadora [display]").val() + v);
}

$.calculadora.operador = function(v){
	var vet = $("#__calculadora [display]").val().split(" ");
	switch(vet.length){
		case 1:
			if(vet[0].length == 0){
				$.calculadora.numero("0");
			}
			break;
		case 3:
			if(vet[2].length == 0){
				$.calculadora.desfazer();
			}else{
				$.calculadora.calcular();
			}
			break;
	}
	$("#__calculadora [display]").val($("#__calculadora [display]").val() + " " + v + " ");
}

$.calculadora.porcentagem = function(){
	var vet = $("#__calculadora [display]").val().split(" ");
	switch(vet[1]){
		case "x":
			$("#__calculadora [display]").val((vet[0] * vet[2]) / 100);
			break;
		case "+":
			$("#__calculadora [display]").val(parseFloat((vet[0] * vet[2]) / 100) + parseFloat(vet[0]));
			break;
		case "-":
			$("#__calculadora [display]").val(parseFloat(vet[0]) - parseFloat((vet[0] * vet[2]) / 100));
			break;
	}
}

$.calculadora.positivonegativo = function(){
	var vet = $("#__calculadora [display]").val().split(" ");
	switch(vet.length){
		case 1:
			if(vet[0].length > 0){
				if(vet[0].substr(0,1) == "-"){
					vet[0] = vet[0].substr(1);
				}else{
					vet[0] = "-" + vet[0];
				}
			}
			break;
		case 3:
			if(vet[2].length > 0){
				if(vet[2].substr(0,1) == "-"){
					vet[2] = vet[2].substr(1);
				}else{
					vet[2] = "-" + vet[2];
				}
			}
			break;
	}
	$("#__calculadora [display]").val(vet.join(" "));
}