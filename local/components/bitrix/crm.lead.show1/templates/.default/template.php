<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
use \Bitrix\Crm\Integration\StorageType;
use \Bitrix\Crm\Conversion\LeadConversionScheme;

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

//Preliminary registration of disk api.
if(CCrmActivity::GetDefaultStorageTypeID() === StorageType::Disk)
{
	CJSCore::Init(array('uploader', 'file_dialog'));
}

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_LEAD_SHOW_TITLE',
	array(
		'#ID#' => $arResult['ELEMENT']['ID'],
		'#TITLE#' => $arResult['ELEMENT']['TITLE']
	)
);

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$bizprocDispatcherID = strtolower($arResult['FORM_ID']).'_bp_disp';

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1'],
	'display' => false
);
if(!empty($arResult['FIELDS']['tab_details']))
{
	$arTabs[] = array(
		'id' => 'tab_details',
		'name' => GetMessage('CRM_TAB_DETAILS'),
		'title' => GetMessage('CRM_TAB_DETAILS_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_details'],
		'display' => false
	);
}

$liveFeedTab = null;
if (!empty($arResult['FIELDS']['tab_live_feed']))
{
	$liveFeedTab = array(
		'id' => 'tab_live_feed',
		'name' => GetMessage('CRM_TAB_LIVE_FEED'),
		'title' => GetMessage('CRM_TAB_LIVE_FEED_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_live_feed']
	);
	$arTabs[] = $liveFeedTab;
}
if (!empty($arResult['FIELDS']['tab_activity']))
{
	$arTabs[] = array(
		'id' => 'tab_activity',
		'name' => GetMessage('CRM_TAB_6'),
		'title' => GetMessage('CRM_TAB_6_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_activity']
	);
}
$arTabs[] = array(
	'id' => $arResult['PRODUCT_ROW_TAB_ID'],
	'name' => GetMessage('CRM_TAB_PRODUCT_ROWS'),
	'title' => GetMessage('CRM_TAB_PRODUCT_ROWS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']]
);
if ($arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED'):
	if (!empty($arResult['FIELDS']['tab_contact']))
		$arTabs[] = array(
			'id' => 'tab_contact',
			//'name' => GetMessage('CRM_TAB_2')." ($arResult[CONTACT_COUNT])",
			'name' => GetMessage('CRM_TAB_2'),
			'title' => GetMessage('CRM_TAB_2_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_contact']
		);
	if (!empty($arResult['FIELDS']['tab_company']))
		$arTabs[] = array(
			'id' => 'tab_company',
			//'name' => GetMessage('CRM_TAB_3')." ($arResult[COMPANY_COUNT])",
			'name' => GetMessage('CRM_TAB_3'),
			'title' => GetMessage('CRM_TAB_3_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_company']
		);
	if (!empty($arResult['FIELDS']['tab_deal']))
		$arTabs[] = array(
			'id' => 'tab_deal',
			//'name' => GetMessage('CRM_TAB_4')." ($arResult[DEAL_COUNT])",
			'name' => GetMessage('CRM_TAB_4'),
			'title' => GetMessage('CRM_TAB_4_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_deal']
		);
endif;

if (!empty($arResult['FIELDS']['tab_quote']))
{
	$arTabs[] = array(
		'id' => 'tab_quote',
		'name' => GetMessage('CRM_TAB_8'),
		'title' => GetMessage('CRM_TAB_8_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_quote']
	);
}

if (isset($arResult['BIZPROC']) && $arResult['BIZPROC'] === 'Y' && !empty($arResult['FIELDS']['tab_bizproc']))
{
	$arTabs[] = array(
		'id' => 'tab_bizproc',
		'name' => GetMessage('CRM_TAB_7'),
		'title' => GetMessage('CRM_TAB_7_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_bizproc']
	);
}
$arTabs[] = array(
	'id' => 'tab_event',
	//'name' => GetMessage('CRM_TAB_HISTORY')." ($arResult[EVENT_COUNT])",
	'name' => GetMessage('CRM_TAB_HISTORY'),
	'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_event']
);
$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;
?>
<button id="bp-push" >Сконвертировать лид</button>
<?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TACTILE_FORM_ID' => 'CRM_LEAD_EDIT_V12',
		'QUICK_PANEL' => array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::LeadName,
			'ENTITY_ID' => $arResult['ELEMENT_ID'],
			'ENTITY_FIELDS' => $element,
			'ENABLE_INSTANT_EDIT' => $arResult['ENABLE_INSTANT_EDIT'],
			'INSTANT_EDITOR_ID' => $instantEditorID,
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get()
		),
		'TABS' => $arTabs,
		'DATA' => $element,
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
<?if($arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEntityType.captions =
			{
				"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
				"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
				"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
				"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
				"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>",
				"<?=CCrmOwnerType::QuoteName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)?>"
			};

			BX.CrmLeadConversionScheme.messages =
				<?=CUtil::PhpToJSObject(LeadConversionScheme::getJavaScriptDescriptions(false))?>;
			BX.CrmLeadConverter.messages =
			{
				accessDenied: "<?=GetMessageJS("CRM_LEAD_CONV_ACCESS_DENIED")?>",
				generalError: "<?=GetMessageJS("CRM_LEAD_CONV_GENERAL_ERROR")?>",
				dialogTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_TITLE")?>",
				syncEditorLegend: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_LEGEND")?>",
				syncEditorFieldListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
				syncEditorEntityListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
				continueButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CONTINUE_BTN")?>",
				cancelButton: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_CANCEL_BTN")?>"
			};
			BX.CrmLeadConverter.permissions =
			{
				contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
				company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
				deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
			};
			BX.CrmLeadConverter.settings =
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
				config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
			};
		}
	);
