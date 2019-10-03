function detalhar(arr_codlancto){
   $.loading(true);
   $.ajax({
      url: "../ajax/painelfinanceiro_resultado_detalhe.php",
      type: "POST",
      data: {
         arr_codlancto: arr_codlancto
      },
      success: function(result){
         $.loading(false);
         $("#detalhe_grid").html(result);
         $.modalWindow({
            title: "Detalhes dos Lançamentos",
            content: $("#modal_detalhe"),
            closeButton: true,
            height: "500px",
            width: "950px"
         });
         fixa_topogrid("#detalhe_grid");
      }
   });
}
