<?php
/**
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Application\UI\Links\Direct;

use cmdbAbstractObject;
use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\DataTable\DataTableUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\MedallionIcon\MedallionIcon;
use Combodo\iTop\Application\UI\Base\Component\Panel\PanelUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Toolbar\ToolbarUIBlockFactory;
use Combodo\iTop\Application\UI\Base\iUIBlock;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlock;
use Dict;
use MetaModel;

/**
 * Class BlockDirectLinksEditTable
 *
 * @internal
 * @since 3.1.0
 * @package Combodo\iTop\Application\UI\Links\Direct
 */
class BlockDirectLinksEditTable extends UIContentBlock
{
	// Overloaded constants
	public const BLOCK_CODE                   = 'ibo-block-direct-links-edit-table';
	public const DEFAULT_JS_TEMPLATE_REL_PATH = 'application/links/direct/block-direct-links-edit-table/layout';

	/** @var \UILinksWidgetDirect */
	public \UILinksWidgetDirect $oUILinksDirectWidget;

	/** @var \AttributeLinkedSet */
	private \AttributeLinkedSet $oAttributeLinkedSet;

	/** @var string */
	public string $sInputName;

	/** @var array */
	public array $aLabels;

	/** @var string */
	public string $sSubmitUrl;

	/** @var string */
	public string $sWizHelper;

	/** @var string */
	public string $sJSDoSearch;

	/**
	 * Constructor.
	 *
	 * @param \UILinksWidgetDirect $oUILinksDirectWidget
	 * @param string $sId
	 *
	 * @throws \ConfigException
	 * @throws \CoreException
	 * @throws \DictExceptionMissingString
	 * @throws \Exception
	 */
	public function __construct(\UILinksWidgetDirect $oUILinksDirectWidget, string $sId)
	{
		parent::__construct($sId, ["ibo-block-direct-links--edit-in-place"]);

		// Retrieve parameters
		$this->oUILinksDirectWidget = $oUILinksDirectWidget;

		// compute
		$this->aLabels = array(
			'delete'          => Dict::S('UI:Button:Delete'),
			'creation_title'  => Dict::Format('UI:CreationTitle_Class', MetaModel::GetName($this->oUILinksDirectWidget->GetLinkedClass())),
			'create'          => Dict::Format('UI:ClickToCreateNew', MetaModel::GetName($this->oUILinksDirectWidget->GetLinkedClass())),
			'remove'          => Dict::S('UI:Button:Remove'),
			'add'             => Dict::Format('UI:AddAnExisting_Class', MetaModel::GetName($this->oUILinksDirectWidget->GetLinkedClass())),
			'selection_title' => Dict::Format('UI:SelectionOf_Class', MetaModel::GetName($this->oUILinksDirectWidget->GetLinkedClass())),
		);
		$oContext = new \ApplicationContext();
		$this->sSubmitUrl = \utils::GetAbsoluteUrlAppRoot().'pages/ajax.render.php?'.$oContext->GetForLink();

		// Don't automatically launch the search if the table is huge
		$bDoSearch = !\utils::IsHighCardinality($this->oUILinksDirectWidget->GetLinkedClass());
		$this->sJSDoSearch = $bDoSearch ? 'true' : 'false';

		// Initialization
		$this->Init();

		// Initialize UI
		$this->InitUI();
	}

	/**
	 * Initialisation.
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function Init()
	{
		$this->oAttributeLinkedSet = MetaModel::GetAttributeDef($this->oUILinksDirectWidget->GetClass(), $this->oUILinksDirectWidget->GetAttCode());
	}

	/**
	 * Initialize UI.
	 *
	 * @return void
	 * @throws \CoreException
	 * @throws \Exception
	 */
	private function InitUI()
	{
		// MedallionIcon
		$oClassIcon = new MedallionIcon(MetaModel::GetClassIcon($this->oUILinksDirectWidget->GetLinkedClass(), false));
		$oClassIcon->SetDescription($this->oAttributeLinkedSet->GetDescription());
		$oClassIcon->AddCSSClass('ibo-block-list--medallion');
		$this->AddSubBlock($oClassIcon);
	}

