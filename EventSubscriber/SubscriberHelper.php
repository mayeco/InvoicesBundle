<?php

namespace Mayeco\InvoicesBundle\EventSubscriber;

use Mayeco\InvoicesBundle\Services\InvoiceHelper;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\UnitOfWork;

abstract class SubscriberHelper
{

    protected $invoices;
    protected $logger;
    protected $em;

    public function __construct(InvoiceHelper $invoices, LoggerInterface $logger, EntityManager $entityManager)
    {
        $this->invoices = $invoices;
        $this->logger = $logger;
        $this->em = $entityManager;
    }

    protected function catchOptimisticLockException(OptimisticLockException $e)
    {
        $this->em->close();
        $this->em->rollback();

        $this->logger->warning($e->getTraceAsString());
    }

    protected function save($entity)
    {
        $this->persistAndFlush($entity);
        return $this->validateConnectionAndCommit($entity);
    }

    private function persistAndFlush($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    private function validateConnectionAndCommit()
    {
        if(!$this->em->getConnection()->isTransactionActive() || $this->em->getConnection()->isRollbackOnly()) {
            return;
        }

        $this->em->commit();
        return true;
    }

    protected function mergeAndLock($entity)
    {
        if (UnitOfWork::STATE_MANAGED !== $this->em->getUnitOfWork()->getEntityState($entity)) {
            $entity = $this->em->merge($entity);
        }

        $this->em->lock($entity, LockMode::PESSIMISTIC_WRITE);

        return $entity;
    }

    protected function isEmOpenOrCreateNew()
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }
    }

}
