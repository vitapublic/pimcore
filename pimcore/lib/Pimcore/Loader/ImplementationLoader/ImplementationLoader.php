<?php

declare(strict_types = 1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Loader\ImplementationLoader;

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;

/**
 * Core implementation loader delegating to classmap and prefix loaders.
 */
class ImplementationLoader implements LoaderInterface, ClassMapLoaderInterface, PrefixLoaderInterface
{
    /**
     * @var ClassMapLoaderInterface
     */
    protected $classMapLoader;

    /**
     * @var PrefixLoaderInterface
     */
    protected $prefixLoader;

    /**
     * @var LoaderInterface[]
     */
    protected $loaders;

    /**
     * @var array
     */
    private $loaderCache = [];

    /**
     * @param ClassMapLoaderInterface $classMapLoader
     * @param PrefixLoaderInterface $prefixLoader
     */
    public function __construct(
        ClassMapLoaderInterface $classMapLoader = null,
        PrefixLoaderInterface $prefixLoader = null
    )
    {
        if (null === $classMapLoader) {
            $classMapLoader = new ClassMapLoader();
        }

        if (null === $prefixLoader) {
            $prefixLoader = new PrefixLoader();
        }

        $this->classMapLoader = $classMapLoader;
        $this->prefixLoader   = $prefixLoader;

        $this->loaders = [
            $this->classMapLoader,
            $this->prefixLoader
        ];

        $this->init();
    }

    /**
     * Initializes loader after construction
     */
    protected function init()
    {
    }

    /**
     * @inheritDoc
     */
    public function addClassMap(string $name, string $className)
    {
        $this->classMapLoader->addClassMap($name, $className);
    }

    /**
     * @inheritDoc
     */
    public function getClassMap(): array
    {
        $this->classMapLoader->getClassMap();
    }

    /**
     * @inheritDoc
     */
    public function setClassMap(array $classMap)
    {
        $this->classMapLoader->setClassMap($classMap);
    }

    /**
     * @inheritDoc
     */
    public function addPrefix(string $prefix, callable $normalizer = null)
    {
        $this->prefixLoader->addPrefix($prefix, $normalizer);
    }

    /**
     * @inheritDoc
     */
    public function addPrefixes(array $prefixes, callable $normalizer = null)
    {
        $this->prefixLoader->addPrefixes($prefixes, $normalizer);
    }

    /**
     * @param string $name
     *
     * @return LoaderInterface|null
     */
    private function getLoader(string $name)
    {
        // loader cache contains index of loader previously found for given name
        if (isset($this->loaderCache[$name])) {
            return $this->loaders[$this->loaderCache[$name]];
        }

        foreach ($this->loaders as $idx => $loader) {
            if ($loader->supports($name)) {
                $this->loaderCache[$name] = $idx;

                return $loader;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(string $name): bool
    {
        return null !== $this->getLoader($name);
    }

    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = [])
    {
        $loader = $this->getLoader($name);
        if (null === $loader) {
            throw new UnsupportedException(sprintf('Loader for "%s" was not found', $name));
        }

        return $loader->build($name, $params);
    }
}
