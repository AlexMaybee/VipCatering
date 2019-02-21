BX.ready(function() {

    //вызов класса через объект
    let survey = new CustomerSurvey();


});

//Класс

class CustomerSurvey{

    //свойства указываются только в конструкторе, никаких var, let и т.д.
    constructor(){
        this.urlStr = window.location.href; //url line
        this.dealId = this.checkIfDealDetailsPage();
        if(this.dealId !== false){
            this.getDealData(this.dealId);
        }

        //функция для деактивации полей опроса ДЛЯ ВСЕХ!!!

        //Функция скрытия поля контакт-компания в сделке

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
        //console.log(deal);
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

                //console.log(data);

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

            //'<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-date" data-cid="SURVEY_EVENT"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Предвидится ли у вас следующее мероприятие, и когда?</span></div><div class="crm-entity-widget-content-block-inner"><div class="crm-entity-widget-content-block-field-container"><input name="SURVEY_EVENT" class="crm-entity-widget-content-input" type="date" value="" style="padding:0 9px" ></div></div></div>'+
            // '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-date" data-cid="SURVEY_CALL_CATE"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Дата звонка для отдела ЛДГ</span></div><div class="crm-entity-widget-content-block-inner"><div class="crm-entity-widget-content-block-field-container"><input name="SURVEY_CALL_CATE" class="crm-entity-widget-content-input" type="date" value="" style="padding:0 9px" id="SURVEY_CALL_CATE"></div></div></div>'+
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-date" data-cid="SURVEY_EVENT"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Предвидится ли у вас следующее мероприятие, и когда?</span></div><div class="crm-entity-widget-content-block-inner"><span class="fields date field-wrap"><span class="fields date field-item"><input onclick="BX.calendar({node: this, field: this, bTime: false, bSetFocus: false})" name="SURVEY_EVENT" type="text" tabindex="0" value="" id="SURVEY_EVENT"><i class="fields date icon" onclick="BX.calendar({node: this.previousSibling, field: this.previousSibling, bTime: false, bSetFocus: false});"></i></span></span></div></div>' +
            '<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-date crm-entity-widget-content-block-edit" data-cid="SURVEY_CALL_CATE"><div class="crm-entity-widget-content-block-title"><span class="crm-entity-widget-content-block-title-text">Дата звонка для отдела ЛДГ</span></div><div class="crm-entity-widget-content-block-inner"><span class="fields datetime field-wrap"><span class="fields datetime field-item"><input onclick="BX.calendar({node: this, field: this, bTime: true, bSetFocus: false, bUseSecond: true})" name="SURVEY_CALL_CATE" type="text" tabindex="0" value="" id="SURVEY_CALL_CATE"><i class="fields datetime icon" onclick="BX.calendar({node: this.previousSibling, field: this.previousSibling, bTime: true, bSetFocus: false, bUseSecond: true});"></i></span></span></div></div>' +
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
        document.getElementById("SURVEY_INFLUENCE").value = result.DEAL.UF_CRM_1550567255; // поле Что именно повлияло на Вашу оценку
        document.getElementById("SURVEY_RECOMMENDATIONS").value = result.DEAL.UF_CRM_1550567291; // поле Что бы рекомендовали нам
        document.getElementById("SURVEY_EVENT").value = result.DEAL.UF_CRM_1550567357; // поле Следующее мероприятие
        document.getElementById("SURVEY_CALL_CATE").value = result.DEAL.UF_CRM_1550567461; // поле Дата звонка для отдела ЛДГ

        //при нажатии на крестик закрывает окно и перезагружает страницу
        $('.bx-core-adm-icon-close').click(function () {
            $('#popupCancel').click();
        });


        Dialog.Show(); //запуск popup
    }

    SurveyPopupValidate(result){

        var self = this,
            errCount = 0,
            dataInpt,
            fields,
            curDate,
            selectedEventDate,
            selectedCallDateTimeDate;

        $('#SurveyPopup input').css('border-color','#c4c7cc');
        $('#SurveyPopup select').css('border-color','#c4c7cc');
        $('#SurveyPopup textarea').css('border-color','#c4c7cc');
        $('#SurveyPopup .MyPopupError').remove();


        dataInpt = $('#SurveyPopup').serializeArray();
        fields = {};
        $.each(dataInpt, function () {
            fields[this.name] = this.value;
        });

        fields.DEAL_ID = result.DEAL.ID


        curDate = new Date(); //текущая дата

        //конверт в js- Fri Mar 22 2019 07:00:00 GMT+0200 (за східноєвропейським стандартним часом)
        selectedEventDate = self.convertToDateObjectDateString(fields.SURVEY_EVENT); //дата события
        selectedCallDateTimeDate = self.convertToDateObjectDateTimeString(fields.SURVEY_CALL_CATE);


       // console.log(selectedCallDateTimeDate);
        //console.log(curDate);


        if(Number(fields.SURVEY_QUALITY) == 0) { //значению 0 соотв. id = 132
            self.errorCreate('SURVEY_QUALITY','Выберете значение!',false);
        }
        if(fields.SURVEY_INFLUENCE.length == 0) self.errorCreate('SURVEY_INFLUENCE','Заполните поле!',false);
        if(fields.SURVEY_RECOMMENDATIONS.length == 0) self.errorCreate('SURVEY_RECOMMENDATIONS','Заполните поле!',false);
        if(fields.SURVEY_EVENT.search(/^(0[1-9]|1\d|2\d|3[01])\.(0[1-9]|1[0-2])\.(19|20)\d{2}$/) == -1) self.errorCreate('SURVEY_EVENT','Выберете дату мероприятия и проверьте ее формат!',true);
        if(fields.SURVEY_CALL_CATE.search(/^(0[1-9]|1\d|2\d|3[01])\.(0[1-9]|1[0-2])\.(19|20)\d{2} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$/) == -1) self.errorCreate('SURVEY_CALL_CATE','Выберете дату, время звонка и проверьте формат!',true);
        if(curDate > selectedEventDate) self.errorCreate('SURVEY_EVENT','Вы выбрали прошедшую дату!', true);
        if(curDate > selectedCallDateTimeDate) self.errorCreate('SURVEY_CALL_CATE','Вы выбрали прошедшую дату и время!', true);

        //console.log('Текущая и выбранная даты Ивента: ' + curDate + ' / ' + selectedCallDateTimeDate);
        //console.log(fields);

        errCount = document.getElementsByClassName('MyPopupError').length;
        /*console.log(errCount);
        if(errCount > 0) console.log('Ошибки есть!');
        else console.log('Ошибок нет!!!');*/
        if(errCount == 0){
            //отправка данных в php! Доделать завтра!
            self.sentSurveyPopupDataToPhp(fields);
           // console.log(fields);
        }

    }


