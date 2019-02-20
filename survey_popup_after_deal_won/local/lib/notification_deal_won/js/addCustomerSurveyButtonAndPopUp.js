BX.ready(function() {

    //вызов класса через объект
    let survey = new CustomerSurvey();

    //console.log(typeof CustomerSurvey);
   // console.log(typeof survey);
   // console.log(survey.matchMassive);


});

//Класс

class CustomerSurvey{

    //свойства указываются только в конструкторе, никаких var, let и т.д.
    constructor(){
        this.urlStr = window.location.href; //url line
        this.dealId = this.checkIfDealDetailsPage();
        if(this.dealId !== false){
           // console.log(this.dealId);


            //ЧЕ ЗА?! НЕ ПАШЕТ! Возврат null
            this.getDealData(this.dealId);


        }

    }

    //методы:

    getDealData(id){

        var self = this;

        BX.ajax({
            method: "POST",
            url: '/local/lib/notification_deal_won/ajax/handler.php',
            data: {'DEAL_ID':id,'ACTION':'GIVE_ME_DEAL_DATA'},
            dataType: "json",
            onsuccess: function (data) {
              //  console.log(data);
                if(data.result.SHOW_BUTTON !== false){

                    //показываем кнопку если выбрана выигрышная стадия текущего направления
                    self.addCustomerButton(data.result.DEAL);
                }
                else {
                    console.log(data.message);
                }
            }
        });
    }


    //проверка на страницу DealDetails, если да - возврат id сделки, иначе false
    checkIfDealDetailsPage(){
        var matchMassive, dealId;

        if(matchMassive = this.urlStr.match(/\/crm\/deal\/details\/([\d]+)/i)){

           // console.log(matchMassive);

            return matchMassive[1] > 0 ? matchMassive[1] : false;
        }
        else return false
    }

    //добавление кнопки на страницу
    addCustomerButton(deal){

        var mdiv = $('.pagetitle-container.pagetitle-align-right-container .crm-entity-actions-container'),
            bp, inText, elemTitle, background,
            self = this; //иначе не получится вызвать нужный метод класса


        //Меняем текст и тайтл кнопки, если поле хоть одно поле было заполнено
        console.log(deal);
        if(deal.UF_CRM_1550567125 == null && deal.UF_CRM_1550567255 === null && deal.UF_CRM_1550567291 === null && deal.UF_CRM_1550567357 === '' && deal.UF_CRM_1550567461 === ''){
            inText = 'Провести опрос';
            elemTitle = 'Заполнить оценку работы со слов клиента';
            background = '#0ba525'; //green
        }
        else{
            inText = 'Исправить опрос';
            elemTitle = 'Внести изменения в оценку работы со слов клиента';
            background = '#0b2ca5'; //blue
        }



        if(mdiv != null){
           bp = document.createElement('span');
            bp.className = 'make_survey_button task-view-button bp_start webform-small-button';
            bp.innerHTML = inText;
            bp.title = elemTitle;
            bp.onclick = function ()
                { self.openLoadDealDataSurveyForPopup(deal.ID) } //присваиваю при создании функцию из этого класса
            bp.style.cssText = 'display: inline-block!important;background-color: ' + background + '; color: #fff';
            mdiv.before(bp);
        }
    }

    openLoadDealDataSurveyForPopup(dealId){
       // alert('Сделка № ' + dealId);

        var self = this;

        BX.ajax({
            method: "POST",
            url: '/local/lib/notification_deal_won/ajax/handler.php',
            data: {'DEAL_ID':dealId,'ACTION':'GIVE_ME_SURVEY_AND_OPTIONS_FIELDS'},
            dataType: "json",
            onsuccess: function (data) {

                console.log(data);

                if(data.result.DEAL != false && data.result.QUALITY_OPTIONS != false){
                    self.surveyPopup(data.result);
                }
                else{
                    console.log(data.message);
                    //декативация кнопки
                    for(var i=0; i<document.getElementsByClassName('make_survey_button').length; i++){
                        document.getElementsByClassName('make_survey_button')[i].style.pointerEvent = 'none';
                        document.getElementsByClassName('make_survey_button')[i].style.opacity = '0.5';
                    }
                }


            }
        });
    }

