$(document).bind("ready", function(){
   listar();
});

function cancelar(element){
   let li = $(element).closest("li");
   let descricao = $(li).attr("descricao");
   $(li).removeAttr("descricao");

   if($(li).is("[idmercadologico]")){
      setTimeout(function(){
         $(li).children().remove();
         $(li).append("<span>" + descricao + "</span>");
      }, 50);
   }else{
      $(li).remove();
   }
}

function editar(li){
   let descricao = $(li).text();
   $(li).attr("descricao", $(li).text());
   $(li).find("span").remove();
   $(li).append("<input type='text' value='" + descricao + "' onkeypress='gravar(this)' onblur='cancelar(this)' />");
   $(li).append("<button class='button-red' onclick='remover(this)'>x</button>");
   $(li).find("input").select();
}

function gravar(element){
   if(window.event.keyCode !== 13){
      return true;
   }

   let li = $(element).closest("li");
   let div = $(li).closest("[nivel]");

   let idmercadologico = $(li).attr("idmercadologico");
   let descricao = $(li).find("input").val();
   let nivel = $(div).attr("nivel");
   let idmercadologicopai = null;
   if(nivel > 1){
      idmercadologicopai = $("div[nivel='" + (nivel - 1) + "'] input:hidden").val();
   }

   $.ajax({
      url: "../ajax/mercadologico_gravar.php",
      data: {idmercadologico, descricao, idmercadologicopai, nivel},
      success: function(result){
         extractScript(result);
      }
   });
}

function incluir(element){
   let div = $(element).closest("[nivel]");
   let nivel = parseInt($(div).attr("nivel"));
   let idmercadologicopai = null;
   if(nivel > 1){
      idmercadologicopai = $("div[nivel='" + (nivel - 1) + "'] input:hidden").val();
   }
   $(div).find("ul").append("<li></li>");
   editar($(div).find("ul li:last").get(0));
}

function listar(nivel = 1){
   let idmercadologicopai = null;
   if(nivel > 1){
      idmercadologicopai = $("div[nivel='" + (nivel - 1) + "'] input:hidden").val();
   }

   $.ajax({
      url: "../ajax/mercadologico_listar.php",
      data: {idmercadologicopai},
      success: function(result){
         result = JSON.parse(result);

         let div = $("[nivel='" + nivel + "']");
         let ul = $(div).find("ul");
         $(ul).children().find(":focus").trigger("blur");
         $(ul).children().remove();

         for(let mercadologico of result){
            $(ul).append("<li idmercadologico='" + mercadologico.idmercadologico + "' onclick='selecionar(this)' ondblclick='editar(this)'><span>" + mercadologico.descricao + "</span></li>");
         }

         $(div).find("button").disabled(false);

         $("div[nivel]").each(function(){
            if(parseInt($(this).attr("nivel")) > nivel){
               $(this).find("input:hidden").val("");
               $(this).find("li").remove();
               $(this).find("button").disabled(true);
            }
         });
      }
   });
}

function remover(element){
   let li = $(element).closest("li");
   if($(li).is("[idmercadologico]")){
      $.messageBox({
         text: "Tem certeza que deseja excluír o mercadológico desejado?",
         buttons: {
            "Sim": function(){
               $.messageBox("close");
               $.loading(true);
               $.ajax({
                  url: "../ajax/mercadologico_remover.php",
                  data: {
                     idmercadologico: $(li).attr("idmercadologico"),
                     nivel: $(li).closest("[nivel]").attr("nivel")
                  },
                  complete: function(){
                     $.loading(false);
                  },
                  success: function(result){
                     extractScript(result);
                  }
               });
            },
            "Não": function(){
               $.messageBox("close");
            }
         }
      });
   }else{
      $(li).remove();
   }
}

function selecionar(element){
   let div = $(element).closest("[nivel]");
   $(div).find("input:hidden").val($(element).attr("idmercadologico"));
   $(div).find(".active").removeClass("active");
   $(element).addClass("active");

   let proximoDiv = $("div[nivel='" + (parseInt($(div).attr("nivel")) + 1) + "']");
   if($(proximoDiv).length > 0){
      listar($(proximoDiv).attr("nivel"));
   }
}