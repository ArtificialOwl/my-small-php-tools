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


use ArtificialOwl\MySmallPhpTools\Exceptions\RequestNetworkException;
use ArtificialOwl\MySmallPhpTools\Exceptions\WellKnownLinkNotFoundException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc23\NC23Request;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc23\NC23Webfinger;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc23\NC23WellKnownLink;
use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;


/**
 * Trait TNC23WellKnown
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc23
 */
trait TNC23WellKnown {


	static $WEBFINGER = '/.well-known/webfinger';


	use TNC23Request;


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

		return $this->getResourceFromLink($link);
	}


	/**
	 * @param NC23WellKnownLink $link
	 *
	 * @return SimpleDataStore
	 * @throws RequestNetworkException
	 */
	public function getResourceFromLink(NC23WellKnownLink $link): SimpleDataStore {
		$request = new NC23Request('');
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
	 * @return NC23WellKnownLink
	 * @throws RequestNetworkException
	 * @throws WellKnownLinkNotFoundException
	 */
	public function getLink(string $host, string $subject, string $rel): NC23WellKnownLink {
		return $this->extractLink($rel, $this->getWebfinger($host, $subject));
	}


	/**
	 * @param string $host
	 * @param string $subject
	 * @param string $rel
	 *
	 * @return NC23Webfinger
	 * @throws RequestNetworkException
	 */
	public function getWebfinger(string $host, string $subject, string $rel = ''): NC23Webfinger {
		$request = new NC23Request(self::$WEBFINGER);
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

		return new NC23Webfinger($result);
	}


	/**
	 * @param string $rel
	 * @param NC23Webfinger $webfinger
	 *
	 * @return NC23WellKnownLink
	 * @throws WellKnownLinkNotFoundException
	 */
	public function extractLink(string $rel, NC23Webfinger $webfinger): NC23WellKnownLink {
		foreach ($webfinger->getLinks() as $link) {
			if ($link->getRel() === $rel) {
				return $link;
			}
		}

		throw new WellKnownLinkNotFoundException();
	}

}

