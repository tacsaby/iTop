<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core\CRUD\Mock;

use Combodo\iTop\Core\CMDBSource\CMDBSourceService;

class MockCMDBSourceService extends CMDBSourceService
{
	private static $iLevel = 0;

	public function Query($sSQLQuery)
	{
		//$iLevel = self::$iLevel;
		//echo __METHOD__.": level: $iLevel - $sSQLQuery\n";

		if (preg_match('/^START TRANSACTION;?$/i', $sSQLQuery)) {
			if (self::$iLevel === 0) {
				echo __METHOD__.": $sSQLQuery\n";
			}
			self::$iLevel++;
		} elseif (preg_match('/^COMMIT;?$/i', $sSQLQuery)) {
			self::$iLevel--;
			if (self::$iLevel === 0) {
				echo __METHOD__.": $sSQLQuery\n";
			}
		} elseif (preg_match('/^ROLLBACK;?$/i', $sSQLQuery)) {
			self::$iLevel--;
			if (self::$iLevel === 0) {
				echo __METHOD__.": $sSQLQuery\n";
			}
		}

		return parent::Query($sSQLQuery);
	}

	public function InsertInto($sSQLQuery)
	{
		$iLevel = self::$iLevel;
		echo __METHOD__.": level: $iLevel - $sSQLQuery\n";

		return parent::InsertInto($sSQLQuery);
	}

	public function IsInsideTransaction()
	{
		$bInsideTransaction = parent::IsInsideTransaction();
		//$bInsideTransaction = (self::$iLevel === 0);
		//echo __METHOD__.": $bInsideTransaction\n";

		return $bInsideTransaction;
	}

}