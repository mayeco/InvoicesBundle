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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;

class RecurringSubscriber extends SubscriberHelper implements EventSubscriberInterface
{

    public function __construct(InvoiceHelper $invoices, LoggerInterface $logger, EntityManager $entityManager)
    {
        parent::__construct($invoices, $logger, $entityManager);
    }

    public static function getSubscribedEvents()
    {
        return array(
            NotificationEvents::RECURRING_INSTALLMENT_SUCCESS => array('onRecurringInstallmentSuccess', 0),
            NotificationEvents::RECURRING_INSTALLMENT_FAILED => array('onRecurringInstallmentFailed', 0),
            NotificationEvents::RECURRING_STOPPED => array('onRecurringStopped', 0),
            NotificationEvents::RECURRING_COMPLETE => array('onRecurringComplete', 0),
            NotificationEvents::RECURRING_RESTARTED => array('onRecurringRestarted', 0),
        );
    }

    public function onRecurringInstallmentSuccess(NotificationEvent $event)
    {

        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            try {
                $invoice = $this->invoices->getOrCreateInvoice($notification->getInvoiceId(), $notification->getSaleId(), $notification->getVendorOrderId());
            } catch (\Exception $e) {
                return;
            }
            
            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $invoice = $this->mergeAndLock($invoice);
                $invoice->setPaymentType($notification->getPaymentType());
                $invoice->setInvoiceUsdAmount($notification->getItemUsdAmount1());
                $invoice->setInvoiceUpdate(new \DateTime());

                if("pass" != $invoice->getFraudStatus()) {
                    $invoice->setFraudStatus("wait");
                }

                if("deposited" != $invoice->getInvoiceStatus()) {
                    $invoice->setInvoiceStatus("pending");
                }

                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $execute = true;
        while ($execute) {

            try {
                $order = $this->invoices->getOrCreateOrder($notification->getSaleId(), $notification->getVendorOrderId());
            } catch (\Exception $e) {
                return;
            }

            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $order = $this->mergeAndLock($order);
                $order->setInvoiceNext($notification->getItemRecDateNext1());
                $order->setSuccessfulRecurring($notification->getItemRecInstallBilled1());
                $order->setRecurringStatus($notification->getItemRecStatus1());
                $order->setRecurringStatusMail("zero");

                if ($this->save($invoice)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        $this->logger->info('RecurringSubscriber::onRecurringInstallmentSuccess', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

    private function updateRecurringOrder(NotificationEvent $event, $order_status = null)
    {

        $notification = $event->getNotification();

        $execute = true;
        while ($execute) {

            try {
                $order = $this->invoices->getOrCreateOrder($notification->getSaleId(), $notification->getVendorOrderId());
            } catch (\Exception $e) {
                return;
            }

            $this->isEmOpenOrCreateNew();
            $this->em->beginTransaction();
            try {

                $order = $this->mergeAndLock($order);
                $order->setInvoiceNext($notification->getItemRecDateNext1());
                $order->setSuccessfulRecurring($notification->getItemRecInstallBilled1());
                $order->setRecurringStatusMail("zero");
                $order->setRecurringStatus($notification->getItemRecStatus1());

                if($order_status) {
                    $order->setRecurringStatus($order_status);
                }

                if ($this->save($order)) {
                    $execute = false;
                }

            } catch (OptimisticLockException $e) {

                $this->catchOptimisticLockException($e);

            }

        }

        return $order;

    }

    public function onRecurringInstallmentFailed(NotificationEvent $event)
    {
        $notification = $event->getNotification();
        $this->updateRecurringOrder($event, "fail");
        $this->logger->info('RecurringSubscriber::onRecurringInstallmentFailed', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

    public function onRecurringStopped(NotificationEvent $event)
    {
        $notification = $event->getNotification();
        $this->updateRecurringOrder($event);
        $this->logger->info('RecurringSubscriber::onRecurringStopped', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

    public function onRecurringComplete(NotificationEvent $event)
    {
        $notification = $event->getNotification();
        $this->updateRecurringOrder($event);
        $this->logger->info('RecurringSubscriber::onRecurringComplete', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

    public function onRecurringRestarted(NotificationEvent $event)
    {
        $notification = $event->getNotification();
        $this->updateRecurringOrder($event);
        $this->logger->info('RecurringSubscriber::onRecurringRestarted', array(
            'ORDEN_ID' => $notification->getSaleId()
        ));
    }

}
