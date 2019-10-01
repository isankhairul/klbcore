<?php namespace Klb\Core\Payment;

use function get_class;
use Kalbe\Model\Finance\Bank;
use Kalbe\Model\Finance\BankAccount;

/**
 * Class PaymentTransaction
 *
 * @package Klb\Core\Payment
 */
abstract class AbstractPaymentTransaction implements \Serializable, PaymentTransactionContract
{
    /**
     * @var string
     */
    protected $currency = 'IDR';
    /**
     * @var array
     */
    protected $row = [];
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var AdditionalInformationContract
     */
    protected $account;
    /**
     * @var \Kalbe\Model\Finance\BankAccount
     */
    protected $bankAccount;
    /**
     * @var \Kalbe\Model\Finance\BankAccount
     */
    protected $beneBank;
    /**
     * @var array
     */
    protected $emailDev = [];
    /**
     * @var array
     */
    protected $label = [];
    /**
     * @var array
     */
    protected $fields = [];
    /**
     * @var array
     */
    protected $fieldKey = [];
    /**
     * @var array
     */
    protected $fieldLength = [];
    /**
     * @var array
     */
    protected $conditions = [];
    /**
     * @var ModelTransactionContract
     */
    protected $transaction;

    /**
     * AbstractPaymentTransaction constructor.
     *
     * @param ModelTransactionContract|null $model
     */
    public function __construct(ModelTransactionContract $model = null)
    {
        if (null !== $model) {
            $this->setModelTransaction($model);
        }
        foreach ($this->rules as $field => $value) {
            $this->fieldKey[] = $field;
            $this->fieldLength[$field] = $value[0];
            $this->conditions[$field] = $value[1];
            if (strpos($field, 'Filler_') !== false) {
                $this->fields[$field] = str_repeat(' ', $value[0]);
            } else {
                $this->fields[$field] = null;
            }

            if (isset($value[2])) {
                $this->label[$field] = $value[2];
            } else {
                $this->label[$field] = $field;
            }
            /**
             * Default Value If Defined
             */
            if (isset($value[3])) {
                $this->fields[$field] = $value[3];
            }
        }

        $this->emailDev = (array)di()->get('config')->payment->notif_email;

        $this->init();
    }

    protected function init()
    {
    }

    /**
     * @return ModelTransactionContract
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param ModelTransactionContract $transaction
     * @return AbstractPaymentTransaction
     */
    public function setModelTransaction(ModelTransactionContract $transaction)
    {
        $this->transaction = $transaction;
        $this->account = $transaction->getAdditionalInformation();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        if(\array_key_exists('exportToBank', $options)){
            $this->transaction->setDownloadToBank($options['exportToBank']);
        }

        return $this;
    }


    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function getRule()
    {
        return array_map(function ($v) {
            return $v[1];
        }, $this->fields);
    }

    /**
     * @return Bank
     */
    protected function getBeneBank()
    {
        if (!empty($this->account->bank)) {
            $this->beneBank = $this->account->bank;
        }
        if (!is_object($this->beneBank) && !empty($this->transaction->getAccountBankId())) {
            $this->beneBank = Bank::findFirst($this->transaction->getAccountBankId());
        }

        return $this->beneBank;
    }

    /**
     * @return string
     */
    public function getRow()
    {
        return join('', $this->row);

    }

    /**
     * @param $amount
     * @param $field
     * @param int $digit
     * @return string
     */
    protected function _amount($amount, $field, $digit = 2)
    {
        $pad_length = isset($this->fieldLength[$field]) ? $this->fieldLength[$field] : $field;
        $amount = number_format($amount, $digit, '.', '');

        return str_pad($amount, $pad_length, '0', STR_PAD_LEFT);
    }

    /**
     * @param $amount
     * @param $field
     * @param int $digit
     * @return string
     */
    protected function _amountNoUnsign($amount, $field, $digit = 2)
    {
        $pad_length = isset($this->fieldLength[$field]) ? $this->fieldLength[$field] : $field;
        $amount = number_format($amount, $digit, '.', '');

        return str_pad($amount, $pad_length, ' ', STR_PAD_RIGHT);
    }

    /**
     * @return array
     */
    protected function _validate()
    {
        $errors = [];
        foreach ($this->conditions as $field => $condition) {
            if ($condition === 'M' && strval($this->__get($field)) === '') {
                $errors[$field] = sprintf('Mandatory variable key "%s" is required', $field);
            }
        }

        return $errors;
    }

