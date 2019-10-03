$.cadastro.after.cancelar = function(){
	localimpressora_novo();
}

$.cadastro.after.deletar = function(){
	localimpressora_novo();
}

$.cadastro.after.carregar = function(){
	tipovenda_out();
}

$.cadastro.after.inserir = function(){
	localimpressora_novo();
}

$.cadastro.after.status = function(status){
	$("#btn_localimpressora_abrir").disabled([2, 3].indexOf(status) === -1);
}

$(document).bind("ready", function () {
	$("[id^='tipovenda_']").bind("click", function () {
		tipovenda_in();
	});
});

function localimpressora_abrir(){
   localimpressora_desenhar(function(){
      $.modalWindow({
         content: $("#modal_localimpressora"),
         title: "Local de impressora por usuário",
         height: "322px",
         width: "500px"
      });
   });
}

function localimpressora_alterar(element){
	var login = $(element).attr("login");
	var localimpressora = $(element).val();
	$.ajax({
      url: "../ajax/parampontovenda_localimpressora_alterar.php",
		data: {
			login: login,
			localimpressora: localimpressora
		}
   });
}

function localimpressora_desenhar(callback){
   $.loading(true);
   $.ajax({
      url: "../ajax/parampontovenda_localimpressora_desenhar.php",
      success: function(html){
         $.loading(false);
         $("#grid_localimpressora").html(html);
			if(callback !== undefined){
				callback();
			}
      }
   });
}

function localimpressora_excluir(login){
   $.loading(true);
   $.ajax({
      url: "../ajax/parampontovenda_localimpressora_excluir.php",
		data: {
			login: login
		},
      success: function(){
         localimpressora_desenhar();
      }
   });
}

function localimpressora_fechar(){
   $.modalWindow("close");
}

function localimpressora_incluir(){
   if($("#localimpressora_login").val() === null || $("#localimpressora_login").val().length === 0){
      $("#localimpressora_login").focus();
      return false;
   }

   $.loading(true);
   $.ajax({
      url: "../ajax/parampontovenda_localimpressora_incluir.php",
      data: {
         login: $("#localimpressora_login").val()
      },
      success: function(){
         localimpressora_desenhar();
      }
   });
}

function localimpressora_novo(){
   $.ajax({
      url: "../ajax/parampontovenda_localimpressora_novo.php"
   });
}

function tipovenda_in() {
	var tipovenda = $("[id^='tipovenda_']:checked").map(function () {
		return $(this).attr("id").substr(-1);
	}).get().join("");
	$("#tipovenda").val(tipovenda);
}

function tipovenda_out() {
	var tipovenda = $("#tipovenda").val();
	$("[id^='tipovenda_']").checked(false);
	for (var i = 0; i < tipovenda.length; i++) {
		$("#tipovenda_" + tipovenda.substr(i, 1)).checked(true);
	}
}
