<?php namespace Klb\Core\Payment\Transaction;

use Klb\Core\Payment\AbstractPaymentTransaction;
use Klb\Core\Payment\PaymentTransactionContract;
use Kalbe\Model\Finance\BankAccount;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use ZipArchive;

/**
 * Class BCA
 *
 * @package Klb\Core\Payment\Transaction
 * @property string $RecordType
 * @property string $CreditAccount
 * @property string $CreditedAmount
 * @property string $CreditedAmountName
 * @property string $TransferType
 * @property string $BICode
 * @property string $BankName
 * @property string $ReceiverBankBranch
 * @property string $Remark1
 * @property string $Remark2
 * @property string $ReceiverCustType
 * @property string $ReceiverCustResidence
 * @property string $BankCode
 */
class BCA extends AbstractPaymentTransaction
{
    /**
     * @var int
     *
     */
    protected static $max = 256;
    protected static $remark1 = null;
    protected static $remark2 = null;
    /**
     * @var array
     */
    protected $headerLabel = [];
    /**
     * @var array
     */
    protected $headerFields = [];
    /**
     * @var array
     */
    protected $headerFieldKey = [];
    /**
     * @var array
     */
    protected $headerFieldLength = [];
    /**
     * @var array
     */
    protected $headerConditions = [];
    /**
     * @var array
     * key is column name
     * value [ 0 => is string/decimal length, 1 => is C/M/O (Conditional/Mandatory/Optional) ]
     */
    protected $rules = [
        /* 1 */
        'RecordType'            => [ 1, 'M', 'RECORD-TYPE', 1 ], //1 =Detail
        /* 2 */
        'CreditAccount'         => [ 34, 'M', 'Credited Account' ],
        /* 3 */
        'Filler_1'              => [ 18, 'M', 'Filler 1' ],
        /* 4 */
        'CreditedAmount'        => [ 20, 'M', 'Credited Amount' ], //Account to be credited
        /* 5 */
        'CreditedAmountName'    => [ 70, 'O', 'Credited Amount Name' ], //Receiving Account Name (mandatory for LLG / RTGS)
        /* 6 */
        'TransferType'          => [ 6, 'M', 'Transfer Type' ], // BCA = B / LLG= N / RTGS = Y
        /* 7 */
        'Filler_2'              => [ 1, 'M', 'Filler 2' ],
        /* 8 */
        'BICode'                => [ 7, 'M', 'BI CODE' ],
        /* 9 */
        'Filler_3'              => [ 4, 'M', 'Filler 3' ],
        /*10 */
        'BankName'              => [ 18, 'M', 'Bank Name' ],
        /*11 */
        'ReceiverBankBranch'    => [ 18, 'O', 'RECEIVER-BANK-BRANCH' ],
        /*12 */
        'Remark1'               => [ 18, 'O', 'TRANSACTION-REMARK-1' ],
        /*13 */
        'Remark2'               => [ 18, 'O', 'TRANSACTION-REMARK-2' ],
        /*14 */
        'Filler_4'              => [ 18, 'M', 'Filler 4' ],
        /*15 */
        'ReceiverCustType'      => [ 1, 'M', 'Receiver Cust Type' ],
        /*16 */
        'ReceiverCustResidence' => [ 1, 'M', 'Receiver Cust Residence', 'R' ], //Status Penduduk Nasabah Penerima ( R = penduduk , N = bukan penduduk) - (Non BCA only)
        /*18 */
        'BankCode'              => [ 3, 'M', 'Bank ID' ],
    ];
    /**
     * @var array
     */
    protected $headers = [
        /* 1 */
        'RecordType'      => [ 1, 'M', 'RECORD-TYPE', 0 ],
        /* 2 */
        'TransactionType' => [ 2, 'M', 'TRANSACTION-TYPE', 'SP' ],
        /* 3 */
        'EffectiveDate'   => [ 8, 'M', 'EFFECTIVE-DATE' ],
        /* 4 */
        'CompanyAccount'  => [ 10, 'M', 'COMPANY-ACCOUNT' ],
        /* 5 */
        'Filler_1'        => [ 1, 'M', 'Filler 1', ' ' ],
        /* 6 */
        'CompanyCode'     => [ 8, 'M', 'COMPANY-CODE' ],
        /* 7 */
        'Filler_2'        => [ 15, 'M', 'Filler 2', ' ' ],
        /* 8 */
        'TotalAmount'     => [ 20, 'M', 'TOTAL-AMOUNT' ],
        /* 9 */
        'TotalRecord'     => [ 5, 'M', 'TOTAL-RECORD' ],
        /*10 */
        'TransferType'    => [ 3, 'M', 'Transfer type' ],
        /*11 */
        'Filler_3'        => [ 15, 'M', 'Filler 3', ' ' ],
        /*12 */
        'Remark1'         => [ 18, 'O', 'REMARK-1' ],
        /*13 */
        'Remark2'         => [ 18, 'O', 'REMARK-2' ],
        /*14 */
        'Filler_4'        => [ 132, 'M', 'Filler 4', ' ' ],
    ];

