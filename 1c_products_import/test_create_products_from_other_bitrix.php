<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
//header( 'Content-type: text/xml' );
//use Bitrix\Main\Web\HttpClient;
$url = 'https://my.vipcatering.com.ua/local/lib/1c_product_integration/index.php';

$http = new \Bitrix\Main\Web\HttpClient(array($options = null));

$user = '';
$pass = '';


$http->setAuthorization($user, $pass);
/*
$http->setHeader('Content-Type', 'application/json', true);
		$json = ['action' => 'getContactListChange'];

         $response = $http->post($url, json_encode($json));*/

$http->setHeader('Content-Type', 'application/json', true);
$massive = array(
    'action' => 'export_products',
    'products' => array(
        array(
            '1C_ID' => 'aa222a-bbb-dd1dd-777', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 1",
            'NAME_UA' => "Новое блюда 1",
            'NAME_EN' => "Новое блюда 1",
            'DISH_LEVEL' => 2, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 310, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 400,
            'CURRENCY' => 'USD',
            'DESCRIPTION' => 'Описание товара № 1', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 5, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 75, //ОСТАТКИ на складе шт
            'WEIGHT' => 150, //Выход блюда в граммах
            'IMAGE' => 'https://cp.itlogic.biz'.CFile::GetPath(74318),
        ),
        array(
            '1C_ID' => 'tttt-45545-gfg-3333313', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 4",
            'NAME_UA' => "Нове блюда 4",
            'NAME_EN' => "NEW блюда 4",
            'DISH_LEVEL' => 2, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 15, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'USD', //Все равно при создании не залетает
            'PRICE' => 500,
            'CURRENCY' => 'UAH', //!!! НЕ меняется вообще, только грн.!!!
            'DESCRIPTION' => 'Описание товара 4', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 1, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 18, //ОСТАТКИ на складе шт
            'WEIGHT' => 185, //Выход блюда в граммах
            'IMAGE' => 'https://cp.itlogic.biz'.CFile::GetPath(85623),
        ),
        array(
            '1C_ID' => 'ffff-llll-gggg-4555', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 5",
            'NAME_UA' => "Нове блюда 5",
            'NAME_EN' => "NEW блюда 5",
            'DISH_LEVEL' => 4, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 500, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 300,
            'CURRENCY' => 'UAH', //!!! НЕ меняется вообще, только грн.!!!
            'DESCRIPTION' => 'Описание товара 2', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 1, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 12, //ОСТАТКИ на складе шт
            'WEIGHT' => 115, //Выход блюда в граммах
            'IMAGE' => 'https://cp.itlogic.biz'.CFile::GetPath(85623),
        ),
        array(
            '1C_ID' => 'PPPP-ppp-gggg-6767', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 7",
            'NAME_UA' => "Нове блюда 7",
            'NAME_EN' => "NEW блюда 7",
            'DISH_LEVEL' => 4, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 100, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 10,
            'CURRENCY' => 'USD', //!!! НЕ меняется вообще, только грн.!!!
            'DESCRIPTION' => 'Описание товара 7', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 1, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 15, //ОСТАТКИ на складе шт
            'WEIGHT' => 169, //Выход блюда в граммах
            'IMAGE' => 'https://cp.itlogic.biz'.CFile::GetPath(85623),
        ),
    ),
);

$response = $http->post($url, json_encode($massive));

echo $response;

?>