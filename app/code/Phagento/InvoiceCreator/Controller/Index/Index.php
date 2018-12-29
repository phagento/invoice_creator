<?php
/**
 * @category    Phagento
 * @package  InvoiceCreator
 * @copyright   Copyright (c) 2018
 * @author  Joenas Ejes
 */

namespace Phagento\InvoiceCreator\Controller\Index;

use Magento\Framework\App\Action\Action;

/**
 * Class Index
 *
 * @package Phagento\InvoiceCreator\Controller\Index\Index
 */
class Index extends Action {
    /**
     * Index resultPageFactory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Framework\View\Result\PageFactory            $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface           $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService           $invoiceService
     * @param \Magento\Framework\DB\Transaction                     $transaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\Controller\Result\JsonFactory      $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_orderRepository  = $orderRepository;
        $this->_invoiceService   = $invoiceService;
        $this->_transaction      = $transaction;
        $this->_invoiceSender    = $invoiceSender;
        $this->_resultJsonFactory = $resultJsonFactory;

        return parent::__construct($context);
    }

    /**
     * Create partial invoice
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute() {
        $resultJson = $this->_resultJsonFactory->create();
        $result = array();

        // Order ID to be invoiced partially
        $orderId = (int) $this->getRequest()->getParam('order_id', false);

        // Amount to invoice
        $amount  = (int) $this->getRequest()->getParam('amount', 0);

        try {
            if (!$orderId || !$amount) {
                $result['message'] = 'Order ID and amount are required!';
                $resultJson->setData($result);
                return $resultJson;
            }

            $order = $this->_orderRepository->get($orderId);

            // Make sure that the amount passed is not greater than the order grand total
            if ($order->getGrandTotal() < $amount) {
                $result['message'] = 'The invoice amount is greater than the order amount! Please try a lower value.';
                $resultJson->setData($result);
                return $resultJson;
            }

            // Do the partial invoice creation
            if ($order->canInvoice()) {
                $orderShippingAmount = $order->getShippingAmount();

                /** @var $item \Magento\Sales\Model\Order\Item */
                $item = $order->getAllItems()[0];

                // Invoice preparation
                $invoice = $this->_invoiceService->prepareInvoice($order, [$item->getId() => 0]);
                $invoice->setShippingAmount($orderShippingAmount);
                $invoice->setSubtotal($amount);
                $invoice->setBaseSubtotal($amount);
                $invoice->setGrandTotal($amount);
                $invoice->setBaseGrandTotal($amount);
                $invoice->register();

                // Create transaction and save
                $transaction = $this->_transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transaction->save();

                // Sends order invoice email to the customer
                $this->_invoiceSender->send($invoice);

                // Add comment to order status history and Notify customer
                $order->addStatusHistoryComment(__('Customer was notified about invoice #%1.', $invoice->getIncrementId()))
                    ->setIsCustomerNotified(true)
                    ->save();

                $result['message'] = 'Partial invoice was successfully created!';
                $resultJson->setData($result);
                return $resultJson;
            } else {
                $result['message'] = 'Error creating invoice!';
                $resultJson->setData($result);
                return $resultJson;
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $resultJson->setData($result);
            return $resultJson;
        }
    }
}