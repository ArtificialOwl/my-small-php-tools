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


namespace ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc21;


use ArtificialOwl\MySmallPhpTools\Exceptions\RequestNetworkException;
use ArtificialOwl\MySmallPhpTools\Exceptions\WellKnownLinkNotFoundException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21Request;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21Webfinger;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21WellKnownLink;
use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;


/**
 * Trait TNC21WellKnown
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc21
 */
trait TNC21WellKnown {


	static $WEBFINGER = '/.well-known/webfinger';


	use TNC21Request;


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return SimpleDataStore
	 * @throws RequestNetworkException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function getResourceData(string $host, string $subject, string $rel): SimpleDataStore {
		$link = $this->getLink($host, $subject, $rel);

		$request = new NC21Request('');
		$request->basedOnUrl($link->getHref());
		$request->addHeader('Accept', $link->getType());
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);
		$data = $this->retrieveJson($request);

		return new SimpleDataStore($data);
	}


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return NC21WellKnownLink
	 * @throws RequestNetworkException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function getLink(string $host, string $subject, string $rel): NC21WellKnownLink {
		return $this->extractLink($rel, $this->getWebfinger($host, $subject));
	}


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return NC21Webfinger
	 * @throws RequestNetworkException
	 */
	public function getWebfinger(string $host, string $subject, string $rel = ''): NC21Webfinger {
		$request = new NC21Request(self::$WEBFINGER);
		$request->setHost($host);
		$request->setProtocols(['https', 'http']);
		$request->setFollowLocation(true);
		$request->setLocalAddressAllowed(true);
		$request->setTimeout(5);

		$request->addParam('resource', $subject);
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

