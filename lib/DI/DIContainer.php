<?php
declare(strict_types=1);


/**
 * Some tools for myself.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * Mostly copied from Nextcloud: lib/private/AppFramework/Utility/SimpleContainer.php
 *
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


namespace ArtificialOwl\MySmallPhpTools\DI;


use Closure;
use ArtificialOwl\MySmallPhpTools\Exceptions\DependencyInjectionException;
use Pimple\Container;
use ReflectionClass;
use ReflectionException;
use stdClass;


/**
 * Class DIContainer
 *
 * @require composer package "pimple/pimple"
 *
 * @package ArtificialOwl\MySmallPhpTools\DI
 */
class DIContainer extends Container {


	/**
	 * @param string $service
	 * @param bool $shared
	 *
	 * @throws DependencyInjectionException
	 */
	public function registerService(string $service, bool $shared = true) {
		$object = $this->resolve($service);
		$this->registerClass(
			$service, function() use ($object) {
			return $object;
		}, $shared
		);
	}


	/**
	 * @param string $interface
	 * @param string $service
	 * @param bool $shared
	 */
	public function registerInterface(string $interface, string $service, bool $shared = true) {
		$this->registerClass(
			$interface, function(DIContainer $container) use ($service) {
			return $container->query($service);
		}, $shared
		);
	}


	/**
	 * The given closure is call the first time the given service is queried.
	 * The closure has to return the instance for the given service.
	 * Created instance will be cached in case $shared is true.
	 *
	 * @param string $name
	 * @param Closure $closure
	 * @param bool $shared
	 */
	public function registerClass(string $name, Closure $closure, bool $shared = true) {
		if ($shared) {
			$this[$name] = $closure;
		} else {
			$this[$name] = parent::factory($closure);
		}
	}


	/**
	 * @param ReflectionClass $class
	 *
	 * @return stdClass
	 * @throws ReflectionException
	 * @throws DependencyInjectionException
	 */
	private function buildClass(ReflectionClass $class) {

		$constructor = $class->getConstructor();
		if ($constructor === null) {
			return $class->newInstance();
		} else {

			$parameters = [];
			foreach ($constructor->getParameters() as $parameter) {
				$parameterClass = $parameter->getClass();
				if ($parameterClass === null) {
					$resolveName = $parameter->getName();
				} else {
					$resolveName = $parameterClass->name;
				}

				try {
					$parameters[] = $this->query($resolveName);
				} catch (DependencyInjectionException $e) {
					// Service not found, use the default value when available
					if ($parameter->isDefaultValueAvailable()) {
						$parameters[] = $parameter->getDefaultValue();
					} else if ($parameterClass !== null) {
						$resolveName = $parameter->getName();
						$parameters[] = $this->query($resolveName);
					} else {
						throw $e;
					}
				}
			}

			return $class->newInstanceArgs($parameters);
		}
	}


	/**
	 * If a parameter is not registered in the container try to instantiate it
	 * by using reflection to find out how to build the class
	 *
	 * @param string $name the class name to resolve
	 *
	 * @return stdClass
	 * @throws DependencyInjectionException if the class could not be found or instantiated
	 */
	public function resolve($name) {
		$baseMsg = 'Could not resolve ' . $name . '!';
		try {
			$class = new ReflectionClass($name);
			if ($class->isInstantiable()) {
				return $this->buildClass($class);
			} else {
				throw new DependencyInjectionException(
					$baseMsg .
					' Class can not be instantiated'
				);
			}
		} catch (ReflectionException $e) {
			throw new DependencyInjectionException($baseMsg . ' ' . $e->getMessage());
		}
	}


	/**
	 * @param string $name name of the service to query for
	 *
	 * @return stdClass
	 * @throws DependencyInjectionException
	 */
	public function query(string $name) {
		if ($this->offsetExists($name)) {
			return $this->offsetGet($name);
		} else {
			$object = $this->resolve($name);

			$this->registerClass(
				$name, function() use ($object) {
				return $object;
			}
			);

			return $object;
		}
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function registerParameter(string $name, $value) {
		$this[$name] = $value;
	}

}

