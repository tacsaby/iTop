<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core\CRUD\Mock;

use Combodo\iTop\Core\CMDBSource\CMDBSourceService;
use Exception;

class FullMockCMDBSourceService extends CMDBSourceService
{

	/**
	 * @inheritDoc
	 */
	public function GetSqlStringColumnDefinition(): string
	{
		echo __METHOD__."\n";
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function InitFromConfig($oConfig)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function Init($sServer, $sUser, $sPwd, $sSource = '', $bTlsEnabled = false, $sTlsCA = null)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetMysqliInstance($sDbHost, $sUser, $sPwd, $sSource = '', $bTlsEnabled = false, $sTlsCa = null, $bCheckTlsAfterConnection = false)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function InitServerAndPort($sDbHost, &$sServer, &$iPort)
	{
		echo __METHOD__."\n";
	}

	public function SetCharacterSet($sCharset = DEFAULT_CHARACTER_SET, $sCollation = DEFAULT_COLLATION)
	{
		echo __METHOD__."\n";
	}

	public function SetTimezone($sTimezone = null)
	{
		echo __METHOD__."\n";
	}

	public function ListDB()
	{
		echo __METHOD__."\n";
	}

	public function IsDB($sSource)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetDBVersion()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetServerInfo()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetDBVendor()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function SelectDB($sSource)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function CreateDB($sSource)
	{
		echo __METHOD__."\n";
	}

	public function DropDB($sDBToDrop = '')
	{
		echo __METHOD__."\n";
	}

	public function CreateTable($sQuery)
	{
		echo __METHOD__."\n";
	}

	public function DropTable($sTable)
	{
		echo __METHOD__."\n";
	}

	public function CacheReset($sTable)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetMysqli()
	{
		echo __METHOD__."\n";
	}

	public function GetErrNo()
	{
		echo __METHOD__."\n";
	}

	public function GetError()
	{
		echo __METHOD__."\n";
	}

	public function DBHost()
	{
		echo __METHOD__."\n";
	}

	public function DBUser()
	{
		echo __METHOD__."\n";
	}

	public function DBPwd()
	{
		echo __METHOD__."\n";
	}

	public function DBName()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function Quote($value, $bAlways = false, $cQuoteStyle = "'")
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function Query($sSQLQuery)
	{
		echo __METHOD__.": $sSQLQuery\n";
	}

	/**
	 * @inheritDoc
	 */
	public function IsInsideTransaction()
	{
		echo __METHOD__."\n";
		return false;
	}

	public function IsDeadlockException(Exception $e)
	{
		echo __METHOD__."\n";
		return false;
	}

	public function GetInsertId()
	{
		echo __METHOD__."\n";
	}

	public function InsertInto($sSQLQuery)
	{
		echo __METHOD__."; $sSQLQuery\n";
		return rand(1, 99999);
	}

	/**
	 * @inheritDoc
	 */
	public function DeleteFrom($sSQLQuery)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function QueryToScalar($sSql, $iCol = 0, $oMysqli = null)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function QueryToArray($sSql, $iMode = MYSQLI_BOTH)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function QueryToCol($sSql, $col)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function ExplainQuery($sSql)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function TestQuery($sSql)
	{
		echo __METHOD__."\n";
	}

	public function NbRows($oResult)
	{
		echo __METHOD__."\n";
	}

	public function AffectedRows()
	{
		echo __METHOD__."\n";
	}

	public function FetchArray($oResult)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetColumns($oResult, $sSql)
	{
		echo __METHOD__."\n";
	}

	public function Seek($oResult, $iRow)
	{
		echo __METHOD__."\n";
	}

	public function FreeResult($oResult)
	{
		echo __METHOD__."\n";
	}

	public function IsTable($sTable)
	{
		echo __METHOD__."\n";
	}

	public function IsKey($sTable, $iKey)
	{
		echo __METHOD__."\n";
	}

	public function IsAutoIncrement($sTable, $sField)
	{
		echo __METHOD__."\n";
	}

	public function IsField($sTable, $sField)
	{
		echo __METHOD__."\n";
	}

	public function IsNullAllowed($sTable, $sField)
	{
		echo __METHOD__."\n";
	}

	public function GetFieldType($sTable, $sField)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function IsSameFieldTypes($sItopGeneratedFieldType, $sDbFieldType)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetFieldSpec($sTable, $sField)
	{
		echo __METHOD__."\n";
	}

	public function HasIndex($sTable, $sIndexId, $aFields = null, $aLength = null)
	{
		echo __METHOD__."\n";
	}

	public function GetTableFieldsList($sTable)
	{
		echo __METHOD__."\n";
	}

	public function GetTableInfo($sTable)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function DBCheckTableCharsetAndCollation($sTableName)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function DumpTable($sTable)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetServerVariable($sVarName)
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function GetRawPrivileges()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function IsSlaveServer()
	{
		echo __METHOD__."\n";
	}

	/**
	 * @inheritDoc
	 */
	public function DBCheckCharsetAndCollation()
	{
		echo __METHOD__."\n";
	}
}