<?php declare(strict_types=1);


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


namespace daita\MySmallPhpTools\Model;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class SimpleDataStore
 *
 * @package daita\MySmallPhpTools\Model
 */
class SimpleDataStore {


	use TArrayTools;


	/** @var array */
	private $data = [];


	/**
	 * SimpleDataStore constructor.
	 *
	 * @param array $data
	 */
	public function __construct(array $data = []) {
		$this->data = $data;
	}


	/**
	 * @param string $key
	 * @param string $value
	 */
	public function s(string $key, string $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function g(string $key): string {
		return $this->get($key, $this->data);
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function a(string $key, string $value): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [];
		}

		$this->data[$key][] = $value;
	}


	/**
	 * @param string $key
	 * @param int $value
	 */
	public function sInt(string $key, int $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 */
	public function gInt(string $key): int {
		return $this->getInt($key, $this->data);
	}

	/**
	 * @param string $key
	 * @param int $value
	 */
	public function aInt(string $key, int $value): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [];
		}

		$this->data[$key][] = $value;
	}


	/**
	 * @param string $key
	 * @param bool $value
	 */
	public function sBool(string $key, bool $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function gBool(string $key): bool {
		return $this->getBool($key, $this->data);
	}

	/**
	 * @param string $key
	 * @param bool $value
	 */
	public function aBool(string $key, bool $value): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [];
		}

		$this->data[$key][] = $value;
	}


	/**
	 * @param string $key
	 * @param array $values
	 */
	public function sArray(string $key, array $values): void {
		$this->data[$key] = $values;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function gArray(string $key): array {
		return $this->getArray($key, $this->data);
	}

	/**
	 * @param string $key
	 * @param array $values
	 */
	public function aArray(string $key, array $values): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [];
		}

		$this->data[$key] = array_merge($this->data[$key], $values);
	}


	/**
	 * @param string $key
	 * @param JsonSerializable $value
	 */
	public function sObj(string $key, JsonSerializable $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function gObj(string $key): JsonSerializable {
		return $this->getObj($key, $this->data);
	}

	/**
	 * @param string $key
	 * @param bool $value
	 */
	public function aObj(string $key, JsonSerializable $value): void {
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = [];
		}

		$this->data[$key][] = $value;
	}


	/**
	 * @return array
	 */
	public function gAll(): array {
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function sAll(array $data): void {
		$this->data = $data;
	}

}

