<?php
/*
* This file is part of the CampaignChain package.
*
* (c) CampaignChain, Inc. <info@campaignchain.com>
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


            $entity->setCreatedDate(new \DateTime('now', new \DateTimeZone('UTC')));
            $entity->setModifiedDate($entity->getCreatedDate());


            $em->persist($entity);
            $classMetadata = $em->getClassMetadata(get_class($entity));
            $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);

        }
        
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!is_subclass_of($entity, 'CampaignChain\CoreBundle\Entity\Meta')) {
                continue;
            }
            $logMetadata = $em->getClassMetadata(get_class($entity));
            $entity->setModifiedDate(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }
}
