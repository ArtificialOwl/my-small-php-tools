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


namespace daita\MySmallPhpTools\ActivityPub\Nextcloud\nc21;


use daita\MySmallPhpTools\Db\Nextcloud\nc21\NC21Signatory;
use daita\MySmallPhpTools\Exceptions\InvalidOriginException;
use daita\MySmallPhpTools\Exceptions\MalformedArrayException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Exceptions\SignatureException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Request;
use daita\MySmallPhpTools\Traits\TArrayTools;
use DateTime;
use Exception;
use OC;
use OCP\IRequest;


/**
 * Class NC21Signature
 *
 * @package daita\MySmallPhpTools\ActivityPub\Nextcloud\nc21
 */
class NC21Signature {


	use TNC21Request;
	use TArrayTools;


	/** @var int */
	private $ttl = 300;


	/**
	 * @return int
	 */
	public function getTtl(): int {
		return $this->ttl;
	}

	/**
	 * @param int $ttl
	 */
	public function setTtl(int $ttl): void {
		$this->ttl = $ttl;
	}


	/**
	 * @param string $host the local host
	 *
	 * @return NC21SignedRequest
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatureException
	 * @throws SignatoryException
	 */
	public function incomingSignedRequest(string $host): NC21SignedRequest {
		$body = file_get_contents('php://input');
		$this->debug('[<<] incoming', ['body' => $body]);

		$signedRequest = new NC21SignedRequest($body);
		$signedRequest->setRequest(OC::$server->get(IRequest::class));
		$signedRequest->setHost($host);

		$this->checkRequestTime($signedRequest);
		$this->checkRequestSignature($signedRequest);

		$keyId = $signedRequest->getData()->g('keyId');
		try {
			$signedRequest->setSignatory($this->retrieveSignatory($keyId));
			$this->checkSignedRequest($signedRequest);
		} catch (SignatoryException $e) {
			$signedRequest->setSignatory($this->retrieveSignatory($keyId, true));
			$this->checkSignedRequest($signedRequest);
		}

		return $signedRequest;
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function checkRequestTime(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getRequest();

		try {
			$dTime = new DateTime($request->getHeader('date'));
			$signedRequest->setTime($dTime->getTimestamp());
		} catch (Exception $e) {
			$this->e($e, ['header' => $request->getHeader('date')]);
			throw new SignatureException('datetime exception');
		}

		if ($signedRequest->getTime() < (time() - $this->getTTL())) {
			throw new SignatureException('object is too old');
		}
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws MalformedArrayException
	 * @throws InvalidOriginException
	 */
	private function checkRequestSignature(NC21SignedRequest $signedRequest): void {
		$this->setDataFromHeaderSignature($signedRequest);
		$this->setSignatureFromDataHeaders($signedRequest);

		$data = $signedRequest->getData();
		$data->haveKeys(['keyId', 'headers', 'signature'], true);

		$signedRequest->setOrigin($this->getKeyOrigin($data->g('keyId')));
		$signedRequest->setSigned(base64_decode($data->g('signature')));


		// TODO: check digest
		//		$body = $signedRequest->getBody(); -- needed to check digest
		//	$this->generateDigest($body);

	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 */
	private function setDataFromHeaderSignature(NC21SignedRequest $signedRequest): void {
		$sign = [];
		$request = $signedRequest->getRequest();
		foreach (explode(',', $request->getHeader('Signature')) as $entry) {
			if ($entry === '' || !strpos($entry, '=')) {
				continue;
			}

			list($k, $v) = explode('=', $entry, 2);
			preg_match('/"([^"]+)"/', $v, $varr);
			if ($varr[0] !== null) {
				$v = trim($varr[0], '"');
			}
			$sign[$k] = $v;
		}

		$signedRequest->setData(new SimpleDataStore($sign));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 */
	private function setSignatureFromDataHeaders(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getRequest();
		$target = strtolower($request->getMethod()) . " " . $request->getRequestUri();

		$estimated = '';
		foreach (explode(' ', $signedRequest->getData()->g('headers')) as $key) {
			if ($key === '(request-target)') {
				$estimated .= "(request-target): " . $target . "\n";
				continue;
			}

			$value = $request->getHeader($key);
			if ($key === 'host') {
				$value = $signedRequest->getHost();
			}

			$estimated .= $key . ': ' . $value . "\n";
		}

		$signedRequest->setSignature(trim($estimated, "\n"));
	}


	/**
	 * @param $keyId
	 *
	 * @return string
	 * @throws InvalidOriginException
	 */
	private function getKeyOrigin($keyId) {
		$host = parse_url($keyId, PHP_URL_HOST);
		if (is_string($host) && ($host !== '')) {
			return $host;
		}

		throw new InvalidOriginException('cannot retrieve origin from ' . $keyId);
	}


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

		try {
			return $this->generateSignatoryFromJson($keyId, $this->retrieveJson($request));
		} catch (RequestNetworkException $e) {
			throw new SignatoryException('network issue - ' . $e->getMessage());
		}
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
		$signatory->setId($this->get('publicKey.id', $json))
				  ->setOwner($this->get('publicKey.owner', $json))
				  ->setPublicKey($this->get('publicKey.publicKeyPem', $json));

		try {
			if ($signatory->getId() !== $keyId
				|| $this->getKeyOrigin($signatory->getOWner()) !== $this->getKeyOrigin($signatory->getId())
				|| $signatory->getPublicKey() === '') {
				throw new SignatoryException('invalid format');
			}
		} catch (InvalidOriginException $e) {
			throw new SignatoryException('invalid origin');
		}

		return $signatory;
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function checkSignedRequest(NC21SignedRequest $signedRequest) {
		$algorithm = $this->getAlgorithm($signedRequest);
		$publicKey = $signedRequest->getSignatory()->getPublicKey();
		if ($publicKey === '') {
			throw new SignatureException('empty public key');
		}

		if (openssl_verify(
				$signedRequest->getSignature(),
				$signedRequest->getSigned(),
				$publicKey,
				$algorithm
			) !== 1) {
			$this->debug('signature issue', ['signed' => $signedRequest, 'algorithm' => $algorithm]);
			throw new SignatureException('signature issue');
		}
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @return string
	 */
	private function getAlgorithm(NC21SignedRequest $signedRequest): string {
		switch ($signedRequest->getData()->g('algorithm')) {
			case 'rsa-sha512':
				return 'sha512';

			case 'rsa-sha256':
			default:
				return 'sha256';
		}
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

}

