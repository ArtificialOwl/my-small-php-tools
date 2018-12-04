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


namespace daita\MySmallPhpTools\Model;


use JsonSerializable;


/**
 * Class Request
 *
 * @package daita\MySmallPhpTools\Model
 */
class Request implements JsonSerializable {

	const TYPE_GET = 0;
	const TYPE_POST = 1;
	const TYPE_PUT = 2;
	const TYPE_DELETE = 3;

	/** @var string */
	private $address = '';

	/** @var string */
	private $url = '';

	/** @var int */
	private $type = 0;

	/** @var array */
	private $headers = [];

	/** @var array */
	private $data = [];

	/** @var int */
	private $timeout = 10;

	/** @var string */
	private $userAgent = '';


	/**
	 * Request constructor.
	 *
	 * @param string $url
	 * @param int $type
	 */
	public function __construct($url, $type = 0) {
		$this->url = $url;
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getAddress(): string {
		return $this->address;
	}

	/**
	 * @param string $address
	 *
	 * @return Request
	 */
	public function setAddress(string $address): Request {
		$this->address = $address;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getParsedUrl(): string {
		$url = $this->getUrl();
		$ak = array_keys($this->getData());
		foreach ($ak as $k) {
			if (!is_string($this->data[$k])) {
				continue;
			}

			$url = str_replace(':' . $k, $this->data[$k], $url);
		}

		return $url;
	}


	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}


	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}


	public function addHeader($header): Request {
		$this->headers[] = $header;

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
	 * @return Request
	 */
	public function setHeaders(array $headers): Request {
		$this->headers = $headers;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}


	/**
	 * @param array $data
	 *
	 * @return Request
	 */
	public function setData(array $data): Request {
		$this->data = $data;

		return $this;
	}


	/**
	 * @param string $data
	 *
	 * @return Request
	 */
	public function setDataJson(string $data): Request {
		$this->setData(json_decode($data, true));

		return $this;
	}


	/**
	 * @param JsonSerializable $data
	 *
	 * @return Request
	 */
	public function setDataSerialize(JsonSerializable $data): Request {
		$this->setDataJson(json_encode($data));

		return $this;
	}


	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return Request
	 */
	public function addData(string $k, string $v): Request {
		$this->data[$k] = $v;

		return $this;
	}


	/**
	 * @param string $k
	 * @param int $v
	 *
	 * @return Request
	 */
	public function addDataInt(string $k, int $v): Request {
		$this->data[$k] = $v;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getDataBody(): string {
		return json_encode($this->getData());
//		if ($this->getData() === []) {
//			return '';
//		}
//
//		return preg_replace(
//			'/([(%5B)]{1})[0-9]+([(%5D)]{1})/', '$1$2', http_build_query($this->getData())
//		);
	}

	/**
	 * @return string
	 */
	public function getUrlData(): string {
//		return json_encode($this->getData());
		if ($this->getData() === []) {
			return '';
		}

		return preg_replace(
			'/([(%5B)]{1})[0-9]+([(%5D)]{1})/', '$1$2', http_build_query($this->getData())
		);
	}


	/**
	 * @return int
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * @param int $timeout
	 *
	 * @return Request
	 */
	public function setTimeout(int $timeout): Request {
		$this->timeout = $timeout;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getUserAgent(): string {
		return $this->userAgent;
	}

	/**
	 * @param string $userAgent
	 *
	 * @return Request
	 */
	public function setUserAgent(string $userAgent): Request {
		$this->userAgent = $userAgent;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'host'    => $this->getAddress(),
			'url'     => $this->getUrl(),
			'timeout' => $this->getTimeout(),
			'type'    => $this->getType(),
			'data'    => $this->getData()
		];
	}
}
