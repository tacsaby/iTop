<?php
/*
 * @copyright   Copyright (C) 2010-2022 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/*
 * this interface add a function witch allow import on object (like ormIPv6)
 * @since 3.1.0
 */

interface iOrmSet
{
	/**
	 * used for query and compare values in import
	 *
	 * @return string
	 */
	public function GetValueForQuery();
}
