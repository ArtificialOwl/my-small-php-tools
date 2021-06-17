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


namespace ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc21;


use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc21\NC21TreeNode;
use Symfony\Component\Console\Output\ConsoleOutput;


/**
 * Trait TNC21ConsoleTree
 *
 * @package ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc21
 */
trait TNC21ConsoleTree {


	/**
	 * @param NC21TreeNode $root
	 * @param callable $method
	 * @param array $config
	 */
	public function drawTree(
		NC21TreeNode $root,
		callable $method,
		array $config = [
			'height'       => 1,
			'node-spacing' => 0,
			'item-spacing' => 0,
		]
	): void {
		$config = array_merge(
			[
				'height'       => 1,
				'node-spacing' => 0,
				'item-spacing' => 0
			], $config
		);

		$output = new ConsoleOutput();

		while (true) {
			$node = $root->current();
			if ($node === null) {
				return;
			}

			$path = $node->getPath();
			array_pop($path);

			$line = $empty = '';
			foreach ($path as $k => $i) {
				$line .= ' ';
				$empty .= ' ';
				if ($k === array_key_last($path)) {
					if ($i->haveNext()) {
						$line .= '├';
						$empty .= '│';
					} else {
						$line .= '└';
						$empty .= ' ';
					}
					$line .= '── ';
					$empty .= '   ';
				} else {
					if ($i->haveNext()) {
						$line .= '│';
						$empty .= '│';
					} else {
						$line .= ' ';
						$empty .= ' ';
					}
					$line .= '   ';
					$empty .= '   ';
				}
			}

			for ($i = 1; $i <= $config['height']; $i++) {
				$draw = $method($node->getItem(), $i);
				if ($draw === '') {
					continue;
				}
				if ($i === 1) {
					$output->write($line);
				} else {
					$output->write($empty);
				}
				$output->writeln($draw);
			}

			if ($node->haveNext()) {
				$empty .= ' │';
			}

			if (!$node->isSplited() && $node->haveNext()) {
				for ($i = 0; $i < $config['node-spacing']; $i++) {
					$output->writeln($empty);
				}
			}

			for ($i = 0; $i < $config['item-spacing']; $i++) {
				$output->writeln($empty);
			}
		}
	}

}

