/*Estou criando e confgiurando a tabela aqui, correto ?*/
 
 
 $(document).ready(function() {


    $('#dataTable').DataTable( {
        orderCellsTop: true,
        dom: 'Blfrtip',
        buttons: [
            'excel'
        ],
        order: [[ 1, 'asc' ]],
        

    });

  
    /*Rodape*/
    $("#dataTable_wrapper").prepend('<div id="rodape" class="row"></div>');
    $("#rodape").prepend('<div id="rodape_numeric" class="col-sm-12 col-md-7"></div>');
    $("#rodape").prepend('<div id="rodape_info" class="col-sm-12 col-md-5"></div>');
    $("#rodape_numeric").append($("#dataTable_paginate"));
    $("#rodape_info").append($("#dataTable_info"));

    /*Tabela*/
    $("#dataTable_wrapper").prepend('<div id="tabela" class="row"></div>');
    $("#tabela").prepend('<div id="dataTable_" class="col-sm-12"></div>');
    $("#dataTable_").append($("#dataTable"));

    /*Button*/ 
    $("#dataTable_wrapper").prepend('<div id="buttons" class="row"></div>');
    $("#buttons").prepend('<div id="buttons" class="col-sm-3 col-dm-3"></div>');
    $("#buttons").append($(".dt-buttons"));

    //$("#dataTable_wrapper").prepend('<div id="filter" class="row"></div>');

    /*Filtro e Pesquisa*/
    $("#dataTable_wrapper").prepend('<div id="length" class="row"></div>');
    $("#length").prepend('<div id="filter_" class="col-sm-12 col-md-6"></div>');
    $("#length").prepend('<div id="length_" class="col-sm-12 col-md-6"></div>');
    $("#filter_").append($("#dataTable_filter"));
    $("#length_").append($("#dataTable_length"));

    /*Função para pegar o valor da classe / texto dos cards e passar para dentro da tabela e deixar vazia para não aparecer*/ 
    $("b").on("click", function(){
      var classe = $(this).attr("class")
      $(".table-responsive input").val(classe).trigger("input")
      $("#table input").trigger('click');
      $(".table-responsive input").val('');
    });

    /*Clicar no botão do head da página e gerar o click no botão do jquery dataTable*/
    $("#tables").click(function(){
        $(".dt-buttons button").trigger('click');
    });

    /*Ocultar o botão data table*/
    $(".dt-button").hide();

  
    

})  

