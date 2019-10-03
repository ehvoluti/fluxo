$.fn.cwMultipleSelect = function(){
	$(this).each(function(){
		// Remove a primeira opcao (vazia) do select
		$(this).find("option").each(function(){
			if($(this).val().length === 0){
				$(this).remove();
			}
		});

		// Aplica o metodo que modifica o elemento
		$(this).multipleSelect({
			keepOpen: false,
			selectAll: true,
			width: "100%",
			selectAllText: "<b>Selecionar todos</b>",
			allSelected: "Todos selecionados",
			countSelected: "# de % selecionados",
			noMatchesFound: ""
		});
	});

	return this;
};