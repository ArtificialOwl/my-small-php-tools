<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
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

namespace daita\MySmallPhpTools\Model;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class CacheItem
 *
 * @package daita\MySmallPhpTools\Model
 */
class CacheItem {


	/** @var string */
	private $url = '';

	/** @var string */
	private $content = '';

	/** @var bool */
	private $cached = false;

	/** @var int */
	private $creation = 0;


	/**
	 * CacheItem constructor.
	 *
	 * @param string $url
	 */
	public function __construct(string $url) {
		$this->url = $url;
	}


	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * @param string $url
	 *
	 * @return CacheItem
	 */
	public function setUrl(string $url): CacheItem {
		$this->url = $url;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * @param string $content
	 *
	 * @return CacheItem
	 */
	public function setContent(string $content): CacheItem {
		$this->content = $content;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isCached(): bool {
		return $this->cached;
	}

	/**
	 * @param bool $cached
	 *
	 * @return CacheItem
	 */
	public function setCached(bool $cached): CacheItem {
		$this->cached = $cached;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * @param int $creation
	 *
	 * @return CacheItem
	 */
	public function setCreation(int $creation): CacheItem {
		$this->creation = $creation;

		return $this;
	}

}

