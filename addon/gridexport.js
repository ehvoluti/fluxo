$.fn.gridExport = function(){
	$(this).each(function(){
		$.ajax({
			async:false,
			url:"../ajax/gridexport.php",
			data:({
				html:$(this).html()
			}),
			method:"POST",
			success:function(){
				window.open("../form/gridexport.php");	
			}
		});
	});	
	return this;
}