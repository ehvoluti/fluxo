function abrir_pedido(){
   switch(operacao){
      case "CP": var idtable = "PedCompra"; break;
      case "DC": var idtable = "PedDevCliente"; break;
      case "DF": var idtable = "PedDevFornecedor"; break;
      case "IM": var idtable = "PedImportacao"; break;
      case "PD": var idtable = "PedPerda"; break;
      case "PR": var idtable = "PedProdutor"; break;
      case "RC": var idtable = "PedRemessaCli"; break;
      case "RF": var idtable = "PedRemessaFor"; break;
      case "TE": var idtable = "CadPedidoTE"; break;
      case "TS": var idtable = "CadPedidoTS"; break;
      case "VD": var idtable = "PedVenda"; break;
      case "SS": var idtable = "PedServicoSS"; break;
      case "SE": var idtable = "PedServicoES"; break;
      case "EC": var idtable = "PedRemessaCliE"; break;
      case "EF": var idtable = "PedRemessaForE"; break;
      default: return false;
   }
   openProgram(idtable, "codestabelec=" + $("#codestabelec").val() + "&numpedido=" + $("#numpedido").val());
}

function abrir_pedido_te(){
   $.ajax({
      async:false,
      url:"../ajax/notafiscal_abrirpedido_te.php",
      data:({
         idnotafiscal:$("#idnotafiscal").val()
      }),
      success:function(html){
         extractScript(html);
      }
   });
}

function atualizaitem(element){
   if(($(element).val()+"").length == 0){
      $(element).val("0").blur();
   }
   $.ajax({
      async:false,
      url:"../ajax/notafiscal_atualizaitem.php",
      data:({
         operacao:operacao,
         iditpedido:$(element).attr("iditpedido"),
         coluna:$(element).attr("coluna"),
         valor:$(element).val()
      }),
      dataType:"html",
      success:function(html){
         extractScript(html);
      }
   });
}

// Atualiza natureza de operacao dos itens
function atualizarnatoperacao(){
   if($("#natoperacao").val() !== null && $("#natoperacao").val().length > 0 && !isNaN($("#numeroitens").val()) && $("#numeroitens").val() > 0){
      $.messageBox({
         type: "alert",
         text: "Deseja alterar a natureza de operação de todos os itens para <b>" + $("#natoperacao").val() + "</b>?",
         buttons: ({
            "Sim": function(){
               $.messageBox("close");
               $.ajaxProgress({
                  url: "../ajax/notafiscal_atualizarnatoperacao.php",
                  data: ({
                     operacao: operacao,
                     codestabelec: $("#codestabelec").val(),
                     codparceiro: $("#codparceiro").val(),
                     natoperacao: $("#natoperacao").val(),
                     modfrete: $("#modfrete").val()
                  }),
                  success: function(html){
                     extractScript(html);
                  }
               });
            },
            "N\u00E3o": function(){
               $.messageBox("close");
            }
         })
      });
   }
}

function atualizatotalitem(iditpedido, calculartotal = true){
   $.ajax({
      async:false,
      url:"../ajax/notafiscal_atualizatotalitem.php",
      data:({
         operacao:operacao,
         codestabelec:$("#codestabelec").val(),
         numpedido:$("#numpedido").val(),
         codparceiro:$("#codparceiro").val(),
         modfrete:$("#modfrete").val(),
         iditpedido:iditpedido
      }),
      dataType:"html",
      success:function(html){
         extractScript(html);
         if(calculartotal){
            atualizatotaiscalc();
         }
      }
   });
}