	/**
	 * @param \WebPage $oPage
	 * @param \DBObjectSet $oValue
	 * @param string $sFormPrefix
	 *
	 * @return void
	 */
	public function InitTable(\WebPage $oPage, \DBObjectSet $oValue, string $sFormPrefix)
	{
		/** @todo fields initialization */
		$this->sInputName = $sFormPrefix.'attr_'.$this->oUILinksDirectWidget->GetAttCode();
		$this->sWizHelper = 'oWizardHelper'.$sFormPrefix;

		try {
			$aAttribs = $this->oUILinksDirectWidget->GetTableConfig();
			$aRows = $this->GetTableRows($oPage, $oValue);
			$aRowActions = $this->GetRowActions();
			$oDatatable = DataTableUIBlockFactory::MakeForForm($this->oUILinksDirectWidget->GetInputId(), $aAttribs, $aRows, '', $aRowActions);
			$oDatatable->SetOptions(['select_mode' => 'custom', 'disable_hyperlinks' => true]);
			$aTablePanel = PanelUIBlockFactory::MakeNeutral('');
			$aTablePanel->SetSubTitle(sprintf('Total: %d objects.', count($aRows)));
			$oToolbar = ToolbarUIBlockFactory::MakeForButton();
			$oActionButtonUnlink = ButtonUIBlockFactory::MakeNeutral('Unlink');
			$oActionButtonUnlink->SetOnClickJsCode("$('#{$this->oUILinksDirectWidget->GetInputId()}').directlinks('instance')._removeSelection();");
			$oToolbar->AddSubBlock($oActionButtonUnlink);
			$oActionButtonLink = ButtonUIBlockFactory::MakeNeutral('Link');
			$oActionButtonLink->SetOnClickJsCode("$('#{$this->oUILinksDirectWidget->GetInputId()}').directlinks('instance')._selectToAdd();");
			$oToolbar->AddSubBlock($oActionButtonLink);
			$oActionButtonCreate = ButtonUIBlockFactory::MakeNeutral('Create');
			$oActionButtonCreate->SetOnClickJsCode("$('#{$this->oUILinksDirectWidget->GetInputId()}').directlinks('instance')._createRow();");
			$oToolbar->AddSubBlock($oActionButtonCreate);
			$oActionButtonDelete = ButtonUIBlockFactory::MakeNeutral('Delete');
			$oActionButtonDelete->SetOnClickJsCode("$('#{$this->oUILinksDirectWidget->GetInputId()}').directlinks('instance')._deleteSelection();");
			$oToolbar->AddSubBlock($oActionButtonDelete);
			$aTablePanel->AddToolbarBlock($oToolbar);
			$aTablePanel->AddSubBlock($oDatatable);
			$this->AddSubBlock($aTablePanel);
		}
		catch (\Exception $e) {
			$oAlert = AlertUIBlockFactory::MakeForDanger('error', 'error while trying to load datatable');
			$oAlert->SetIsClosable(false);
			$oAlert->SetIsCollapsible(false);
			$this->AddSubBlock($oAlert);
		}
	}

	/**
	 * Return table rows.
	 *
	 * @param \DBObjectSet $oValue
	 *
	 * @return array
	 * @throws \ArchivedObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \DictExceptionMissingString
	 * @throws \MySQLException
	 * @throws \Exception
	 */
	private function GetTableRows(\WebPage $oPage, \DBObjectSet $oValue): array
	{
		// result data
		$aRows = array();


		$iAddedId = -1; // Unique id for new links

		// set pointer to start
		$oValue->Rewind();
		// create a row table for each value...
		while ($oLinkObj = $oValue->Fetch()) {

			$aRows[] = $this->CreateRow($oLinkObj, $iAddedId, $oPage);
		}

		return $aRows;
	}

