<?php

namespace LinguaLeo\Servaxle;

use Castel;

class ServiceContainer extends Castel
{
    /**
     * Global globalContainer
     *
     * @var ServiceContainer
     */
    protected $globalContainer;

    /**
     * Attached classes
     *
     * @var array
     */
    protected $attachedClasses = [];

    /**
     * Instantiates the container.
     *
     * @param array $values
     * @param \LinguaLeo\Servaxle\ServiceContainer $globalContainer
     */
    public function __construct(array $values = [], ServiceContainer $globalContainer = null)
    {
        parent::__construct($values);
        $this->globalContainer = $globalContainer;
    }

    /**
     * Returns the globalContainer globalContainer
     *
     * @return \LinguaLeo\Servaxle\ServiceContainer
     */
    public function getGlobalContainer()
    {
        return $this->globalContainer;
    }

    /**
     * Attaches a class to property
     *
     * @param string $property
     * @param string $class
     * @return \LinguaLeo\Servaxle\ServiceContainer
     */
    public function attach($property, $class)
    {
        $this->attachedClasses[$property][] = $class;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($id)
    {
        if (isset($this->attachedClasses[$id])) {
            $container = new self([], $this->globalContainer);
            foreach ($this->attachedClasses[$id] as $className) {
                $this->register($container, new $className());
            }
            return $this->$id = $container;
        }
        return parent::__get($id);
    }

    /**
     * Registers a provider
     *
     * @param \LinguaLeo\Servaxle\ServiceContainer $container
     * @param \LinguaLeo\Servaxle\ServiceProviderInterface $provider
     */
    protected function register(ServiceContainer $container, ServiceProviderInterface $provider)
    {
        $provider->register($container);
    }
}