var ajaxTotaisCalc = null;
function atualizatotaiscalc(atualizar_peso){
   atualizar_peso = (typeof(atualizar_peso) == "undefined" ? true : atualizar_peso);
   if(ajaxTotaisCalc != null){
      ajaxTotaisCalc.abort();
   }
   $("#divtotais").find("input:text").add("#_totalbruto,#_totalliquido").not("[importacao] input:text").val("")
   ajaxTotaisCalc = $.ajax({
      async:false,
      url:"../ajax/notafiscal_atualizatotaiscalc.php",
      data:({
         operacao:operacao,
         codestabelec:$("#codestabelec").val(),
         codparceiro:$("#codparceiro").val(),
         modfrete:$("#modfrete").val(),
         atualizar_peso:(atualizar_peso ? "S" : "N"),
         tipoajuste:$("#tipoajuste").val()
      }),
      dataType:"html",
      success:function(html){
         extractScript(html);
         copiacalculados();
         ajaxTotaisCalc = null;
      }
   });
}

function buscarlancamentos(){
   var codestabelec = $("#codestabelec").val();
   var idnotafiscal = $("#idnotafiscal").val();
   if(codestabelec.length > 0 && idnotafiscal.length > 0){
      $.ajax({
         url:"../ajax/notafiscal_buscarlancamentos.php",
         data:({
            codestabelec:codestabelec,
            idnotafiscal:idnotafiscal
         }),
         dataType:"html",
         success:function(html){
            $("#gridfinanceiro").html(html);
         }
      });
   }
}

function buscarpedido(){
   if($("#codestabelec").val().length > 0 && $("#numpedido").val().length > 0){
      incluirpedido();
   }else{
      //		if(operacao == "TE"){
      //			operacao = "TS";
      //		}
      //
      //		if(operacao == "EX"){
      //			operacao = "VD";
      //		}

      $("#codestabelec,#numpedido").disabled(false).locate({
         table:"pedido",
         filter:"operacao:'" + operacao + "';status:'P'",
         afterClose:function(){
            $("#numpedido").disabled(true);
            if($("#numpedido").val().length > 0){
               incluirpedido();
            }
         }
      });
   }
}

function cancelar_notafiscal(){
   $.messageBox({
      type:"info",
      text:"Tem certeza que deseja cancelar a nota fiscal?",
      buttons:({
         "Sim":function(){
            $.messageBox("close");
            $.messageBox({
               type:"alert",
               title:"Aten&ccedil;&atilde;o",
               text:"Ao cancelar a nota fiscal n&atilde;o ser&aacute; mais poss&iacute;vel reverter o processo.<br>Todos os lan&ccedil;amentos financeiros e movimenta&ccedil;&otilde;es de estoque ser&atilde;o perdidos.<br>Tem certeza que deseja prosseguir com o cancelamento da nota fiscal?",
               buttons:({
                  "Sim":function(){
                     $.messageBox("close");
                     $.loading(true);
                     $.ajax({
                        url:"../ajax/notafiscal_cancelar.php",
                        data:({
                           idnotafiscal:$("#idnotafiscal").val()
                        }),
                        success:function(html){
                           $.loading(false);
                           extractScript(html);
                        }
                     });
                  },
                  "N\u00E3o":function(){
                     $.messageBox("close");
                  }
               })
            });
         },
         "N\u00E3o":function(){
            $.messageBox("close");
         }
      })
   });
}

function carregaritens_notafiscal(){
   $("#divgriditens").html("<label>Carregando...</label>");
   $.ajax({
      url:"../ajax/notafiscal_itnotafiscal_notafiscal.php",
      data:({
         operacao:operacao,
         idnotafiscal:$("#idnotafiscal").val(),
         codestabelec:$("#codestabelec").val(),
         codparceiro:$("#codparceiro").val()
      }),
      success:function(html){
         $("#divgriditens").html(html);
      }
   });
}

