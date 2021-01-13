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


use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
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

		$signatory = new NC21Signatory($keyId);
		$this->downloadSignatory($signatory, $keyId);

		return $signatory;
	}


	/**
	 * @param NC21Signatory $signatory
	 * @param string $keyId
	 * @param string $url
	 *
	 * @throws SignatoryException
	 */
	public function downloadSignatory(
		NC21Signatory $signatory,
		string $keyId = '',
		array $params = []
	): void {
		$request = new NC21Request('', Request::TYPE_GET);
		$request->basedOnUrl(($keyId !== '') ? $keyId : $signatory->getId());
		$request->setParams($params);
		$request->addHeader('Accept', 'application/ld+json');
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);

		try {
			$this->updateSignatory($signatory, $this->retrieveJson($request), $keyId);
		} catch (RequestNetworkException $e) {
			throw new SignatoryException('network issue - ' . $e->getMessage());
		}
	}


	/**
	 * @param NC21Signatory $signatory
	 * @param array $json
	 * @param string $keyId
	 *
	 * @throws SignatoryException
	 */
	public function updateSignatory(NC21Signatory $signatory, array $json, string $keyId = ''): void {
		$signatory->setOrigData($json)
				  ->import($json);

		if ($keyId === '') {
			$keyId = $signatory->getKeyId();
		}

		try {
			if (($signatory->getId() !== $keyId && $signatory->getKeyId() !== $keyId)
				|| $signatory->getId() !== $signatory->getKeyOwner()
				|| $this->getKeyOrigin($signatory->getKeyId()) !== $this->getKeyOrigin($signatory->getId())
				|| $signatory->getPublicKey() === '') {
				$this->debug('invalid format', ['signatory' => $signatory, 'keyId' => $keyId]);
				throw new SignatoryException('invalid format');
			}
		} catch (InvalidOriginException $e) {
			throw new SignatoryException('invalid origin');
		}
	}


	/**
	 * @param string $keyId
	 *
	 * @return string
	 * @throws InvalidOriginException
	 */
	public function getKeyOrigin(string $keyId) {
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
	 * @param string $clear
	 * @param NC21Signatory $signatory
	 *
	 * @return string
	 * @throws SignatoryException
	 */
	public function signString(string $clear, NC21Signatory $signatory): string {
		$privateKey = $signatory->getPrivateKey();
		if ($privateKey === '') {
			throw new SignatoryException('empty private key');
		}

		openssl_sign($clear, $signed, $privateKey, $this->getOpenSSLAlgo($signatory));

		return base64_encode($signed);
	}


	/**
	 * @param string $clear
	 * @param string $signed
	 * @param string $publicKey
	 * @param string $algo
	 *
	 * @throws SignatureException
	 */
	public function verifyString(
		string $clear, string $signed, string $publicKey, string $algo = NC21Signatory::SHA256
	) {
		if (openssl_verify($clear, $signed, $publicKey, $algo) !== 1) {
			throw new SignatureException('signature issue');
		}
	}

}

