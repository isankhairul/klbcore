<?php namespace Klb\Core\Payment\Transaction;
use Klb\Core\Payment\AbstractPaymentTransaction;
use Kalbe\Model\Finance\BankAccount;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

/**
 * Class OCBC
 * @package Klb\Core\Payment\Transaction
 * @property string $OrgIDVelocity
 * @property string $ProductType
 * @property string $CustomerRef
 * @property int $ValueDate
 * @property string $DebitAcctCcy
 * @property string $DebitAcctNo
 * @property string $CreditAcctCcy
 * @property string $CreditAcctNo
 * @property string $TransferCcy
 * @property double $TransferAmount
 * @property string $PaymentDetails
 * @property string $FxType
 * @property string $FxDealerName
 * @property string $FxDealRef1
 * @property double $FxDealAmt1
 * @property string $ReservedColumn1
 * @property double $ReservedColumn2
 * @property string $SwiftChargesMethod
 * @property string $SenderName
 * @property string $SenderAddr1
 * @property string $SenderAddr2
 * @property string $SenderAddr3
 * @property string $PayeeName
 * @property string $PayeeAddr1
 * @property string $PayeeAddr2
 * @property string $PayeeAddr3
 * @property string $BeneBankID
 * @property string $BeneBankNetworkID
 * @property string $BeneBankName
 * @property string $BeneBankBranchName
 * @property string $BeneBankCountryCode
 * @property string $ResidentStatus
 * @property string $RemittanceCountryOfResidence
 * @property string $RemitterCategory
 * @property string $BeneCountryOfResidence
 * @property string $BeneCategory
 * @property string $BeneAffiliationStatus
 * @property string $PaymentPurpose
 * @property string $DoubleCheck_CustRefNo
 * @property string $DoubleCheck_TrfAmtCcy
 * @property string $Add_FavPayment
 * @property string $Add_Template
 * @property string $Notf_Sender
 * @property string $Notf_Sender_Completed
 * @property string $Notf_Sender_Rejected
 * @property string $Notf_Sender_Suspected
 * @property string $Notf_Sender_ChannelType
 * @property string $Notf_Sender_DestID
 * @property string $Notf_Bene_Completed
 * @property string $Notf_Bene_ChannelType
 * @property string $Notf_Bene_DestID
 * @property string $Recr_On
 * @property string $Recr_On_Type
 * @property string $Recr_On_Type_Value
 * @property string $Recr_Date_Start
 * @property string $Recr_Date_End
 * @property string $SaveAsBeneficiary
 * @property string $BeneNickName
 * @property string $BeneEmailAddress
 * @property string $BenePhoneNumber
 *
 */
