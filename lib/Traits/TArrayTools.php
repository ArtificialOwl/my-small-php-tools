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


namespace daita\MySmallPhpTools\Traits;


use daita\MySmallPhpTools\Exceptions\ArrayNotFoundException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use Exception;

/**
 * Trait TArrayTools
 *
 * @package daita\MySmallPhpTools\Traits
 */
trait TArrayTools {


	/**
	 * @param string $k
	 * @param array $arr
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get(string $k, array $arr, string $default = ''): string {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				$r = $arr[$subs[0]];
				if (!is_array($r)) {
					return $default;
				}

				return $this->get($subs[1], $r, $default);
			} else {
				return $default;
			}
		}

		if ($arr[$k] === null || !is_string($arr[$k]) && (!is_int($arr[$k]))) {
			return $default;
		}

		return (string)$arr[$k];
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param int $default
	 *
	 * @return int
	 */
	protected function getInt(string $k, array $arr, int $default = 0): int {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				$r = $arr[$subs[0]];
				if (!is_array($r)) {
					return $default;
				}

				return $this->getInt($subs[1], $r, $default);
			} else {
				return $default;
			}
		}

		if ($arr[$k] === null) {
			return $default;
		}

		return intval($arr[$k]);
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param float $default
	 *
	 * @return float
	 */
	protected function getFloat(string $k, array $arr, float $default = 0): float {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				$r = $arr[$subs[0]];
				if (!is_array($r)) {
					return $default;
				}

				return $this->getFloat($subs[1], $r, $default);
			} else {
				return $default;
			}
		}

		if ($arr[$k] === null) {
			return $default;
		}

		return intval($arr[$k]);
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param bool $default
	 *
	 * @return bool
	 */
	protected function getBool(string $k, array $arr, bool $default = false): bool {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				return $this->getBool($subs[1], $arr[$subs[0]], $default);
			} else {
				return $default;
			}
		}

		if ($arr[$k] === null) {
			return $default;
		}

		if (is_bool($arr[$k])) {
			return $arr[$k];
		}

		if ($arr[$k] === '1') {
			return true;
		}

		if ($arr[$k] === '0') {
			return false;
		}

		return $default;
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param array $default
	 *
	 * @return array
	 */
	protected function getArray(string $k, array $arr, array $default = []): array {
		if ($arr === null) {
			return $default;
		}

		if (!array_key_exists($k, $arr)) {
			$subs = explode('.', $k, 2);
			if (sizeof($subs) > 1) {
				if (!array_key_exists($subs[0], $arr)) {
					return $default;
				}

				$r = $arr[$subs[0]];
				if (!is_array($r)) {
					return $default;
				}

				return $this->getArray($subs[1], $r, $default);
			} else {
				return $default;
			}
		}

		$r = $arr[$k];
		if ($r === null || (!is_array($r) && !is_string($r))) {
			return $default;
		}

		if (is_string($r)) {
			$r = json_decode($r, true);
		}

		if (!is_array($r)) {
			return $default;
		}

		return $r;
	}


	/**
	 * @param string $k
	 * @param array $arr
	 * @param array $import
	 * @param array $default
	 *
	 * @return array
	 */
	protected function getList(string $k, array $arr, array $import, array $default = []): array {
		$list = $this->getArray($k, $arr, $default);

		$r = [];
		list ($obj, $method) = $import;
		foreach ($list as $item) {
			try {
				$o = new $obj();
				$o->$method($item);

				$r[] = $o;
			} catch (Exception $e) {
			}
		}

		return $r;
	}


	/**
	 * @param string $k
	 * @param string $value
	 * @param array $list
	 *
	 * @return mixed
	 * @throws ArrayNotFoundException
	 */
	protected function extractArray(string $k, string $value, array $list) {
		foreach ($list as $arr) {
			if (!array_key_exists($k, $arr)) {
				continue;
			}

			if ($arr[$k] === $value) {
				return $arr;
			}
		}

		throw new ArrayNotFoundException();
	}


	/**
	 * @param array $keys
	 * @param array $arr
	 *
	 * @throws MalformedArrayException
	 */
	protected function mustContains(array $keys, array $arr) {
		foreach ($keys as $key) {
			if (!array_key_exists($key, $arr)) {
				throw new MalformedArrayException(
					'source: ' . json_encode($arr) . ' - missing key: ' . $key
				);
			}
		}
	}


	/**
	 * @param array $arr
	 */
	protected function cleanArray(array &$arr) {
		$arr = array_filter(
			$arr,
			function($v) {
				if (is_string($v)) {
					return ($v !== '');
				}
				if (is_array($v)) {
					return !empty($v);
				}

				return true;
			}
		);
	}

}

