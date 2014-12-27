<?php

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

        if (!is_numeric($order_id) || !is_numeric($user_id)) {
            throw \InvalidArgumentException("order_id not numeric");
        }

        $repository = $this->em->getRepository("MayecoInvoicesBundle:Order");
        $order = $repository->findOneBy(
            array(
                "order_id" => $order_id,
            )
        );

        if ($create && !$order) {

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
