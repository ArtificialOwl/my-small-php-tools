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


namespace ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc23;


use ArtificialOwl\MySmallPhpTools\Exceptions\JsonNotRequestedException;
use Exception;
use JsonSerializable;
use OC;
use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;


/**
 * Trait TNC23Controller
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc23
 */
trait TNC23Controller {


	use TNC23Logger;


	/**
	 * @param string $annotation
	 *
	 * @return bool
	 */
	public function hasAnnotation(string $annotation): bool {
		$refractor = OC::$server->get(ControllerMethodReflector::class);

		return $refractor->hasAnnotation($annotation);
	}


	/**
	 * @param array $data
	 * @param bool $status
	 * @param int $code
	 *
	 * @return DataResponse
	 */
	public function success(array $data = [], bool $status = true, int $code = Http::STATUS_OK
	): DataResponse {
		$this->debug('success', ['data' => $data]);

		if ($status) {
			$data = array_merge(['status' => 1], $data);
		}

		return new DataResponse(json_decode(json_encode($data), true), $code);
	}


	/**
	 * @param JsonSerializable $data
	 * @param int $status
	 *
	 * @return DataResponse
	 */
	public function successObj(JsonSerializable $data, int $status = Http::STATUS_OK): DataResponse {
		$this->debug('success', ['obj' => $data]);

		return new DataResponse(json_decode(json_encode($data), true), $status);
	}


	/**
	 * @param Exception $e
	 * @param array $data
	 * @param int $status
	 *
	 * @return DataResponse
	 */
	public function fail(Exception $e, array $data = [], int $status = Http::STATUS_NOT_FOUND): DataResponse {
		$this->e($e, ['data' => $data]);

		return new DataResponse(array_merge(['status' => -1, 'error' => $e->getMessage()], $data), $status);
	}


	/**
	 * use this one if a method from a Controller is only PublicPage when remote client asking for Json
	 *
	 * try {
	 *      $this->publicPageJsonLimited();
	 *      return new DataResponse(['test' => 42]);
	 * } catch (JsonNotRequestedException $e) {}
	 *
	 *
	 * @throws NotLoggedInException
	 * @throws JsonNotRequestedException
	 */
	public function publicPageJsonLimited(): void {
		if (!$this->jsonRequested()) {
			if (!OC::$server->get(IUserSession::class)
							->isLoggedIn()) {
				throw new NotLoggedInException();
			}

			throw new JsonNotRequestedException();
		}
	}


	/**
	 * @return bool
	 */
	public function jsonRequested(): bool {
		return ($this->areWithinAcceptHeader(
			[
				'application/json',
				'application/ld+json',
				'application/activity+json'
			]
		));
	}


	/**
	 * @param array $needles
	 *
	 * @return bool
	 */
	public function areWithinAcceptHeader(array $needles): bool {
		$request = OC::$server->get(IRequest::class);
		$accepts = array_map([$this, 'trimHeader'], explode(',', $request->getHeader('Accept')));

		foreach ($accepts as $accept) {
			if (in_array($accept, $needles)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param string $header
	 *
	 * @return string
	 */
	private function trimHeader(string $header): string {
		$header = trim($header);
		$pos = strpos($header, ';');
		if ($pos === false) {
			return $header;
		}

		return substr($header, 0, $pos);
	}

}

