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

namespace Mayeco\InvoicesBundle\Services;

use Mayeco\InvoicesBundle\Entity\Invoice;
use Mayeco\InvoicesBundle\Entity\Order;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class InvoiceHelper
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getOrCreateInvoice($invoice_id, $order_id, $user_id, $create = true)
    {
        if (!is_numeric($invoice_id)) {
            throw \InvalidArgumentException("invoice_id not numeric");
        }

        $repository = $this->em->getRepository("MayecoInvoicesBundle:Invoice");
        $invoice = $repository->findOneBy(
            array(
                "invoice_id" => $invoice_id,
            )
        );

        if ($create && !$invoice) {

            $order = $this->getOrCreateOrder($order_id, $user_id, $create);

            $invoice = new Invoice();
            $invoice->setInvoiceCreate(new \DateTime());
            $invoice->setInvoiceId($invoice_id);
            $invoice->setOrder($order);
            $invoice->setInvoiceStatusMail("zero");

            $this->em->beginTransaction();

            try {

                $this->persistFlushAndCommit($invoice);

            } catch (UniqueConstraintViolationException $e) {

                if ($this->em->getConnection()->isTransactionActive()) {
                    $this->em->close();
                    $this->em->rollback();
                }

                if (!$this->em->isOpen()) {
                    $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
                }

                $invoice = $repository->findOneBy(
                    array(
                        "invoice_id" => $invoice_id,
                    )
                );

            }
        }

        if(!$invoice) {
            throw new \LogicException("Invoice is null");
        }

        return $invoice;
    }

    public function getOrCreateOrder($order_id, $user_id, $create = true)
    {

        if (!is_numeric($order_id)) {
            throw \InvalidArgumentException("order_id is not numeric");
        }

        $repository = $this->em->getRepository("MayecoInvoicesBundle:Order");
        $order = $repository->findOneBy(
            array(
                "order_id" => $order_id,
            )
        );

        if ($create && !$order) {

            if (!is_numeric($user_id)) {
                throw \InvalidArgumentException("user_id is not numeric");
            }

            $order = new Order();
            $order->setSaleDateUpdated(new \DateTime());
            $order->setOrderId($order_id);
            $order->setCreditCardProcessed("Y");
            $order->setRecurringStatusMail("zero");

            $user_repository = $this->em->getRepository("AppBundle:User");
            $user = $user_repository->findOneBy(
                array(
                    "id" => $user_id,
                )
            );

            if(!$user) {
                throw new \LogicException("User is null");
            }

            $order->setUser($user);
            $this->em->beginTransaction();
            try {

                $this->persistFlushAndCommit($order);

            } catch (UniqueConstraintViolationException $e) {

                if ($this->em->getConnection()->isTransactionActive()) {
                    $this->em->close();
                    $this->em->rollback();
                }

                if (!$this->em->isOpen()) {
                    $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
                }

                $order = $repository->findOneBy(
                    array(
                        "order_id" => $order_id,
                    )
                );

            }

        }

        if(!$order) {
            throw new \LogicException("Order is null");
        }

        return $order;
    }

    private function persistFlushAndCommit($object)
    {
        $this->em->persist($object);
        $this->em->flush();
        $this->em->commit();
    }

}