function carregaritens_pedido(){
   $("#divgriditens").html("<label>Carregando...</label>");
   $.ajax({
      url:"../ajax/notafiscal_itnotafiscal_pedido.php",
      data:({
         async:false,
         operacao:operacao,
         codestabelec:$("#codestabelec").val(),
         codparceiro:$("#codparceiro").val(),
         modfrete:$("#modfrete").val()
      }),
      success:function(html){
         $("#divgriditens").html(html);
         $("#divgriditens input:text").attr({
            alt:"Editar valor",
            title:"Editar valor"
         });
         atualizatotaiscalc(false);
         if(param_notafiscal_atualizanotaped === "S"){
            $("input[coluna=quantidade][iditpedido]").each(function(){
               atualizatotalitem($(this).attr("iditpedido"), false);
            });
            atualizatotaiscalc();
         }
      }
   });
}

function copiacalculados(){
   if(!in_array(operacao,["CP"]) || inftotnfcompra == "N"){
      $("#totalbruto,#_totalbruto").val($("#totalbrutoc").val());
      $("#totaldesconto").val($("#totaldescontoc").val());
      $("#totalacrescimo").val($("#totalacrescimoc").val());
      $("#totalpis").val($("#totalpisc").val());
      $("#totalcofins").val($("#totalcofinsc").val());
      $("#totalfrete").val($("#totalfretec").val());
      $("#totalipi").val($("#totalipic").val());
      $("#totalseguro").val($("#totalseguroc").val());
      $("#totalbaseicms").val($("#totalbaseicmsc").val());
      $("#totalicms").val($("#totalicmsc").val());
      $("#totalbaseicmssubst").val($("#totalbaseicmssubstc").val());
      $("#totalicmssubst").val($("#totalicmssubstc").val());
      $("#totalliquido,#_totalliquido").val($("#totalliquidoc").val());
      $("#totalbonificado").val($("#totalbonificadoc").val());
      $("#totalarecolher").val($("#totalarecolherc").val());
   }
}

function incluirpedido(){
   var codestabelec = $("#codestabelec").val();
   var numpedido = $("#numpedido").val();
   $("#btnCadInserir").click();
   $("#codestabelec").val(codestabelec);
   $("#numpedido").val(numpedido);
   puxarpedido();
}

function gerararqnfe_sucesso(mensagem){
   $.messageBox({
      type:"sucess",
      text:mensagem,
      buttons:({
         "Sim":function(){
            gerararqnfe("imprimirnfe");
            $.messageBox("close");
         },
         "N\u00E3o":function(){
            $.messageBox("close");
         }
      })
   })
}

function cancelarnfe_sucesso(mensagem){
   $.messageBox({
      type:"sucess",
      text:mensagem,
      buttons:({
         "Sim":function(){
            gerararqnfe("imprimirevento");
            $.messageBox("close");
         },
         "N\u00E3o":function(){
            $.messageBox("close");
         }
      })
   })
}


function gerararqnfe(metodoajax){
   $.loading(true);
   $.ajax({
      url:"../ajax/notafiscal_" + metodoajax + ".php",
      data:({
         idnotafiscal:$("#idnotafiscal").val(),
         tipodesc:$("#tipodesc").val(),
         chavenfe:$("#chavenfe").val(),
         justificativa_cancelamento:$("#justificativa_cancelamento").val(),
         justificativa_inutilizacao:$("#justificativa_inutilizacao").val(),
         texto_cce:$("#cce").val(),
         imprimirnf:$("#imprimirnf").val(),
         documento:($("#op_ambos").checked() ? "A" : ($("#op_evento").checked() ? "E" : "D")),
         emailextra:$("#emailextra").val()
      }),
      dataType:"html",
      success:function(html){
         $.loading(false);
         extractScript(html);
         $.modalWindow('close');
      }
   });
}

function mostrarfinanceiro(bool){
   var aba = $("#abafinanceiro");
   var end = $("#tabendnf");
   if(bool && !aba.is(":visible")){
      aba.show();
      end.width(parseInt(end.width()) - 100);
   }else if(aba.is(":visible")){
      if(aba[0].className == "tabon"){
         $("#abacabecalho").click();
      }
      aba.hide();
      end.width(parseInt(end.width()) + 100);
   }
}

