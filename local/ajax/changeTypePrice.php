<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

CModule::IncludeModule("iblock");

$return = [];

if($_REQUEST['action']) {
    switch ($_REQUEST['action']) {
        case 'getProductsPrice':

            $category_id = CCrmDeal::GetByID($_REQUEST['deal_id']);
            switch ($category_id['CATEGORY_ID']) {
                case 0:
                    $type_price = 3;
                    break;
                case 2:
                    $type_price = 4;
                    break;
                default:
                    $type_price = 3;
            }
            $return = getPriceProduct($_REQUEST['id'], $type_price);

            break;
    }
}

function getPriceProduct($id, $type_price)
{
    $arSelect = Array("ID", "CATALOG_GROUP_".$type_price,); // Уменьшил количество полей
    $arFilter = Array("IBLOCK_ID"=> 59, "ID" => $id);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    if($ob = $res->GetNextElement())
        $arFields = $ob->GetFields();
        if($arFields['ID'])
            $arFields['PRICE_VALUE'] = $arFields['CATALOG_PRICE_'.$type_price];
            return $arFields;
}

echo json_encode($return);

?>
