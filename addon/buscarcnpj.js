var __buscarcnpj_settings = null;

$.fn.buscarcnpj = function(settings){
	settings = $.extend({
        razaosocial:null,
		nomefantasia:null,
		cep:null,
		numero:null,
        complemento:null
	},settings);
    
    __buscarcnpj_settings = settings;
    
    $(this).bind("change",function(){
        
        if(param("CADASTRO","CONSULTARCNPJ") != "S"){
            return true;
        }
        
        var cnpj = trim($(this).val().replace(".","").replace(".","").replace("/","").replace("-",""));
        if(cnpj.length == 14){
            $.modalWindow({
                title:"Consultar CNPJ",
                width:"450px",
                content:$("#__buscarcnpj_div")
            });
            $("#__buscarcnpj_iframe").unbind("load").bind("load",function(){
                this.contentWindow.scrollTo(0,240);
                $(this).contents().find("[name='cnpj']").val(cnpj);
                $("#__buscarcnpj_iframe").contents().find("#idLetra").focus();
                $(this).contents().find("[name='submit1']").bind("click",function(){
                    if($("#__buscarcnpj_iframe").contents().find("#idLetra").val().length == 0){
                        parent.$.messageBox({
                            type:"error",
                            text:"Informe os caracteres ao lado.",
                            focusOnClose:$("#__buscarcnpj_iframe").contents().find("#idLetra")
                        });
                        return false;
                    }
                    __buscarcnpj_verificardados();
                });
            }).attr("src","http://www.receita.fazenda.gov.br/PessoaJuridica/CNPJ/cnpjreva/cnpjreva_solicitacao2.asp");
        }
    });
}

$(document).bind("ready",function(){
    var div_main = document.createElement("div");
    document.body.appendChild(div_main);
    $(div_main).attr({
        "id":"__buscarcnpj_div"
    }).hide();

    var iframe = document.createElement("iframe");
    div_main.appendChild(iframe);
    $(iframe).attr({
        "id":"__buscarcnpj_iframe",
        "frameborder":"0",
        "scrolling":"no"
    }).css({
        "height":"130px",
        "width":"420px"
    });

    var div_button = document.createElement("div");
    div_main.appendChild(div_button);
    $(div_button).css({
        "padding-top":"10px",
        "text-align":"center"
    });

    var button_consultar = document.createElement("input");
    button_consultar.type = "button";
    div_button.appendChild(button_consultar);
    $(button_consultar).val("Consultar").bind("click",function(){
        $("#__buscarcnpj_iframe").contents().find("[name='submit1']").click();
    }).css({
        "margin-right":"4px"
    });

    var button_cancelar = document.createElement("input");
    button_cancelar.type = "button";
    div_button.appendChild(button_cancelar);
    $(button_cancelar).val("Cancelar").bind("click",function(){
        $.modalWindow("close");
    });
});

function __buscarcnpj_verificardados(){
    if($("#__buscarcnpj_iframe").contents().find("table").length > 10){
        $("#__buscarcnpj_iframe").contents().find("font").each(function(){
            var label = trim($(this).text());
            if(label.indexOf("NOME EMPRESARIAL",0) > -1){
                $(__buscarcnpj_settings.razaosocial).val(trim($(this).next().next().text()));
            }else if(label.indexOf("NOME DE FANTASIA",0) > -1){
                $(__buscarcnpj_settings.nomefantasia).val(trim($(this).next().next().text()));
            }else if(label.indexOf("CEP",0) > -1){
                $(__buscarcnpj_settings.cep).val(trim($(this).next().next().text().replace(".",""))).trigger("change");
            }else if(label.indexOf("N�MERO",0) > -1){
                $(__buscarcnpj_settings.numero).val(trim($(this).next().next().text()));
            }else if(label.indexOf("COMPLEMENTO",0) > -1){
                $(__buscarcnpj_settings.complemento).val(trim($(this).next().next().text()));
            }
        });
        $.modalWindow("close");
    }else{
        setTimeout("__buscarcnpj_verificardados()",500);
    }
}