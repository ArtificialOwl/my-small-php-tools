<?php

declare(strict_types=1);


/**
 * Some tools for myself.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021, Maxence Lange <maxence@artificial-owl.com>
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


namespace ArtificialOwl\MySmallPhpTools\ActivityPub\Nextcloud\nc21;


use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidOriginException;
use ArtificialOwl\MySmallPhpTools\Exceptions\ItemNotFoundException;
use ArtificialOwl\MySmallPhpTools\Exceptions\MalformedArrayException;
use ArtificialOwl\MySmallPhpTools\Exceptions\SignatoryException;
use ArtificialOwl\MySmallPhpTools\Exceptions\SignatureException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21Signatory;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21SignedRequest;
use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Signatory;
use DateTime;
use Exception;
use OC;
use OCP\IRequest;


/**
 * Class NC21Signature
 *
 * @package ArtificialOwl\MySmallPhpTools\ActivityPub\Nextcloud\nc21
 */
class NC21Signature {


	const DATE_HEADER = 'D, d M Y H:i:s T';
	const DATE_OBJECT = 'Y-m-d\TH:i:s\Z';

	const DATE_TTL = 300;


	use TNC21Signatory;


	/** @var int */
	private $ttl = self::DATE_TTL;
	private $dateHeader = self::DATE_HEADER;


