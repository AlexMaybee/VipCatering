<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('bizproc');
$id = $_POST['id'];
CBPDocument::StartWorkflow(
      41,
      array("crm","CCrmDocumentLead","LEAD_".$id),
 array(),
      $arErrors
);