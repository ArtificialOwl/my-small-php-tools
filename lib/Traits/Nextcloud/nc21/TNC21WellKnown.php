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


use daita\MySmallPhpTools\Exceptions\RequestContentException;
use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\WellKnownLinkNotFoundException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21Webfinger;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21WellKnownLink;


/**
 * Trait TNC21Setup
 *
 * @package daita\MySmallPhpTools\Traits\Nextcloud\nc21
 */
trait TNC21WellKnown {


	static $WEBFINGER = '/.well-known/webfinger';


	use TNC21Request;


	/**
	 * @param string $host
	 * @param string $resource
	 * @param string $rel
	 *
	 * @return NC21Webfinger
	 * @throws RequestContentException
	 * @throws RequestNetworkException
	 */
	public function getWebfinger(string $host, string $resource, string $rel = '') {
		$request = new NC21Request(self::$WEBFINGER);
		$request->setHost($host);
		$request->setProtocols(['https', 'http']);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);

		$request->addParam('resource', $resource);
		if ($rel !== '') {
			$request->addParam('rel', $rel);
		}

		$result = $this->retrieveJson($request);

		return new NC21Webfinger($result);
	}


	/**
	 * @param string $rel
	 * @param NC21Webfinger $webfinger
	 *
	 * @return NC21WellKnownLink
	 * @throws WellKnownLinkNotFoundException
	 */
	public function extractLink(string $rel, NC21Webfinger $webfinger): NC21WellKnownLink {
		foreach ($webfinger->getLinks() as $link) {
			if ($link->getRel() === $rel) {
				return $link;
			}
		}

		throw new WellKnownLinkNotFoundException();
	}

}

