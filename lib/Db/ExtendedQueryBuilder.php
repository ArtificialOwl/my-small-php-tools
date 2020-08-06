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


namespace daita\MySmallPhpTools\Db;


use daita\MySmallPhpTools\Exceptions\DateTimeException;
use daita\MySmallPhpTools\Exceptions\RowNotFoundException;
use daita\MySmallPhpTools\IExtendedQueryBuilder;
use daita\MySmallPhpTools\IQueryRow;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Exception;
use OC\DB\QueryBuilder\QueryBuilder;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;


/**
 * Class ExtendedQueryBuilder
 *
 * @package daita\MySmallPhpTools\Db
 */
class ExtendedQueryBuilder extends QueryBuilder implements IExtendedQueryBuilder {


	/** @var string */
	private $defaultSelectAlias = '';


	/**
	 * @param string $alias
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function setDefaultSelectAlias(string $alias): IExtendedQueryBuilder {
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
	 *
	 * @return ExtendedQueryBuilder
	 */
	public function limitToId(int $id): IExtendedQueryBuilder {
		$this->limitToDBFieldInt('id', $id);

		return $this;
	}


	/**
	 * @param array $ids
	 *
	 * @return IExtendedQueryBuilder
	 */
	public function limitToIds(array $ids): IExtendedQueryBuilder {
		$this->limitToDBFieldArray('id', $ids);

		return $this;
	}


	/**
	 * Limit the request to the Id (string)
	 *
	 * @param string $id
	 *
	 * @return ExtendedQueryBuilder
	 */
	public function limitToIdString(string $id): IExtendedQueryBuilder {
		$this->limitToDBField('id', $id, false);

		return $this;
	}


	/**
	 * Limit the request to the UserId
	 *
	 * @param string $userId
	 *
	 * @return ExtendedQueryBuilder
	 */
	public function limitToUserId(string $userId): IExtendedQueryBuilder {
		$this->limitToDBField('user_id', $userId, false);

		return $this;
	}


	/**
	 * Limit the request to the creation
	 *
	 * @param int $delay
	 *
	 * @return ExtendedQueryBuilder
	 * @throws Exception
	 */
	public function limitToCreation(int $delay = 0): IExtendedQueryBuilder {
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
	public function limitToDBField(string $field, string $value, bool $cs = true, string $alias = '') {
		$expr = $this->exprLimitToDBField($field, $value, true, $cs, $alias);

		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function filterDBField(string $field, string $value, bool $cs = true, string $alias = ''
	) {
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
	public function limitToDBFieldArray(
		string $field, array $values, bool $cs = true, string $alias = ''
	) {
		$expr = $this->exprLimitToDBFieldArray($field, $values, true, $cs, $alias);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param string $value
	 * @param bool $cs - case sensitive
	 * @param string $alias
	 */
	public function filterDBFieldArray(
		string $field, string $value, bool $cs = true, string $alias = ''
	) {
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
	public function limitToDBFieldInt(string $field, int $value, string $alias = '') {
		$expr = $this->exprLimitToDBFieldInt($field, $value, $alias, true);
		$this->andWhere($expr);
	}


	/**
	 * @param string $field
	 * @param int $value
	 * @param string $alias
	 */
	public function filterDBFieldInt(string $field, int $value, string $alias = '') {
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
	public function limitToDBFieldEmpty(string $field) {
		$expr = $this->expr();
		$pf =
			($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
															  . '.' : '';
		$field = $pf . $field;

		$this->andWhere($expr->eq($field, $this->createNamedParameter('')));
	}


	/**
	 * @param string $field
	 */
	public function filterDBFieldEmpty(string $field) {
		$expr = $this->expr();
		$pf =
			($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
															  . '.' : '';
		$field = $pf . $field;

		$this->andWhere($expr->neq($field, $this->createNamedParameter('')));
	}


	/**
	 * @param string $field
	 * @param DateTime $date
	 * @param bool $orNull
	 */
	public function limitToDBFieldDateTime(string $field, DateTime $date, bool $orNull = false) {
		$expr = $this->expr();
		$pf =
			($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
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
	public function limitToSince(int $timestamp, string $field) {
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
	public function searchInDBField(string $field, string $value) {
		$expr = $this->expr();

		$pf = ($this->getType() === DBALQueryBuilder::SELECT) ? $this->getDefaultSelectAlias()
																. '.' : '';
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
	) {
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

//
//	/**
//	 * @param IQueryBuilder $qb
//	 * @param string $field
//	 * @param string $value
//	 *
//	 * @return string
//	 */
//	public function exprValueNotWithinJsonFormat(IQueryBuilder $qb, string $field, string $value): string {
//		$dbConn = $this->getConnection();
//		$expr = $qb->expr();
//		$func = $qb->func();
//
//
//		return $expr->notLike(
//			$func->lower($field),
//			$qb->createNamedParameter(
//				'%"' . $func->lower($dbConn->escapeLikeParameter($value)) . '"%'
//			)
//		);
//	}


	/**
	 * @param callable $method
	 *
	 * @return IQueryRow
	 * @throws RowNotFoundException
	 */
	public function getRow(callable $method): IQueryRow {
		$cursor = $this->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new RowNotFoundException();
		}

		return $method($data, $this);
	}

	/**
	 * @param callable $method
	 *
	 * @return IQueryRow[]
	 */
	public function getRows(callable $method): array {
		$rows = [];
		$cursor = $this->execute();
		while ($data = $cursor->fetch()) {
			try {
				$rows[] = $method($data, $this);
			} catch (Exception $e) {
			}
		}
		$cursor->closeCursor();

		return $rows;
	}

}

