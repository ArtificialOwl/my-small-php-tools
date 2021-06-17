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


namespace ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc20;


use OCP\IConfig;

/**
 * Trait TNC20Setup
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc20
 */
trait TNC20Setup {


	/** @var array */
	private $_setup = [];


	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function setup(string $key, string $value = ''): string {
		if ($value !== '') {
			$this->_setup[$key] = $value;
		}

		if (array_key_exists($key, $this->_setup)) {
			return $this->_setup[$key];
		}

		return '';
	}


	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function appConfig(string $key): string {
		$app = $this->setup('app');
		if ($app === '') {
			return '';
		}

		/** @var IConfig $config */
		$config = \OC::$server->get(IConfig::class);

		return $config->getAppValue($app, $key, '');
	}

}

