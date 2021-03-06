<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

/**
 * ResolveTargetEntityListener
 *
 * Mechanism to overwrite interfaces or classes specified as association
 * targets.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.2
 */
class ResolveTargetEntityListener implements EventSubscriber
{
    /**
     * @var array[] indexed by original entity name
     */
    private $resolveTargetEntities = [];

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
            Events::onClassMetadataNotFound
        ];
    }

    /**
     * Adds a target-entity class name to resolve to a new class name.
     *
     * @param string $originalEntity
     * @param string $newEntity
     *
     * @return void
     */
    public function addResolveTargetEntity($originalEntity, $newEntity)
    {
        $this->resolveTargetEntities[ltrim($originalEntity, "\\")] = ltrim($newEntity, "\\");
    }

    /**
     * @param OnClassMetadataNotFoundEventArgs $args
     *
     * @internal this is an event callback, and should not be called directly
     *
     * @return void
     */
    public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $args)
    {
        if (array_key_exists($args->getClassName(), $this->resolveTargetEntities)) {
            $resolvedClassName = $this->resolveTargetEntities[$args->getClassName()];
            $resolvedMetadata  = $args->getObjectManager()->getClassMetadata($resolvedClassName);

            $args->setFoundMetadata($resolvedMetadata);
        }
    }

    /**
     * Processes event and resolves new target entity names.
     *
     * @param LoadClassMetadataEventArgs $args
     *
     * @return void
     *
     * @internal this is an event callback, and should not be called directly
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        /* @var $cm \Doctrine\ORM\Mapping\ClassMetadata */
        $class = $args->getClassMetadata();

        foreach ($class->discriminatorMap as $key => $className) {
            if (isset($this->resolveTargetEntities[$className])) {
                $targetEntity = $this->resolveTargetEntities[$className];

                $class->discriminatorMap[$key] = $targetEntity;
            }
        }

        foreach ($class->getDeclaredPropertiesIterator() as $association) {
            if ($association instanceof AssociationMetadata &&
                isset($this->resolveTargetEntities[$association->getTargetEntity()])) {
                $targetEntity = $this->resolveTargetEntities[$association->getTargetEntity()];

                $association->setTargetEntity($targetEntity);
            }
        }

        foreach ($this->resolveTargetEntities as $interface => $targetEntity) {
            if ($targetEntity === $class->getClassName()) {
                $args->getEntityManager()->getMetadataFactory()->setMetadataFor($interface, $class);
            }
        }
    }
}
