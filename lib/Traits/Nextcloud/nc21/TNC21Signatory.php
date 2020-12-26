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


namespace daita\MySmallPhpTools\Traits\Nextcloud\nc21;


use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Request;


/**
 * Trait TNC21KeyPairs
 *
 * @package daita\MySmallPhpTools\Traits\Nextcloud\nc21
 */
trait TNC21Signatory {


	use TNC21Request;


	/**
	 * return Signatory by its Id from cache or from direct request.
	 * Should be overwritten.
	 *
	 * @param string $keyId
	 * @param bool $refresh
	 *
	 * @return NC21Signatory
	 * @throws SignatoryException
	 */
	public function retrieveSignatory(string $keyId, bool $refresh = false): NC21Signatory {
		if (!$refresh) {
			throw new SignatoryException();
		}

		$request = new NC21Request('', Request::TYPE_GET);
		$request->basedOnUrl($keyId);
		$request->setContentType('application/json');

		try {
			return $this->generateSignatoryFromJson($keyId, $this->retrieveJson($request));
		} catch (RequestNetworkException $e) {
			throw new SignatoryException('network issue - ' . $e->getMessage());
		}
	}


	/**
	 * @param $keyId
	 *
	 * @return string
	 * @throws InvalidOriginException
	 */
	public function getKeyOrigin($keyId) {
		$host = parse_url($keyId, PHP_URL_HOST);
		if (is_string($host) && ($host !== '')) {
			return $host;
		}

		throw new InvalidOriginException('cannot retrieve origin from ' . $keyId);
	}


	/**
	 * @param NC21Signatory $signatory
	 * @param string $digest
	 * @param int $bits
	 * @param int $type
	 */
	public function generateKeys(
		NC21Signatory $signatory,
		string $digest = 'rsa',
		int $bits = 2048,
		int $type = OPENSSL_KEYTYPE_RSA
	) {
		$res = openssl_pkey_new(
			[
				'digest_alg'       => $digest,
				'private_key_bits' => $bits,
				'private_key_type' => $type,
			]
		);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];

		$signatory->setPublicKey($publicKey);
		$signatory->setPrivateKey($privateKey);
	}


	/**
	 * @param string $keyId
	 * @param array $json
	 *
	 * @return NC21Signatory
	 * @throws SignatoryException
	 */
	private function generateSignatoryFromJson(string $keyId, array $json) {
		$signatory = new NC21Signatory();
		$signatory->setKeyId($this->get('publicKey.id', $json))
				  ->setId($this->get('publicKey.owner', $json))
				  ->setPublicKey($this->get('publicKey.publicKeyPem', $json));

		try {
			if ($signatory->getId() !== $keyId
				|| $this->getKeyOrigin($signatory->getKeyId()) !== $this->getKeyOrigin($signatory->getId())
				|| $signatory->getPublicKey() === '') {
				throw new SignatoryException('invalid format');
			}
		} catch (InvalidOriginException $e) {
			throw new SignatoryException('invalid origin');
		}

		return $signatory;
	}


}

