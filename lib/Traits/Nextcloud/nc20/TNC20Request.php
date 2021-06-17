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


namespace ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc20;


use ArtificialOwl\MySmallPhpTools\Exceptions\RequestContentException;
use ArtificialOwl\MySmallPhpTools\Exceptions\RequestNetworkException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc20\NC20Request;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc20\NC20RequestResult;
use ArtificialOwl\MySmallPhpTools\Model\Request;
use Exception;
use OC;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;


/**
 * Trait TNC20Request
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc20
 */
trait TNC20Request {


	use TNC20Logger;


	/**
	 * @param int $size
	 */
	public function setMaxDownloadSize(int $size) {
	}


	/**
	 * @param NC20Request $request
	 *
	 * @return array
	 * @throws RequestNetworkException
	 * @throws RequestContentException
	 */
	public function retrieveJson(NC20Request $request): array {
		$this->doRequest($request);
		$requestResult = $request->getResult();

		return $requestResult->getAsArray();
	}


	/**
	 * @param NC20Request $request
	 *
	 * @return string
	 * @throws RequestNetworkException
	 */
	public function doRequest(NC20Request $request) {
		$request->setClient(
			$this->clientService()
				 ->newClient()
		);

		$this->generationClientOptions($request);

		$this->debug('doRequest initiated', ['request' => $request]);

		foreach ($request->getProtocols() as $protocol) {
			$request->setUsedProtocol($protocol);

			try {
				$response = $this->useClient($request);
				$request->setResult(new NC20RequestResult($response));
				break;
			} catch (Exception $e) {
				$this->exception($e, self::$INFO, ['request' => $request]);
			}
		}

		$this->debug('doRequest done', ['request' => $request]);

		if (!$request->hasResult()) {
			throw new RequestNetworkException();
		}

		// deprecated, should not returns anything
		return $request->getResult()
					   ->getContent();
	}


	/**
	 * @return IClientService
	 */
	public function clientService(): IClientService {
		if (isset($this->clientService) && $this->clientService instanceof IClientService) {
			return $this->clientService;
		} else {
			return OC::$server->get(IClientService::class);
		}
	}


	/**
	 * @param NC20Request $request
	 */
	private function generationClientOptions(NC20Request $request) {
		$options = [
			'headers' => $request->getHeaders(),
			'cookies' => $request->getCookies(),
			'verify'  => $request->isVerifyPeer(),
			'timeout'     => $request->getTimeout()
		];

		if (!empty($request->getData())) {
			$options['body'] = $request->getDataBody();
		}

		if (!empty($request->getParams())) {
			$options['form_params'] = $request->getParams();
		}

		if ($request->isLocalAddressAllowed()) {
			$options['nextcloud']['allow_local_address'] = true;
		}

		if ($request->isFollowLocation()) {
			$options['allow_redirects'] = [
				'max'     => 10,
				'strict'  => true,
				'referer' => true,
			];
		} else {
			$options['allow_redirects'] = false;
		}

		$request->setClientOptions($options);
	}


	/**
	 * @param NC20Request $request
	 *
	 * @return IResponse
	 * @throws Exception
	 */
	private function useClient(NC20Request $request): IResponse {
		$client = $request->getClient();

		switch ($request->getType()) {
			case Request::TYPE_POST:
				return $client->post($request->getCompleteUrl(), $request->getClientOptions());
			case Request::TYPE_PUT:
				return $client->put($request->getCompleteUrl(), $request->getClientOptions());
			case Request::TYPE_DELETE:
				return $client->delete($request->getCompleteUrl(), $request->getClientOptions());
			case Request::TYPE_GET:
				return $client->get(
					$request->getCompleteUrl() . '?' . $request->getUrlParams(), $request->getClientOptions()
				);
			default:
				throw new Exception('unknown request type ' . json_encode($request));
		}
	}

}

