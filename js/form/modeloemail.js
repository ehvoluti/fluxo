tinymce.remove();

tinymce.init({
	language: "pt_BR",
	selector: "textarea",
	height: 220,
	plugins: [
		"advlist autolink link image lists charmap print preview hr pagebreak spellchecker",
		"table contextmenu directionality emoticons template textcolor paste fullpage textcolor colorpicker textpattern"
	],
	toolbar1: "cut copy paste | searchreplace | bullist numlist | outdent indent blockquote | undo redo | link unlink image media code | insertdatetime preview | forecolor backcolor",
	toolbar2: "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | fontselect fontsizeselect",
	menubar: false,
	toolbar_items_size: "small",
	statusbar: false, // remove a barra inferior
	elementpath: false, // inicializa sem texto
	readonly : 1
});

$(document).bind("ready", function(){
	$("[name=tipoenvio]").checked(false);
	$("[name=tipoenvio]").change(function(){
		if($("[name=tipoenvio]:checked").attr("id") == "terceiro"){
			$(".proprio").css("display", "none");
			$("#tipoenvio").val("T");
			$("#senha-confirmar").removeAttr("required");
			$("#senha").removeAttr("required");
		}else{
			$(".proprio").css("display", "table-row");
			$("#tipoenvio").val("P");
			$("#senha-confirmar").Attr("required", "Senha");
			$("#senha").Attr("required", "Senha");
		}
	});
});

$.cadastro.after.carregar = function(){
	tinymce.get("corpo_aux").setContent($("#corpo").val());

	if($("#tipoenvio").val() == "T"){
		$("#terceiro").checked(true).trigger("change");
	}else{
		$("#proprio").checked(true).trigger("change");
	}
	$("#senha-confirmar").val($("#senha").val());
	tinymce.activeEditor.getBody().setAttribute('contenteditable', false);
	tinymce.activeEditor.setMode('readonly');
};

$.cadastro.after.alterar = function(){
	tinymce.activeEditor.getBody().setAttribute('contenteditable', true);
	tinymce.activeEditor.setMode('design');
};

$.cadastro.after.cancelar = function(){
	tinymce.activeEditor.getBody().setAttribute('contenteditable', false);
	tinymce.activeEditor.setMode('readonly');
};

$.cadastro.after.inserir = function(){
	tinymce.activeEditor.getBody().setAttribute('contenteditable', true);
	tinymce.activeEditor.setMode('design');
};

$.cadastro.before.salvar = function(){
	$("#corpo").val(tinymce.get("corpo_aux").getContent());
	if($("#senha").val() != $("#senha-confirmar").val()){
		$.messageBox({
			type:"error",
			text:"A senha para confirmação é diferente."
		});
		return false;
	}
	return true;
};

$.cadastro.before.limpar = function(){
	$("#corpo").val("");
	if(!jQuery.isEmptyObject(tinymce.get("corpo_aux"))){
		tinymce.get("corpo_aux").setContent("");
	}
	$("[name=tipoenvio]").checked(false);
	return true;
};