class OCBC extends AbstractPaymentTransaction{
    /**
     * @var array
     * key is column name
     * value [ 0 => is string/decimal length, 1 => is C/M/O (Conditional/Mandatory/Optional)
     */
    protected $rules = [
        'OrgIDVelocity' => [ 30, 'M' ],
        'ProductType' => [ 4, 'M' ],
        'CustomerRef' => [ 20, 'O' ],
        'ValueDate' => [ 8, 'M' ],
        'DebitAcctCcy' => [ 3, 'M' ],
        'DebitAcctNo' => [ 19, 'M' ],
        'CreditAcctCcy' => [ 3, 'M' ],
        'CreditAcctNo' => [ 19, 'M' ],
        'TransferCcy' => [ 3, 'C' ],
        'TransferAmount' => [ 18, 'M' ],
        'PaymentDetails' => [ 100, 'C' ],
        'FxType' => [ 1, 'C' ],
        'FxDealerName' => [ 35, 'C' ],
        'FxDealRef1' => [ 30, 'C' ],
        'FxDealAmt1' => [ 14, 'C' ],
        'ReservedColumn1' => [ 35, 'C' ],
        'ReservedColumn2' => [ 14, 'C' ],
        'SwiftChargesMethod' => [ 3, 'C' ],
        'SenderName' => [ 35, 'C' ],
        'SenderAddr1' => [ 100, 'C' ],
        'SenderAddr2' => [ 100, 'C' ],
        'SenderAddr3' => [ 100, 'C' ],
        'PayeeName' => [ 100, 'C' ],
        'PayeeAddr1' => [ 100, 'C' ],
        'PayeeAddr2' => [ 100, 'C' ],
        'PayeeAddr3' => [ 100, 'C' ],
        'BeneBankID' => [ 3, 'C' ],
        'BeneBankNetworkID' => [ 20, 'C' ],
        'BeneBankName' => [ 100, 'C' ],
        'BeneBankBranchName' => [ 50, 'C' ],
        'BeneBankCountryCode' => [ 2, 'C' ],
        'ResidentStatus' => [ 1, 'C' ],
        'RemittanceCountryOfResidence' => [ 2, 'C' ],
        'RemitterCategory' => [ 4, 'C' ],
        'BeneCountryOfResidence' => [ 2, 'C' ],
        'BeneCategory' => [ 4, 'C' ],
        'BeneAffiliationStatus' => [ 1, 'C' ],
        'PaymentPurpose' => [ 5, 'C' ],
        'DoubleCheck_CustRefNo' => [ 1, 'C' ],
        'DoubleCheck_TrfAmtCcy' => [ 1, 'C' ],
        'Add_FavPayment' => [ 1, 'C' ],
        'Add_Template' => [ 1, 'C' ],
        'Notf_Sender' => [ 1, 'O' ],
        'Notf_Sender_Completed' => [ 1, 'C' ],
        'Notf_Sender_Rejected' => [ 1, 'C' ],
        'Notf_Sender_Suspected' => [ 1, 'C' ],
        'Notf_Sender_ChannelType' => [ 5, 'C' ],
        'Notf_Sender_DestID' => [ 100, 'C' ],
        'Notf_Bene_Completed' => [ 1, 'O' ],
        'Notf_Bene_ChannelType' => [ 5, 'C' ],
        'Notf_Bene_DestID' => [ 100, 'C' ],
        'Recr_On' => [ 1, 'O' ],
        'Recr_On_Type' => [ 2, 'C' ],
        'Recr_On_Type_Value' => [ 2, 'C' ],
        'Recr_Date_Start' => [ 8, 'C' ],
        'Recr_Date_End' => [ 8, 'C' ],
        'SaveAsBeneficiary' => [ 1, 'O' ],
        'BeneNickName' => [ 40, 'C' ],
        'BeneEmailAddress' => [ 100, 'O' ],
        'BenePhoneNumber' => [ 20, 'O' ]
    ];

    /**
     * @inheritDoc
     */
    public static function getBankCode()
    {
        return '028';
    }
    /**
     * @inheritDoc
     */
    public function getPartnerTypeCode()
    {
        switch($this->account->getPartnerType()){
            case 'personal':
                return 'A0';
            case 'perusahaan':
                return 'E0';
        }
        return null;
    }
    /**
     *
     */
    private function _configureProductType(){
        /**
         * OAT - Own Fund Transfer
         * IFT - Internal Fund Transfer
         * LLG
         * RTGS
         * OT - Online Transfer
         * TT - Telegraphic Transfer
         */
        if(!$this->isOCBC()){
            $this->ProductType = 'LLG';
        } else {
            $this->ProductType = 'IFT';
        }
        switch($this->ProductType){
            case 'LLG':
            case 'TT':
            case 'RTGS':
            case 'OT':
                $this->conditions['SenderName'] = 'M';
                $this->conditions['SenderAddr1'] = 'M';
                $this->conditions['SenderAddr2'] = 'M';
                $this->conditions['SenderAddr3'] = 'M';
                $this->conditions['PayeeName'] = 'M';
                $this->conditions['PayeeAddr1'] = 'M';
                $this->conditions['PayeeAddr2'] = 'M';
                $this->conditions['PayeeAddr3'] = 'M';
                if(in_array($this->ProductType, [ 'LLG', 'TT' ])) {
                    $this->conditions['RemitterCategory'] = 'M';
                    $this->conditions['BeneCategory'] = 'M';
                } else if(in_array($this->ProductType, [ 'LLG', 'RGTS' ])) {
                    $this->conditions['BeneBankBranchName'] = 'M';
                } else if(in_array($this->ProductType, [ 'OT', 'LLG', 'RGTS' ])) {
                    $this->conditions['BeneBankID'] = 'M';
                } else if($this->ProductType !== 'OT') {
                    $this->conditions['BeneBankNetworkID'] = 'M';
                    $this->conditions['ResidentStatus'] = 'M';
                } else if($this->ProductType === 'TT') {
                    $this->conditions['BeneBankCountryCode'] = 'M';
                    $this->conditions['RemittanceCountryOfResidence'] = 'M';
                    $this->conditions['BeneCountryOfResidence'] = 'M';
                }
                $this->conditions['BeneBankName'] = 'M';

            break;

                break;
            case 'AA':
                $this->conditions['SwiftChargesMethod'] = 'M';
                break;
        }
    }

