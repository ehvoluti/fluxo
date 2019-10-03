var __supervisor_settings = null;

$.superVisor = function(settings){

	if(typeof settings === "string"){
		switch(settings){
			case "close":
				$.modalWindow("close");
				$("[__supervisor]").remove();
				break;
		}
		return true;
	}

	settings = $.extend({
		type: null,
		success: function(){},
		fail: function(){}
	}, settings);

	__supervisor_settings = settings;

	// Verifica se existe ao menos um supervisor
	var abortar = false;
	$.ajax({
		async: false,
		url: "../ajax/supervisor_existe.php",
		data: ({
			type: settings.type
		}),
		success: function(html){
			abortar = (html === "0");
		}
	});
	if(abortar){
		settings.success();
		return true;
	}

	var div, table, tr, td, label, input, div2;

	div = document.createElement("div");
	document.body.appendChild(div);
	$(div).attr("__supervisor", true).css("display", "none");

	table = document.createElement("table");
	div.appendChild(table);

	tr = document.createElement("tr");
	table.appendChild(tr);

	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "label2");

	label = document.createElement("label");
	td.appendChild(label);
	$(label).attr("for", "__supervisor_login").html("Usu&aacute;rio:");

	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "field");

	input = document.createElement("input");
	input.type = "text";
	td.appendChild(input);
	$(input).attr({
		"id": "__supervisor_login",
		"class": "field_full"
	});

	tr = document.createElement("tr");
	table.appendChild(tr);

	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "label2");

	label = document.createElement("label");
	td.appendChild(label);
	$(label).attr("for", "__supervisor_senha").html("Senha:");

	td = document.createElement("td");
	tr.appendChild(td);
	$(td).attr("class", "field");

	input = document.createElement("input");
	input.type = "password";
	td.appendChild(input);
	$(input).attr({
		"id": "__supervisor_senha",
		"class": "field_full"
	});

	div2 = document.createElement("div");
	div.appendChild(div2);
	$(div2).css({
		"padding-top": "10px",
		"text-align": "center"
	});

	input = document.createElement("input");
	input.type = "button";
	div2.appendChild(input);
	$(input).attr({
		"id": "__supervisor_confirmar",
		"value": "Confirmar"
	}).css({
		"margin-right": "5px"
	}).bind("click", function(){
		if($("#__supervisor_login").val().length === 0){
			$("#__supervisor_login").focus();
			return false;
		}
		if($("#__supervisor_senha").val().length === 0){
			$("#__supervisor_senha").focus();
			return false;
		}
		$.ajax({
			async: false,
			url: "../ajax/supervisor_verificar.php",
			data: ({
				type: __supervisor_settings.type,
				login: $("#__supervisor_login").val(),
				senha: $("#__supervisor_senha").val()
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});

	input = document.createElement("input");
	input.type = "button";
	div2.appendChild(input);
	$(input).attr({
		"value": "Cancelar"
	}).bind("click", function(){
		$.superVisor("close");
		__supervisor_settings.fail();
	});

	$("[__supervisor] *").css("font-size", "11pt");
	$("[__supervisor] input:button").height("34px");
	$("#__supervisor_login,#__supervisor_senha").bind("keypress", function(e){
		if(e.keyCode === 13){
			$("#__supervisor_confirmar").click();
		}
	});

	$.modalWindow({
		title: "Supervisor",
		content: $("[__supervisor]"),
		width: "300px",
		afterClose: function(){

		}
	});

	$.gear();
};