<?php namespace BreezeChms;

    /**
     * @author Daniel Boorn <daniel.boorn@gmail.com>
     * @copyright Rapid Digital LLC <www.rapiddigitalllc.com>
     * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
     **/


/**
 * Class Exception
 * @package BreezeChms
 */
class Exception extends \Exception
{

    public $response;
    public $extra;

    public function __construct($message, $code = 0, $response = null, $extra = null, \OAuthException $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->extra = $extra;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getExtra()
    {
        return $this->extra;
    }

}

/**
 * Class API
 * @package BreezeChms
 */
class API
{


    public $error = null;
    public $paths;

    protected $settings = array(
        'key' => '',
    );

    protected $endpointId;
    protected $pathIds = array();
    protected $response;

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = (object)$settings;
        $this->loadApiPaths();
    }

    /**
     * @param null $settings
     * @return API
     */
    public static function forge($settings = null)
    {
        $self = new self($settings);
        return $self;
    }

    /**
     * @param $name
     * @param $args
     * @return $this|API
     */
    public function __call($name, $args)
    {
        $this->endpointId .= $this->endpointId ? "_{$name}" : $name;
        if (count($args) > 0 && gettype($args[0]) != "array" && gettype($args[0]) != "object") $this->pathIds[] = array_shift($args);
        if (isset($this->paths[$this->endpointId])) {
            $r = $this->invoke($this->endpointId, $this->paths[$this->endpointId]['verb'], $this->paths[$this->endpointId]['path'], $this->pathIds, current($args));
            $this->reset();
            return $r;
        }
        return $this;
    }

    /**
     *
     */
    public function reset()
    {
        $this->endpointId = null;
        $this->pathIds = array();
    }

    /**
     * @param $path
     * @param $ids
     * @return string
     * @throws \Exception
     */
    protected function parsePath($path, $ids)
    {
        $parts = explode("/", ltrim($path, '/'));
        for ($i = 0; $i < count($parts); $i++) {
            if ($parts[$i]{0} == "{") {
                if (count($ids) == 0) throw new \Exception("Api Endpoint Path is Missing 1 or More IDs [path={$path}].");
                $parts[$i] = array_shift($ids);
            }
        }
        return '/' . implode("/", $parts);
    }

    /**
     * @param $id
     * @param $verb
     * @param $path
     * @param null $ids
     * @param null $params
     * @return $this
     * @throws Exception
     * @throws \Exception
     */
    public function invoke($id, $verb, $path, $ids = null, $params = null)
    {
        $path = $this->parsePath($path, $ids);
        $url = "{$this->settings->baseUrl}{$path}";
        $this->response = $this->fetch($url, $params, $verb);
        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->response;
    }


    /**
     * @return mixed
     */
    public function error()
    {
        return $this->response['data'];
    }

    /**
     *
     */
    protected function loadApiPaths()
    {
        $filename = __DIR__ . "/api_paths.json";
        $this->paths = json_decode(file_get_contents($filename), true);
    }

    /**
     * @param $url
     * @param null $data
     * @param string $verb
     * @return mixed
     * @throws Exception
     */
    public function fetch($url, $data = null, $verb = 'GET')
    {
        $url .= $data ? "?" . http_build_query($data) : "";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Api-Key: ' . $this->settings->key
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

        if ($verb == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if (($response = curl_exec($ch)) === false) {
            throw new Exception(curl_error($ch), 500, $response, $data);
        }
        curl_close($ch);

        return json_decode($response, true);
    }


    /**
     * parse header string to array
     *
     * @source http://php.net/manual/en/function.http-parse-headers.php#77241
     * @param string $header
     * @return array $retVal
     */
    public static function http_parse_headers($header)
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace_callback('/\x0D\x0A[\x09\x20]+/', function ($m) {
            return "";
        }, $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function ($m) {
                    return strtoupper($m[0]);
                }, strtolower(trim($match[1])));

                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }


}
