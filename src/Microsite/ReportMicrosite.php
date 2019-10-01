<?php namespace Klb\Core\Microsite;


use Phalcon\DiInterface;
use Phalcon\Http\Client\Provider\Curl;
use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Class ReportMicrosite
 *
 * @package Klb\Core\Microsite
 */
class ReportMicrosite extends Component
{
    /**
     * @var
     */
    private $token;
    private $expires;
    private $tokenType;

    private $microConfig;
    /** @var Curl */
    private $curl;
    private $currentUrl;

    /**
     * ReportMicrosite constructor.
     *
     * @param DiInterface|null $di
     */
    public function __construct()
    {
        $this->microConfig = $this->di->get('config')->microsite->report;
        $this->logger->error(\json_encode($this->microConfig));
        try {
            $this->curl = new Curl();
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 0);
            $this->curl->setOption(CURLOPT_TIMEOUT, 500);
        } catch (\Exception $e) {
        }
    }

    /**
     * @return \Phalcon\Session\Adapter|\Phalcon\Session\Adapter\Files|\Phalcon\Session\AdapterInterface
     */
    private function getStorage(){
        return $this->session;
    }
    /**
     * @param $email
     * @param $password
     */
    public function login($email, $password)
    {

        try {
            $payload = $this->jsonResponse($this->curl->post($this->microConfig->token_url, [
                'email' => $email,
                'password' => $password,
            ]));

            $expire = $payload->expires + 3600;//Remeber 1 hours
            $this->cookies->set('RMR_EMAIL', $email, $expire);
            $this->cookies->set('RMR_PASSWORD', $password, $expire);
            $this->setPayload($payload, true);
            $this->logger->debug("MS-LOGIN EXP: " . \date('Y-m-d H:i:s', $payload->expires));
        } catch (\Exception $httpException) {
            $this->logger->error("MS-ERROR: {$this->microConfig->token_url}\tPARAMS: " . \json_encode(\compact('email', 'password')) . "\tERROR: " . $httpException->getMessage());
        }
    }

    public function logout()
    {
        $this->getStorage()->remove('RMR_TOKEN');
        $this->getStorage()->remove('RMR_TOKEN_TYPE');
        $this->getStorage()->remove('RMR_TOKEN_EXP');
        $this->cookies->get('RMR_EMAIL')->delete();
        $this->cookies->get('RMR_PASSWORD')->delete();
    }

    /**
     * @param $payload
     */
    protected function setPayload($payload, $save = false)
    {
        if(false === $payload){
            $this->logger->error("MS-ERROR: {$this->microConfig->cookie_name}\tERROR: PAYLOAD IS FALSE");
            return;
        }
        $this->token = $payload->{$this->microConfig->token_name};
        $this->tokenType = $payload->token_type;
        $this->expires = $payload->expires + 0;

        if($save === true){
            $this->getStorage()->set('RMR_TOKEN', $this->token);
            $this->getStorage()->set('RMR_TOKEN_TYPE', $this->tokenType);
            $this->getStorage()->set('RMR_TOKEN_EXP', $this->expires);
        }
    }

    /**
     * @return stdClass
     */
    public function getPayload()
    {

        if(!$this->hasToken()){
            $this->refreshToken();
        }

        $payload = $this->getStorageValue();

        $this->setPayload($payload);

        return $payload;
    }

    /**
     * @return stdClass
     */
    public function getStorageValue(){
        $payload = new stdClass();
        $payload->{$this->microConfig->token_name} = $this->getStorage()->get('RMR_TOKEN');
        $payload->token_type = $this->getStorage()->get('RMR_TOKEN_TYPE');
        $payload->expires = $this->getStorage()->get('RMR_TOKEN_EXP');
        return $payload;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @return mixed
     */
    public function getMicroConfig()
    {
        return $this->microConfig;
    }

    /**
     * @return Curl
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * @return bool
     */
    public function hasToken()
    {
        $payload = $this->getStorageValue();
        $this->logger->debug('MS-HAS-TOKEN: ' . \json_encode($payload));
        if(empty($payload->expires)){
            $this->logger->debug('MS-HAS-TOKEN: Empty expires');
            return false;
        }
        if(empty($payload->{$this->microConfig->token_name})){
            $this->logger->debug('MS-HAS-TOKEN: Empty access_token');
            return false;
        }
        if(empty($payload->token_type)){
            $this->logger->debug('MS-HAS-TOKEN: Empty token_type');
            return false;
        }

        if(\time() > $payload->expires){
            $this->logger->debug('MS-HAS-TOKEN: Token has been expire');
            return false;
        }
        return true;
    }

    /**
     * @param $response
     * @return mixed
     * @throws \Exception
     */
    protected function jsonResponse($response)
    {
        $this->logger->debug("MS-RESPONSE (@$this->currentUrl): " . \json_encode($response));
        if (!empty($response->body) && \is_string($response->body)) {
            $response = \json_decode($response->body);
            if(!isset($response->code)){
                throw new \Exception("Invalid response");
            }
            if ($response->code !== 200) {
                throw new \Exception($response->message, $response->code);
            }

            return $response->data;
        }

        return $response;
    }

    /**
     *
     */
    public function refreshToken()
    {
        $email = $this->cookies->get('RMR_EMAIL')->getValue();
        $password = $this->cookies->get('RMR_PASSWORD')->getValue();
        if(empty($email)){
            $user = $this->getDI()->get('auth')->getUser();
            $email = $user->email;
            $password = $user->password;
        }
        $this->logger->debug("MS-REFRESH-TOKEN\tEMAIL: " . $email/* . "\tPASSWORD: " . $password*/);
        $this->login($email, $password);
    }

    /**
     * @return array
     */
    protected function getAuthorizeHeader()
    {
        $this->getPayload();
        $headers = [
            'Authorization:' . $this->tokenType . ' ' . $this->token,
        ];
        $this->logger->debug("MS-HEADER: " . $this->tokenType . ' ' . $this->token);
        return $headers;
    }

    /**
     * @param $url
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function get($url, array $params)
    {
        return $this->jsonResponse(
            $this->curl->get(
                $this->getUrl($url),
                $this->prepareParams($params, "GET"),
                $this->getAuthorizeHeader()
            )
        );
    }

    /**
     * @param $url
     * @return string
     */
    private function getUrl($url)
    {
        return $this->currentUrl = $this->microConfig->url . $url;
    }

    /**
     * @param $params
     * @return mixed
     */
    private function prepareParams($params, $method){
        if(!empty($params['_url'])){
            unset($params['_url']);
        }
        $this->logger->debug("MS-REQUEST: $method " . $this->currentUrl . "\tPARAMS: " . \http_build_query($params));
        return $params;
    }

    /**
     * @param $url
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function put($url, array $params)
    {
        return $this->jsonResponse(
            $this->curl->put(
                $this->getUrl($url),
                $this->prepareParams($params, "PUT"),
                true,
                $this->getAuthorizeHeader()
            )
        );
    }

    /**
     * @param $url
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function delete($url, array $params)
    {
        return $this->jsonResponse(
            $this->curl->delete(
                $this->getUrl($url),
                $this->prepareParams($params, "DELETE"),
                $this->getAuthorizeHeader()
            )
        );
    }

    /**
     * @param $url
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function post($url, array $params)
    {
        return $this->jsonResponse(
            $this->curl->post(
                $this->getUrl($url),
                $this->prepareParams($params, "POST"),
                true,
                $this->getAuthorizeHeader()
            )
        );
    }

    /**
     * @param $method
     * @param $urlPath
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function call($method, $urlPath, array $params)
    {
        return $this->$method($urlPath, $params);
    }
}
