$(document).bind("ready", function(){
	$("input:file").bind("change", function(){
		if($("#dtimport").val().length === 0){
			$.messageBox({
				type: "error",
				text: "Informe a data de importação.",
				focusOnClose: $("#dtimport")
			});
			return false;
		}
		$(this).upload({
			filename: "../temp/upload/relatorio.adw",
			onComplete: function(){
				importar();
			}
		});
	});
});

function importar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/adw_importar.php",
		data: ({
			codestabelec: $("#codestabelec").val(),
			dtimport: $("#dtimport").val()
		}),
		success: function(html){
			$.loading(false);
			if(!$("#divAdw").is(":visible")){
				$("#divAdw").height("0px").show().animate({
					"height": "345px"
				}, "slow");
			}
			$("#divGrade").html(html);
			listar();
		}
	});
	return true;
}

function listar(){
	$.loading(true);
	$.ajax({
		url: "../ajax/adw_listar.php",
		data: ({
			dtimport: $("#dtimport").val()
		}),
		success: function(html){
			if(!$("#divAdw").is(":visible")){
				$("#divAdw").height("0px").show().animate({
					"height": "345px"
				}, "slow");
			}
			$("#divGrade").html(html);
			$.loading(false);
		}
	});
	return true;
}