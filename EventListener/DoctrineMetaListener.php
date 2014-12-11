<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\CoreBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

class DoctrineMetaListener
{
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            /*
             * Check if the class is a child of the Meta class, the latter
             * handling the automatic createDate and modifiedDate methods.
             */
            if (!is_subclass_of($entity, 'CampaignChain\CoreBundle\Entity\Meta')) {
                continue;
            }

            $logMetadata = $em->getClassMetadata(get_class($entity));

            if ($entity->getCreatedDate() == null) {
                $entity->setCreatedDate(new \DateTime('now', new \DateTimeZone('UTC')));
            } else {
                $entity->setModifiedDate(new \DateTime('now', new \DateTimeZone('UTC')));
            }

            $em->persist($entity);
            $classMetadata = $em->getClassMetadata(get_class($entity));
            $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);
        }
    }
}