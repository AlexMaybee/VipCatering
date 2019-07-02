$( document ).ready(function($) {

	// добавляем маску для множественного поля телефон
	$(function() {
        $('input[name^="COMFM[PHONE]"]').mask("389999999999"); 
    $( 'body' ).delegate( '.bx-crm-edit-fm-add', 'click',function(){ 
        $('input[name^="COMFM[PHONE]"]').mask("389999999999"); 
    });

    });

 });