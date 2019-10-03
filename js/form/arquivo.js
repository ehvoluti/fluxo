$(document).bind("ready", function(){
	setInterval(function(){
		arquivo_listar();
	}, 1000);

	arquivo_listar();
});

function arquivo_download(codarquivo){
	window.open("../ajax/arquivo_download.php?codarquivo=" + codarquivo);
}

function arquivo_listar(){
	$.ajax({
		url: "../ajax/arquivo_listar.php",
		success: function(result){
			$("#arquivos").html(result);
		}
	});
}

function arquivo_remover(codarquivo){
	$.loading(true);
	$.ajax({
		url: "../ajax/arquivo_remover.php",
		data: {
			codarquivo: codarquivo
		},
		success: function(result){
			$.loading(false);
			extractScript(result);
		}
	});
}