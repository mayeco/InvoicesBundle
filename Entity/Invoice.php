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

namespace Mayeco\InvoicesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\Mayeco\InvoicesBundle\Entity\InvoiceRepository")
 * @ORM\Table(name="invoices_invoices")
 */
class Invoice
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
     * @ORM\Column(name="invoice_id", type="string", length=255, nullable=false, unique=true)
     */
    private $invoice_id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_usd_amount", type="float", nullable=true)
     */
    private $invoice_usd_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_status", type="string", length=255, nullable=true)
     */
    private $invoice_status;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_type", type="string", length=255, nullable=true)
     */
    private $payment_type;


    /**
     * @var string
     *
     * @ORM\Column(name="invoice_create", type="datetime", nullable=true)
     */
    private $invoice_create;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_update", type="datetime", nullable=true)
     */
    private $invoice_update;

    /**
     * @var string
     *
     * @ORM\Column(name="fraud_status", type="string", length=255, nullable=true)
     */
    private $fraud_status;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_status_mail", type="string", length=255, nullable=true)
     */
    private $invoice_status_mail;

    /**
     * @ORM\ManyToOne(targetEntity="\Mayeco\InvoicesBundle\Entity\Order", inversedBy="invoices", cascade={"persist"})
     **/
    private $order;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     **/
    private $version;

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
     * Set invoice_id
     *
     * @param string $invoiceId
     * @return Invoice
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;

        return $this;
    }

    /**
     * Get invoice_id
     *
     * @return string 
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * Set invoice_usd_amount
     *
     * @param float $invoiceUsdAmount
     * @return Invoice
     */
    public function setInvoiceUsdAmount($invoiceUsdAmount)
    {
        if(empty($invoiceUsdAmount)){
            return $this;
        }

        if("zero" == $invoiceUsdAmount){
            $invoiceUsdAmount = 0;
        }

        $this->invoice_usd_amount = $invoiceUsdAmount;

        return $this;
    }

    /**
     * Get invoice_usd_amount
     *
     * @return float 
     */
    public function getInvoiceUsdAmount()
    {
        return $this->invoice_usd_amount;
    }

    /**
     * Set invoice_status
     *
     * @param string $invoiceStatus
     * @return Invoice
     */
    public function setInvoiceStatus($invoiceStatus)
    {
        if(empty($invoiceStatus)){
            return $this;
        }

        $this->invoice_status = $invoiceStatus;

        return $this;
    }

    /**
     * Get invoice_status
     *
     * @return string 
     */
    public function getInvoiceStatus()
    {
        return $this->invoice_status;
    }

    /**
     * Set payment_type
     *
     * @param string $paymentType
     * @return Invoice
     */
    public function setPaymentType($paymentType)
    {
        if(empty($paymentType)){
            return $this;
        }

        $this->payment_type = $paymentType;

        return $this;
    }

    /**
     * Get payment_type
     *
     * @return string 
     */
    public function getPaymentType()
    {
        return $this->payment_type;
    }

    /**
     * Set invoice_create
     *
     * @param \DateTime $invoiceCreate
     * @return Invoice
     */
    public function setInvoiceCreate($invoiceCreate)
    {
        $this->invoice_create = $invoiceCreate;

        return $this;
    }

    /**
     * Get invoice_create
     *
     * @return \DateTime 
     */
    public function getInvoiceCreate()
    {
        return $this->invoice_create;
    }

    /**
     * Set invoice_update
     *
     * @param \DateTime $invoiceUpdate
     * @return Invoice
     */
    public function setInvoiceUpdate($invoiceUpdate)
    {
        $this->invoice_update = $invoiceUpdate;

        return $this;
    }

    /**
     * Get invoice_update
     *
     * @return \DateTime 
     */
    public function getInvoiceUpdate()
    {
        return $this->invoice_update;
    }

    /**
     * Set fraud_status
     *
     * @param string $fraudStatus
     * @return Invoice
     */
    public function setFraudStatus($fraudStatus)
    {
        if(empty($fraudStatus)){
            return $this;
        }

        $this->fraud_status = $fraudStatus;

        return $this;
    }

    /**
     * Get fraud_status
     *
     * @return string
     */
    public function getFraudStatus()
    {
        return $this->fraud_status;
    }

    /**
     * Set fraud_status
     *
     * @param string $fraudStatus
     * @return Invoice
     */
    public function setInvoiceStatusMail($invoiceStatusMail)
    {
        if(empty($invoiceStatusMail)){
            return $this;
        }

        if("zero" == $invoiceStatusMail){
            $invoiceStatusMail = 0;
        }

        $this->invoice_status_mail = $invoiceStatusMail;

        return $this;
    }

    /**
     * Get fraud_status
     *
     * @return string
     */
    public function getInvoiceStatusMail()
    {
        return $this->invoice_status_mail;
    }

    /**
     * Set order
     *
     * @param \Mayeco\InvoicesBundle\Entity\Order $order
     * @return Invoice
     */
    public function setOrder(\Mayeco\InvoicesBundle\Entity\Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Mayeco\InvoicesBundle\Entity\Order 
     */
    public function getOrder()
    {
        return $this->order;
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

}
