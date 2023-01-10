<?php
/*
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Core\CMDBSource;

use CMDBSource;
use Config;
use Exception;
use mysqli;
use mysqli_result;

/**
 * CMDBSource
 * database access wrapper
 *
 * @package     iTopORM
 */
class CMDBSourceService implements iCMDBSourceService
{
	/**
	 * SQL charset & collation declaration for text columns
	 *
	 * Using a function instead of a constant or attribute to avoid crash in the setup for older PHP versions (cannot
	 * use expression as value)
	 *
	 * @see https://dev.mysql.com/doc/refman/5.7/en/charset-column.html
	 * @since 2.5.1 N°1001 switch to utf8mb4
	 */
	public function GetSqlStringColumnDefinition(): string
	{
		return CMDBSource::GetSqlStringColumnDefinition();
	}

	/**
	 * @param Config $oConfig
	 *
	 * @throws \MySQLException
	 * @uses \CMDBSource::Init()
	 * @uses \CMDBSource::SetCharacterSet()
	 */
	public function InitFromConfig($oConfig)
	{
		CMDBSource::InitFromConfig($oConfig);
	}

	/**
	 * @param string $sServer
	 * @param string $sUser
	 * @param string $sPwd
	 * @param string $sSource database to use
	 * @param bool $bTlsEnabled
	 * @param string $sTlsCA
	 *
	 * @throws \MySQLException
	 */
	public function Init($sServer, $sUser, $sPwd, $sSource = '', $bTlsEnabled = false, $sTlsCA = null)
	{
		CMDBSource::Init($sServer, $sUser, $sPwd, $sSource, $bTlsEnabled, $sTlsCA);
	}

	/**
	 * @param string $sDbHost
	 * @param string $sUser
	 * @param string $sPwd
	 * @param string $sSource database to use
	 * @param bool $bTlsEnabled
	 * @param string $sTlsCa
	 * @param bool $bCheckTlsAfterConnection If true then verify after connection if it is encrypted
	 *
	 * @return \mysqli
	 * @throws \MySQLException
	 *
	 * @uses IsOpenedDbConnectionUsingTls when asking for a TLS connection, to check if it was really opened using TLS
	 */
	public function GetMysqliInstance($sDbHost, $sUser, $sPwd, $sSource = '', $bTlsEnabled = false, $sTlsCa = null, $bCheckTlsAfterConnection = false)
	{
		return CMDBSource::GetMysqliInstance($sDbHost, $sUser, $sPwd, $sSource, $bTlsEnabled, $sTlsCa, $bCheckTlsAfterConnection);
	}

	/**
	 * @param string $sDbHost initial value ("p:domain:port" syntax)
	 * @param string $sServer server variable to update
	 * @param int $iPort port variable to update
	 */
	public function InitServerAndPort($sDbHost, &$sServer, &$iPort)
	{
		CMDBSource::InitServerAndPort($sDbHost, $sServer, $iPort);
	}

	public function SetCharacterSet($sCharset = DEFAULT_CHARACTER_SET, $sCollation = DEFAULT_COLLATION)
	{
		CMDBSource::SetCharacterSet($sCharset, $sCollation);
	}

	public function SetTimezone($sTimezone = null)
	{
		CMDBSource::SetTimezone($sTimezone);
	}

	public function ListDB()
	{
		return CMDBSource::ListDB();
	}

	public function IsDB($sSource)
	{
		return CMDBSource::IsDB($sSource);
	}

	/**
	 * Get the version of the database server.
	 *
	 * @return string
	 * @throws \MySQLException
	 *
	 * @uses \CMDBSource::QueryToScalar() so needs a connection opened !
	 */
	public function GetDBVersion()
	{
		return CMDBSource::GetDBVersion();
	}

	/**
	 * @deprecated Use `CMDBSource::GetDBVersion` instead.
	 * @uses mysqli_get_server_info
	 */
	public function GetServerInfo()
	{
		return CMDBSource::GetServerInfo();
	}

	/**
	 * Get the DB vendor between MySQL and its main forks
	 *
	 * @return string
	 *
	 * @uses \CMDBSource::GetServerVariable() so needs a connection opened !
	 */
	public function GetDBVendor()
	{
		return CMDBSource::GetDBVendor();
	}

	/**
	 * @param string $sSource
	 *
	 * @throws \MySQLException
	 */
	public function SelectDB($sSource)
	{
		CMDBSource::SelectDB($sSource);
	}

	/**
	 * @param string $sSource
	 *
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 */
	public function CreateDB($sSource)
	{
		CMDBSource::CreateDB($sSource);
	}

	public function DropDB($sDBToDrop = '')
	{
		CMDBSource::DropDB($sDBToDrop);
	}

	public function CreateTable($sQuery)
	{
		CMDBSource::CreateTable($sQuery);
	}

