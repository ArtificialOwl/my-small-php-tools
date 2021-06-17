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


namespace ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22;


use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use JsonSerializable;


/**
 * Class NC22InteractiveShellSession
 *
 * @package ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22
 */
class NC22InteractiveShellSession implements JsonSerializable {


	/** @var string */
	private $prompt = '%PATH%>';

	/** @var string */
	private $path = '';

	/** @var array */
	private $availableCommands = [];

	/** @var array */
	private $commands = [];

	/** @var array */
	private $globalCommands = [];

	/** @var SimpleDataStore */
	private $data;


	/**
	 * NC22InteractiveShellSession constructor.
	 */
	public function __construct() {
		$this->data = new SimpleDataStore();
	}


	/**
	 * @param string $prompt
	 */
	public function setPrompt(string $prompt): self {
		$this->prompt = $prompt;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPrompt(): string {
		return $this->prompt;
	}


	/**
	 * @param string $path
	 *
	 * @return NC22InteractiveShellSession
	 */
	public function setPath(string $path): self {
		$this->path = trim($path, '.');

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath(string $command = ''): string {
		if ($command === '') {
			return $this->path;
		}

		return trim($this->path . '.' . $command, '.');
	}

	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	public function addPath(string $path): self {
		$this->path .= '.' . $path;
		$this->path = trim($this->path, '.');

		return $this;
	}

	/**
	 * @return $this
	 */
	public function goParent(): self {
		$path = explode('.', $this->path);
		if (!empty($path)) {
			array_pop($path);
			$this->path = implode('.', $path);
		}

		return $this;
	}


	/**
	 * @param array $commands
	 */
	public function setCommands(array $commands): self {
		$this->commands = $commands;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getCommands(): array {
		return $this->commands;
	}


	/**
	 * @param array $commands
	 *
	 * @return $this
	 */
	public function setGlobalCommands(array $commands = []): self {
		$this->globalCommands = $commands;

		return $this;
	}


	/**
	 * @param array $availableCommands
	 *
	 * @return NC22InteractiveShellSession
	 */
	public function setAvailableCommands(array $availableCommands): self {
		$this->availableCommands = array_unique($availableCommands);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAvailableCommands(): array {
		return array_merge($this->availableCommands, $this->globalCommands);
	}


	/**
	 * @param string $command
	 *
	 * @return bool
	 */
	public function isCommandAvailable(array $commands, string $isAvailable): bool {
		foreach ($commands as $command) {
			if (strpos($command, $this->getPath($isAvailable)) === 0) {
				return true;
			}
		}

		return false;
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


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'path'              => $this->getPath(),
			'availableCommands' => $this->getAvailableCommands(),
			'data'              => $this->getData()
		];
	}

}

