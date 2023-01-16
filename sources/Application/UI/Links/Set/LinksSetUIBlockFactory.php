<?php
/**
 * Copyright (C) 2013-2022 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Application\UI\Links\Set;

use AttributeLinkedSet;
use Combodo\iTop\Application\UI\Base\Component\Input\Set\Set;
use Combodo\iTop\Application\UI\Base\Component\Input\Set\SetUIBlockFactory;
use Combodo\iTop\Controller\Links\LinkSetModel;
use Combodo\iTop\Controller\Links\LinkSetRepository;
use Combodo\iTop\Controller\Links\LinksSetDataTransformer;
use iDBObjectSetIterator;

/**
 * Class LinksSetUIBlockFactory
 *
 * @api
 *
 * @since 3.1.0
 * @package Combodo\iTop\Application\UI\Links\Set
 */
class LinksSetUIBlockFactory extends SetUIBlockFactory
{

	/**
	 * Make a link set block.
	 *
	 * @param string $sId Block identifier
	 * @param AttributeLinkedSet $oAttDef Link set attribute definition
	 * @param iDBObjectSetIterator $oDbObjectSet Link set value
	 * @param string $sWizardHelperJsVarName Wizard helper name
	 *
	 * @return \Combodo\iTop\Application\UI\Base\Component\Input\Set\Set
	 */
	public static function MakeForLinkSet(string $sId, AttributeLinkedSet $oAttDef, iDBObjectSetIterator $oDbObjectSet, string $sWizardHelperJsVarName): Set
	{
		$sTargetClass = LinkSetModel::GetTargetClass($oAttDef);
		$sTargetField = LinkSetModel::GetTargetField($oAttDef);

		// Set UI block for OQL
		$oSetUIBlock = SetUIBlockFactory::MakeForOQL($sId, $sTargetClass, $oAttDef->GetValuesDef()->GetFilterExpression(), $sWizardHelperJsVarName);

		// Current value
		$aCurrentValues = LinksSetDataTransformer::Decode($oDbObjectSet, $sTargetClass, $sTargetField);

		// Initial options data
		$aInitialOptions = LinkSetRepository::LinksDbSetToTargetObjectArray($oDbObjectSet, $sTargetClass, $sTargetField);
		if ($aInitialOptions !== null) {
			$oSetUIBlock->GetDataProvider()->SetOptions($aInitialOptions);
			// Set value
			$oSetUIBlock->SetValue(json_encode($aCurrentValues));
		} else {
			$oSetUIBlock->SetHasError(true);
		}

		return $oSetUIBlock;
	}

	/**
	 * Make a link set block for bulk modify.
	 *
	 * @param string $sId Block identifier
	 * @param AttributeLinkedSet $oAttDef Link set attribute definition
	 * @param iDBObjectSetIterator $oDbObjectSet Link set value
	 * @param string $sWizardHelperJsVarName Wizard helper name
	 *
	 * @return \Combodo\iTop\Application\UI\Base\Component\Input\Set\Set
	 */
	public static function MakeForBulkLinkSet(string $sId, AttributeLinkedSet $oAttDef, iDBObjectSetIterator $oDbObjectSet, string $sWizardHelperJsVarName, array $aBulkContext): Set
	{
		$oSetUIBlock = self::MakeForLinkSet($sId, $oAttDef, $oDbObjectSet, $sWizardHelperJsVarName);

		// Bulk modify specific
		$oSetUIBlock->GetDataProvider()->SetGroupField('group');
		$oSetUIBlock->SetOptionsTemplate('application/object/set/bulk_option_renderer.html.twig');
		$oSetUIBlock->SetIsMultiValuesSynthesis(true);

		$aBinderSettings = [
			'bulk_oql'                  => $aBulkContext['oql'],
			'attribute_linked_set_code' => $oAttDef->GetCode(),
			'target_field'              => LinkSetModel::GetTargetField($oAttDef),
		];

		$aOptions = $oSetUIBlock->GetDataProvider()->GetOptions();
		$aOptions = LinksBulkDataBinder::Bind(LinkSetModel::GetTargetClass($oAttDef), $aOptions, $aBinderSettings);
		$oSetUIBlock->GetDataProvider()->SetOptions($aOptions);

		// Data binder
		$oSetUIBlock->GetDataProvider()->SetPostParam('binder', [
			'class_name' => addslashes(LinksBulkDataBinder::class),
			'settings'   => $aBinderSettings,
		]);

		return $oSetUIBlock;
	}
}