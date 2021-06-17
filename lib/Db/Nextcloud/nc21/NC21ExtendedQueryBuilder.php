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


namespace ArtificialOwl\MySmallPhpTools\Db\Nextcloud\nc21;


use ArtificialOwl\MySmallPhpTools\Exceptions\DateTimeException;
use ArtificialOwl\MySmallPhpTools\Exceptions\InvalidItemException;
use ArtificialOwl\MySmallPhpTools\Exceptions\RowNotFoundException;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Exception;
use OC;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;


/**
 * Class NC21ExtendedQueryBuilder
 *
 * @package ArtificialOwl\MySmallPhpTools\Db\Nextcloud\nc21
 */
class NC21ExtendedQueryBuilder extends QueryBuilder {


	/** @var string */
	private $defaultSelectAlias = '';


	/**
	 * NC21ExtendedQueryBuilder constructor.
	 */
	public function __construct() {
		parent::__construct(
			OC::$server->get(IDBConnection::class),
			OC::$server->get(SystemConfig::class),
			OC::$server->get(ILogger::class)
		);
	}


	/**
	 * @param string $alias
	 *
	 * @return self
	 */
	public function setDefaultSelectAlias(string $alias): self {
		$this->defaultSelectAlias = $alias;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultSelectAlias(): string {
		return $this->defaultSelectAlias;
	}


	/**
	 * Limit the request to the Id
	 *
	 * @param int $id
	 */
	public function limitToId(int $id): void {
		$this->limitToDBFieldInt('id', $id);
	}

	/**
	 * @param array $ids
	 */
	public function limitToIds(array $ids): void {
		$this->limitToDBFieldArray('id', $ids);
	}

	/**
	 * @param string $id
	 */
	public function limitToIdString(string $id): void {
		$this->limitToDBField('id', $id, true);
	}

	/**
	 * @param string $userId
	 */
	public function limitToUserId(string $userId): void {
		$this->limitToDBField('user_id', $userId, true);
	}

	/**
	 * @param string $uniqueId
	 */
	public function limitToUniqueId(string $uniqueId): void {
		$this->limitToDBField('unique_id', $uniqueId, true);
	}

	/**
	 * @param string $memberId
	 */
	public function limitToMemberId(string $memberId): void {
		$this->limitToDBField('member_id', $memberId, true);
	}

	/**
	 * @param string $status
	 */
	public function limitToStatus(string $status): void {
		$this->limitToDBField('status', $status, false);
	}

	/**
	 * @param int $type
	 */
	public function limitToType(int $type): void {
		$this->limitToDBFieldInt('type', $type);
	}

	/**
	 * @param string $type
	 */
	public function limitToTypeString(string $type): void {
		$this->limitToDBField('type', $type, false);
	}

	/**
	 * @param string $token
	 */
	public function limitToToken(string $token): void {
		$this->limitToDBField('token', $token, true);
	}


	/**
	 * Limit the request to the creation
	 *
	 * @param int $delay
	 *
	 * @return self
	 * @throws Exception
	 */
	public function limitToCreation(int $delay = 0): self {
		$date = new DateTime('now');
		$date->sub(new DateInterval('PT' . $delay . 'M'));

		$this->limitToDBFieldDateTime('creation', $date, true);

		return $this;
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function limitToDBField(string $field, string $value, bool $cs = true, string $alias = ''): void {
		$expr = $this->exprLimitToDBField($field, $value, true, $cs, $alias);

		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function filterDBField(string $field, string $value, bool $cs = true, string $alias = ''): void {
		$expr = $this->exprLimitToDBField($field, $value, false, $cs, $alias);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $eq
	 * @param bool $cs
	 * @param string $alias
	 *
	 * @return string
	 */
	public function exprLimitToDBField(
		string $field, string $value, bool $eq = true, bool $cs = true, string $alias = ''
	): string {
		$expr = $this->expr();

		$pf = '';
		if ($this->getType() === DBALQueryBuilder::SELECT) {
			$pf = (($alias === '') ? $this->getDefaultSelectAlias() : $alias) . '.';
		}
		$field = $pf . $field;

		$comp = 'eq';
		if ($eq === false) {
			$comp = 'neq';
		}

		if ($cs) {
			return $expr->$comp($field, $this->createNamedParameter($value));
		} else {
			$func = $this->func();

			return $expr->$comp(
				$func->lower($field), $func->lower($this->createNamedParameter($value))
			);
		}
	}


	/**
	 * @param string $field
	 * @param array $values
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function limitToDBFieldArray(string $field, array $values, bool $cs = true, string $alias = ''
	): void {
		$expr = $this->exprLimitToDBFieldArray($field, $values, true, $cs, $alias);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function filterDBFieldArray(string $field, string $value, bool $cs = true, string $alias = ''
	): void {
		$expr = $this->exprLimitToDBField($field, $value, false, $cs, $alias);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param array $values
	 * @param bool $eq
	 * @param bool $cs
	 * @param string $alias
	 *
	 * @return ICompositeExpression
	 */
	public function exprLimitToDBFieldArray(
		string $field, array $values, bool $eq = true, bool $cs = true, string $alias = ''
	): ICompositeExpression {
		$expr = $this->expr();

		$pf = '';
		if ($this->getType() === DBALQueryBuilder::SELECT) {
			$pf = (($alias === '') ? $this->getDefaultSelectAlias() : $alias) . '.';
		}
		$field = $pf . $field;

		$func = $this->func();
		if ($eq === false) {
			$comp = 'neq';
			$junc = $expr->andX();
		} else {
			$comp = 'eq';
			$junc = $expr->orX();
		}

		foreach ($values as $value) {
			if ($cs) {
				$junc->add($expr->$comp($field, $this->createNamedParameter($value)));
			} else {
				$junc->add(
					$expr->$comp(
						$func->lower($field), $func->lower($this->createNamedParameter($value))
					)
				);
			}
		}

		return $junc;
	}


	/**
	 * @param string $field
	 * @param int $value
	 * @param string $alias
	 */
	public function limitToDBFieldInt(string $field, int $value, string $alias = ''): void {
		$expr = $this->exprLimitToDBFieldInt($field, $value, $alias, true);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param int $value
	 * @param string $alias
	 */
	public function filterDBFieldInt(string $field, int $value, string $alias = ''): void {
		$expr = $this->exprLimitToDBFieldInt($field, $value, $alias, false);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param int $value
	 * @param string $alias
	 * @param bool $eq
	 *
	 * @return string
	 */
	public function exprLimitToDBFieldInt(string $field, int $value, string $alias = '', bool $eq = true
	): string {
		$expr = $this->expr();

		$pf = '';
		if ($this->getType() === DBALQueryBuilder::SELECT) {
			$pf = (($alias === '') ? $this->getDefaultSelectAlias() : $alias) . '.';
		}
		$field = $pf . $field;

		$comp = 'eq';
		if ($eq === false) {
			$comp = 'neq';
		}

		return $expr->$comp($field, $this->createNamedParameter($value, IQueryBuilder::PARAM_INT));
	}


	/**
	 * @param string $field
	 */
	public function limitToDBFieldEmpty(string $field): void {
		$expr = $this->expr();
		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
																. '.' : '';
		$field = $pf . $field;

		$this->andWhere($expr->eq($field, $this->createNamedParameter('')));
	}


	/**
	 * @param string $field
	 */
	public function filterDBFieldEmpty(string $field): void {
		$expr = $this->expr();
		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
																. '.' : '';
		$field = $pf . $field;

		$this->andWhere($expr->neq($field, $this->createNamedParameter('')));
	}


	/**
	 * @param string $field
	 * @param DateTime $date
	 * @param bool $orNull
	 */
	public function limitToDBFieldDateTime(string $field, DateTime $date, bool $orNull = false): void {
		$expr = $this->expr();
		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
																. '.' : '';
		$field = $pf . $field;

		$orX = $expr->orX();
		$orX->add(
			$expr->lte($field, $this->createNamedParameter($date, IQueryBuilder::PARAM_DATE))
		);

		if ($orNull === true) {
			$orX->add($expr->isNull($field));
		}

		$this->andWhere($orX);
	}


	/**
	 * @param int $timestamp
	 * @param string $field
	 *
	 * @throws DateTimeException
	 */
	public function limitToSince(int $timestamp, string $field): void {
		try {
			$dTime = new DateTime();
			$dTime->setTimestamp($timestamp);
		} catch (Exception $e) {
			throw new DateTimeException($e->getMessage());
		}

		$expr = $this->expr();
		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias() . '.' : '';
		$field = $pf . $field;

		$orX = $expr->orX();
		$orX->add(
			$expr->gte($field, $this->createNamedParameter($dTime, IQueryBuilder::PARAM_DATE))
		);

		$this->andWhere($orX);
	}


	/**
	 * @param string $field
	 * @param string $value
	 */
	public function searchInDBField(string $field, string $value): void {
		$expr = $this->expr();

		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias() . '.' : '';
		$field = $pf . $field;

		$this->andWhere($expr->iLike($field, $this->createNamedParameter($value)));
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string $fieldRight
	 * @param string $alias
	 *
	 * @return string
	 */
	public function exprFieldWithinJsonFormat(
		IQueryBuilder $qb, string $field, string $fieldRight, string $alias = ''
	): string {
		$func = $qb->func();
		$expr = $qb->expr();

		if ($alias === '') {
			$alias = $this->defaultSelectAlias;
		}

		$concat = $func->concat(
			$qb->createNamedParameter('%"'),
			$func->concat($fieldRight, $qb->createNamedParameter('"%'))
		);

		return $expr->iLike($alias . '.' . $field, $concat);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string $value
	 * @param bool $eq (eq, not eq)
	 * @param bool $cs (case sensitive, or not)
	 *
	 * @return string
	 */
	public function exprValueWithinJsonFormat(
		IQueryBuilder $qb, string $field, string $value, bool $eq = true, bool $cs = true
	): string {
		$dbConn = $this->getConnection();
		$expr = $qb->expr();
		$func = $qb->func();

		$value = $dbConn->escapeLikeParameter($value);
		if ($cs) {
			$field = $func->lower($field);
			$value = $func->lower($value);
		}

		$comp = 'iLike';
		if ($eq) {
			$comp = 'notLike';
		}

		return $expr->$comp($field, $qb->createNamedParameter('%"' . $value . '"%'));
	}


	/**
	 * @param string $object
	 * @param array $params
	 *
	 * @return INC21QueryRow
	 * @throws RowNotFoundException
	 */
	public function asItem(string $object, array $params = []): INC21QueryRow {
		return $this->getRow([$this, 'parseSimpleSelectSql'], $object, $params);
	}

	/**
	 * @param string $object
	 * @param array $params
	 *
	 * @return INC21QueryRow[]
	 */
	public function asItems(string $object, array $params = []): array {
		return $this->getRows([$this, 'parseSimpleSelectSql'], $object, $params);
	}


	/**
	 * @param array $data
	 * @param NC21ExtendedQueryBuilder $qb
	 * @param string $object
	 * @param array $params
	 *
	 * @return INC21QueryRow
	 * @throws InvalidItemException
	 */
	private function parseSimpleSelectSql(
		array $data,
		NC21ExtendedQueryBuilder $qb,
		string $object,
		array $params
	): INC21QueryRow {
		$item = new $object();
		if (!($item instanceof INC21QueryRow)) {
			throw new InvalidItemException();
		}

		if (!empty($params)) {
			$data['_params'] = $params;
		}

		$item->importFromDatabase($data);

		return $item;
	}


	/**
	 * @param callable $method
	 * @param string $object
	 * @param array $params
	 *
	 * @return INC21QueryRow
	 * @throws RowNotFoundException
	 */
	public function getRow(callable $method, string $object = '', array $params = []): INC21QueryRow {
		$cursor = $this->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new RowNotFoundException();
		}

		return $method($data, $this, $object, $params);
	}


	/**
	 * @param callable $method
	 * @param string $object
	 * @param array $params
	 *
	 * @return INC21QueryRow[]
	 */
	public function getRows(callable $method, string $object = '', array $params = []): array {
		$rows = [];
		$cursor = $this->execute();
		while ($data = $cursor->fetch()) {
			try {
				$rows[] = $method($data, $this, $object, $params);
			} catch (Exception $e) {
			}
		}
		$cursor->closeCursor();

		return $rows;
	}


	/**
	 * @param array $fields
	 * @param string $alias
	 * @param string $prefix
	 * @param array $default
	 *
	 * @return $this
	 */
	public function generateSelectAlias(
		array $fields,
		string $alias,
		string $prefix,
		array $default = []
	): self {

		foreach ($fields as $field) {
			if (array_key_exists($field, $default)) {
				$left = $this->createFunction(
					'COALESCE(' . $alias . '.' . $field
					. ', ' . $this->createNamedParameter($default[$field]) . ')'
				);
			} else {
				$left = $alias . '.' . $field;
			}

			$this->selectAlias($left, $prefix . $field);
		}

		return $this;
	}

}

