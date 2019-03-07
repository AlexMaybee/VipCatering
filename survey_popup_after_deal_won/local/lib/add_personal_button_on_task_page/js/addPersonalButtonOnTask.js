BX.ready(function() {

   // console.log('Тестовая запись для вывода кнопки!');
    let personButton = new PersonButtonAdd();

});

class PersonButtonAdd{
    constructor(){
        this.url = window.location.href;

        this.isTaskOpened = this.chechUrlIfTaskOpened(this.url);

        //если страница верная, то проверяем id задачи и пользователя (id пользователя проверено при сверке строки)
        if(this.isTaskOpened !== false){
            //если id задачи есть, делаем аякс-запрос для получения id сделки, к которой прикреплена задача
            if(typeof(this.isTaskOpened[2]) != undefined && this.isTaskOpened[2] > 0){
                this.getDealIdFunction(this.isTaskOpened[1],this.isTaskOpened[2]); //передаю id текущего польз. (на всяк случай) + id задачи
            }
            else console.log('Не та страница, чтобы показывать кнопку с персоналом!');
        }


        //console.log(this.url);
        //console.log(this.isTaskOpened);
    }

    //функция сверки url, что он соотв. открытой задаче
    chechUrlIfTaskOpened (urlStr){
        var matchMassive;
        if(matchMassive = urlStr.match(/\/company\/personal\/user\/([\d]+)\/tasks\/task\/view\/([\d]+)/i)){

          //  console.log(matchMassive);

            return matchMassive[1] > 0 ? matchMassive : false; //в массиве 0 - url, 1 - current user id, 2 - task id
        }
        else return false
    }

    //запрос для получения id сделки из текущей задачи
    getDealIdFunction(curUserId,taskId){
        //console.log('Ajax-запрос параметров задачи в действии!')
        var self = this;

        BX.ajax({
            method: "POST",
            url: '/local/lib/add_personal_button_on_task_page/ajax/handler.php',
            data: {'CURRENT_USER_ID':curUserId,'TASK_ID':taskId,'ACTION':'GIVE_ME_CURRENT_TASK_DATA'},
            dataType: "json",
            onsuccess: function (data) {

               // console.log(data);

                if(data.result !== false){
                    self.addPersonButton(data.result);
                }
                else{
                    console.log(data.message);
                }

            }
        });
    }

    addPersonButton(deal_id){
        var mdiv = document.getElementsByClassName('task-view-button complete'),
            bp, inText, elemTitle, background,
            self = this; //иначе не получится вызвать нужный метод класса

        if(mdiv != null){
            bp = document.createElement('a');
            bp.className = 'component-personal-add ui-btn';
            bp.innerHTML = 'Добавить персонал';
            bp.href = '/add-pers.php?quote=' + deal_id ;
            bp.target = '_blank';
            bp.style.cssText = 'display: inline-block!important;background-color: #1313b5; color: #fff';
            mdiv[0].before(bp);
        }
    }


}