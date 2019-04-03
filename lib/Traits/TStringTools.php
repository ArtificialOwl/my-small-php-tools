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


/**
 * Trait TStringTools
 *
 * @package daita\MySmallPhpTools\Traits
 */
trait TStringTools {


	/**
	 * Generate uuid: 2b5a7a87-8db1-445f-a17b-405790f91c80
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	protected function uuid(int $length = 0): string {
		$uuid = sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff), mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);


		if ($length > 0) {
			$uuid = substr($uuid, 0, $length);
		}

		return $uuid;
	}


	/**
	 * @param string $str1
	 * @param string $str2
	 * @param bool $cs case sensitive ?
	 *
	 * @return string
	 */
	protected function commonPart(string $str1, string $str2, bool $cs = true): string {
		for ($i = 0; $i < strlen($str1) && $i < strlen($str2); $i++) {
			$chr1 = $str1[$i];
			$chr2 = $str2[$i];

			if (!$cs) {
				$chr1 = strtolower($chr1);
				$chr2 = strtolower($chr2);
			}

			if ($chr1 !== $chr2) {
				break;
			}
		}

		return substr($str1, 0, $i);
	}

}

