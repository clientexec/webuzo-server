<?php

/**
* Provides an interface to issue commands to a remote WHM server.
* @link https://webuzo.com/docs/api/
* @author Brijesh Kothari
* @version 1.0
*/
class WebuzoApi
{
    protected $host;
    protected $username;
    protected $apikey;
    protected $ssl = false;
    var $port;
    var $schema;
    var $type = 'json';
    var $result;
    var $request;
    var $url;

    /**
    * Let's start her up!
    * @param string $host Host name of server
    * @param string $username Username with Webuzo admin privileges
    * @param string $apikey API Key
    * @param boolean $ssl Use an SSL connection
    * @param string $output Output type
    */
    public function __construct($host, $username, $apikey, $ssl = false, $output = 'json')
    {
        $this->host = $host;
        $this->username = $username;
        $this->apikey = $apikey;
        $this->ssl = $ssl;
        $this->port = ( $ssl == true ) ? 2005 : 2004;
        $this->enduser_port = ( $ssl == true ) ? 2003 : 2002;
        $this->schema = ( $ssl == true ) ? 'https://' : 'http://';
        $this->output = 'json';
    }

    /**
    * Makes a request through the Webuzo API (Admin)
    * @link https://webuzo.com/docs/api/
    * @param string $act
    * @param array $params
    * @return boolean
    */
	function call($act, $params = array(), $cookies = array()){
		
		global $webuzo_conf;
		
		$this->url = $this->schema.$this->host.':'.$this->port.'/index.php?act='.$act.'&api='.$this->output.'&apiuser='.$this->username.'&apikey='.rawurlencode($this->apikey).'&skip_callback=1';
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
			
		// Time OUT
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
		// UserAgent
		curl_setopt($ch, CURLOPT_USERAGENT, 'Softaculous');
		
		// Cookies
		if(!empty($cookies)){
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookies, '', '; '));
		}
		
		if(!empty($params)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// Get response from the server.
		$data = curl_exec($ch);

        if ($data === false) {
            $errorDesc = curl_error($ch);
            if ($errorDesc != '') {
                $error = "Webuzo API Request (".$act.") / cURL Error: ". $errorDesc.' - URL : '.$this->url;
            } else {
                $errorNumber = curl_errno($ch);
                if ($errorNumber == 3) {
                    $error = "Webuzo API Request (".$act.") / cURL Error: Server Hostname is not valid.";
                }
            }

            CE_Lib::log(1, $error);
            throw new Exception($error);
        }

        $data = str_replace('“', "'", $data);
        $data = str_replace('”', "'", $data);
		
		// As a security prevention measure - Though this cannot happen
		$data = str_replace($pass, '12345678901234567890123456789012', $data);
		
		if($this->output == 'serialize'){
			$result = $this->result = unserialize($data);
		}else{
			$result = $this->result = json_decode(utf8_encode($data), true);
		}

        $this->request = array ( 'url' => $this->url, 'act' => $act, 'params' => $params, 'raw' => $data);
        $this->verbose_request = array ( 'url' => $this->url, 'act' => $act, 'params' => $params, 'raw' => $data, 'json' => $result);
			
		curl_close($ch);
		
		// The following line is a method to test
		//if(preg_match('/sync/is', $url)) echo $data;
		
		if(empty($data)){
			return false;
		}
		
		// As a security prevention measure - Though this cannot happen
		$data = str_replace($pass, '12345678901234567890123456789012', $data);
		
		if($this->output == 'serialize'){
			$result = unserialize($data);
		}else{
			$result = json_decode($data, true);
		}

        CE_Lib::log(4, 'Webuzo API Request: '.print_r($this->request, true));
        CE_Lib::log(5, 'Webuzo API Request: '.print_r($this->verbose_request, true));
		
        if(!is_array($result)){
            throw new Exception("Failed to connect to Webuzo Server");
        }elseif(isset($result['error'])) {
            throw new CE_Exception("Webuzo returned an error:  ".implode('<br />', $result['error']));
        }
		
		return $result;
	}

    /**
    * Makes a request through the Webuzo API (Enduser)
    * @link https://webuzo.com/docs/api/
    * @param string $act
    * @param array $params
    * @return boolean
    */
	function enduser_call($act, $params = array(), $cookies = array()){
		
		global $webuzo_conf;
		
		$this->url = $this->schema.$this->host.':'.$this->enduser_port.'/index.php?act='.$act.'&api='.$this->output.'&apiuser='.$this->username.'&apikey='.rawurlencode($this->apikey).'&skip_callback=1';
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
			
		// Time OUT
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
		// UserAgent
		curl_setopt($ch, CURLOPT_USERAGENT, 'Softaculous');
		
		// Cookies
		if(!empty($cookies)){
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookies, '', '; '));
		}
		
		if(!empty($params)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// Get response from the server.
		$data = curl_exec($ch);

        if ($data === false) {
            $errorDesc = curl_error($ch);
            if ($errorDesc != '') {
                $error = "Webuzo API Enduser Request (".$act.") / cURL Error: ". $errorDesc;
            } else {
                $errorNumber = curl_errno($ch);
                if ($errorNumber == 3) {
                    $error = "Webuzo API Enduser Request (".$act.") / cURL Error: Server Hostname is not valid.";
                }
            }

            CE_Lib::log(1, $error);
            throw new Exception($error);
        }

        $data = str_replace('“', "'", $data);
        $data = str_replace('”', "'", $data);
		
		// As a security prevention measure - Though this cannot happen
		$data = str_replace($pass, '12345678901234567890123456789012', $data);
		
		if($this->output == 'serialize'){
			$result = $this->result = unserialize($data);
		}else{
			$result = $this->result = json_decode(utf8_encode($data), true);
		}

        $this->request = array ( 'url' => $this->url, 'act' => $act, 'params' => $params, 'raw' => $data);
        $this->verbose_request = array ( 'url' => $this->url, 'act' => $act, 'params' => $params, 'raw' => $data, 'json' => $result);
			
		curl_close($ch);
		
		// The following line is a method to test
		//if(preg_match('/sync/is', $url)) echo $data;
		
		if(empty($data)){
			return false;
		}
		
		// As a security prevention measure - Though this cannot happen
		$data = str_replace($pass, '12345678901234567890123456789012', $data);
		
		if($this->output == 'serialize'){
			$result = unserialize($data);
		}else{
			$result = json_decode($data, true);
		}

        CE_Lib::log(4, 'Webuzo API Enduser Request : '.print_r($this->request, true));
        CE_Lib::log(5, 'Webuzo API Enduser Request : '.print_r($this->verbose_request, true));
		
        if(!is_array($result)){
            throw new Exception("Failed to connect to Webuzo Server");
        }elseif(isset($result['error'])) {
            throw new CE_Exception("Webuzo returned an error:  ".implode('<br />', $result['error']));
        }
		
		return $result;
	}

    /**
    * Gets plans/packages
    * @return Array of packages (key = package name, index = package array)
    */
    public function packages()
    {
        $result = $this->call('plans');
        $packages = array();
		
		if(!empty($result['plans'])){
			$packages = $result['plans'];
		}

        return $packages;
    }

    /**
    * Gets all the accounts
    * @return Array of accounts (key = account username, index = account array)
    */
    public function accounts()
    {
        $result = $this->call('users');
        $accounts = array();
		
		if(!empty($result['users'])){
			$accounts = $result['users'];
		}

        return $accounts;
    }

    /**
    * Gets all suspended accounts
    */
    public function suspended()
    {
        $result = $this->call('users');

        $accounts = array();
		
		if(!empty($result['users'])){
			foreach ($result['users'] as $a) {
				if(!empty($a['status']) && $a['status'] == 'suspended'){
					$accounts[$a['user']] = $a;
				}
			}
		}

        return $accounts;
    }

    /**
    * Gets the current Webuzo version on the server.
    * @return string Current Webuzo version
    */
    public function version()
    {
        $result = $this->call();
		
		if(!empty($response['version'])){
			return $response['version'];
		}
		
        return '';
    }
}
