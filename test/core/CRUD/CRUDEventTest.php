<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core\CRUD;

use Combodo\iTop\Service\Events\EventData;
use Combodo\iTop\Service\Events\EventService;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use DBObjectSet;
use lnkPersonToTeam;
use MetaModel;
use ormLinkSet;
use Team;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class CRUDEventTest extends ItopDataTestCase
{
	const USE_TRANSACTION = true;
	const CREATE_TEST_ORG = true;
	const DEBUG_UNIT_TEST = true;
	private static int $iEventCalls;

	protected function setUp(): void
	{
		parent::setUp();
//		require_once "Mock/MockCMDBSourceService.php";
//		DBObject::InitCMDBSource(new MockCMDBSourceService());
		self::$iEventCalls = 0;
		$oEventReceiver = new CRUDEventReceiver();
		$oEventReceiver->RegisterCRUDListeners();
	}

	public static function IncrementCallCount()
	{
		self::$iEventCalls++;
	}

	public function testDBInsert()
	{
		$oOrg = $this->CreateOrganization("Organization1");
		$this->assertIsObject($oOrg);
		$this->assertEquals(3, self::$iEventCalls);
	}

	public function testDBInsertTeam()
	{
		$sLinkedClass = lnkPersonToTeam::class;
		$aLinkedObjectsArray = [];
		$oSet = DBObjectSet::FromArray($sLinkedClass, $aLinkedObjectsArray);
		$oLinkSet = new ormLinkSet(Team::class, 'persons_list', $oSet);
		for ($i = 0; $i < 3; $i++) {
			$oPerson = $this->CreatePerson($i);
			$this->assertIsObject($oPerson);
			$oLink = MetaModel::NewObject(lnkPersonToTeam::class, ['person_id' => $oPerson->GetKey()]);
			$oLinkSet->AddItem($oLink);
		}

		$this->debug("");

		self::$iEventCalls = 0;
		$oTeam = MetaModel::NewObject(Team::class, ['name' => 'TestTeam1', 'persons_list' => $oLinkSet, 'org_id' => $this->getTestOrgId()]);
		$oTeam->DBInsert();
		$this->assertIsObject($oTeam);
		$this->assertEquals(12, self::$iEventCalls);
	}


	public static function DebugStatic($sMsg)
	{
		if (static::$DEBUG_UNIT_TEST) {
			if (is_string($sMsg)) {
				echo "$sMsg\n";
			} else {
				print_r($sMsg);
			}
		}
	}
}


class ClassesWithDebug
{
	/**
	 * static version of the debug to be accessible from other objects
	 *
	 * @param $sMsg
	 */
	public static function DebugStatic($sMsg)
	{
		CRUDEventTest::DebugStatic($sMsg);
	}

	/**
	 * @param $sMsg
	 */
	public function Debug($sMsg)
	{
		CRUDEventTest::DebugStatic($sMsg);
	}
}

class CRUDEventReceiver extends ClassesWithDebug
{
	// Event callbacks
	public function OnEvent(EventData $oData)
	{
		$sEvent = $oData->GetEvent();
		$oObject = $oData->Get('object');
		$sClass = get_class($oObject);
		$iKey = $oObject->GetKey();
		$this->Debug(__METHOD__.": received event '$sEvent' for $sClass::$iKey");
		CRUDEventTest::IncrementCallCount();
	}

	public function RegisterCRUDListeners()
	{
		EventService::RegisterListener(EVENT_DB_COMPUTE_VALUES, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CHECK_TO_WRITE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CHECK_TO_DELETE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CREATE_DONE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_UPDATE_DONE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_DELETE_DONE, [$this, 'OnEvent']);
	}

}