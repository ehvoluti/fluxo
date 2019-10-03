$.cadastro.after.alterar = function(){
	tabela_ajusteicms();
};

$.cadastro.after.inserir = function(){
	tabela_ajusteicms();
};

$.cadastro.after.limpar = function(){
	$("#codajuste").unbind("change");
};

function tabela_ajusteicms(){
	$("#codajuste").bind("change", function(){
		$.ajax({
			url: "../ajax/outroscreditodesbito_buscarinfoajuste.php",
			data: ({
				codajuste: $(this).val(),
			}),
			success: function(html){
				extractScript(html);
			}
		});
	});
}