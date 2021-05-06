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


namespace daita\MySmallPhpTools\Model\Nextcloud\nc22;


use daita\MySmallPhpTools\Model\SimpleDataStore;

/**
 * Class NC22InteractiveShellSession
 *
 * @package daita\MySmallPhpTools\Model\Nextcloud\nc22
 */
class NC22InteractiveShellSession {


	/** @var string */
	private $path = '';

	/** @var array */
	private $availableCommands = [];

	/** @var SimpleDataStore */
	private $data;


	/**
	 * NC22InteractiveShellSession constructor.
	 */
	public function __construct() {
	}


	/**
	 * @param string $path
	 *
	 * @return NC22InteractiveShellSession
	 */
	public function setPath(string $path): self {
		$this->path = $path;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}


	/**
	 * @param array $availableCommands
	 *
	 * @return NC22InteractiveShellSession
	 */
	public function setAvailableCommands(array $availableCommands): self {
		$this->availableCommands = $availableCommands;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAvailableCommands(): array {
		return $this->availableCommands;
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return NC22InteractiveShellSession
	 */
	public function setData(SimpleDataStore $data): self {
		$this->data = $data;

		return $this;
	}

	/**
	 * @return SimpleDataStore
	 */
	public function getData(): SimpleDataStore {
		return $this->data;
	}

}