function puxarpedido(){
   $.loading(true);
   $.ajax({
      url:"../ajax/notafiscal_puxapedido.php",
      data:({
         "operacao":operacao,
         "codestabelec":$("#codestabelec").val(),
         "numpedido":$("#numpedido").val()
      }),
      success:function(html){
         $.loading(false);
         extractScript(html);
         $("#codparceiro").description();
         $("body").focusFirst();
         if(inftotnfcompra != "S"){
            $("#divtotais :input").attr("readonly","readonly");
            $("#divtotais :input").disabled(true);
         }
         $("#numnotafis").focus();
      }
   });
}

function verificadivergencias(){
   return true;
   $("#totalbaseicms,#totalicms,#totalbaseicmssubst,#totalicmssubst,#totalfrete,#totalipi,#totalliquido").filter("[value='']").val("0,00");
   if($("#totalliquido").val() == "0,00" && $("#totalliquido_c").val() != "0,00" && operacao != "AE" && operacao != "AS"){
      $.messageBox({
         type:"error",
         text:"Informe o total da nota fiscal.",
         afterClose:function(){
            $("#abatotais").click();
            $("#totalliquido").focus();
         }
      });
      return false;
   }
   var diferenca = new Array();
   if($("#totalbaseicms").val() != $("#totalbaseicmsc").val()){
      diferenca[diferenca.length] = ({
         nome:"Total Base ICMS",
         informado:$("#totalbaseicms").val(),
         calculado:$("#totalbaseicmsc").val()
      });
   }
   if($("#totalicms").val() != $("#totalicmsc").val()){
      diferenca[diferenca.length] = ({
         nome:"Total ICMS",
         informado:$("#totalicms").val(),
         calculado:$("#totalicmsc").val()
      });
   }
   if($("#totalbaseicmssubst").val() != $("#totalbaseicmssubstc").val()){
      diferenca[diferenca.length] = ({
         nome:"Total Base ICMS Substituto",
         informado:$("#totalbaseicmssubst").val(),
         calculado:$("#totalbaseicmssubstc").val()
      });
   }
   if($("#totalicmssubst").val() != $("#totalicmssubstc").val()){
      diferenca[diferenca.length] = ({
         nome:"Total ICMS Substituto",
         informado:$("#totalicmssubst").val(),
         calculado:$("#totalicmssubstc").val()
      });
   }
   if($("#totalfrete").val() != $("#totalfretec").val()){
      diferenca[diferenca.length] = ({
         nome:"Total Frete",
         informado:$("#totalfrete").val(),
         calculado:$("#totalfretec").val()
      });
   }
   if($("#totalipi").val() != $("#totalipic").val()){
      diferenca[diferenca.length] = ({
         nome:"Total IPI",
         informado:$("#totalipi").val(),
         calculado:$("#totalipic").val()
      });
   }
   if($("#totalliquido").val() != $("#totalliquidoc").val()){
      diferenca[diferenca.length] = ({
         nome:"Total Liqu&iacute;do",
         informado:$("#totalliquido").val(),
         calculado:$("#totalliquidoc").val()
      });
   }
   console.log(diferenca);
   if(diferenca.length == 0){
      return true;
   }else{
      var grade = "<table style=\"width:100%\" id=\"gradediferenca\">";
      grade += "<tr style=\"font-weight:bold\"><td></td><td>Calculado</td><td>Informado</td><td>Diferen&ccedil;a</td></tr>"
      for(var i = 0; i < diferenca.length; i++){
         var valdiferenca = number_format(parseFloat(diferenca[i].calculado.replace(",",".")) - parseFloat(diferenca[i].informado.replace(",",".")),2,",","");
         grade += "<tr><td style=\"font-weight:bold\">" + diferenca[i].nome + "</td><td align=\"right\">" + diferenca[i].calculado + "</td><td align=\"right\">" + diferenca[i].informado + "</td><td align=\"right\">" + valdiferenca + "</td></tr>";
      }
      grade += "</table>";
      $.messageBox({
         type:"error",
         title:"Existem diverg&ecirc;ncias nos totais informados!",
         text:"<br>" + grade + "<br><br>&Eacute; necess&aacute;rio corrigir as diverg&ecirc;ncias para prosseguir com a grava&ccedil;&atilde;o da nota fiscal.<br><br>",
         buttons:({
            "Ok":function(){
               $.messageBox("close");
            },
            "Detalhes":function(){
               var variaveis = "operacao=" + operacao + "&codestabelec="+ $("#codestabelec").val() + "&numnotafiscal=" + $("#numnotafis").val() + "&serie=" + $("#serie").val();
               variaveis += "&codparceiro=" + $("#codparceiro").val() + "&operacao=" + $("#operacao").val() + "&numpedido=" + $("#numpedido").val();
               variaveis += "&totalbaseicms=" + $("#totalbaseicms").val() + "&totalicms=" + $("#totalicms").val();
               variaveis += "&totalbaseicmssubst=" + $("#totalbaseicmssubst").val() + "&totalicmssubst=" + $("#totalicmssubst").val();
               variaveis += "&totalfrete=" + $("#totalfrete").val() + "&totalipi=" + $("#totalipi").val() + "&totalliquido=" + $("#totalliquido").val();
               variaveis += "&totalbaseicmsc=" + $("#totalbaseicmsc").val() + "&totalicmsc=" + $("#totalicmsc").val();
               variaveis += "&totalbaseicmssubstc=" + $("#totalbaseicmssubstc").val() + "&totalicmssubstc=" + $("#totalicmssubstc").val();
               variaveis += "&totalfretec=" + $("#totalfretec").val() + "&totalipic=" + $("#totalipic").val() + "&totalliquidoc=" + $("#totalliquidoc").val();
               window.open("../form/notafiscal_divergencia.php?" + variaveis);
            }
         }),
         afterOpen:function(){
            $("#gradediferenca td:not(:first)").css("border","1px solid #AAAAAA");
            $("#gradediferenca tr:first td:not(:first)").css("background-color","#EEEEEE");
            $("#gradediferenca td:first-child:not(:first)").css("background-color","#EEEEEE");
         }
      });
      return false;
   }
}

