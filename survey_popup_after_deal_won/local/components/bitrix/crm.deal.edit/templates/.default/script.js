$(document).ready(function(){


// изменение стадии сделки и появл соответствующих полей в зависимости от стадии
// на клик
 $("select[name='STAGE_ID'").change(function(){
 	if ($(this).val()=='1'){   // кп подтверждено
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").hide();


 	} else if ($(this).val()=='5') {  // мероприятие проводится
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").hide();
 	} else if ($(this).val()=='ON_HOLD') {  // мероприятие откладывается
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").show();
 		$("#uf_crm_1470840818_wrap").hide();
 	} else if ($(this).val()=='4') {  // получение оплаты
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").hide();
 	} else if ($(this).val()=='WON') {  // состоявш сделка
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").hide();
 	} else if ($(this).val()=='LOSE') {  // не состоявш сделка
 		$("#uf_crm_1470727444_wrap").show();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").show();

 	} else {
 		$("#uf_crm_1470727444_wrap").hide();
 		$("#uf_crm_1470727554_wrap").hide();
 		$("#uf_crm_1470840818_wrap").hide();
 	};

 });
// без клика
if ($("select[name='STAGE_ID'").val() == "1"){
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").hide();

} else if ($("select[name='STAGE_ID'").val() == "5") {
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").hide();
} else if ($("select[name='STAGE_ID'").val() == "ON_HOLD") {
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").show();
		$("#uf_crm_1470840818_wrap").hide();
} else if ($("select[name='STAGE_ID'").val() == "4") {
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").hide();
} else if ($("select[name='STAGE_ID'").val() == "WON") {
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").hide();
} else if ($("select[name='STAGE_ID'").val() == "LOSE") {
		$("#uf_crm_1470727444_wrap").show();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").show();

} else {
		$("#uf_crm_1470727444_wrap").hide();
		$("#uf_crm_1470727554_wrap").hide();
		$("#uf_crm_1470840818_wrap").hide();



};

});

//рассчитываем формулы для полей
// поле "Доход меню"

$(function() {


	$("input[name=UF_CRM_1473321196], input[name=UF_CRM_1473321204]").on("input", function(){
		
		let 	n1 = parseFloat($("input[name=UF_CRM_1473321196]").val()), 
     		    n2 = parseFloat($("input[name=UF_CRM_1473321204]").val()); 
     	$("input[name=UF_CRM_1473321214]").val(n1-n2);        
      
});

});

//рассчитываем формулы для полей
// поле "Доход обслуживание"

$(function() {


	$("input[name=UF_CRM_1473321224], input[name=UF_CRM_1473321234]").on("input", function(){
		
		let 	n1 = parseFloat($("input[name=UF_CRM_1473321224]").val()), 
     		    n2 = parseFloat($("input[name=UF_CRM_1473321234]").val()); 
     	$("input[name=UF_CRM_1473321241]").val(n1-n2);        
      
});

});

//рассчитываем формулы для полей
// поле "Прибыль компании"
// поле "Процент 5%"
$(function() {


	$("input[name=UF_CRM_1473321214], input[name=UF_CRM_1473321241], input[name=UF_CRM_1473321248]").on("input", function(){
		
		let 	n1 = parseFloat($("input[name=UF_CRM_1473321214]").val()), 
     		    n2 = parseFloat($("input[name=UF_CRM_1473321241]").val()),
     		    n3 = parseFloat($("input[name=UF_CRM_1473321248]").val()); 
     	$("input[name=UF_CRM_1473321256]").val(n1+n2+n3);  
     	$("input[name=UF_CRM_1473321267]").val((n1+n2+n3)*0.05);      
      
});

});


//рассчитываем формулы для полей
// поле "Процент 5%" (если заполняем отдельно поле "прибыль компании", без предыдущих функциональный расчетов полей)

$(function() {


	$("input[name=UF_CRM_1473321256]").on("input", function(){
		
		let 	n1 = parseFloat($("input[name=UF_CRM_1473321256]").val());  
     	$("input[name=UF_CRM_1473321267]").val(n1*0.05);        
      
});

});