</script>
<?endif;?>

<?if($arResult['ENABLE_INSTANT_EDIT']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmInstantEditorMessages =
			{
				editButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_EDIT_BTN_TTL'))?>',
				lockButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_LOCK_BTN_TTL'))?>'
			};

			var instantEditor = BX.CrmInstantEditor.create(
				'<?=CUtil::JSEscape($instantEditorID)?>',
				{
					containerID: [],
					ownerType: 'L',
					ownerID: <?=$arResult['ELEMENT_ID']?>,
					url: '/bitrix/components/bitrix/crm.lead.show/ajax.php?<?= bitrix_sessid_get()?>',
					callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix)?>
				}
			);

			var prodEditor = BX.CrmProductEditor.getDefault();

			function handleProductRowChange()
			{
				if(prodEditor)
				{
					var haveProducts = prodEditor.getProductCount() > 0;
					instantEditor.setFieldReadOnly('OPPORTUNITY', haveProducts);
					instantEditor.setFieldReadOnly('CURRENCY_ID', haveProducts);
				}
			}

			function handleSelectProductEditorTab(objForm, objFormName, tabID, tabElement)
			{
				var productRowsTabId = "<?=$arResult['PRODUCT_ROW_TAB_ID']?>";
				if (typeof(productRowsTabId) === "string" && productRowsTabId.length > 0 && tabID === productRowsTabId)
					BX.onCustomEvent("CrmHandleShowProductEditor", [prodEditor]);
			}

			if(prodEditor)
			{
				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						instantEditor.setFieldValue('OPPORTUNITY', ttl);
						if(prodEditor.isViewMode())
						{
							//emulate save field event to refresh controls
							instantEditor.riseSaveFieldValueEvent('OPPORTUNITY', ttl);
						}
					}
				);

				handleProductRowChange();

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					handleProductRowChange
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					handleProductRowChange
				);

				BX.addCustomEvent(
					'BX_CRM_INTERFACE_FORM_TAB_SELECTED',
					handleSelectProductEditorTab
				);
			}
		}
	);
</script>
<?endif;?>

<?if(isset($arResult['ENABLE_BIZPROC_LAZY_LOADING']) && $arResult['ENABLE_BIZPROC_LAZY_LOADING'] === true):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var bpContainerId = "<?=$arResult['BIZPROC_CONTAINER_ID']?>";
			if(!BX(bpContainerId))
			{
				return;
			}

			BX.CrmBizprocDispatcher.create(
				"<?=CUtil::JSEscape($bizprocDispatcherID)?>",
				{
					containerID: bpContainerId,
					entityTypeName: "<?=CCrmOwnerType::LeadName?>",
					entityID: <?=$arResult['ELEMENT_ID']?>,
					serviceUrl: "/bitrix/components/bitrix/crm.lead.show/bizproc.php?lead_id=<?=$arResult['ELEMENT_ID']?>&post_form_uri=<?=urlencode($arResult['POST_FORM_URI'])?>&<?=bitrix_sessid_get()?>",
					formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					pathToEntityShow: "<?=CUtil::JSEscape($arResult['PATH_TO_LEAD_SHOW'])?>"
				}
			);
		}
	);
</script>
<?endif;?>

<?if(isset($arResult['ENABLE_LIVE_FEED_LAZY_LOAD']) && $arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] === true):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var liveFeedContainerId = "<?=CUtil::JSEscape($arResult['LIVE_FEED_CONTAINER_ID'])?>";
			if(!BX(liveFeedContainerId))
			{
				return;
			}

			var params =
			{
				"ENTITY_TYPE_NAME" : "<?=CCrmOwnerType::LeadName?>",
				"ENTITY_ID": <?=$arResult['ELEMENT_ID']?>,
				"POST_FORM_URI": "<?=CUtil::JSEscape($arResult['POST_FORM_URI'])?>",
				"ACTION_URI": "<?=CUtil::JSEscape($arResult['ACTION_URI'])?>",
				"PATH_TO_USER_PROFILE": "<?=CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>"
			};

			BX.addCustomEvent(
				window,
				"SonetLogBeforeGetNextPage",
				function(data)
					{
						if(!BX.type.isNotEmptyString(data["url"]))
						{
							return;
						}

						var request = {};
						for(var key in params)
						{
							if(params.hasOwnProperty(key))
							{
								request["PARAMS[" + key + "]"] = params[key];
							}
						}
						data["url"] = BX.util.add_url_param(data["url"], request);
					}
			);

			BX.CrmFormTabLazyLoader.create(
				"<?=CUtil::JSEscape(strtolower($arResult['FORM_ID'])).'_livefeed'?>",
				{
					containerID: liveFeedContainerId,
					serviceUrl: "/bitrix/components/bitrix/crm.entity.livefeed/lazyload.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					formID: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
					tabID: "tab_live_feed",
					params: params
				}
			);
		}
	);
</script>
<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {

		$('#bp-push').on('click', function(){
			let id = window.location.pathname.split('/')[4];
			$.ajax({
			    type: "POST",
			    url: '//'+window.location.hostname+':'+window.location.port+'/local/lib/bp_push.php',
			    data: 'id='+id,
			    success: function(data){
			    	console.log(data);
			    }
			});
		});
        $('.crm-btn-convert-wrap').hide();
	});
</script>
<?endif;?>
