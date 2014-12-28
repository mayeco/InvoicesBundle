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
use Mayeco\NotificationsBundle\NotificationEvents;
use Mayeco\NotificationsBundle\NotificationEvent;
use Mayeco\NotificationsBundle\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;

class InvoicesSubscriber extends SubscriberHelper implements EventSubscriberInterface
{

    public function __construct(InvoiceHelper $invoices, LoggerInterface $logger, EntityManager $entityManager)
    {
        parent::__construct($invoices, $logger, $entityManager);
    }

    public static function getSubscribedEvents()
    {
        return array(
            NotificationEvents::RESPONSE_SUCCESS => array('onResponseSuccess', 0),
            NotificationEvents::ORDER_CREATED => array('onOrderCreated', 0),
            NotificationEvents::FRAUD_STATUS_CHANGED => array('onFraudStatusChanged', 0),
            NotificationEvents::INVOICE_STATUS_CHANGED => array('onInvoiceStatusChanged', 0),
            NotificationEvents::REFUND_ISSUED => array('onRefundIssued', 0),
        );
    }

    public function onResponseSuccess(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $execute = true;
        while ($execute) {

            $invoice = $this->invoices->getOrCreateInvoice($response->getInvoiceId(), $response->getOrderNumber(), $response->getMerchantOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setInvoiceUsdAmount($response->getTotal());
                $invoice->setInvoiceUpdate(new \DateTime());
                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        if ("Y" != $response->getCreditCardProcessed()) {

            $execute = true;
            while ($execute) {

                $order = $this->invoices->getOrCreateOrder($response->getOrderNumber(), $response->getMerchantOrderId());
                $this->isEmOpenOrCreateNew();
                $this->em->beginTransaction();
                try {

                    $order = $this->mergeAndLock($order);
                    $order->setCreditCardProcessed($response->getCreditCardProcessed());
                    $order->setSaleDateUpdated(new \DateTime());
                    if ($this->save($order)) {
                        $execute = false;
                    }

                } catch (OptimisticLockException $e) {

                    $this->catchOptimisticLockException($e);

                }

            }

        }

        $this->logger->info('InvoicesSubscriber::onOrderCreated', array(
            'ORDEN_ID' => $response->getOrderNumber(),
        ));
    }

    public function onOrderCreated(NotificationEvent $event)
    {
        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            $invoice = $this->invoices->getOrCreateInvoice($notification->getInvoiceId(), $notification->getSaleId(), $notification->getVendorOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setInvoiceUsdAmount($notification->getInvoiceUsdAmount());
                $invoice->setPaymentType($notification->getPaymentType());
                $invoice->setFraudStatus($notification->getFraudStatus());
                $invoice->setInvoiceStatus($notification->getInvoiceStatus());
                $invoice->setInvoiceUpdate(new \DateTime());
                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $execute = true;
        while ($execute) {

            $order = $this->invoices->getOrCreateOrder($notification->getSaleId(), $notification->getVendorOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $order = $this->mergeAndLock($order);
                $order->setSaleDatePlaced($notification->getSaleDatePlaced());
                $order->setInvoiceNext($notification->getItemRecDateNext1());

                if (intval($notification->getItemRecInstallBilled1()) > intval($order->getSuccessfulRecurring())) {
                    $order->setSuccessfulRecurring($notification->getItemRecInstallBilled1());
                }

                $order->setRecurringStatus($notification->getItemRecStatus1());
                $order->setRecurringOrder($notification->getItemRecurrence1());
                $order->setSaleDateUpdated(new \DateTime());
                if ($this->save($order)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $this->logger->info('InvoicesSubscriber::onOrderCreated', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));

    }

    public function onFraudStatusChanged(NotificationEvent $event)
    {
        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            $invoice = $this->invoices->getOrCreateInvoice($notification->getInvoiceId(), $notification->getSaleId(), $notification->getVendorOrderId());
            if ("pass" == $invoice->getFraudStatus()) {
                return;
            }

            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setFraudStatus($notification->getFraudStatus());
                $invoice->setInvoiceUpdate(new \DateTime());
                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        if ("fail" == $notification->getFraudStatus()) {

            $execute = true;
            while ($execute) {

                $order = $this->invoices->getOrCreateOrder($notification->getSaleId(), $notification->getVendorOrderId());
                $this->isEmOpenOrCreateNew();
                $this->em->beginTransaction();
                try {

                    $order = $this->mergeAndLock($order);
                    $order->setRecurringStatus("cancelled");
                    $order->setRecurringStatusMail("zero");
                    $order->setSaleDateUpdated(new \DateTime());
                    if ($this->save($order)) {
                        $execute = false;
                    }

                } catch (OptimisticLockException $e) {

                    $this->catchOptimisticLockException($e);

                }

            }

        }

        $this->logger->info('InvoicesSubscriber::onFraudStatusChanged', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));

    }

    public function onInvoiceStatusChanged(NotificationEvent $event)
    {
        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            $invoice = $this->invoices->getOrCreateInvoice($notification->getInvoiceId(), $notification->getSaleId(), $notification->getVendorOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setFraudStatus($notification->getFraudStatus());
                $invoice->setInvoiceStatus($notification->getInvoiceStatus());
                $invoice->setInvoiceUsdAmount($notification->getInvoiceUsdAmount());
                $invoice->setPaymentType($notification->getPaymentType());
                $invoice->setInvoiceUpdate(new \DateTime());
                if ($this->save($invoice)) {
                    $execute = false;;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $execute = true;
        while ($execute) {

            $order = $this->invoices->getOrCreateOrder($notification->getSaleId(), $notification->getVendorOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $order = $this->mergeAndLock($order);
                $order->setSaleDatePlaced($notification->getSaleDatePlaced());
                $order->setRecurringOrder($notification->getItemRecurrence1());
                $order->setRecurringStatus($notification->getItemRecStatus1());
                $order->setInvoiceNext($notification->getItemRecDateNext1());

                if (intval($notification->getItemRecInstallBilled1()) > intval($order->getSuccessfulRecurring())) {
                    $order->setSuccessfulRecurring($notification->getItemRecInstallBilled1());
                }

                $order->setSaleDateUpdated(new \DateTime());
                if ($this->save($order)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }
        }

        $this->logger->info('InvoicesSubscriber::onInvoiceStatusChanged', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));

    }

    public function onRefundIssued(NotificationEvent $event)
    {
        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            $invoice = $this->invoices->getOrCreateInvoice($notification->getInvoiceId(), $notification->getSaleId(), $notification->getVendorOrderId());
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setInvoiceUsdAmount("zero");
                $invoice->setInvoiceStatusMail("zero");
                $invoice->setInvoiceStatus("refund");
                $invoice->setInvoiceUpdate(new \DateTime());
                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $this->logger->info('InvoicesSubscriber::onRefundIssued', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

}