    //отправка данных в php
    sentSurveyPopupDataToPhp(fields){

        var self1 = this;

        BX.ajax({
            method: "POST",
            url: '/local/lib/notification_deal_won/ajax/handler.php',
            data: {'FIELDS':fields,'ACTION':'SAVE_CUSTOMER_SURVEY_FIELDS_IN_DEAL'},
            dataType: "json",
            onsuccess: function (data) {

                console.log(data);

                //удаляем содердимое и выводим сообщение
                $('.bx-core-window.bx-core-adm-dialog .bx-core-adm-dialog-content-wrap-inner').empty();

                //если ошибка, показываем ее

                if(data.result == false){
                    $('.bx-core-window.bx-core-adm-dialog .bx-core-adm-dialog-content-wrap-inner').append('<h2 style="text-align: center; color: red;">' + data.message + '</h2>');
                }
                else {
                    $('.bx-core-window.bx-core-adm-dialog .bx-core-adm-dialog-content-wrap-inner').append('<h2 style="text-align: center; color: green;">' + data.message + '</h2>');
                    setTimeout(self1.closePopupAfterSuccess, 4000);
                }


            }
        });
    }


    //Вывод ошибок: id элемента ДОМ; текст ошибки; флаг true, если ошибку нужно вывести под родительским элементом
    errorCreate(domElemId,text,parentEl){
        var selectedElem = document.getElementById(domElemId);
        var error = document.createElement('div');

        selectedElem.style.borderColor = 'red';
        error.className ='MyPopupError';
        error.innerHTML = text;
        error.style = "color:red;font-weight:600;margin:10px 0;";
        parentEl == false ? selectedElem.after(error) : selectedElem.closest('.crm-entity-widget-content-block').after(error);
    }

    //возврат объекта типа Fri Mar 22 2019 07:00:00 GMT+0200 (за східноєвропейським стандартним часом)
    convertToDateObjectDateTimeString(string){
        var mass = [],
        t = 0,
        r = 0,
        j = 0,
        selectedCallDateTimeDate = string.split(' ');

        for(t;t<selectedCallDateTimeDate.length; t++){
            if(t == 0){
                for(r; r<selectedCallDateTimeDate[t].split('.').length; r++){
                    mass.push(selectedCallDateTimeDate[t].split('.')[r]);
                }
            }
            if(t == 1){
                for(j; j<selectedCallDateTimeDate[t].split(':').length; j++){
                    mass.push(selectedCallDateTimeDate[t].split(':')[j]);
                }
            }
        }
        console.log(mass);
        if(Number(mass[1]) > 0) mass[1] = Number(mass[1] - 1); //у js нумерация месяца стартует с 0, а у php с 1
        return new Date(mass[2],(mass[1]),mass[0],mass[3],mass[4],mass[5]);
    }


    //возврат даты из строки в объект типа Fri Mar 22 2019 07:00:00 GMT+0200 (за східноєвропейським стандартним часом)
    convertToDateObjectDateString(string){
        var mass = [],
            t = 0,
            r = 0,
            j = 0,
            selectedCallDateTimeDate = string.split(' ');

        for(t;t<selectedCallDateTimeDate.length; t++){
            if(t == 0){
                for(r; r<selectedCallDateTimeDate[t].split('.').length; r++){
                    mass.push(selectedCallDateTimeDate[t].split('.')[r]);
                }
            }
        }
        console.log(mass);
        if(Number(mass[1]) > 0) mass[1] = Number(mass[1] - 1); //у js нумерация месяца стартует с 0, а у php с 1
        return new Date(mass[2],(mass[1]),mass[0]);
    }


    //закрытие окна авто!
    closePopupAfterSuccess() {
        $('#popupCancel').click();
    }

}