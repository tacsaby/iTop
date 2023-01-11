<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core\CRUD\Mock;

use Combodo\iTop\Core\CMDBSource\CMDBSourceService;

class MockCMDBSourceService extends CMDBSourceService
{
	private $iLevel = 0;

	public function Query($sSQLQuery)
	{
		echo __METHOD__.": level: $this->iLevel - $sSQLQuery\n";

		if (preg_match('/^START TRANSACTION;?$/i', $sSQLQuery)) {
			$this->iLevel++;
		} elseif (preg_match('/^COMMIT;?$/i', $sSQLQuery)) {
			$this->iLevel--;
		} elseif (preg_match('/^ROLLBACK;?$/i', $sSQLQuery)) {
			$this->iLevel--;
		}

		return parent::Query($sSQLQuery);
	}

	public function InsertInto($sSQLQuery)
	{
		echo __METHOD__.": level: $this->iLevel - $sSQLQuery\n";

		return parent::InsertInto($sSQLQuery);
	}

	public function IsInsideTransaction()
	{
		$bInsideTransaction = parent::IsInsideTransaction();
		echo __METHOD__.": $bInsideTransaction\n";

		return $bInsideTransaction;
	}

}