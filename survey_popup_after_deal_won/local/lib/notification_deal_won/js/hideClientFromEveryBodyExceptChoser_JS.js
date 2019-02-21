BX.ready(function() {

    //вызов класса через объект
    let showDealClients = new ShowDealClientsToChosen();


});


class ShowDealClientsToChosen{

    constructor(){

        this.url = window.location.href;

        console.log(this.url);

        //скрытие колонки клиента в списке сделок /crm/deal/category/0/
        this.hideClientInDealList();

        //скрытие контакта в канбане
        this.hideClientInCanBan(); //crm-kanban-item-contact



    }

    //скрытие колонки клиента в списке сделок /crm/deal/category/0/
    hideClientInDealList(){
        var matches, dealList, i;

        if(matches = this.url.match(/\/crm\/deal\/category\/([\d]+)/i)){

            dealList = document.querySelectorAll('[bx-tooltip-user-id]'); //.closest('.crm-info-wrapper')

            //скрытие
            for(i=0; i < dealList.length; i++){
                if(dealList[i].closest('.crm-info-wrapper')){
                    //   console.log(this.dealList[i]);
                    dealList[i].closest('.crm-info-wrapper').style.display = 'none';
                }
            }

           // console.log(dealList.length);
        }


    }

    hideClientInCanBan(){
        var matches, kanbanList, k=0;

        if(matches = this.url.match(/\/crm\/deal\/kanban\/category\//i)){


            kanbanList = document.getElementsByClassName('crm-kanban-item-contact');


            for(var o= 0; o < kanbanList.length; o++){
                kanbanList[o].closest('.crm-kanban-item-total').style.display = 'none';
            }


            console.log(kanbanList);
        }
    }


}