	/**
	 * @param string $host the local host
	 * @param string $body
	 *
	 * @return NC21SignedRequest
	 * @throws InvalidOriginException
	 * @throws MalformedArrayException
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	public function incomingSignedRequest(string $host, string $body = ''): NC21SignedRequest {
		if ($body === '') {
			$body = file_get_contents('php://input');
		}

		$this->debug('[<<] incoming', ['body' => $body]);

		$signedRequest = new NC21SignedRequest($body);
		$signedRequest->setIncomingRequest(OC::$server->get(IRequest::class));
		$signedRequest->setHost($host);

		$this->verifyIncomingRequestTime($signedRequest);
		$this->verifyIncomingRequestContent($signedRequest);
		$this->setIncomingSignatureHeader($signedRequest);
		$this->setIncomingClearSignature($signedRequest);
		$this->parseIncomingSignatureHeader($signedRequest);
		$this->verifyIncomingRequestSignature($signedRequest);

		return $signedRequest;
	}


	/**
	 * @param NC21Request $request
	 * @param NC21Signatory $signatory
	 *
	 * @return NC21SignedRequest
	 * @throws SignatoryException
	 */
	public function signOutgoingRequest(NC21Request $request, NC21Signatory $signatory): NC21SignedRequest {
		$signedRequest = new NC21SignedRequest($request->getDataBody());
		$signedRequest->setOutgoingRequest($request)
					  ->setSignatory($signatory);

		$this->setOutgoingSignatureHeader($signedRequest);
		$this->setOutgoingClearSignature($signedRequest);
		$this->setOutgoingSignedSignature($signedRequest);
		$this->signingOutgoingRequest($signedRequest);

		return $signedRequest;
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function verifyIncomingRequestTime(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getIncomingRequest();

		try {
			$dTime = new DateTime($request->getHeader('date'));
			$signedRequest->setTime($dTime->getTimestamp());
		} catch (Exception $e) {
			$this->e($e, ['header' => $request->getHeader('date')]);
			throw new SignatureException('datetime exception');
		}

		if ($signedRequest->getTime() < (time() - $this->ttl)) {
			throw new SignatureException('object is too old');
		}
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function verifyIncomingRequestContent(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getIncomingRequest();

		if (strlen($signedRequest->getBody()) !== (int)$request->getHeader('content-length')) {
			throw new SignatureException('issue with content-length');
		}

		if ($request->getHeader('digest') !== ''
			&& $signedRequest->getDigest() !== $request->getHeader('digest')) {
			throw new SignatureException('issue with digest');
		}
	}

	/**
	 * @param NC21SignedRequest $signedRequest
	 */
	private function setIncomingSignatureHeader(NC21SignedRequest $signedRequest): void {
		$sign = [];
		$request = $signedRequest->getIncomingRequest();
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

		$signedRequest->setSignatureHeader(new SimpleDataStore($sign));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function setIncomingClearSignature(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getIncomingRequest();
		$headers = explode(' ', $signedRequest->getSignatureHeader()->g('headers'));

		$enforceHeaders = array_merge(
			['content-length', 'date', 'host'],
			$this->setupArray('enforceSignatureHeaders')
		);
		if (!empty(array_diff($enforceHeaders, $headers))) {
			throw new Exception('missing elements in \'headers\'');
		}

		$target = strtolower($request->getMethod()) . " " . $request->getRequestUri();
		$estimated = ['(request-target): ' . $target];

		foreach ($headers as $key) {
			$value = $request->getHeader($key);
			if ($key === 'host') {
				$value = $signedRequest->getHost();
			}
			if ($value === '') {
				throw new SignatureException('empty elements in \'headers\'');
			}

			$estimated[] = $key . ': ' . $value;
		}
		$signedRequest->setClearSignature(implode("\n", $estimated));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws MalformedArrayException
	 * @throws InvalidOriginException
	 */
	private function parseIncomingSignatureHeader(NC21SignedRequest $signedRequest): void {
		$data = $signedRequest->getSignatureHeader();
		$data->hasKeys(['keyId', 'headers', 'signature'], true);

		$signedRequest->setOrigin($this->getKeyOrigin($data->g('keyId')));
		$signedRequest->setSignedSignature($data->g('signature'));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatoryException
	 * @throws SignatureException
	 */
	private function verifyIncomingRequestSignature(NC21SignedRequest $signedRequest) {
		$data = $signedRequest->getSignatureHeader();

		try {
			$signedRequest->setSignatory($this->retrieveSignatory($data->g('keyId'), false));
			$this->verifySignedRequest($signedRequest);
		} catch (SignatoryException $e) {
			$signedRequest->setSignatory($this->retrieveSignatory($data->g('keyId'), true));
			$this->verifySignedRequest($signedRequest);
		}
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatureException
	 */
	private function verifySignedRequest(NC21SignedRequest $signedRequest) {
		$publicKey = $signedRequest->getSignatory()->getPublicKey();
		if ($publicKey === '') {
			throw new SignatureException('empty public key');
		}

		try {
			$this->verifyString(
				$signedRequest->getClearSignature(),
				base64_decode($signedRequest->getSignedSignature()),
				$publicKey,
				$this->getUsedEncryption($signedRequest)
			);
		} catch (SignatureException $e) {
			$this->debug('signature issue', ['signed' => $signedRequest]);
			throw $e;
		}
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 */
	private function setOutgoingSignatureHeader(NC21SignedRequest $signedRequest): void {
		$request = $signedRequest->getOutgoingRequest();

		$data = new SimpleDataStore();
		$data->s('(request-target)', NC21Request::method($request->getType()) . ' ' . $request->getPath())
			 ->sInt('content-length', strlen($signedRequest->getBody()))
			 ->s('date', gmdate($this->dateHeader))
			 ->s('digest', $signedRequest->getDigest())
			 ->s('host', $request->getHost());

		$signedRequest->setSignatureHeader($data);
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 */
	private function setOutgoingClearSignature(NC21SignedRequest $signedRequest): void {
		$signing = [];
		$data = $signedRequest->getSignatureHeader();
		foreach ($data->keys() as $element) {
			try {
				$value = $data->gItem($element);
				$signing[] = $element . ': ' . $value;
				if ($element !== '(request-target)') {
					$signedRequest->getOutgoingRequest()->addHeader($element, $value);
				}
			} catch (ItemNotFoundException $e) {
			}
		}

		$signedRequest->setClearSignature(implode("\n", $signing));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @throws SignatoryException
	 */
	private function setOutgoingSignedSignature(NC21SignedRequest $signedRequest): void {
		$clear = $signedRequest->getClearSignature();
		$signed = $this->signString($clear, $signedRequest->getSignatory());
		$signedRequest->setSignedSignature($signed);
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @return void
	 */
	private function signingOutgoingRequest(NC21SignedRequest $signedRequest): void {
		$headers = array_diff($signedRequest->getSignatureHeader()->keys(), ['(request-target)']);
		$signatory = $signedRequest->getSignatory();
		$signatureElements = [
			'keyId="' . $signatory->getKeyId() . '"',
			'algorithm="' . $this->getChosenEncryption($signatory) . '"',
			'headers="' . implode(' ', $headers) . '"',
			'signature="' . $signedRequest->getSignedSignature() . '"'
		];

		$signedRequest->getOutgoingRequest()->addHeader('Signature', implode(',', $signatureElements));
	}


	/**
	 * @param NC21SignedRequest $signedRequest
	 *
	 * @return string
	 */
	private function getUsedEncryption(NC21SignedRequest $signedRequest): string {
		switch ($signedRequest->getSignatureHeader()->g('algorithm')) {
			case 'rsa-sha512':
				return NC21Signatory::SHA512;

			case 'rsa-sha256':
			default:
				return NC21Signatory::SHA256;
		}
	}

	/**
	 * @param NC21Signatory $signatory
	 *
	 * @return string
	 */
	private function getChosenEncryption(NC21Signatory $signatory): string {
		switch ($signatory->getAlgorithm()) {
			case NC21Signatory::SHA512:
				return 'ras-sha512';

			case NC21Signatory::SHA256:
			default:
				return 'ras-sha256';
		}
	}


	/**
	 * @param NC21Signatory $signatory
	 *
	 * @return int
	 */
	public function getOpenSSLAlgo(NC21Signatory $signatory): int {
		switch ($signatory->getAlgorithm()) {
			case NC21Signatory::SHA512:
				return OPENSSL_ALGO_SHA512;

			case NC21Signatory::SHA256:
			default:
				return OPENSSL_ALGO_SHA256;
		}
	}

}

