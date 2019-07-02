BX.ready(function() {

    //console.log('Тестовая запись для шаблонов предложений!');
    let quoteTemplates = new QuoteTemplates();

});


class QuoteTemplates{
    constructor(){
        this.checkUrlIfQuoteCreation();
    }

    //сравнение строки, чтобі єто біла страница именно создания предложения
    checkUrlIfQuoteCreation(){
        let matchMassive, url = window.location.href;
        if(matchMassive = url.match(/\/crm\/quote\/edit\/([\d]+)\//i)){
            if(matchMassive[1] == '0')
                this.getQuotesFunction();
        }
    }

    //запрос всех предложений, в которых есть галочка "Использовать как шаблон"
    getQuotesFunction(curUserId,taskId){

        var self = this;

        BX.ajax({
            method: "POST",
            url: '/local/lib/add_quote_template/ajax/handler.php',
            data: {'ACTION':'GIVE_ME_QUOTES_WHICH_MARKED_AS_TEMPLATES'},
            dataType: "json",
            onsuccess: function (data) {

               //  console.log(data);

                if(data.result){
                    let searchBlock = document.getElementById('toolbar_quote_edit');
                    if(searchBlock) {
                        let customBlock = document.createElement('div');
                        customBlock.id = 'quote_block_template_custom_my';

                        //добавляем на стр. блок, в который будем добавлять индикаторы
                        searchBlock.after(customBlock);
                        //вызов функции для создания селекта и кнопки
                        self.createSelect(data.result,customBlock.id);
                    }
                }
                else{
                    console.log(data.error);
                }
            }
        });
    }

    createSelect(massive,customBlock){
        let parent = document.getElementById(customBlock),
            selectElem = document.createElement('select'),
            text = document.createElement('span'),
            button = document.createElement('button'),
            newOption = document.createElement('option'),
            valueSelect
            self = this;

        text.innerText = 'Выберите шаблон: ';

        // selectElem.style.cssText = 'margin-left:10px;min-width: 150px;padding-left: 10px;border:1px solid #f0f4f5;padding: 10px';
        selectElem.id = 'quote_select_template_custom_my';
        selectElem.onchange = function () {
            self.deactivateButton();
        };

        newOption.text = 'Не выбрано';
        newOption.value = '';
        selectElem.add(newOption);

        button.innerText = 'Применить шаблон';
        button.id = 'quote_button_template_custom_my';
       // button.style.cssText = 'background-color:#83b7dc; margin-left:20px; padding: 10px; border:1px solid #f0f4f5';
        button.onclick = function(){
            self.clickOnButton(selectElem.id);
        }

        massive.map(function (option,index) {
            newOption = document.createElement('option');
            if(option.TITLE) newOption.text = option.TITLE;
            else newOption.text = 'Предложение by ' + option.ASSIGNED_BY_LAST_NAME + ' ' + option.ASSIGNED_BY_NAME;
            newOption.value = option.ID;
            selectElem.add(newOption);
        });

        parent.append(selectElem);
        selectElem.before(text);
        selectElem.after(button);

        //деактивируем кнопку при загрузке (т.к. по умолч. "Не вібрано")
        this.deactivateButton();
    }

    //нажатие на кнопку
    clickOnButton(selectId){
        let  valueSelect = document.getElementById(selectId).value;
        if(valueSelect)
            window.location.href = '/crm/quote/edit/' + valueSelect + '/?copy=1';
    }

    //деактивация кнопки при загрузке и смене значения селекта
    deactivateButton(){
        let  valueSelect = document.getElementById('quote_select_template_custom_my').value;
        let  button = document.getElementById('quote_button_template_custom_my');
        console.log('changed to:',valueSelect, button);
        if(valueSelect == '') button.disabled = true;
        else button.disabled = false;
    }

}