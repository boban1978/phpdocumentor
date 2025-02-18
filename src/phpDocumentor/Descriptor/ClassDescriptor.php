<?php
declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2018 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Descriptor;

use phpDocumentor\Descriptor\Tag\BaseTypes\TypedVariableAbstract;

/**
 * Descriptor representing a Class.
 */
class ClassDescriptor extends DescriptorAbstract implements Interfaces\ClassInterface
{
    /** @var ClassDescriptor|null $extends Reference to an instance of the superclass for this class, if any. */
    protected $parent;

    /** @var Collection $implements References to interfaces that are implemented by this class. */
    protected $implements;

    /** @var boolean $abstract Whether this is an abstract class. */
    protected $abstract = false;

    /** @var boolean $final Whether this class is marked as final and can't be subclassed. */
    protected $final = false;

    /** @var Collection $constants References to constants defined in this class. */
    protected $constants;

    /** @var Collection $properties References to properties defined in this class. */
    protected $properties;

    /** @var Collection $methods References to methods defined in this class. */
    protected $methods;

    /** @var Collection $usedTraits References to traits consumed by this class */
    protected $usedTraits;

    /**
     * Initializes the all properties representing a collection with a new Collection object.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setInterfaces(new Collection());
        $this->setUsedTraits(new Collection());
        $this->setConstants(new Collection());
        $this->setProperties(new Collection());
        $this->setMethods(new Collection());
    }

    /**
     * {@inheritDoc}
     */
    public function setParent($parents)
    {
        $this->parent = $parents;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function setInterfaces(Collection $implements)
    {
        $this->implements = $implements;
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaces()
    {
        return $this->implements;
    }

    /**
     * {@inheritDoc}
     */
    public function setFinal($final)
    {
        $this->final = $final;
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * {@inheritDoc}
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * {@inheritDoc}
     */
    public function setConstants(Collection $constants)
    {
        $this->constants = $constants;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * {@inheritDoc}
     */
    public function getInheritedConstants()
    {
        if ($this->getParent() === null || (!$this->getParent() instanceof self)) {
            return new Collection();
        }

        $inheritedConstants = clone $this->getParent()->getConstants();

        return $inheritedConstants->merge($this->getParent()->getInheritedConstants());
    }

    /**
     * {@inheritDoc}
     */
    public function setMethods(Collection $methods)
    {
        $this->methods = $methods;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * {@inheritDoc}
     */
    public function getInheritedMethods()
    {
        $inheritedMethods = new Collection();

        foreach ($this->getUsedTraits() as $trait) {
            if (!$trait instanceof TraitDescriptor) {
                continue;
            }

            $inheritedMethods = $inheritedMethods->merge(clone $trait->getMethods());
        }

        if ($this->getParent() === null || (!$this->getParent() instanceof self)) {
            return $inheritedMethods;
        }

        $inheritedMethods = $inheritedMethods->merge(clone $this->getParent()->getMethods());

        return $inheritedMethods->merge($this->getParent()->getInheritedMethods());
    }

    /**
     * @return Collection
     */
    public function getMagicMethods()
    {
        /** @var Collection $methodTags */
        $methodTags = clone $this->getTags()->get('method', new Collection());

        $methods = new Collection();

        /** @var Tag\MethodDescriptor $methodTag */
        foreach ($methodTags as $methodTag) {
            $method = new MethodDescriptor();
            $method->setName($methodTag->getMethodName());
            $method->setDescription($methodTag->getDescription());
            $method->setStatic($methodTag->isStatic());
            $method->setParent($this);

            $returnTags = $method->getTags()->get('return', new Collection());
            $returnTags->add($methodTag->getResponse());

            foreach ($methodTag->getArguments() as $name => $argument) {
                $method->addArgument($name, $argument);
            }

            $methods->add($method);
        }

        if ($this->getParent() instanceof static) {
            $methods = $methods->merge($this->getParent()->getMagicMethods());
        }

        return $methods;
    }

    /**
     * {@inheritDoc}
     */
    public function setProperties(Collection $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getInheritedProperties()
    {
        $inheritedProperties = new Collection();

        foreach ($this->getUsedTraits() as $trait) {
            if (!$trait instanceof TraitDescriptor) {
                continue;
            }

            $inheritedProperties = $inheritedProperties->merge(clone $trait->getProperties());
        }

        if ($this->getParent() === null || (!$this->getParent() instanceof self)) {
            return $inheritedProperties;
        }

        $inheritedProperties = $inheritedProperties->merge(clone $this->getParent()->getProperties());

        return $inheritedProperties->merge($this->getParent()->getInheritedProperties());
    }

    /**
     * @return Collection
     */
    public function getMagicProperties()
    {
        /** @var Collection $propertyTags */
        $propertyTags = clone $this->getTags()->get('property', new Collection());
        $propertyTags = $propertyTags->merge($this->getTags()->get('property-read', new Collection()));
        $propertyTags = $propertyTags->merge($this->getTags()->get('property-write', new Collection()));

        $properties = new Collection();

        /** @var Tag\PropertyDescriptor $propertyTag */
        foreach ($propertyTags as $propertyTag) {
            if (! $propertyTag instanceof TypedVariableAbstract) {
                continue;
            }
            $property = new PropertyDescriptor();
            $property->setName(ltrim($propertyTag->getVariableName(), '$'));
            $property->setDescription($propertyTag->getDescription());
            $property->setType($propertyTag->getType());
            $property->setParent($this);

            $properties->add($property);
        }

        if ($this->getParent() instanceof self) {
            $properties = $properties->merge($this->getParent()->getMagicProperties());
        }

        return $properties;
    }

    /**
     * @param PackageDescriptor $package
     */
    public function setPackage($package)
    {
        parent::setPackage($package);

        foreach ($this->getConstants() as $constant) {
            // TODO #840: Workaround; for some reason there are NULLs in the constants array.
            if ($constant) {
                $constant->setPackage($package);
            }
        }

        foreach ($this->getProperties() as $property) {
            // TODO #840: Workaround; for some reason there are NULLs in the properties array.
            if ($property) {
                $property->setPackage($package);
            }
        }

        foreach ($this->getMethods() as $method) {
            // TODO #840: Workaround; for some reason there are NULLs in the methods array.
            if ($method) {
                $method->setPackage($package);
            }
        }
    }

    /**
     * Sets a collection of all traits used by this class.
     *
     * @param Collection $usedTraits
     */
    public function setUsedTraits($usedTraits)
    {
        $this->usedTraits = $usedTraits;
    }

    /**
     * Returns the traits used by this class.
     *
     * Returned values may either be a string (when the Trait is not in this project) or a TraitDescriptor.
     *
     * @return Collection
     */
    public function getUsedTraits()
    {
        return $this->usedTraits;
    }

    public function getInheritedElement()
    {
        return $this->getParent();
    }
}