    //метод сам popup
    surveyPopup(result){

        var self = this;

        var Dialog = new BX.CDialog({
            title: "Оценка качества клиентом ",
            head: 'Заполните поля со слов клиента',
            content: '<form method="POST" style="overflow:hidden;" action="" name="SurveyPopup" id="SurveyPopup">' +
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-select" data-cid="SURVEY_QUALITY"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Оценка качества</span></div><div class="crm-entity-widget-content-block-inner"><span class="fields enumeration field-wrap"><span class="fields enumeration enumeration-select field-item"><select name="SURVEY_QUALITY" tabindex="0" id="SURVEY_QUALITY"></select></span></span></div></div>' +
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-text" data-cid="SURVEY_INFLUENCE"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Что именно повлияло на Вашу оценку?</span></div><div class="crm-entity-widget-content-block-inner"><span class="fields string field-wrap"><span class="fields string field-item"><textarea cols="20" rows="2" class="fields string" name="SURVEY_INFLUENCE" tabindex="0" id="SURVEY_INFLUENCE"></textarea></span></span></div></div>' +
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-text" data-cid="SURVEY_RECOMMENDATIONS"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Что бы рекомендовали нам, чтоб мы стали лучше и вам было комфортней для работы с нами?</span></div><div class="crm-entity-widget-content-block-inner"><span class="fields string field-wrap"><span class="fields string field-item"><textarea cols="20" rows="3" class="fields string" name="SURVEY_RECOMMENDATIONS" id="SURVEY_RECOMMENDATIONS" tabindex="0"></textarea></span></span></div></div>' +
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-date" data-cid="SURVEY_EVENT"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Предвидится ли у вас следующее мероприятие, и когда?</span></div><div class="crm-entity-widget-content-block-inner"><div class="crm-entity-widget-content-block-field-container"><input name="SURVEY_EVENT" class="crm-entity-widget-content-input" type="date" value="" style="padding:0 9px" id="SURVEY_EVENT"></div></div></div>'+
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-date" data-cid="SURVEY_CALL_CATE"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Дата звонка для отдела ЛДГ</span></div><div class="crm-entity-widget-content-block-inner"><div class="crm-entity-widget-content-block-field-container"><input name="SURVEY_CALL_CATE" class="crm-entity-widget-content-input" type="date" value="" style="padding:0 9px" id="SURVEY_CALL_CATE"></div></div></div>'+
            '</form>',
            icon: 'head-block',
            resizable: true,
            draggable: true,
            height: '500',
            width: '500',
        });

        Dialog.SetButtons([
            {
                'title': 'Сохранить',
                'id': 'surveyResultSave',
                'name': 'surveyResultSave',
                'action': function(){

                    //Функция валидации Полей!!!
                    self.SurveyPopupValidate(result);
                }
            },
            {
                'title': 'Отмена',
                'id': 'popupCancel',
                'name': 'popupCancel',
                'action':  function () {
                    this.parentWindow.Close();
                    location.reload();
                }
            }
        ]);


        //Заполнение полей
        document.getElementById("SURVEY_QUALITY").innerHTML = result.QUALITY_OPTIONS; // поле Оценки
        document.getElementById("SURVEY_INFLUENCE").value = 'TEXT AKKAKAKAK'; // поле Что именно повлияло на Вашу оценку
        document.getElementById("SURVEY_RECOMMENDATIONS").value = 'TEXT DDADADADADAD'; // поле Что бы рекомендовали нам
        document.getElementById("SURVEY_EVENT").value = new Date().toISOString().substring(0, 10); // поле Следующее мероприятие
        document.getElementById("SURVEY_CALL_CATE").value = result.DEAL.UF_CRM_1550567461; // поле Дата звонка для отдела ЛДГ

        //при нажатии на крестик закрывает окно и перезагружает страницу
        $('.bx-core-adm-icon-close').click(function () {
            $('#popupCancel').click();
        });


        Dialog.Show(); //запуск popup
    }

    SurveyPopupValidate(result){

        var self = this,
            errCount = 0;

        $('#SurveyPopup input').css('border-color','#c4c7cc');
        $('#SurveyPopup select').css('border-color','#c4c7cc');
        $('#SurveyPopup textarea').css('border-color','#c4c7cc');
        $('#SurveyPopup .MyPopupError').remove();


        var dataInpt = $('#SurveyPopup').serializeArray();
        var fields = {};
        $.each(dataInpt, function () {
            fields[this.name] = this.value;
        });

        if(Number(fields.SURVEY_QUALITY) == 0) { //значению 0 соотв. id = 132
           // $('#SURVEY_QUALITY').css({'border-color': 'red'});
            self.errorCreate('SURVEY_QUALITY','Выберете значение!',false);
        }
        if(fields.SURVEY_INFLUENCE.length == 0) self.errorCreate('SURVEY_INFLUENCE','Заполните поле!',false);
        if(fields.SURVEY_RECOMMENDATIONS.length == 0) self.errorCreate('SURVEY_RECOMMENDATIONS','Заполните поле!',false);
        if(fields.SURVEY_EVENT.search(/[\d]{4}-[\d]{2}-['\d']{2}/i) == -1) self.errorCreate('SURVEY_EVENT','Выберете дату мероприятия и проверьте ее формат!',true);
        if(fields.SURVEY_CALL_CATE.search(/[\d]{4}-[\d]{2}-['\d']{2}/i) == -1) self.errorCreate('SURVEY_CALL_CATE','Выберете дату звонка и проверьте ее формат!',true);

        errCount = document.getElementsByClassName('MyPopupError').length;
        /*console.log(errCount);
        if(errCount > 0) console.log('Ошибки есть!');
        else console.log('Ошибок нет!!!');*/
        if(errCount == 0){
            //отправка данных в php! Доделать завтра!
        }

    }

    errorCreate(domElemId,text,parentEl){
        var selectedElem = document.getElementById(domElemId);
        var error = document.createElement('div');

        selectedElem.style.borderColor = 'red';
        error.className ='MyPopupError';
        error.innerHTML = text;
        error.style = "color:red;font-weight:600;margin:10px 0;";
        parentEl == false ? selectedElem.after(error) : selectedElem.closest('.crm-entity-widget-content-block').after(error);
    }


}