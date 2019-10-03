$(document).bind("ready", function(){
	$("#operacao").bind("change", function(){
		verificar_operacao();
	});
});

$.cadastro.after.alterar = function(){
	verificar_operacao();
};

$.cadastro.after.inserir = function(){
	verificar_operacao();
};

function verificar_operacao(){
	var operacao = $("#operacao").val();
	$("#codtabela").disabled(operacao !== "VD" || ["2", "3"].indexOf($.cadastro.status()) === -1);
	if($("#operacao").val() == "VD"){
		$("#informarepresentante").disabled(false);
		$("#statusemanalise").disabled(false);
	 }else{      
		$("#informarepresentante").checked(false).disabled(true);
		$("#statusemanalise").checked(false).disabled(true);
	 }
}