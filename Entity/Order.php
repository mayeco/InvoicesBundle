<?php

namespace Mayeco\InvoicesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\Mayeco\InvoicesBundle\Entity\OrderRepository")
 * @ORM\Table(name="invoices_orders")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=255, nullable=false, unique=true)
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="credit_card_processed", type="string", length=255, nullable=true)
     */
    private $credit_card_processed;

    /**
     * @var string
     *
     * @ORM\Column(name="sale_date_placed", type="datetime", nullable=true)
     */
    private $sale_date_placed;

    /**
     * @var string
     *
     * @ORM\Column(name="sale_date_updated", type="datetime", nullable=true)
     */
    private $sale_date_updated;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_next", type="datetime", nullable=true)
     */
    private $invoice_next;

    /**
     *
     * @ORM\Column(name="successful_recurring", type="integer", nullable=true)
     */
    private $successful_recurring;

    /**
     *
     * @ORM\Column(name="recurring_status", type="string", length=255, nullable=true)
     */
    private $recurring_status;

    /**
     *
     * @ORM\Column(name="recurring_status_mail", type="string", length=255, nullable=true)
     */
    private $recurring_status_mail;

    /**
     *
     * @ORM\Column(name="recurring_next_mail", type="string", length=255, nullable=true)
     */
    private $recurring_next_mail;

    /**
     * @var string
     *
     * @ORM\Column(name="order_recurrence", type="string", length=255, nullable=true)
     */
    private $recurring_order;

    /**
     * @var string
     *
     * @ORM\OneToMany(targetEntity="\Mayeco\InvoicesBundle\Entity\Invoice", mappedBy="order")
     */
    private $invoices;

    /**
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entity\User", inversedBy="orders", cascade={"persist"})
     **/
    private $user;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     **/
    private $version;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->invoices = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return Order
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id
     *
     * @return string 
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set sale_date_placed
     *
     * @param \DateTime $saleDatePlaced
     * @return Order
     */
    public function setSaleDatePlaced($saleDatePlaced)
    {
        if(empty($saleDatePlaced)){
            return $this;
        }

        $this->sale_date_placed = $saleDatePlaced;

        return $this;
    }

    /**
     * Get sale_date_placed
     *
     * @return \DateTime 
     */
    public function getSaleDatePlaced()
    {
        return $this->sale_date_placed;
    }

    /**
     * Set sale_date_updated
     *
     * @param \DateTime $saleDateUpdated
     * @return Order
     */
    public function setSaleDateUpdated($saleDateUpdated)
    {
        $this->sale_date_updated = $saleDateUpdated;

        return $this;
    }

    /**
     * Get sale_date_updated
     *
     * @return \DateTime 
     */
    public function getSaleDateUpdated()
    {
        return $this->sale_date_updated;
    }

    /**
     * Add invoices
     *
     * @param \Mayeco\InvoicesBundle\Entity\Invoice $invoices
     * @return Order
     */
    public function addInvoice(\Mayeco\InvoicesBundle\Entity\Invoice $invoices)
    {
        $this->invoices[] = $invoices;

        return $this;
    }

    /**
     * Remove invoices
     *
     * @param \Mayeco\InvoicesBundle\Entity\Invoice $invoices
     */
    public function removeInvoice(\Mayeco\InvoicesBundle\Entity\Invoice $invoices)
    {
        $this->invoices->removeElement($invoices);
    }

    /**
     * Get invoices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Set invoice_next
     *
     * @param \DateTime $invoiceNext
     * @return Invoice
     */
    public function setInvoiceNext($invoiceNext)
    {
        if(empty($invoiceNext)){
            return $this;
        }

        $this->invoice_next = $invoiceNext;

        return $this;
    }

    /**
     * Get invoice_next
     *
     * @return \DateTime
     */
    public function getInvoiceNext()
    {
        return $this->invoice_next;
    }

    /**
     * @return string
     */
    public function getCreditCardProcessed()
    {
        return $this->credit_card_processed;
    }

    /**
     * @param string $credit_card_processed
     */
    public function setCreditCardProcessed($credit_card_processed)
    {
        $this->credit_card_processed = $credit_card_processed;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulRecurring()
    {
        return $this->successful_recurring;
    }

    /**
     * @param mixed $successful_recurring
     */
    public function setSuccessfulRecurring($successful_recurring)
    {
        if(empty($successful_recurring) || intval($this->successful_recurring) > intval($successful_recurring)){
            return $this;
        }

        $this->successful_recurring = $successful_recurring;

        $this->setRecurringNextMail("zero");

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecurringStatus()
    {
        return $this->recurring_status;
    }

    /**
     * @param mixed $recurring_status
     */
    public function setRecurringStatus($recurring_status)
    {
        if(empty($recurring_status)){
            return $this;
        }

        $this->recurring_status = $recurring_status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecurringStatusMail()
    {
        return $this->recurring_status_mail;
    }

    /**
     * @param mixed $recurring_status
     */
    public function setRecurringStatusMail($recurring_status_mail)
    {
        if(empty($recurring_status_mail)){
            return $this;
        }

        if("zero" == $recurring_status_mail){
            $recurring_status_mail = 0;
        }

        $this->recurring_status_mail = $recurring_status_mail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecurringNextMail()
    {
        return $this->recurring_next_mail;
    }

    /**
     * @param mixed $recurring_status
     */
    public function setRecurringNextMail($recurring_next_mail)
    {
        if(empty($recurring_next_mail)){
            return $this;
        }

        if("zero" == $recurring_next_mail){
            $recurring_next_mail = 0;
        }

        $this->recurring_next_mail = $recurring_next_mail;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecurringOrder()
    {
        return $this->recurring_order;
    }

    /**
     * @param string $recurring_order
     */
    public function setRecurringOrder($recurring_order)
    {
        $this->recurring_order = $recurring_order;

        return $this;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     * @return Order
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
