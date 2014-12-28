<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * MIT license.
 */

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
