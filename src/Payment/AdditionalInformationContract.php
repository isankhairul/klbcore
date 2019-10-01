<?php namespace Klb\Core\Payment;

interface AdditionalInformationContract {

    /**
     * @return mixed
     */
    public function getEmail();

    /**
     * @param mixed $email
     * @return AdditionalInformationContract
     */
    public function setEmail($email);

    /**
     * @return mixed
     */
    public function getCode();

    /**
     * @param mixed $code
     * @return AdditionalInformationContract
     */
    public function setCode($code);

    /**
     * @return mixed
     */
    public function getNamaPemilik();

    /**
     * @param mixed $nama_pemilik
     * @return AdditionalInformationContract
     */
    public function setNamaPemilik($nama_pemilik);

    /**
     * @return mixed
     */
    public function getTelp();

    /**
     * @param mixed $telp
     * @return AdditionalInformationContract
     */
    public function setTelp($telp);

    /**
     * @return mixed
     */
    public function getFax();

    /**
     * @param mixed $fax
     * @return AdditionalInformationContract
     */
    public function setFax($fax);

    /**
     * @return mixed
     */
    public function getBankId();

    /**
     * @param mixed $bank_id
     * @return AdditionalInformationContract
     */
    public function setBankId($bank_id);

    /**
     * @return mixed
     */
    public function getCabangBankId();

    /**
     * @param mixed $cabang_bank_id
     * @return AdditionalInformationContract
     */
    public function setCabangBankId($cabang_bank_id);

    /**
     * @return mixed
     */
    public function getNoRekening();

    /**
     * @param mixed $no_rekening
     * @return AdditionalInformationContract
     */
    public function setNoRekening($no_rekening);

    /**
     * @return mixed
     */
    public function getNamaRekening();

    /**
     * @param mixed $nama_rekening
     * @return AdditionalInformationContract
     */
    public function setNamaRekening($nama_rekening);

    /**
     * @return mixed
     */
    public function getBankBranch();

    /**
     * @param mixed $bank_branch
     * @return AdditionalInformationContract
     */
    public function setBankBranch($bank_branch);

    /**
     * @return mixed
     */
    public function getCabangSort();

    /**
     * @param mixed $cabang_sort
     * @return AdditionalInformationContract
     */
    public function setCabangSort($cabang_sort);

    /**
     * @return mixed
     */
    public function getPropinsiId();

    /**
     * @param mixed $propinsi_id
     * @return AdditionalInformationContract
     */
    public function setPropinsiId($propinsi_id);

    /**
     * @return mixed
     */
    public function getKotaId();

    /**
     * @param mixed $kota_id
     * @return AdditionalInformationContract
     */
    public function setKotaId($kota_id);

    /**
     * @return mixed
     */
    public function getKecamatanId();

    /**
     * @param mixed $kecamatan_id
     * @return AdditionalInformationContract
     */
    public function setKecamatanId($kecamatan_id);

    /**
     * @return mixed
     */
    public function getTransferBankAccountId();

    /**
     * @param mixed $transfer_bank_account_id
     * @return AdditionalInformationContract
     */
    public function setTransferBankAccountId($transfer_bank_account_id);

    /**
     * @return mixed
     */
    public function getPartnerType();

    /**
     * @param mixed $partner_type
     * @return AdditionalInformationContract
     */
    public function setPartnerType($partner_type);

    /**
     * @return mixed
     */
    public function getCabangName();

    /**
     * @param mixed $cabang_name
     * @return AdditionalInformationContract
     */
    public function setCabangName($cabang_name);
    /**
     * @return mixed
     */
    public function getBank();

    /**
     * @param mixed $bank
     * @return AdditionalInformationContract
     */
    public function setBank($bank);

    /**
     * @return mixed
     */
    public function getPropinsi();

    /**
     * @param mixed $propinsi
     * @return AdditionalInformationContract
     */
    public function setPropinsi($propinsi);

    /**
     * @return mixed
     */
    public function getKota();

    /**
     * @param mixed $kota
     * @return AdditionalInformationContract
     */
    public function setKota($kota);

    /**
     * @return mixed
     */
    public function getKecamatan();

    /**
     * @param mixed $kecamatan
     * @return AdditionalInformationContract
     */
    public function setKecamatan($kecamatan);
}
