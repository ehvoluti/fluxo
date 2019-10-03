$(document).bind("ready", function(){
	$("input:file").bind("change", function(){
		buscar_dirname()
		$(this).upload({
			dirname: $("#_dirname").val(),
			onComplete: function(){
				$.messageBox({
					type: "success",
					text: "Arquivo enviado com Sucesso!",
					focusOnClose: $(this)
				});
			}
		});
	});
});

function buscar_dirname(){
	$.ajax({
		async: false,
		url: "../ajax/retornobancario_buscar_dirname.php",
		data: ({
			codestabelec: $("#codestabelec").val()
		}),
		success: function(html){
			$("#_dirname").val(html);
		}
	});
}
