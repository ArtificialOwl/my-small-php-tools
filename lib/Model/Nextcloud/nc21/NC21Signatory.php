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


namespace daita\MySmallPhpTools\Db\Nextcloud\nc21;


use daita\MySmallPhpTools\Traits\TArrayTools;
use JsonSerializable;


/**
 * Class NC21Signatory
 *
 * @package daita\MySmallPhpTools\Db\Nextcloud\nc21
 */
class NC21Signatory implements JsonSerializable {


	use TArrayTools;


	/** @var string */
	protected $id = '';

	/** @var string */
	private $keyId = '';

	/** @var string */
	private $publicKey = '';

	/** @var string */
	private $privateKey = '';


	/**
	 * NC21Signatory constructor.
	 *
	 * @param string $id
	 */
	public function __construct(string $id = '') {
		$this->id = $id;
		$this->keyId = $this->id . '#main-key';
	}


	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return self
	 */
	public function setId(string $id): self {
		$this->id = $id;

		return $this;
	}


	/**
	 * @param string $keyId
	 *
	 * @return self
	 */
	public function setKeyId(string $keyId): self {
		$this->keyId = $keyId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getKeyId(): string {
		return $this->keyId;
	}


	/**
	 * @param string $publicKey
	 *
	 * @return self
	 */
	public function setPublicKey(string $publicKey): self {
		$this->publicKey = $publicKey;

		return $this;
	}

	/**
	 * @param string $privateKey
	 *
	 * @return self
	 */
	public function setPrivateKey(string $privateKey): self {
		$this->privateKey = $privateKey;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPublicKey(): string {
		return $this->publicKey;
	}

	/**
	 * @return string
	 */
	public function getPrivateKey(): string {
		return $this->privateKey;
	}

	/**
	 * @return bool
	 */
	public function hasPublicKey(): bool {
		return ($this->publicKey !== '');
	}

	/**
	 * @return bool
	 */
	public function hasPrivateKey(): bool {
		return ($this->privateKey !== '');
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'publicKey' =>
				[
					'id'           => $this->getKeyId(),
					'owner'        => $this->getId(),
					'publicKeyPem' => $this->getPublicKey()
				]
		];
	}

}

