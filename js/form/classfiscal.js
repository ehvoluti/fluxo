$(document).bind("ready", function(){
	$("#codcstpart, #origem").bind("change", function(){
		$("#codcst").val(String($("#origem").val()) + String($("#codcstpart").val()));
		$("#motivodesoneracao").disabled($(this).val() !== "30");
	});
});

$.cadastro.after.carregar = function(){
	$("#origem").val($("#codcst").val().substr(0, 1));
	$("#codcstpart").val($("#codcst").val().substr(1, 2));
};

$.cadastro.after.inserir = function(){
	$("#motivodesoneracao").disabled(true);
}