    /**
     *
     */
    protected function _pad()
    {
        $this->row = [];
        foreach ($this->fields as $field => $value) {
            $this->row[] = txt_pad($this->fieldLength, $value, $field);
        }
    }


    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        if ($this->__isset($name)) {
            unset($this->fields[$name]);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if ($this->__isset($name)) {
            $value = strval($value);
            if (strlen($value) > $this->fieldLength[$name]) {
                $value = substr($value, 0, $this->fieldLength[$name]);
            }
            $this->fields[$name] = $value;
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->__isset($name) ? $this->fields[$name] : "";
    }

    public function toArray()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return $this->fields;
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize($serialized)
    {
        return unserialize($serialized);
    }

    /**
     * @param ModelTransactionContract $transaction
     * @return AbstractPaymentTransaction
     */
    public static function newInstance(ModelTransactionContract $transaction)
    {
        return new static($transaction);
    }

    /**
     * @param PaymentTransactionContract|null $provider
     * @param array $results
     * @return array
     */
    public static function newRow(PaymentTransactionContract $provider = null, array $results = [])
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function export($payments, BankAccount $bankAccount, array $options = [])
    {
        $rows = [];

        if (empty($options['transfer_date'])) {
            $options['transfer_date'] = date('m/d/Y');
        }

        $options['transfer_date_time'] = strtotime($options['transfer_date']);
        /** @var ModelTransactionContract $payment */
        if (!empty($options['csv_format'])) {
            $header = [];
            foreach ($payments as $payment) {

                try {
                    $rows[] = static::newInstance($payment)->setBankAccount($bankAccount)->setOptions($options)->process()->toArray();
                    if ($header === []) {
                        $header = array_keys($rows[0]);
                        array_unshift($rows, $header);
                    }
                    if ($payment->getMandatoryFields()) {
                        $payment->setMandatoryFields(null);
                        $payment->save();
                    }
                } catch (\Exception $e) {
                    $payment->setMandatoryFields($e->getMessage());
                    $payment->save();
                }
            }

            return [ $rows ];
        }
        $response = [
            'sum'           => 0,
            'total'         => 0,
            'total_failed'  => 0,
            'total_success' => 0,
            'errors'        => [],
        ];

        if (!empty($options['multiple'])) {
            $transferTypes = []; //LLG or other
            $providers = [];
            foreach ($payments as $payment) {
                $oldStatus = $payment->getTransStatus();
                try {
                    $provider = static::newInstance($payment)->setBankAccount($bankAccount)->setOptions($options)->process();
                    $fields = $provider->toArray();
                    if (!array_key_exists($options['multiple'], $fields)) {
                        continue;
                    }
                    $transferType = $fields[$options['multiple']];
                    if (!isset($transferTypes[$transferType])) {
                        $transferTypes[$transferType] = [
                            'rows'     => [],
                            'response' => $response,
                        ];
                        $providers[$transferType] = $provider;
                    }

                    $transferTypes[$transferType]['rows'][] = $provider->getRow();
                    $transferTypes[$transferType]['response']['total']++;

                    if ($payment->getMandatoryFields()) {
                        $payment->setMandatoryFields(null);
                    }
                    $payment->setTransStatus(ModelTransactionContract::STATUS_DOWNLOADED);
                    if (false !== $payment->save()) {
                        $transferTypes[$transferType]['response']['sum'] += $payment->getTotalAmount();
                        $transferTypes[$transferType]['response']['total_success']++;
                        if (ModelTransactionContract::STATUS_DOWNLOADED !== $oldStatus) {
                            static::getEventManager()->fire('payment:downloaded', null, [
                                'class'  => get_class($payment),
                                'id'     => $payment->getTransId(),
                                'by'     => isset($options['by']) ? $options['by'] : null,
                                'status' => ModelTransactionContract::STATUS_DOWNLOADED,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $payment->setMandatoryFields($e->getMessage());
                    $payment->save();
                }
            }
            $results = [];
            foreach ($transferTypes as $type => $values) {
                $newRow = static::newRow($providers[$type], $values['response']);
                if (count($newRow) > 0) {
                    array_unshift($values['rows'], $newRow[0]);
                }
                $results[$type] = [ join("\r\n", $values['rows']) . "\r\n", $values['response'] ];
            }

            return $results;
        }

        $rows = static::newRow();
        foreach ($payments as $payment) {
            $oldStatus = $payment->getTransStatus();
            $response['total']++;
            try {
                $rows[] = static::newInstance($payment)->setBankAccount($bankAccount)->setOptions($options)->process()->getRow();

                if ($payment->getMandatoryFields()) {
                    $payment->setMandatoryFields(null);
                }
                $payment->setTransStatus(ModelTransactionContract::STATUS_DOWNLOADED);
                if (false !== $payment->save()) {
                    $response['sum'] += $payment->getTotalAmount();
                    $response['total_success']++;
                    if (ModelTransactionContract::STATUS_DOWNLOADED !== $oldStatus) {
                        static::getEventManager()->fire('payment:downloaded', null, [
                            'class'  => get_class($payment),
                            'id'     => $payment->getTransId(),
                            'by'     => isset($options['by']) ? $options['by'] : null,
                            'status' => ModelTransactionContract::STATUS_DOWNLOADED,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $response['total_failed']++;
                $response['errors'][$payment->getTransId()] = $e->getMessage();
                $payment->setMandatoryFields($e->getMessage());
                $payment->save();
            }
        }

        return [ join("\r\n", $rows) . "\r\n", $response ];
    }

    /**
     * @return \Phalcon\Events\ManagerInterface
     */
    protected static function getEventManager()
    {
        return di('dispatcher')->getEventsManager();
    }
}
