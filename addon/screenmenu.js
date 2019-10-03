$(document).bind("ready", function(){
	if($("#mainForm").length === 0){
		return false;
	}
	$.ajax({
		async: false,
		url: "../misc/screenmenu.php",
		success: function(html){
			$("body").append(html);
			$.screenMenu.create();
		}
	});
});