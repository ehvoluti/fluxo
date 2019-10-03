$.fn.upload = function(settings){
	/* **************************************
	 ATENCAO
	 Preencher o parametro dirname ou filename
	 Nao deve ser preenchido os 2
	 ************************************** */
	settings = $.extend({
		url: "../ajax/upload.php",
		dirname: null, // Diretorio para onde deve mover o arquivo
		filename: null, // Nome do arquivo (deve conter diretorio) movido
		onComplete: function(){
			return true;
		}
	}, settings);

	$(this).each(function(){
		var e_file = this;
		var data = new FormData();
		data.append("dirname", settings.dirname);
		data.append("filename", settings.filename);
		$($(this)[0].files).each(function(i, file){
			data.append("file_" + i, file);
		});
		$.loading(true);
		$.ajax({
			url: settings.url,
			cache: false,
			contentType: false,
			data: data,
			processData: false,
			type: "POST",
			complete: function(){
				$(e_file).val("");
			},
			success: function(){
				$.loading(false);
				settings.onComplete();
			},
			error: function(jqXHR, textStatus, errorThrown){
				$.loading(false);
				$.messageBox({
					type: "error",
					text: "Houve uma falha ao efetuar upload do arquivo:<br>" + errorThrown
				});
			}
		});
	});

	return this;
};
