<?php

declare(strict_types=1);


/**
 * Some tools for myself.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
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


namespace daita\MySmallPhpTools\Traits\Nextcloud\nc21;


use daita\MySmallPhpTools\Exceptions\InvalidItemException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\INC21Convert;


/**
 * Trait TNC21Convert
 *
 * @package daita\MySmallPhpTools\Traits\Nextcloud\nc21
 */
trait TNC21Convert {


	/**
	 * @param array $data
	 * @param string $class
	 *
	 * @return INC21Convert
	 * @throws InvalidItemException
	 */
	public function convert(array $data, string $class): INC21Convert {
		if ($class instanceof INC21Convert) {
			throw new InvalidItemException(get_class($class) . ' does not implement INC21Convert');
		}

		/** @var INC21Convert $item */
		$item = new $class;
		$item->import($data);

		return $item;
	}


	/**
	 * @param array $data
	 * @param string $class
	 *
	 * @return INC21Convert[]
	 * @throws InvalidItemException
	 */
	public function convertArray(array $data, string $class): array {
		$arr = [];
		foreach ($data as $entry) {
			$arr[] = $this->convert($entry, $class);
		}

		return $arr;
	}

}

