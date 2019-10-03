$(document).bind("ready", function(){

	$("#editor").on("keyup", function(e){
		if(e.keyCode == 32){
			var text = $(this).text();
			// " ".charCodeAt(0) == 160
			var word = text.split(" ").join(" ").split(" ");
			var newHTML = "";

			$.each(word, function(index, value){
				value = value.trim();
				if(value.length == 0){
					return true;
				}
				switch(value.toUpperCase()){
					case "SELECT":
					case "FROM":
					case "WHERE":
					case "LIKE":
					case "BETWEEN":
					case "NOT LIKE":
					case "FALSE":
					case "NULL":
					case "FROM":
					case "TRUE":
					case "NOT IN":
					case "LIMIT":
						newHTML += "<span class='statement'>"+value+"</span>&nbsp;";
						break;
					default:
						newHTML += "<span class='other'>"+ value+"</span>&nbsp;";
				}
			});
			$(this).html(newHTML);

			//// Set cursor postion to end of text

//			var child = $(this).children();
//			var range = document.createRange();
//			var sel = window.getSelection();
//			range.setStart(child[child.length - 1], 1);
//			range.collapse(true);
//			sel.removeAllRanges();
//			sel.addRange(range);
//			$(this)[0].focus();

			range = document.createRange();
			range.collapse(true);
			$(this)[0].focus();

		}
	});
});