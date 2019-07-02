//код исполняем, только когда DOM загружен
BX.ready(function() {
    var href = window.location.href,
        dealId;
//узнаем id задачи из URL
    if (matches = href.match(/\/deal\/details\/([\d]+)\//i)) {
        dealId = matches[1];
    }

    if (dealId > 0) {
        createButtonBP(dealId);
    }


    function createButtonBP(dealId) {
        var wrap = document.getElementById("pagetitle-menu");
        var addButtonMore = BX.create("span", {
            props: {className: "ui-btn-double ui-btn-primary ui-btn-primary_custom"},
            children: [
                BX.create("button", {
                    props: {className: "ui-btn-main", type: "button"},
                    html: 'БП',
                    events: {click: BX.proxy(this.addEntry, this)}
                })
            ]
        });
        wrap.appendChild(addButtonMore);


        var menuItems = [
            {
                text: 'Скачать предложение',
                onclick: function () {
                    //location.href = '/bitrix/components/itlogic/estimate.element.add/templates/.default1/quote_excel.php?quote=' + dealId + '&lang=RU';
                    window.open('/bitrix/components/itlogic/estimate.element.add/templates/quote_excel/ajax.php?quote=' + dealId + '&lang=RU', '_blank');
                }
            },
            {
                text: 'Скачать предложение без артикула',
                onclick: function () {
                    //location.href = '/bitrix/components/itlogic/estimate.element.add/templates/.default1/quote_excel.php?quote=' + dealId + '&lang=RU';
                    window.open('/bitrix/components/itlogic/estimate.element.add/templates/quote_excel/ajax.php?quote=' + dealId + '&lang=RU&sku=N', '_blank');
                }
            },
            {
                text: 'Скачать выбранные предложения',
                onclick: function () {
                    var datas = $('div[data-tab-id="tab_quote"] form').serializeArray(),
                        check_prod = [];
                    if(datas.length > 0) {
                        for (let val of datas) {
                            if (val.name == "ID[]") {
                                check_prod.push(val.value);
                            }
                        }
                    }
                    if(check_prod.length == 0) {
                        alert('Выберите предложения!');
                        return false;
                    }
                    console.log(JSON.stringify(check_prod));
                    window.open('/bitrix/components/itlogic/estimate.element.add/templates/quote_excel/ajax.php?quote=' + dealId + '&lang=RU&quote_ids=' + JSON.stringify(check_prod), '_blank');
                }
            },
            {
                text: 'Скачать предложение pdf',
                onclick: function () {
                    window.open('/bitrix/components/itlogic/estimate.element.add/templates/quote_excel/ajax.php?quote=' + dealId + '&lang=RU&file=pdf', '_blank');
                }
            },
        ];

        var addButtonExtra = BX.create("span", {
            props: {className: "ui-btn-extra"},
            events: {
                click: function () {
                    var popup = BX.PopupMenu.create(
                        'bp_menu',
                        addButtonExtra,
                        menuItems,
                        {
                            closeByEsc: true,
                            autoHide: true,
                            zIndex: this.zIndex,
                            offsetTop: 0,
                            offsetLeft: 15,
                            angle: true
                        }
                    );
                    popup.show();

                }
            }
        });

        addButtonMore.appendChild(addButtonExtra);
    }

    var panelProduct = BX.findChild( //найти пасынков...
        $('workarea-content'), //...для родителя
        { //с такими вот свойствами
            tag: 'div',
            className: 'bx-crm-footer-interface-toolbar-container'
        },
        true
    );

})
