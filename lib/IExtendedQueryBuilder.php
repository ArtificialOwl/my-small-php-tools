<?php
declare(strict_types=1);


/**
 * Some tools for myself.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace daita\MySmallPhpTools;


use Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;


/**
 * Interface IExtendedQueryBuilder
 *
 * @package daita\MySmallPhpTools
 */
interface IExtendedQueryBuilder extends IQueryBuilder {


	/**
	 * @param string $alias
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function setDefaultSelectAlias(string $alias): IExtendedQueryBuilder;


	/**
	 * @return string
	 */
	public function getDefaultSelectAlias(): string;


	/**
	 * Limit the request to the Id
	 *
	 * @param int $id
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function limitToId(int $id): IExtendedQueryBuilder;


	/**
	 * Limit the request to the Id (string)
	 *
	 * @param string $id
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function limitToIdString(string $id): IExtendedQueryBuilder;


	/**
	 * Limit the request to the UserId
	 *
	 * @param string $userId
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function limitToUserId(string $userId): IExtendedQueryBuilder;


	/**
	 * Limit the request to the creation
	 *
	 * @param int $delay
	 *
	 * @return IExtendedQueryBuilder
	 * @throws Exception
	 */
	public function limitToCreation(int $delay = 0): IExtendedQueryBuilder;

}

