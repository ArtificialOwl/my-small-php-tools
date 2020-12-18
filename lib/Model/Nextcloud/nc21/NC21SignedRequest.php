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


namespace daita\MySmallPhpTools\Model\Nextcloud\nc21;


use daita\MySmallPhpTools\Db\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use JsonSerializable;
use OCP\IRequest;


/**
 * Class NC21SignedRequest
 *
 * @package daita\MySmallPhpTools\Model\Nextcloud\nc21
 */
class NC21SignedRequest implements JsonSerializable {


	/** @var string */
	private $body;

	/** @var int */
	private $time = 0;

	/** @var IRequest */
	private $request;

	/** @var string */
	private $origin;

	/** @var SimpleDataStore */
	private $data;

	/** @var string */
	private $host = '';

	/** @var string */
	private $signature = '';

	/** @var string */
	private $signed = '';

	/** @var NC21Signatory */
	private $signatory;

	/**
	 * NC21SignedRequest constructor.
	 *
	 * @param string $body
	 */
	public function __construct(string $body = '') {
		$this->body = $body;
	}


	/**
	 * @return IRequest
	 */
	public function getRequest(): IRequest {
		return $this->request;
	}

	/**
	 * @param IRequest $request
	 *
	 * @return NC21SignedRequest
	 */
	public function setRequest(IRequest $request): self {
		$this->request = $request;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getBody(): string {
		return $this->body;
	}

	/**
	 * @param string $body
	 *
	 * @return self
	 */
	public function setBody(string $body): self {
		$this->body = $body;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTime(): int {
		return $this->time;
	}

	/**
	 * @param int $time
	 *
	 * @return self
	 */
	public function setTime(int $time): self {
		$this->time = $time;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOrigin(): string {
		return $this->origin;
	}

	/**
	 * @param string $origin
	 *
	 * @return self
	 */
	public function setOrigin(string $origin): self {
		$this->origin = $origin;

		return $this;
	}


	/**
	 * @return SimpleDataStore
	 */
	public function getData(): SimpleDataStore {
		return $this->data;
	}

	/**
	 * @param SimpleDataStore $data
	 *
	 * @return self
	 */
	public function setData(SimpleDataStore $data): self {
		$this->data = $data;

		return $this;
	}

	/**
	 * @param string $signature
	 *
	 * @return NC21SignedRequest
	 */
	public function setSignature(string $signature): self {
		$this->signature = $signature;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSignature(): string {
		return $this->signature;
	}


	/**
	 * @param string $signed
	 *
	 * @return self
	 */
	public function setSigned(string $signed): self {
		$this->signed = $signed;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSigned(): string {
		return $this->signed;
	}


	/**
	 * @param string $host
	 *
	 * @return NC21SignedRequest
	 */
	public function setHost(string $host): self {
		$this->host = $host;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHost(): string {
		return $this->host;
	}


	/**
	 * @param NC21Signatory $signatory
	 */
	public function setSignatory(NC21Signatory $signatory): void {
		$this->signatory = $signatory;
	}

	/**
	 * @return NC21Signatory
	 */
	public function getSignatory(): NC21Signatory {
		return $this->signatory;
	}

	/**
	 * @return bool
	 */
	public function hasSignatory(): bool {
		return ($this->signatory !== null);
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [];
	}

}

