<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core\CRUD;

use Combodo\iTop\Service\Events\EventData;
use Combodo\iTop\Service\Events\EventService;
use Combodo\iTop\Test\UnitTest\Core\CRUD\Mock\MockCMDBSourceService;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use ContactType;
use CoreException;
use DBObject;
use DBObjectSet;
use DBSearch;
use lnkPersonToTeam;
use MetaModel;
use ormLinkSet;
use Person;
use Team;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class CRUDEventTest extends ItopDataTestCase
{
	const USE_TRANSACTION = true;
	const CREATE_TEST_ORG = false;
	const DEBUG_UNIT_TEST = true;
	private static int $iEventCalls;

	protected function setUp(): void
	{
		parent::setUp();
		require_once "Mock/MockCMDBSourceService.php";
		DBObject::InitCMDBSource(new MockCMDBSourceService());
		$this->CreateTestOrganization();
		self::$iEventCalls = 0;
	}

	public static function IncrementCallCount()
	{
		self::$iEventCalls++;
	}

	public function testDBInsert()
	{
		$oEventReceiver = new CRUDEventReceiver();
		$oEventReceiver->RegisterCRUDListeners();
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

		$oEventReceiver = new CRUDEventReceiver();
		$oEventReceiver->RegisterCRUDListeners();
		self::$iEventCalls = 0;
		$oTeam = MetaModel::NewObject(Team::class, ['name' => 'TestTeam1', 'persons_list' => $oLinkSet, 'org_id' => $this->getTestOrgId()]);
		$oTeam->DBInsert();
		$this->assertIsObject($oTeam);
		// 3 for Team, 3 x 3 for lnkPersonToTeam
		$this->assertEquals(12, self::$iEventCalls);
	}

	/**
	 * The test create a team containing a Person. During the insert of the lnkPersonToTeam a modification is done on the link, check that the link is saved correctly.
	 *
	 * @return void
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public function testDBInsertTeamWithModificationsDuringInsert()
	{
		$sLinkedClass = lnkPersonToTeam::class;
		$aLinkedObjectsArray = [];
		$oSet = DBObjectSet::FromArray($sLinkedClass, $aLinkedObjectsArray);
		$oLinkSet = new ormLinkSet(Team::class, 'persons_list', $oSet);

		$oPerson = $this->CreatePerson(1);
		$this->assertIsObject($oPerson);
		$oLink = MetaModel::NewObject(lnkPersonToTeam::class, ['person_id' => $oPerson->GetKey()]);
		$oLinkSet->AddItem($oLink);

		$this->debug("\n-------------> Test Starts HERE\n");
		$oEventReceiver = new CRUDEventReceiverModifications([['event' => EVENT_DB_CREATE_DONE, 'class' => lnkPersonToTeam::class, 'fct' => 'AddRoleToLink']]);
		$oEventReceiver->RegisterCRUDListeners();

		self::$iEventCalls = 0;
		$oTeam = MetaModel::NewObject(Team::class, ['name' => 'TestTeam1', 'persons_list' => $oLinkSet, 'org_id' => $this->getTestOrgId()]);
		$oTeam->DBInsert();
		$this->assertIsObject($oTeam);
		// 3 for Team, 3 for lnkPersonToTeam, 3 for ContactType and 3 for the update of lnkPersonToTeam
		$this->assertEquals(12, self::$iEventCalls);

		// Read the object explicitly from the DB
		$oSet = new DBObjectSet(DBSearch::FromOQL('SELECT Team WHERE id=:id'), [], ['id' => $oTeam->GetKey()]);
		$oTeamResult = $oSet->Fetch();
		$oLinkSet = $oTeamResult->Get('persons_list');
		$oLinkSet->rewind();
		$oLink = $oLinkSet->current();
		// Check that role has been set
		$this->assertNotEquals(0, $oLink->Get('role_id'));
	}

	/**
	 * It is not allowed to modify object during check to write operation
	 * @return void
	 * @throws \Exception
	 */
	public function testCheckToWriteProtectedOnInsert()
	{
		$oEventReceiver = new CRUDEventReceiverModifications([['event' => EVENT_DB_CHECK_TO_WRITE, 'class' => Person::class, 'fct' => 'SetPersonFunction']]);
		$oEventReceiver->RegisterCRUDListeners();

		$this->expectException(CoreException::class);
		$this->CreatePerson(1);
	}

	/**
	 * It is not allowed to modify object during check to write operation
	 * @return void
	 * @throws \Exception
	 */
	public function testCheckToWriteProtectedOnUpdate()
	{
		$oPerson = $this->CreatePerson(1);

		$oEventReceiver = new CRUDEventReceiverModifications([['event' => EVENT_DB_CHECK_TO_WRITE, 'class' => Person::class, 'fct' => 'SetPersonFunction']]);
		$oEventReceiver->RegisterCRUDListeners();
		$this->expectException(CoreException::class);
		$oPerson->Set('function', 'test');
		$oPerson->DBUpdate();
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

class CRUDEventReceiverModifications extends ClassesWithDebug
{
	private $aCallbacks = [];

	public function __construct(array $aCallbacks)
	{
		foreach ($aCallbacks as $aCallback) {
			$this->aCallbacks[$aCallback['event']][$aCallback['class']] = [$this, $aCallback['fct']];
		}
	}

	// Event callbacks
	public function OnEvent(EventData $oData)
	{
		$sEvent = $oData->GetEvent();
		$oObject = $oData->Get('object');
		$sClass = get_class($oObject);
		$iKey = $oObject->GetKey();
		$this->Debug(__METHOD__.": received event '$sEvent' for $sClass::$iKey");
		CRUDEventTest::IncrementCallCount();

		if (isset($this->aCallbacks[$sEvent][$sClass])) {
			call_user_func($this->aCallbacks[$sEvent][$sClass], $oObject);
		}
	}

	public function RegisterCRUDListeners()
	{
		$this->Debug('Registering Test event listeners');
		EventService::RegisterListener(EVENT_DB_COMPUTE_VALUES, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CHECK_TO_WRITE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CHECK_TO_DELETE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_CREATE_DONE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_UPDATE_DONE, [$this, 'OnEvent']);
		EventService::RegisterListener(EVENT_DB_DELETE_DONE, [$this, 'OnEvent']);
	}

	/**
	 * @param $oObject
	 *
	 * @return void
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	private function AddRoleToLink($oObject): void
	{
		$oContactType = MetaModel::NewObject(ContactType::class, ['name' => 'test_'.$oObject->GetKey()]);
		$oContactType->DBInsert();
		$oObject->Set('role_id', $oContactType->GetKey());
	}

	private function SetPersonFunction($oObject): void
	{
		$oObject->Set('function', 'test_'.rand());
	}

}
