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


use ArtificialOwl\MySmallPhpTools\Exceptions\SignatoryException;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc23\NC23Signatory;
use OC;
use OCP\IConfig;


/**
 * Trait TNC23LocalSignatory
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc23
 */
trait TNC23LocalSignatory {


	use TNC23Signatory;

	static $SIGNATORIES_APP = 'signatories';


	/**
	 * @param NC23Signatory $signatory
	 * @param bool $generate
	 *
	 * @throws SignatoryException
	 */
	public function fillSimpleSignatory(NC23Signatory $signatory, bool $generate = false): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatories = json_decode(OC::$server->get(IConfig::class)->getAppValue($app, 'key_pairs'), true);
		if (!is_array($signatories)) {
			$signatories = [];
		}

		$sign = $this->getArray($signatory->getId(), $signatories);
		if (!empty($sign)) {
			$signatory->setKeyId($this->get('keyId', $sign))
					  ->setKeyOwner($this->get('keyOwner', $sign))
					  ->setPublicKey($this->get('publicKey', $sign))
					  ->setPrivateKey($this->get('privateKey', $sign));

			return;
		}

		if (!$generate) {
			throw new SignatoryException('signatory not found');
		}

		$this->createSimpleSignatory($signatory);
	}


	/**
	 * @param NC23Signatory $signatory
	 */
	public function createSimpleSignatory(NC23Signatory $signatory): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatory->setKeyId($signatory->getId() . '#main-key');
		$signatory->setKeyOwner($signatory->getId());
		$this->generateKeys($signatory);

		$signatories =
			json_decode(OC::$server->get(IConfig::class)->getAppValue($app, 'key_pairs', '[]'), true);
		$signatories[$signatory->getId()] = [
			'keyId'      => $signatory->getKeyId(),
			'keyOwner'   => $signatory->getKeyOwner(),
			'publicKey'  => $signatory->getPublicKey(),
			'privateKey' => $signatory->getPrivateKey()
		];

		OC::$server->get(IConfig::class)->setAppValue($app, 'key_pairs', json_encode($signatories));
	}


	/**
	 * @param NC23Signatory $signatory
	 */
	public function removeSimpleSignatory(NC23Signatory $signatory): void {
		$app = $this->setup('app', '', self::$SIGNATORIES_APP);
		$signatories = json_decode(OC::$server->get(IConfig::class)->getAppValue($app, 'key_pairs'), true);
		if (!is_array($signatories)) {
			$signatories = [];
		}

		unset($signatories[$signatory->getId()]);
		OC::$server->get(IConfig::class)->setAppValue($app, 'key_pairs', json_encode($signatories));
	}

}