	public function DropTable($sTable)
	{
		CMDBSource::DropTable($sTable);
	}

	public function CacheReset($sTable)
	{
		CMDBSource::CacheReset($sTable);
	}

	/**
	 * @return \mysqli
	 *
	 * @since 2.5.0 N°1260
	 */
	public function GetMysqli()
	{
		return CMDBSource::GetMysqli();
	}

	public function GetErrNo()
	{
		return CMDBSource::GetErrNo();
	}

	public function GetError()
	{
		return CMDBSource::GetError();
	}

	public function DBHost()
	{
		return CMDBSource::DBHost();
	}

	public function DBUser()
	{
		return CMDBSource::DBUser();
	}

	public function DBPwd()
	{
		return CMDBSource::DBPwd();
	}

	public function DBName()
	{
		return CMDBSource::DBName();
	}

	/**
	 * Quote variable and protect against SQL injection attacks
	 * Code found in the PHP documentation: quote_smart($value)
	 *
	 * @param mixed $value
	 * @param bool $bAlways should be set to true when the purpose is to create a IN clause,
	 *                      otherwise and if there is a mix of strings and numbers, the clause would always be false
	 * @param string $cQuoteStyle
	 *
	 * @return array|string
	 */
	public function Quote($value, $bAlways = false, $cQuoteStyle = "'")
	{
		return CMDBSource::Quote($value, $bAlways, $cQuoteStyle);
	}

	/**
	 * @param string $sSQLQuery
	 *
	 * @return \mysqli_result|null
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 * @throws \CoreException
	 *
	 * @since 2.7.0 N°679 handles nested transactions
	 */
	public function Query($sSQLQuery)
	{
		return CMDBSource::Query($sSQLQuery);
	}

	/**
	 * @api
	 * @see m_iTransactionLevel
	 * @return bool true if there is one transaction opened, false otherwise (not a single 'START TRANSACTION' sent)
	 * @since 2.7.0 N°679
	 */
	public function IsInsideTransaction()
	{
		return CMDBSource::IsInsideTransaction();
	}

	public function IsDeadlockException(Exception $e)
	{
		return CMDBSource::IsDeadlockException($e);
	}

	public function GetInsertId()
	{
		return CMDBSource::GetInsertId();
	}

	public function InsertInto($sSQLQuery)
	{
		return CMDBSource::InsertInto($sSQLQuery);
	}

	/**
	 * @param $sSQLQuery
	 *
	 * @throws \CoreException
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 */
	public function DeleteFrom($sSQLQuery)
	{
		CMDBSource::DeleteFrom($sSQLQuery);
	}

	/**
	 * @param string $sSql
	 * @param int $iCol beginning at 0
	 * @param mysqli $oMysqli if not null will query using this connection, otherwise will use {@see GetMySQLiForQuery}
	 *
	 * @return string corresponding cell content on the first line
	 * @throws \MySQLException
	 * @throws \MySQLQueryHasNoResultException
	 * @since 2.7.5-2 2.7.6 3.0.0 N°4215 new optional mysqli param
	 */
	public function QueryToScalar($sSql, $iCol = 0, $oMysqli = null)
	{
		return CMDBSource::QueryToScalar($sSql, $iCol, $oMysqli);
	}

	/**
	 * @param string $sSql
	 * @param int $iMode
	 *
	 * @return array
	 * @throws \MySQLException if query cannot be processed
	 */
	public function QueryToArray($sSql, $iMode = MYSQLI_BOTH)
	{
		return CMDBSource::QueryToArray($sSql, $iMode);
	}

	/**
	 * @param string $sSql
	 * @param int $col
	 *
	 * @return array
	 * @throws \MySQLException
	 */
	public function QueryToCol($sSql, $col)
	{
		return CMDBSource::QueryToCol($sSql, $col);
	}

	/**
	 * @param string $sSql
	 *
	 * @return array
	 * @throws \MySQLException if query cannot be processed
	 */
	public function ExplainQuery($sSql)
	{
		return CMDBSource::ExplainQuery($sSql);
	}

	/**
	 * @param string $sSql
	 *
	 * @return string
	 * @throws \MySQLException if query cannot be processed
	 */
	public function TestQuery($sSql)
	{
		return CMDBSource::TestQuery($sSql);
	}

	public function NbRows($oResult)
	{
		return CMDBSource::NbRows($oResult);
	}

	public function AffectedRows()
	{
		return CMDBSource::AffectedRows();
	}

	public function FetchArray($oResult)
	{
		return CMDBSource::FetchArray($oResult);
	}

	/**
	 * @param mysqli_result $oResult
	 * @param string $sSql
	 *
	 * @return string[]
	 * @throws \MySQLException
	 */
	public function GetColumns($oResult, $sSql)
	{
		return CMDBSource::GetColumns($oResult, $sSql);
	}