function custobonificado(idnotafiscal){
   // Ratear Bonificação
   $.ajax({
      url:"../ajax/notafiscal_custobonificado.php",
      data:({
         idnotafiscal:idnotafiscal
      }),
      dataType:"html",
      success:function(html){
         extractScript(html);
      }
   });
}

function mostrardivdownloadxmlnfe(){
   $("#chavexmlnfe").val($("#chavenfe").val()).disabled(false);
   $.modalWindow({
      content:$("#div_downloadxml"),
      title:"Download de XML NFe",
      width:"500px"
   });
}

function downloadxmlnfe(){
   if($("#chavexmlnfe").val().length == 0){
      $.messageBox({
         type:"error",
         text:"Informe a chave da NF-e para download.",
         afterClose:function(){
            $("#chavexmlnfe").focus();
         }
      });
      return false;
   }

   $.ajax({
      url: "../ajax/notafiscal_downloadxml_sefaz.php",
      type: "POST",
      dataType: "html",
      data:{
         chavenfe: $("#chavexmlnfe").val(),
         codestabelec: $("#codestabelec").val(),
         codparceiro: $("#codparceiro").val(),
         numnotafis: $("#numnotafis").val(),
         operacao: operacao,
         downsimpl: "S",
         emissaopropria: $("#emissaopropria").val(),
         idnotafiscal: $("#idnotafiscal").val()
      },
      beforeSend: function(){
         $.loading(true);
      },
      success: function(html){
         extractScript(html);
      },
      complete: function(){
         $.modalWindow('close');
         $.loading(false);
      }
   });
}
