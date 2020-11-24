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


namespace daita\MySmallPhpTools\Model\Nextcloud\nc20;


use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestResultNotJsonException;
use OCP\Http\Client\IResponse;


/**
 * Class NC20RequestResult
 *
 * @package daita\MySmallPhpTools\Model\Nextcloud\nc20
 */
class NC20RequestResult {


	const TYPE_STRING = 0;
	const TYPE_JSON = 1;
	const TYPE_BINARY = 2;


	/** @var int */
	private $statusCode = 0;

	/** @var array */
	private $headers = [];

	/** @var mixed */
	private $content;


	/**
	 * NC20RequestResult constructor.
	 *
	 * @param IResponse $response
	 * @param bool $binary
	 */
	public function __construct(IResponse $response, bool $binary = false) {
		$this->setStatusCode($response->getStatusCode());
		$this->setContent($response->getBody());
		$this->setHeaders($response->getHeaders());
	}


	/**
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * @param int $statusCode
	 *
	 * @return self
	 */
	public function setStatusCode(int $statusCode): self {
		$this->statusCode = $statusCode;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * @param array $headers
	 *
	 * @return self
	 */
	public function setHeaders(array $headers): self {
		$this->headers = $headers;

		return $this;
	}


	/**
	 * @param string $content
	 *
	 * @return self
	 */
	public function setContent(string $content): self {
		$this->content = $content;

		return $this;
	}


	/**
	 * @return string
	 * @throws RequestContentException
	 */
	public function getContent(): string {
		if (is_null($this->content) || !is_string($this->content)) {
			throw new RequestContentException();
		}

		return $this->content;
	}

	/**
	 * @return array
	 * @throws RequestResultNotJsonException
	 */
	public function getAsArray(): array {
		try {
			$arr = json_decode($this->getContent(), true);
		} catch (RequestContentException $e) {
			throw new RequestResultNotJsonException();
		}

		if (!is_array($arr)) {
			throw new RequestResultNotJsonException();
		}

		return $arr;
	}


	/**
	 * @return string
	 */
	public function getBinary() {
		return $this->content;
	}


	/**
	 * @return int
	 */
	public function getType(): int {
		try {
			$this->getContent();
		} catch (RequestContentException $e) {
			return self::TYPE_BINARY;
		}

		try {
			$this->getAsArray();

			return self::TYPE_JSON;
		} catch (RequestResultNotJsonException $e) {
		}

		return self::TYPE_STRING;
	}

}