	public function Seek($oResult, $iRow)
	{
		return CMDBSource::Seek($oResult, $iRow);
	}

	public function FreeResult($oResult)
	{
		return CMDBSource::FreeResult($oResult);
	}

	public function IsTable($sTable)
	{
		return CMDBSource::IsTable($sTable);
	}

	public function IsKey($sTable, $iKey)
	{
		return CMDBSource::IsKey($sTable, $iKey);
	}

	public function IsAutoIncrement($sTable, $sField)
	{
		return CMDBSource::IsAutoIncrement($sTable, $sField);
	}

	public function IsField($sTable, $sField)
	{
		return CMDBSource::IsField($sTable, $sField);
	}

	public function IsNullAllowed($sTable, $sField)
	{
		return CMDBSource::IsNullAllowed($sTable, $sField);
	}

	public function GetFieldType($sTable, $sField)
	{
		return CMDBSource::GetFieldType($sTable, $sField);
	}

	/**
	 * There may have some differences between DB ! For example in :
	 *   * MySQL 5.7 we have `INT`
	 *   * MariaDB >= 10.2 you get `int DEFAULT 'NULL'`
	 *
	 * We still need to do a case sensitive comparison for enum values !
	 *
	 * A better solution would be to generate SQL field definitions ({@see GetFieldSpec} method) based on the DB used... But for
	 * now (N°2490 / SF #1756 / PR #91) we did implement this simpler solution
	 *
	 * @see GetFieldDataTypeAndOptions extracts all info from the SQL field definition
	 *
	 * @param string $sDbFieldType
	 *
	 * @param string $sItopGeneratedFieldType
	 *
	 * @return bool true if same type and options (case sensitive comparison only for type options), false otherwise
	 *
	 * @throws \CoreException
	 * @since 2.7.0 N°2490
	 */
	public function IsSameFieldTypes($sItopGeneratedFieldType, $sDbFieldType)
	{
		return CMDBSource::IsSameFieldTypes($sItopGeneratedFieldType, $sDbFieldType);
	}

	/**
	 * @see \AttributeDefinition::GetSQLColumns()
	 *
	 * @param string $sField
	 *
	 * @param string $sTable
	 *
	 * @return bool|string
	 */
	public function GetFieldSpec($sTable, $sField)
	{
		return CMDBSource::GetFieldSpec($sTable, $sField);
	}

	public function HasIndex($sTable, $sIndexId, $aFields = null, $aLength = null)
	{
		return CMDBSource::HasIndex($sTable, $sIndexId, $aFields, $aLength);
	}

	public function GetTableFieldsList($sTable)
	{
		return CMDBSource::GetTableFieldsList($sTable);
	}

	public function GetTableInfo($sTable)
	{
		return CMDBSource::GetTableInfo($sTable);
	}

	/**
	 * @see https://dev.mysql.com/doc/refman/5.7/en/charset-table.html
	 *
	 * @param string $sTableName
	 *
	 * @return string query to upgrade table charset and collation if needed, null if not
	 * @throws \MySQLException
	 *
	 * @since 2.5.0 N°1001 switch to utf8mb4
	 */
	public function DBCheckTableCharsetAndCollation($sTableName)
	{
		return CMDBSource::DBCheckTableCharsetAndCollation($sTableName);
	}

	/**
	 * @param string $sTable
	 *
	 * @return array
	 * @throws \MySQLException if query cannot be processed
	 */
	public function DumpTable($sTable)
	{
		return CMDBSource::DumpTable($sTable);
	}

	/**
	 * Returns the value of the specified server variable
	 *
	 * @param string $sVarName Name of the server variable
	 *
	 * @return mixed Current value of the variable
	 * @throws \MySQLQueryHasNoResultException|\MySQLException
	 */
	public function GetServerVariable($sVarName)
	{
		return CMDBSource::GetServerVariable($sVarName);
	}

	/**
	 * Returns the privileges of the current user
	 *
	 * @return string privileges in a raw format
	 */
	public function GetRawPrivileges()
	{
		return CMDBSource::GetRawPrivileges();
	}

	/**
	 * Determine the slave status of the server
	 *
	 * @return bool true if the server is slave
	 */
	public function IsSlaveServer()
	{
		return CMDBSource::IsSlaveServer();
	}

	/**
	 * @see https://dev.mysql.com/doc/refman/5.7/en/charset-database.html
	 * @return string query to upgrade database charset and collation if needed, null if not
	 * @throws \MySQLException
	 *
	 * @since 2.5.0 N°1001 switch to utf8mb4
	 */
	public function DBCheckCharsetAndCollation()
	{
		return CMDBSource::DBCheckCharsetAndCollation();
	}
}