    private function _configureFx(){
        if($this->DebitAcctCcy !== $this->CreditAcctCcy){
            $this->FxType = 'S';
            $this->conditions['FxType'] = 'M';
            $this->conditions['FxDealRef1'] = 'M';
            $this->conditions['FxDealAmt1'] = 'M';
            $this->conditions['FxDealerName'] = 'M';
        } else {
            $this->FxType = '';
            $this->FxDealAmt1 = $this->_amount(0, 0, 7);
            $this->FxDealRef1 = '';
            $this->FxDealerName = '';
        }
    }

    /**
     *
     */
    private function _configureOther(){
        if($this->Notf_Sender){
            $this->conditions['Notf_Sender_Completed'] = 'M';
            $this->conditions['Notf_Sender_Rejected'] = 'M';
            $this->conditions['Notf_Sender_Suspected'] = 'M';
            $this->conditions['Notf_Sender_ChannelType'] = 'M';
            $this->conditions['Notf_Sender_DestID'] = 'M';
            $this->conditions['Notf_Bene_Completed'] = 'M';
            if($this->Notf_Bene_Completed) {
                $this->conditions['Notf_Bene_ChannelType'] = 'M';
                $this->conditions['Notf_Bene_DestID'] = 'M';
            }
        }

        if($this->Recr_On){
            $this->conditions['Recr_On_Type'] = 'M';
            $this->conditions['Recr_On_Type_Value'] = 'M';
            $this->conditions['Recr_Date_Start'] = 'M';
            $this->conditions['Recr_Date_End'] = 'M';
            $this->Recr_Date_Start = a_format_date($this->Recr_Date_Start);
            $this->Recr_Date_End = a_format_date($this->Recr_Date_End);
        }

        if($this->SaveAsBeneficiary){
            $this->conditions['BeneNickName'] = 'M';
        }
    }
    /**
     * @return bool
     */
    public function isOCBC(){
        return strval($this->transaction->getAccountBankId()) === strval(static::getBankCode());
    }

