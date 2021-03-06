<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;

/**
 * The IdentifierFlattener utility now houses some of the identifier manipulation logic from unit of work, so that it
 * can be re-used elsewhere.
 *
 * @since       2.5
 * @author      Rob Caiger <rob@clocal.co.uk>
 *
 * @internal do not use in your own codebase: no BC compliance on this class
 */
final class IdentifierFlattener
{
    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * The metadata factory, used to retrieve the ORM metadata of entity classes.
     *
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * Initializes a new IdentifierFlattener instance, bound to the given EntityManager.
     *
     * @param UnitOfWork           $unitOfWork
     * @param ClassMetadataFactory $metadataFactory
     */
    public function __construct(UnitOfWork $unitOfWork, ClassMetadataFactory $metadataFactory)
    {
        $this->unitOfWork = $unitOfWork;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * convert foreign identifiers into scalar foreign key values to avoid object to string conversion failures.
     *
     * @param ClassMetadata $class
     * @param array         $id
     *
     * @return array
     */
    public function flattenIdentifier(ClassMetadata $class, array $id)
    {
        $flatId = [];

        foreach ($class->identifier as $field) {
            $property = $class->getProperty($field);

            if ($property instanceof FieldMetadata) {
                $flatId[$field] = $id[$field];

                continue;
            }

            if (isset($id[$field]) && is_object($id[$field])) {
                /* @var $targetClassMetadata ClassMetadata */
                $targetClassMetadata  = $this->metadataFactory->getMetadataFor($property->getTargetEntity());
                $targetClassPersister = $this->unitOfWork->getEntityPersister($property->getTargetEntity());
                // @todo guilhermeblanco Bring this back:
                // $identifiers         = $this->unitOfWork->isInIdentityMap($id[$field])
                //     ? $this->unitOfWork->getEntityIdentifier($id[$field])
                //     : $targetClassPersister->getIdentifier($id[$field])
                // ;
                $identifiers          = $targetClassPersister->getIdentifier($id[$field]);

                $associatedId = $this->flattenIdentifier($targetClassMetadata, $identifiers);

                $flatId[$field] = implode(' ', $associatedId);

                continue;
            }

            $associatedId = [];

            foreach ($property->getJoinColumns() as $joinColumn) {
                $associatedId[] = $id[$joinColumn->getColumnName()];
            }

            $flatId[$field] = implode(' ', $associatedId);
        }

        return $flatId;
    }
}
