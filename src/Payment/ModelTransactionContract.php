<?php namespace Klb\Core\Payment;

/**
 * Interface ModelTransactionContract
 *
 * @package Klb\Core\Payment
 * @method save()
 */
interface ModelTransactionContract
{
    const STATUS_CLOSED = 'closed';
    const STATUS_PENDING = 'pending';
    const STATUS_FAILED = 'failed';
    const STATUS_DOWNLOADED = 'downloaded';

    /**
     * @param $transactionId
     * @return ModelTransactionContract
     */
    public function setTransId($transactionId);

    /**
     * @param $status
     * @return ModelTransactionContract
     */
    public function setTransStatus($status);

    /**
     * @param $postingDate
     * @return ModelTransactionContract
     */
    public function setPostingDate($postingDate);

    /**
     * @param $accountCode
     * @return ModelTransactionContract
     */
    public function setAccountCode($accountCode);
    /** SETTER */
    public function setAccountBankId($accountBankId);

    /**
     * @param $accountName
     * @return ModelTransactionContract
     */
    public function setAccountName($accountName);

    /**
     * @param $noRek
     * @return ModelTransactionContract
     */
    public function setAccountNoRekening($noRek);

    /**
     * @param $namaRek
     * @return ModelTransactionContract
     */
    public function setAccountNamaRekening($namaRek);

    /**
     * @param $bankAccountId
     * @return ModelTransactionContract
     */
    public function setBankAccountId($bankAccountId);

    /**
     * @param $cabangSort
     * @return ModelTransactionContract
     */
    public function setCabangSort($cabangSort);

    /**
     * @param $totalAmount
     * @return ModelTransactionContract
     */
    public function setTotalAmount($totalAmount);

    /**
     * @param $totalMutasi
     * @return ModelTransactionContract
     */
    public function setTotalMutasi($totalMutasi);

    /**
     * @param $closedDate
     * @return ModelTransactionContract
     */
    public function setClosedDate($closedDate);

    /**
     * @param $closedBy
     * @return ModelTransactionContract
     */
    public function setClosedBy($closedBy);

    /**
     * @param $remarks
     * @return ModelTransactionContract
     */
    public function setRemarks($remarks);

    /**
     * @param $realizationDate
     * @return ModelTransactionContract
     */
    public function setRealizationDate($realizationDate);

    /**
     * @param $mandatoryFields
     * @return ModelTransactionContract
     */
    public function setMandatoryFields($mandatoryFields);

    /**
     * @param $additionalInformation
     * @return ModelTransactionContract
     */
    public function setAdditionalInformation($additionalInformation);

    /**
     * @param $createdAt
     * @return ModelTransactionContract
     */
    public function setCreatedAt($createdAt);
    /** GETTER */
    public function getTransId();

    /**
     * @return mixed
     */
    public function getAccountBankId();

    /**
     * @return mixed
     */
    public function getPostingDate();

    /**
     * @return mixed
     */
    public function getAccountCode();

    /**
     * @return mixed
     */
    public function getAccountName();

    /**
     * @return mixed
     */
    public function getTransStatus();

    /**
     * @return mixed
     */
    public function getAccountNoRekening();

    /**
     * @return mixed
     */
    public function getAccountNamaRekening();

    /**
     * @return mixed
     */
    public function getBankAccountId();

    /**
     * @return mixed
     */
    public function getTotalAmount();

    /**
     * @return mixed
     */
    public function getTotalMutasi();

    /**
     * @return mixed
     */
    public function getClosedDate();

    /**
     * @return mixed
     */
    public function getClosedBy();

    /**
     * @return mixed
     */
    public function getRemarks();

    /**
     * @return mixed
     */
    public function getRealizationDate();

    /**
     * @return mixed
     */
    public function getMandatoryFields();

    /**
     * @return AdditionalInformationContract
     */
    public function getAdditionalInformation();

    /**
     * @return mixed
     */
    public function getCreatedAt();

    /**
     * @param $flag
     * @return ModelTransactionContract
     */
    public function setDownloadToBank($flag);

    /**
     * @return bool
     */
    public function isDownloadToBank();
}