    protected function init()
    {
        foreach ($this->headers as $field => $value) {
            $this->headerFieldKey[] = $field;
            $this->headerFieldLength[$field] = $value[0];
            $this->headerConditions[$field] = $value[1];
            if (strpos($field, 'Filler_') !== false) {
                $this->headerFields[$field] = str_repeat(' ', $value[0]);
            } else {
                $this->headerFields[$field] = null;
            }

            if (isset($value[2])) {
                $this->headerLabel[$field] = $value[2];
            } else {
                $this->headerLabel[$field] = $field;
            }
            /**
             * Default Value If Defined
             */
            if (isset($value[3])) {
                $this->headerFields[$field] = $value[3];
            }
        }
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return [
            'fields'     => $this->headerFields,
            'label'      => $this->headerLabel,
            'length'     => $this->headerFieldLength,
            'conditions' => $this->headerConditions,
            'keys'       => $this->headerFieldKey,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getBankCode()
    {
        return '014';
    }

    /**
     * @inheritDoc
     */
    public function getPartnerTypeCode()
    {
        switch ($this->account->getPartnerType()) {
            case 'personal':
                return 1;
            case 'perusahaan':
                return 2;
        }

        return null;
    }

    /**
     *
     */
    protected function _configureProductType()
    {
        /**
         * OAT - Own Fund Transfer
         * IFT - Internal Fund Transfer
         * LLG
         * RTGS
         * OT - Online Transfer
         * TT - Telegraphic Transfer
         */
        if (!$this->isBCA()) {
            $this->conditions['CreditedAmountName'] = 'M';
            $this->TransferType = 'LLG';
            $this->BankCode = '888';
            $this->headerFields['TransactionType'] = 'MP';
        } else {
//            $this->ReceiverCustResidence = "";
            $this->ReceiverCustResidence = "N";//Request pak daniel 2018-06-08
            $this->conditions['ReceiverCustResidence'] = 'O';
            $this->TransferType = 'BCA';
            $this->BankCode = self::getBankCode();
            $this->headerFields['TransactionType'] = 'SP';
        }
    }

    /**
     * @return bool
     */
    public function isBCA()
    {
        return strval($this->transaction->getAccountBankId()) === strval(static::getBankCode());
    }

    /**
     * @return void
     */
    protected function _matrix()
    {
        /** Product Type */
        $this->_configureProductType();
        $beneBank = $this->getBeneBank();
//        $this->TransferDate = a_format_date($this->options['transfer_date_time'], 'd/m/Y');
        $this->CreditAccount = preg_replace('/\D/', '', $this->transaction->getAccountNoRekening());
        $this->CreditedAmount = $this->_amount(trim($this->transaction->getTotalAmount()), 'CreditedAmount', 2);
        /*--- PAYEE ---*/
        $this->CreditedAmountName = $this->transaction->getAccountNamaRekening();
        $this->ReceiverCustType = $this->getPartnerTypeCode();
        $this->ReceiverBankBranch = $this->account->getBankBranch() ?: "";
        $this->BICode = $beneBank->sandi_bi;
        $this->BankName = $beneBank->bank_short_name;
        $this->Remark1 = static::_remark1($this->transaction->getPostingDate());
        $this->Remark2 = $this->transaction->getAccountName();
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        /**
         * Check if payment transaction was not set correctly
         */
        if (null === $this->transaction) {
            throw new \InvalidArgumentException('Invalid argument for payment transaction');
        }
        if (null === $this->account) {
            throw new \InvalidArgumentException('Invalid argument for partner');
        }
        /**
         * Calculate matrix format for bank
         */
        $this->_matrix();
        /**
         *
         */
        $this->_header();
        /**
         * Validate the value and check mandatory variable
         */
        $errors = $this->_validate();
        if (count($errors) > 0) {
            throw new \InvalidArgumentException(join(", ", $errors));
        }

        $this->_pad();

        return $this;
    }

    /**
     * @return string
     */
    protected function _header()
    {
        $bankAccount = $this->bankAccount;
        $period = $this->options['period'];
        $transferDate = $this->options['transfer_date'];
        if ($bankAccount->account_number && $bankAccount->company_code) {
            $periodTime = strtotime($period);
            $periodPrefix = date('Ymd', $periodTime);
            $this->headerFields['TransferType'] = $this->TransferType;
//            $this->headerFields['EffectiveDate'] = $periodPrefix;
            $this->headerFields['EffectiveDate'] = date('Ymd', strtotime($transferDate));
            $this->headerFields['CompanyAccount'] = $bankAccount->account_number;
            $this->headerFields['CompanyCode'] = $bankAccount->company_code;
            $this->headerFields['Remark1'] = str_pad(static::_remark1($period), $this->headerFieldLength['Remark1'], ' ', STR_PAD_RIGHT);
            $this->headerFields['Remark2'] = str_pad(static::_remark2($period), $this->headerFieldLength['Remark2'], ' ', STR_PAD_RIGHT);

        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public static function newRow(PaymentTransactionContract $provider = null, array $results = [])
    {
        $rows = [];
        if ($provider instanceof BCA) {
            $headers = $provider->getHeader();
            $headers['fields']['TotalAmount'] = str_pad(number_format($results['sum'], 2, '.', ''), $headers['length']['TotalAmount'], '0', STR_PAD_LEFT);
            $headers['fields']['TotalRecord'] = str_pad($results['total_success'], $headers['length']['TotalRecord'], '0', STR_PAD_LEFT);
            $irows = "";
            $checkLength = [];
            foreach ($headers['fields'] as $field => $value) {
                $checkLength[$field] = txt_pad($headers['length'], $value, $field);
                $irows .= txt_pad($headers['length'], $value, $field);
            }

            return [ $irows ];
        }

        return $rows;
    }


    /**
     * @param $period
     * @return string
     */
    protected static function _remark1($period)
    {
        if (null !== static::$remark1) {
            return static::$remark1;
        }
        static::$remark1 = 'KALBESTORE';

        return static::$remark1;
    }

    protected static function _remark2($period)
    {
        if (null !== static::$remark2) {
            return static::$remark2;
        }
        $periodTime = strtotime($period);
        $lastWeekTime = strtotime('-7 days', $periodTime);
        static::$remark2 = sprintf('%s BCA', date('d', $lastWeekTime) . '-' . date('d M Y', $periodTime));

        return static::$remark2;
    }

    /**
     * @inheritDoc
     */
    public static function exportToTxt(RequestInterface $request, ResponseInterface $response, $payments, BankAccount $bankAccount, array $options = [])
    {
        $options['period'] = $request->getQuery('period');
        $periodPrefix = date('Ymd', strtotime($options['period']));
        $options['transfer_date'] = $request->getQuery('transfer_date');
        $options['multiple'] = 'TransferType';
        $options['exportToBank'] = true;
        $rows = static::export($payments, $bankAccount, $options);
        $files = [];
        foreach ($rows as $type => $row) {
            $ltype = strtolower($type);
            $files[] = [
                $periodPrefix . '_' . $ltype . '_payment.txt'           => $row[0],
                $periodPrefix . '_' . $ltype . '_check_sum_payment.txt' => $row[1]['sum'],
            ];
        }
        $zipFilename = tempnam(sys_get_temp_dir(), $periodPrefix . '_payment');
        $filename = $periodPrefix . '_payment.zip';
        $contentType = 'application/zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFilename, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Cannot open <$zipFilename>\n");
        }
        foreach ($files as $file) {
            foreach ($file as $iFile => $content) {
                #add it to the zip
                $zip->addFromString($iFile, $content);
            }
        }
        $zip->close();
        $response->setHeader("Content-Type", $contentType);
        $response->setHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');

        return $response->setFileToSend($zipFilename, $filename)->send();
    }
}