	public function CreateRow($oLinkObj, &$iAddedId, $oPage)
	{
		$sPrefix = "{$this->oUILinksDirectWidget->GetAttCode()}{$this->oUILinksDirectWidget->GetNameSuffix()}";

		$aArgs = [];
		if ($oLinkObj->IsNew()) {
			$key = $iAddedId--;
			$aArgs['wizHelper'] = "oWizardHelper{$this->oUILinksDirectWidget->GetInputId()}_".-$key;
		} else {
			$key = $oLinkObj->GetKey();
			$aArgs['wizHelper'] = "oWizardHelper{$this->oUILinksDirectWidget->GetInputId()}".$key;
		}

		$aRow = array();
		$aRow['form::select'] = '<input data-link-id="'.$oLinkObj->GetKey().'" data-unique-id="'.$key.'" type="checkbox" class="selection selectList'.$this->oUILinksDirectWidget->GetInputId().'" onClick="oWidget'.$this->oUILinksDirectWidget->GetInputId().'.directlinks(\'instance\')._onSelectChange();" value="'.$oLinkObj->GetKey().'"/>';

		$aArgs['prefix'] = $sPrefix."[{$oLinkObj->GetKey()}][";


		$aArgs['this'] = $oLinkObj;

		foreach ($this->oUILinksDirectWidget->GetZList() as $sLinkedAttCode) {

			// tentative d'ajout des attributs en édition
			$sValue = $oLinkObj->Get($sLinkedAttCode);
			$sDisplayValue = $oLinkObj->GetEditValue($sLinkedAttCode);
			$oAttDef = MetaModel::GetAttributeDef($this->oUILinksDirectWidget->GetLinkedClass(), $sLinkedAttCode);

			// show form field if writable
			if ($oAttDef->IsWritable()) {
				$aRow[$sLinkedAttCode] = '<div class="field_container" style="border:none;"><div class="field_data"><div class="field_value">'
					.cmdbAbstractObject::GetFormElementForField(
						$oPage,
						$this->oUILinksDirectWidget->GetLinkedClass(),
						$sLinkedAttCode,
						$oAttDef,
						$sValue,
						$sDisplayValue,
						$this->GetFieldId($oLinkObj->GetKey(), $sLinkedAttCode),
						']',
						0,
						$aArgs
					)
					.'</div></div></div>';
			} else {
				$aRow[$sLinkedAttCode] = $oLinkObj->GetAsHTML($sLinkedAttCode);
			}

		}

		$aFieldsMap = [];
		$this->AddWizardHelperInit($oPage, $aArgs['wizHelper'], $this->oUILinksDirectWidget->GetLinkedClass(), $oLinkObj->GetState(), $aFieldsMap);

		return $aRow;
	}

	private function GetFieldId($iLnkId, $sFieldCode, $bSafe = true)
	{
		$sFieldId = $this->oUILinksDirectWidget->GetInputId().'_'.$sFieldCode.'['.$iLnkId.']';

		return ($bSafe) ? \utils::GetSafeId($sFieldId) : $sFieldId;
	}

	/**
	 * Return row actions.
	 *
	 * @return \string[][]
	 */
	private function GetRowActions(): array
	{
		$aRowActions = array();

		if (!$this->oAttributeLinkedSet->GetReadOnly()) {
			$aRowActions[] = array(
				'tooltip'       => 'remove link',
				'icon_classes'  => 'fas fa-minus',
				'js_row_action' => "$('#{$this->oUILinksDirectWidget->GetInputId()}').directlinks('instance')._deleteRow($(':checkbox', oTrElement));",
			);
		}

		return $aRowActions;
	}

	private function AddWizardHelperInit($oP, $sWizardHelperVarName, $sWizardHelperClass, $sState, $aFieldsMap): void
	{
		$iFieldsCount = count($aFieldsMap);
		$sJsonFieldsMap = json_encode($aFieldsMap);

		$oP->add_script(
			<<<JS
var $sWizardHelperVarName = new WizardHelper('$sWizardHelperClass', '', '$sState');
$sWizardHelperVarName.SetFieldsMap($sJsonFieldsMap);
$sWizardHelperVarName.SetFieldsCount($iFieldsCount);
$sWizardHelperVarName.SetReturnNotEditableFields(true);
$sWizardHelperVarName.SetWizHelperJsVarName('$sWizardHelperVarName');
JS
		);
	}
}