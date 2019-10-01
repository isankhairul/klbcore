<?php namespace Klb\Core\Payment;
use Kalbe\Model\Finance\BankAccount;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Model\ResultInterface;

/**
 * Interface PaymentTransactionContract
 * @package Klb\Core\Payment
 */
interface PaymentTransactionContract {
    /**
     * @param BankAccount $bankAccount
     * @return PaymentTransactionContract
     */
    public function setBankAccount(BankAccount $bankAccount);

    /**
     * @param array $options
     * @return PaymentTransactionContract
     */
    public function setOptions(array $options);
    /**
     * @return PaymentTransactionContract
     */
    public function process();

    /**
     * @return mixed
     */
    public function getRow();

    /**
     * @return mixed
     */
    public static function getBankCode();

    /**
     * @return mixed
     */
    public function getPartnerTypeCode();

    /**
     * @param ModelTransactionContract[]|ResultInterface[] $payments
     * @param BankAccount $bankAccount
     * @param array $options
     * @return array
     */
    public static function export($payments, BankAccount $bankAccount, array $options = []);

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param $payments
     * @param BankAccount $bankAccount
     * @param array $options
     * @return ResponseInterface
     */
    public static function exportToTxt(RequestInterface $request, ResponseInterface $response, $payments, BankAccount $bankAccount, array $options = []);

    /**
     * @return array
     */
    public function toArray();
}
