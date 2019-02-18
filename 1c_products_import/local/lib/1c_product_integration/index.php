<?php

define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('NO_AGENT_CHECK', true);
define("STATISTIC_SKIP_ACTIVITY_CHECK", true);
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/local/lib/1c_product_integration/class.php');

/** @global CMain $APPLICATION */
global $APPLICATION;


IncludeModuleLangFile(__FILE__);

$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
$exch1cEnabled = ($exch1cEnabled === 'Y');
if ($exch1cEnabled)
{
    if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
    {
        preg_match("/(project|tf)$/is", $license_name, $matches);
        if (strlen($matches[0]) > 0)
            $exch1cEnabled = false;
    }
}
CModule::IncludeModule('crm');
CModule::IncludeModule('iblock');



//Методы!!!

$obj = new Products1C;

//echo $obj->test();

/*
$arFilter = Array("IBLOCK_ID"=> 59, 'PROPERTY_310' => 'aa222a-bbb-dd1dd-777');//PROPERTY_116 - Это поле с ID laravel
$arSelect = Array('ID','NAME',"IBLOCK_SECTION_ID",'DETAIL_TEXT','DETAIL_PICTURE','PURCHASING_PRICE'); // Уменьшил количество полей

$productSearch = $obj->searchProductInBase($arFilter,$arSelect);

echo '<pre>';
print_r($productSearch[0]);
echo '</pre>';*/

/*
$newProdFields = array(
    //'XML_ID ' => '111',
    'NAME' => "Рос название блюда № 4",
    'PURCHASING_PRICE' => 2000, //Все равно при создании не залетает
    'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
    'PRICE' => 3000,
    'CURRENCY' => 'UAH',
    'DESCRIPTION' => 'Описание товара № 4', //Поле пояснения товара вкладки "подробно"
    'MEASURE' => 9, //Ед. измерения
    'STORE_AMOUNT' => 57, //ОСТАТКИ на складе
    //'SECTION_ID' => 138, // это папка каталога товаров Категории Клубных карт
    "PROPERTY_VALUES" => array(
        '306' => 'Укр назва блюда № 4', //Укр Название
        '307' => 'Eng name of dish № 4', //Eng Название
        '308' => "354", //Выход блюда в граммах
        '309' => 3, //Сложность от 1 до 5
        '310' => 'aa222a-bbb-dd1dd-777', //ID в 1С
    ),
    //'CATALOG_ID' => 27, //это ID блока товарного каталога, где нужно его создать = Категории Клубных карт - НЕ НУЖЕН
);
$createProdRes = $obj->createNewProductWithProp($newProdFields);

echo '<pre>';
print_r($createProdRes);
echo '</pre>';


//update Base price
$upd_fields = array(
    'PURCHASING_PRICE' => $newProdFields['PURCHASING_PRICE'],
    'PURCHASING_CURRENCY' => $newProdFields['PURCHASING_CURRENCY'],
    'PRICE' => $newProdFields['PRICE'],
    'CURRENCY' => $newProdFields['CURRENCY'],
    //'MEASURE' => $newProdFields['MEASURE'],
);
$updPriceRes = $obj->updateProductAndPurchasingPrice($createProdRes,$upd_fields);
echo '<pre>';
print_r($updPriceRes);
echo '</pre>';

//обновление остатков на складе
$updStoreFields = array(
    'PRODUCT_ID' => $createProdRes,
    'STORE_ID' => 1, //у них 1 склад, его ID = 1
    'AMOUNT' => $newProdFields['STORE_AMOUNT'], //кол-во товара
);

$updStoreRes = $obj->addProductAmountToStore($updStoreFields);
echo '<pre>';
print_r($updStoreRes);
echo '</pre>';


$obj->log($newProdFields);*/


/*
$massive = array(
    'action' => 'import_products',
    'products' => array(
        array(
            '1C_ID' => 'aa222a-bbb-dd1dd-777', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 1",
            'NAME_UA' => "Новое блюда 1",
            'NAME_EN' => "Новое блюда 1",
            'DISH_LEVEL' => 4, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 200, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 300,
            'CURRENCY' => 'USD',
            'DESCRIPTION' => 'Описание товара № 1', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 5, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 57, //ОСТАТКИ на складе шт
            'WEIGHT' => 150, //Выход блюда в граммах
            'IMAGE' => '/upload/catalog/add/store.png',
        ),
        array(
            '1C_ID' => 'bbb-333a-gggg-333', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Новое блюда 3",
            'NAME_UA' => "Нове блюда 3",
            'NAME_EN' => "NEW блюда 3",
            'DISH_LEVEL' => 5, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 130, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 300,
            'CURRENCY' => 'USD', //!!! НЕ меняется вообще, только грн.!!!
            'DESCRIPTION' => 'Описание товара 3', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 1, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 12, //ОСТАТКИ на складе шт
            'WEIGHT' => 115, //Выход блюда в граммах
        ),
        array(
            '1C_ID' => 'rrrr-55-mmm-plpl', //ID блюда в 1С !!!UNIQUE!!!
            'NAME' => "Нов блюдо 5",
            'NAME_UA' => "Нов блюдо 5",
            'NAME_EN' => "NEW  блюдо 5",
            'DISH_LEVEL' => 1, //Сложность от 1 до 5
            'PURCHASING_PRICE' => 110, //Все равно при создании не залетает
            'PURCHASING_CURRENCY' => 'UAH', //Все равно при создании не залетает
            'PRICE' => 250,
            'CURRENCY' => 'UAH', //!!! НЕ меняется вообще, только грн.!!!
            'DESCRIPTION' => 'Описание товара 5', //Поле пояснения товара вкладки "подробно"
            'MEASURE' => 5, //Ед. измерения: 1 - метр, 3 - литр, 5- грамм, 7 - кг, 9 - шт
            'STORE_AMOUNT' => 41, //ОСТАТКИ на складе шт
            'WEIGHT' => 140, //Выход блюда в граммах
        ),

    ),

);


//!!!!!! #### 15.02 Закончил на создании товаров и обновлении цен!!!


$toGo = $obj->doActionWithProduct($massive);
echo '<pre>';
print_r($toGo);
echo '</pre>';*/

//echo $obj->updateProduct(164636,$massive);



/*$file = '/upload/catalog/add/store.png';
echo $obj->saveFile($file);*/

if(isset($_POST['testSubmit'])){
    echo '<pre>';
    print_r($_POST);
    print_r($_FILES);
    echo '</pre>';

    echo $obj->saveFile($_FILES['fileField']);

}
?>

<form id="testovaya" method="post" action="" enctype="multipart/form-data">
    <div>
        <label for="name">Имя</label>
        <input type="text" name="name" id="name">
    </div>
    <div>
        <label for="testFile">Загрузка файла</label>
        <input type="file" name="fileField" id="testFile">
    </div>
    <div class="btn">
        <input type="submit" name="testSubmit" value="Загрузить файл!">
    </div>
</form>
