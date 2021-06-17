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


namespace ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22;


use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;


/**
 * Class NC22TreeNode
 *
 * @package ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22
 */
class NC22TreeNode {


	/** @var NC22TreeNode[] */
	private $children = [];

	/** @var NC22TreeNode */
	private $parent;

	/** @var SimpleDataStore */
	private $item;


	/** @var NC22TreeNode */
	private $currentChild;

	/** @var bool */
	private $displayed = false;

	/** @var bool */
	private $splited = false;


	/**
	 * NC22TreeNode constructor.
	 *
	 * @param NC22TreeNode|null $parent
	 * @param SimpleDataStore $item
	 */
	public function __construct(?NC22TreeNode $parent, SimpleDataStore $item) {
		$this->parent = $parent;
		$this->item = $item;

		if ($this->parent !== null) {
			$this->parent->addChild($this);
		}
	}

	/**
	 * @return bool
	 */
	public function isRoot(): bool {
		return (is_null($this->parent));
	}


	/**
	 * @param array $children
	 *
	 * @return NC22TreeNode
	 */
	public function setChildren(array $children): self {
		$this->children = $children;

		return $this;
	}

	/**
	 * @param NC22TreeNode $child
	 *
	 * @return $this
	 */
	public function addChild(NC22TreeNode $child): self {
		$this->children[] = $child;

		return $this;
	}


	/**
	 * @return SimpleDataStore
	 */
	public function getItem(): SimpleDataStore {
		$this->displayed = true;

		return $this->item;
	}


	/**
	 * @return NC22TreeNode
	 */
	public function getParent(): NC22TreeNode {
		return $this->parent;
	}


	/**
	 * @return $this
	 */
	public function getRoot(): NC22TreeNode {
		if ($this->isRoot()) {
			return $this;
		}

		return $this->getParent()->getRoot();
	}


	/**
	 * @return NC22TreeNode[]
	 */
	public function getPath(): array {
		if ($this->isRoot()) {
			return [$this];
		}

		return array_merge($this->parent->getPath(), [$this]);
	}


	/**
	 * @return int
	 */
	public function getLevel(): int {
		if ($this->isRoot()) {
			return 0;
		}

		return $this->getParent()->getLevel() + 1;
	}


	/**
	 * @return NC22TreeNode|null
	 */
	public function current(): ?NC22TreeNode {
		if (!$this->isDisplayed()) {
			return $this;
		}

		$this->splited = true;
		if ($this->initCurrentChild()) {
			$next = $this->getCurrentChild()->current();
			if (!is_null($next)) {
				return $next;
			}
		}

		if (!$this->haveNext()) {
			return null;
		}

		return $this->next();
	}


	/**
	 * @return NC22TreeNode
	 */
	private function next(): NC22TreeNode {
		$this->currentChild = array_shift($this->children);

		return $this->currentChild;
	}

	/**
	 * @return bool
	 */
	public function haveNext(): bool {
		return !empty($this->children);
	}


	/**
	 * @return bool
	 */
	private function initCurrentChild(): bool {
		if (is_null($this->currentChild)) {
			if (!$this->haveNext()) {
				return false;
			}
			$this->next();
		}

		return true;
	}

	/**
	 * @return NC22TreeNode|null
	 */
	private function getCurrentChild(): ?NC22TreeNode {
		return $this->currentChild;
	}

	/**
	 * @return bool
	 */
	private function isDisplayed(): bool {
		return $this->displayed;
	}

	/**
	 * @return bool
	 */
	public function isSplited(): bool {
		return $this->splited;
	}

}

