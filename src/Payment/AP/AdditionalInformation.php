<?php namespace Klb\Core\Payment\AP;


use Klb\Core\Payment\AdditionalInformationContract;

/**
 * Class AdditionalInformation
 *
 * @package Klb\Core\Payment\AR
 */
class AdditionalInformation implements AdditionalInformationContract
{
    private $email;
    private $stockist_code;
    private $nama_pemilik;
    private $telp;
    private $fax;
    private $bank_id;
    private $cabang_bank_id;
    private $no_rekening;
    private $nama_rekening;
    private $bank_branch;
    private $cabang_sort;
    private $propinsi_id;
    private $kota_id;
    private $kecamatan_id;
    private $ap_bank_account_id;
    private $partner_type;
    private $cabang_name;
    private $bank;
    private $propinsi;
    private $kota;
    private $kecamatan;

    /**
     * AdditionalInformation constructor.
     *
     * @param array $additonal_information
     */
    public function __construct(array $additonal_information = null)
    {

        if(null !== $additonal_information){
            foreach ( $additonal_information as $information => $value ){
                $this->$information = $value;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return AdditionalInformationContract
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->stockist_code;
    }

    /**
     * @param mixed $code
     * @return AdditionalInformationContract
     */
    public function setCode($code)
    {
        $this->stockist_code = $code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNamaPemilik()
    {
        return $this->nama_pemilik;
    }

    /**
     * @param mixed $nama_pemilik
     * @return AdditionalInformationContract
     */
    public function setNamaPemilik($nama_pemilik)
    {
        $this->nama_pemilik = $nama_pemilik;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTelp()
    {
        return $this->telp;
    }

    /**
     * @param mixed $telp
     * @return AdditionalInformationContract
     */
    public function setTelp($telp)
    {
        $this->telp = $telp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param mixed $fax
     * @return AdditionalInformationContract
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankId()
    {
        return $this->bank_id;
    }

    /**
     * @param mixed $bank_id
     * @return AdditionalInformationContract
     */
    public function setBankId($bank_id)
    {
        $this->bank_id = $bank_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCabangBankId()
    {
        return $this->cabang_bank_id;
    }

    /**
     * @param mixed $cabang_bank_id
     * @return AdditionalInformationContract
     */
    public function setCabangBankId($cabang_bank_id)
    {
        $this->cabang_bank_id = $cabang_bank_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoRekening()
    {
        return $this->no_rekening;
    }

    /**
     * @param mixed $no_rekening
     * @return AdditionalInformationContract
     */
    public function setNoRekening($no_rekening)
    {
        $this->no_rekening = $no_rekening;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNamaRekening()
    {
        return $this->nama_rekening;
    }

    /**
     * @param mixed $nama_rekening
     * @return AdditionalInformationContract
     */
    public function setNamaRekening($nama_rekening)
    {
        $this->nama_rekening = $nama_rekening;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankBranch()
    {
        return $this->bank_branch;
    }

    /**
     * @param mixed $bank_branch
     * @return AdditionalInformationContract
     */
    public function setBankBranch($bank_branch)
    {
        $this->bank_branch = $bank_branch;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCabangSort()
    {
        return $this->cabang_sort;
    }

    /**
     * @param mixed $cabang_sort
     * @return AdditionalInformationContract
     */
    public function setCabangSort($cabang_sort)
    {
        $this->cabang_sort = $cabang_sort;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPropinsiId()
    {
        return $this->propinsi_id;
    }

    /**
     * @param mixed $propinsi_id
     * @return AdditionalInformationContract
     */
    public function setPropinsiId($propinsi_id)
    {
        $this->propinsi_id = $propinsi_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKotaId()
    {
        return $this->kota_id;
    }

    /**
     * @param mixed $kota_id
     * @return AdditionalInformationContract
     */
    public function setKotaId($kota_id)
    {
        $this->kota_id = $kota_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKecamatanId()
    {
        return $this->kecamatan_id;
    }

    /**
     * @param mixed $kecamatan_id
     * @return AdditionalInformationContract
     */
    public function setKecamatanId($kecamatan_id)
    {
        $this->kecamatan_id = $kecamatan_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransferBankAccountId()
    {
        return $this->ap_bank_account_id;
    }

    /**
     * @param mixed $ap_bank_account_id
     * @return AdditionalInformationContract
     */
    public function setTransferBankAccountId($ap_bank_account_id)
    {
        $this->ap_bank_account_id = $ap_bank_account_id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPartnerType()
    {
        return $this->partner_type;
    }

    /**
     * @param mixed $partner_type
     * @return AdditionalInformationContract
     */
    public function setPartnerType($partner_type)
    {
        $this->partner_type = $partner_type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCabangName()
    {
        return $this->cabang_name;
    }

    /**
     * @param mixed $cabang_name
     * @return AdditionalInformationContract
     */
    public function setCabangName($cabang_name)
    {
        $this->cabang_name = $cabang_name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param mixed $bank
     * @return AdditionalInformationContract
     */
    public function setBank($bank)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPropinsi()
    {
        return $this->propinsi;
    }

    /**
     * @param mixed $propinsi
     * @return AdditionalInformationContract
     */
    public function setPropinsi($propinsi)
    {
        $this->propinsi = $propinsi;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKota()
    {
        return $this->kota;
    }

    /**
     * @param mixed $kota
     * @return AdditionalInformationContract
     */
    public function setKota($kota)
    {
        $this->kota = $kota;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKecamatan()
    {
        return $this->kecamatan;
    }

    /**
     * @param mixed $kecamatan
     * @return AdditionalInformationContract
     */
    public function setKecamatan($kecamatan)
    {
        $this->kecamatan = $kecamatan;

        return $this;
    }
}
