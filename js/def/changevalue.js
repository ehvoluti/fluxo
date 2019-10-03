$.changeValue = function(settings){

	settings = $.extend({
		table: null, // Nome da tabela a ser realizada a alteracao
		key: null, // Valor da chave primaria da tabela (deve ser passado em Array caso seja chava composta)
		columns: {}, // Colunas e valores. Exemplo: {quantidade: 2, nome: "Teste"}
		success: function(){}, // Em caso de sucesso
		fail: function(){} // Em caso de falha
	}, settings);

	$.ajax({
		url: "../ajax/changevalue.php",
		data: {
			table: settings.table,
			key: settings.key,
			columns: settings.columns
		},
		success: function(result){
			result = JSON.parse(result);
			switch(result.status){
				case "0":
					settings.success();
					break;
				case "2":
					fail: settings.fail(result.message);
					break;
			}

		},
		error: function(error){
			settings.fail(error.statusText);
		}
	});

};