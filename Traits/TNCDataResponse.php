<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
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

namespace daita\MySmallPhpTools\Traits;


use JsonSerializable;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

trait TNCDataResponse {


	/**
	 * @param string $message
	 *
	 * @return DataResponse
	 */
	private function fail(string $message = ''): DataResponse {
		return new DataResponse(
			['status' => -1, 'message' => $message], Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}


	/**
	 * @param array $result
	 *
	 * @return DataResponse
	 */
	private function success(array $result): DataResponse {
		$data =
			[
				'result' => $result,
				'status' => 1
			];

		return new DataResponse($data, Http::STATUS_OK);
	}


	/**
	 * @param JsonSerializable $result
	 *
	 * @return DataResponse
	 */
	private function directSuccess(JsonSerializable $result): DataResponse {
		return new DataResponse($result, Http::STATUS_OK);
	}

}