    /**
     * @return string
     */
    private function _getCustomerRef(){
        return 'PR'.date('Ymd', strtotime($this->transaction->getPostingDate())).'-'.date('Ymd', strtotime($this->transaction->getPostingDate() . ' -7 days'));
    }
    /**
     * @return void
     */
    private function _matrix(){
        $email = $this->account->getEmail();
        /** Product Type */
        $this->OrgIDVelocity = $this->bankAccount->org_velocity;
        $this->_configureProductType();
        $this->ValueDate = a_format_date($this->options['transfer_date_time']);
        $this->CustomerRef = $this->_getCustomerRef();
        $this->DebitAcctCcy = $this->currency;
        $this->DebitAcctNo = preg_replace('/\D/', '', $this->bankAccount->account_number); //Default Rek
        $this->CreditAcctCcy = $this->currency;
        $this->CreditAcctNo = preg_replace('/\D/', '', $this->transaction->getAccountNoRekening());
        $this->TransferCcy = $this->currency;
        $this->TransferAmount = $this->_amountNoUnsign($this->transaction->getTotalAmount(), 'TransferAmount', 2);
//        $this->PaymentDetails = $this->ProductType === 'OT' ? '' : '';
        $this->PaymentDetails = $this->ProductType . '-' . $this->transaction->getTransId();
        $this->_configureFx();
        $this->ReservedColumn1 = '';
        $this->ReservedColumn2 = '0.0000000';
        /** @var string Check SwiftChargesMethod */
        $this->SwiftChargesMethod = '';

//        $this->SenderName = $this->bankAccount->account_number;
//        Request Sofie pantek
        $this->SenderName = 'KARYA HASTA DINAMIKA';
        $this->SenderAddr1 = $this->bankAccount->Propinsi->propinsi_name;
        $this->SenderAddr2 = $this->bankAccount->Kota->kota_name;
        $this->SenderAddr3 = $this->bankAccount->Kecamatan->kecamatan_name;
        /*--- PAYEE ---*/
        $this->PayeeName = $this->transaction->getAccountNamaRekening();
        $this->PayeeAddr1 = $this->account->getPropinsi();
        $this->PayeeAddr2 = $this->account->getKota();
        $this->PayeeAddr3 = $this->account->getKecamatan();
        /** ---  */
        $this->BeneBankID = $this->transaction->getAccountBankId();
        $this->BeneBankNetworkID = $this->getBeneBank()->bank_network_id;
        $this->BeneBankName = $this->getBeneBank()->bank_title;
        $this->BeneBankBranchName = $this->account->getBankBranch() ?: "";
        $this->BeneBankCountryCode = 'ID';
        $this->ResidentStatus = 'Y';
        $this->RemittanceCountryOfResidence = 'ID';
        $this->RemitterCategory = 'E0';
        $this->BeneCountryOfResidence = 'ID';
        $this->BeneCategory = $this->getPartnerTypeCode();
        $this->BeneAffiliationStatus = 'N';
        $this->PaymentPurpose = '';
        $this->DoubleCheck_CustRefNo = '1';
        $this->DoubleCheck_TrfAmtCcy = '0';
        $this->Add_FavPayment = '0';
        $this->Add_Template = '0';
        $this->Notf_Sender = '1';
        $this->Notf_Sender_Completed = '1';
        $this->Notf_Sender_Rejected = '1';
        $this->Notf_Sender_Suspected = '1';
        $this->Notf_Sender_ChannelType = 'EMAIL';
        $this->Notf_Sender_DestID = join(',', $this->emailDev);
        $this->Notf_Bene_Completed = '1';
        $this->Notf_Bene_ChannelType = 'EMAIL';
        $this->Notf_Bene_DestID = $email;
        $this->Recr_On = '0';
        $this->Recr_On_Type = '';
        $this->Recr_On_Type_Value = '';
        $this->Recr_Date_Start = '';
        $this->Recr_Date_End = '';
        $this->SaveAsBeneficiary = 1;
        $this->BeneNickName = $this->transaction->getAccountCode();
        $this->BeneEmailAddress = $email;
        $this->BenePhoneNumber = $this->account->getTelp();
        $this->_configureOther();
    }

    /**
     * @inheritdoc
     */
    public function process(){
        /**
         * Check if payment transaction was not set correctly
         */
        if(null === $this->transaction){
            throw new \InvalidArgumentException('Invalid argument for payment transaction');
        }
        if(null === $this->account){
            throw new \InvalidArgumentException('Invalid argument for partner');
        }
        /**
         * Calculate matrix format for bank
         */
        $this->_matrix();
        /**
         * Validate the value and check mandatory variable
         */
        $errors = $this->_validate();
        if(count($errors) > 0){
            throw new \InvalidArgumentException(join(", ", $errors));
        }

        $this->_pad();

        return $this;
    }

    public function getRow()
    {
        return join('', $this->row);

    }

    /**
     * @inheritDoc
     */
    public static function exportToTxt(RequestInterface $request, ResponseInterface $response, $payments, BankAccount $bankAccount, array $options = [])
    {
        $options['period'] = $request->getQuery('period');
        $options['periodPrefix'] = date('Ymd', strtotime($options['period']));
        $options['transfer_date'] = $request->getQuery('transfer_date');
        $options['multiple'] = 'TransferType';
        $options['exportToBank'] = true;
        list($result, ) = static::export($payments, $bankAccount, $options);

        $filename = $options['periodPrefix'].'_payment.txt';
        $contentType = 'text/plain';
        $response->setHeader("Content-Type", $contentType);
        $response->setHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        return $response->setContent($result)->send();
    }
}
