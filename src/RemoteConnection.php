<?php
// ==========================================================================
// Project: UniWeb
// All Rights Reserved
// UniWeb by Proximify is proprietary software.
// ==========================================================================

namespace Proximify\PublicationImporter;

/**
 * Connects to a remote server and fetches assets from it.
 */
class RemoteConnection
{
	/** maximum time in seconds that a libcurl transfer operation can take */
	//const MAXIMUM_OP_TIME = 300;
	const USERAGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:24.0) Gecko/20100101 Firefox/24.0';
	protected $ch;
	protected $maximumOpTime = 300;

	/**
	 * Post or put method that starts with an '@' will result in curl trying to load 
	 * the data from file instead of including the contents of the field in the request 
	 * body. Since we have values that start with '@', we need to disable that. 
	 * With PHP >= 5.5.0, we can set CURLOPT_SAFE_UPLOAD to true and use a CURLFile
	 * object to send files. If PHP < 5.5.0, we need to do more work.
	 */
	static function hasSafeUpload()
	{
		return (version_compare(PHP_VERSION, '5.5.0') >= 0);
	}

	static function createFileObject($filename, $mimetype = '', $postname = '')
	{
		if (!self::hasSafeUpload() && !class_exists('CURLFile')) {
			require_once('curl_file.php');
		}

		return new CURLFile($filename, $mimetype, $postname);
	}

	/**
	 * Initializes a remote connection
	 */
	public function init()
	{
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->maximumOpTime);
		curl_setopt($this->ch, CURLOPT_USERAGENT, self::USERAGENT);

		// Post or put method that starts with an '@' will result in curl trying to load 
		// the data from file instead of including the contents of the field in the request body.
		// Since we have values that start with '@', we need to disable that. 
		// Requires PHP 5.5.0+
		// In 7+ it's on by default, and we only use 7+
		// if (self::hasSafeUpload())
		// 	curl_setopt($this->ch, CURLOPT_SAFE_UPLOAD, true);
	}

	public function initVPN($info)
	{
		$proxyUrl = $info['proxyUrl'] ?? false;
		$proxyListUrl = $info['proxyListUrl'] ?? false;

		if ($proxyListUrl) {
			$conn = new self();

			$list = $conn->get($proxyListUrl);

			$list = $list ? json_decode($list, true) : [];

			foreach ($list as $proxy) {
				$status = $proxy['status'] ?? false;
				$proxyUrl = $proxy['hostname'] ?? false; // or 'station'

				if ($status == 'online' && $proxyUrl) {
					break;
				}
			}
		}

		dbg_log($proxyUrl);

		if (!$proxyUrl)
			return;

		$username = $info['proxyUsername'];
		$password = $info['proxyPassword'];

		/**
		 * Technique borrowed from:
		 * https://dynamogeek.com/entry/20-using-nordvpn-with-curl-in-php/
		 * Also see:
		 * https://www.bestproxyandvpn.com/help/tutorials/proxy/using-proxies-in-php.html
		 */

		// Prevents the header from being included in the results
		curl_setopt($this->ch, CURLOPT_HEADER, FALSE);

		// Causes the response to be returned as a string
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);

		// Any SOCKS capable proxy URL
		curl_setopt($this->ch, CURLOPT_PROXY, $proxyUrl);

		// Sets the proxy type for the connection to SOCKS
		curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

		// The port that NordVPN uses for all SOCKS connections
		curl_setopt($this->ch, CURLOPT_PROXYPORT, '1080');

		// Allows the connection to be redirected
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);

		// Don't put your creds directly in the code
		curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, "$username:$password");

		// Sets the authentication type for proxy connections
		curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);

		// curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, 1);
	}

	/**
	 * Closes a remote connection
	 */
	public function close()
	{
		curl_close($this->ch);
	}

	/**
	 * Sets the User Agent. Some hosts require that a common user agent be present in the POST. 
	 */
	public function setUserAgent($agent)
	{
		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
	}

	/**
	 * Sets the maximum time in seconds that a libcurl transfer operation can take.
	 * The default is 300
	 */
	public function setTimeout($maximumOPTime)
	{
		$this->maximumOpTime = $maximumOPTime;

		if ($this->ch)
			curl_setopt($this->ch, CURLOPT_TIMEOUT, $maximumOPTime);
	}

	/**
	 * Sets the URL
	 */
	public function setURL($url)
	{
		// require_once 'configuration_file.php';

		// $knownHosts = ConfigurationFile::getKnownHosts();
		// $host = parse_url($url, PHP_URL_HOST);

		// if (isset($knownHosts[$host])) {
		// 	$info = $knownHosts[$host];

		// 	if (!empty($info['resolveIP'])) {
		// 		// See https://www.php.net/manual/en/function.curl-setopt.php
		// 		$ip = $info['resolveIP'];
		// 		$addresses = ["$url:443:$ip", "$url:80:$ip"];
		// 		curl_setopt($this->ch, CURLOPT_RESOLVE, $addresses);
		// 	}

		// 	if (!empty($info['useProxy']))
		// 		$this->initVPN($info);
		// }

		curl_setopt($this->ch, CURLOPT_URL, $url);
	}

	/**
	 * Sets Cookie file. This is the file that cURL will read and gets the cookies from. 
	 *
	 * The cookie jar setting is where curl writes cookies to, but a separate setting is 
	 * required for curl to send cookies back to the server. This is the CURLOPT_COOKIEFILE 
	 * setting. If it is not set then no cookies will be sent to the server. If the file 
	 * does not exist, then no error is issued.
	 */
	public function setCookieFile($cookieFile)
	{
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookieFile);
	}

	/**
	 * Sets Cookie jar. cURL writes the returned cookies from the server into this file. 
	 *
	 * CURLOPT_COOKIEJAR species the filename where the cookies should be stored. If the 
	 * server sets any they will be written to this file, and it will be created if it 
	 * does not already exist.
	 */
	public function setCookieJar($cookieJar)
	{
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookieJar);
	}

	/**
	 * Sets header value.
	 * @param int $headerValue: The value of CUROPT_HEADER which can be 0 or 1. 
	 */
	public function setHeaderValue($headerValue)
	{
		curl_setopt($this->ch, CURLOPT_HEADER, $headerValue);
	}

	/**
	 * Makes the request POST
	 * @param array $postFields the array to be posted to the server.
	 */
	public function setToPOST($postFields)
	{
		$postFields = $this->makeSafePostFields($postFields);

		// @TODO Why do this?
		if (is_array($postFields))
			curl_setopt($this->ch, CURLOPT_POST, count($postFields));

		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postFields);
	}

	/**
	 * Tells curls that the response of the request should be saved to the given file.
	 * $param $fp A file pointer usually obtained from fopen($fname,'w').
	 */
	public function setReturnFile($fp)
	{
		curl_setopt($this->ch, CURLOPT_FILE, $fp);
	}

	function makeSafePostFields($postFields)
	{
		if (self::hasSafeUpload() || !is_array($postFields))
			return $postFields;

		$hasFiles = false;
		$hasAtPrefix = false;

		foreach ($postFields as $value) {
			if (is_object($value) && get_class($value) == 'CURLFile')
				$hasFiles = true;
			elseif (is_string($value) && $value && $value[0] == '@')
				$hasAtPrefix = true;
		}

		if ($hasFiles && $hasAtPrefix)
			throw new Exception('PHP < 5.5.0 cannot deal with files and @ prefix together');

		if ($hasFiles) {
			foreach ($postFields as $key => $value) {
				if (is_object($value) && get_class($value) == 'CURLFile') {
					$size = filesize($value->name);

					$postFields[$key] = sprintf(
						'@%s;filename=%s;type=%s;size=%d',
						$value->name,
						$value->postname,
						$value->mime,
						$size
					);
				}
			}
		} elseif ($hasAtPrefix) {
			// Values in $postFields cannot be prefixed with @. This should be fixed with
			// CURLOPT_SAFE_UPLOAD, but that requires PHP 5.5.0+. So for PHP 5.2.0+, if
			// we convert the post fields into a string, the @str is not seen as a file.
			// This is all explained in the help of CURLOPT_POSTFIELDS
			// http://php.net/manual/en/function.curl-setopt.php
			$postFields = http_build_query($postFields);
		}

		return $postFields;
	}

	/**
	 * Makes the request raw POST. This differs from normal post in that: it is no longer
	 * key value pair, but only one string to be sent. 
	 *
	 * @param The string to send to the server.
	 */
	public function setToPOSTRaw($postString)
	{
		curl_setopt($this->ch, CURLOPT_POST, 1);

		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postString);
	}

	/** 
	 * Set custom outward headers. Given an array, this will set HTTP Headers. 
	 */
	public function setHTTPHeaders($headers)
	{
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 * TRUE to automatically set the Referer: field in requests where it follows
	 * a Location: redirect.
	 * If  the server reports that the requested page has moved
	 * to a different location (indicated with a Location: header and a
	 * 3XX  response code), this option will make curl redo the request
	 * on the new place.
	 */
	public function setAutoReferrer($status = true)
	{
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, $status);
	}

	/**
	 * Sends the HTTP Request
	 */
	public function sendRequest()
	{
		$result = curl_exec($this->ch);

		if ($result === false) {
			$msg = curl_error($this->ch);
			throw new Exception($msg);
		}

		return $result;
	}

	/** 
	 * Sends a POST request. 
	 * @param string $url to request 
	 * @param array $post values to send 
	 * @return string 
	 */
	public function post($url, $post = NULL, $secure = true, $headers = NULL)
	{
		$this->init();
		$this->setURL($url);
		$this->setToPOST($post);

		if ($headers)
			$this->setHTTPHeaders($headers);

		if (!$secure)
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

		$result = $this->sendRequest();

		$this->close();

		return $result;
	}

	/** 
	 * Sends a GET request. 
	 * @param string $url to request 
	 * @param array $get GET values to send 
	 * @return string 
	 */
	public function get($url, array $get = NULL, $disableSSL = false)
	{
		$this->init();

		if ($disableSSL)
			$this->disableSSL();

		if (!$get)
			$this->setURL($url);
		else
			$this->setURL($this->urlParamsJoin($url, $get));

		$result = $this->sendRequest();

		$this->close();

		return $result;
	}

	/** 
	 * Sends both GET and POST request parameters. 
	 * @param string $url to request 
	 * @param array $get GET values to send 
	 * @param array $post POST values to send 
	 * @return string 
	 */
	function fetch($url, array $get = NULL, array $post = NULL)
	{
		$this->init();

		$this->setURL($this->urlParamsJoin($url, $get));

		$this->setToPOST($post);

		$result = $this->sendRequest();

		$this->close();

		return $result;
	}

	/**
	 * Loads a file from the remote server.
	 * @param string $url to request 
	 * @param string $filename name of the file to request 
	 */
	function load($url, $filename)
	{
		// TODO: this file is to be updated to the new style - seyed.

		$fp = @fopen($filename, "w");

		$ch = curl_init($url);

		if (!$fp) {
			trigger_error(curl_error($ch));
			return false;
		}

		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		if (!$result = curl_exec($ch)) {
			trigger_error(curl_error($ch));
		}

		curl_close($ch);

		// echo file contents...

		@fclose($this->curl_file);

		return $result;
	}

	/**
	 * Internal helper function: Appends the parameters to the URL
	 */
	function urlParamsJoin($url, $params)
	{
		return $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($params);
	}

	/**
	 * Given a CRT file, this function Enables SSL connection
	 *
	 * @param $crtFile The path to a file holding one or more certificates to verify 
	 * the peer with, or the path to a directory that holds multiple CA certificates.
	 */
	public function enableSSL($crtFileOrDir = null)
	{
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($crtFileOrDir && file_exists($crtFileOrDir)) {
			$opt = is_dir($crtFileOrDir) ? CURLOPT_CAPATH : CURLOPT_CAINFO;

			curl_setopt($this->ch, $opt, $crtFileOrDir);
		}
	}

	/**
	 * This function disables host certificate verification in case of HTTPS.
	 */
	public function disableSSL()
	{
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
	}

	/**
	 * Send a get request, then stores the result in a file specified. 
	 */
	public function getToFile($url, $filename)
	{
		$fp = fopen($filename, "w");

		if (!$fp)
			throw new Exception('Failed to open the file as writable');

		$this->init();

		$this->setURL($url);

		if (!curl_setopt($this->ch, CURLOPT_FILE, $fp))
			throw new Exception('Failed to set the file as output');

		$this->sendRequest();

		$this->close();

		fclose($fp);
	}

	/**
	 * Send a post request, then stores the result in a file specified. 
	 */
	public function postToFile($url, $filename, array $post = NULL)
	{
		$fp = fopen($filename, "w");

		if (!$fp)
			throw new Exception('Failed to open the file as writable');

		$this->init();

		$this->setURL($url);

		if (!curl_setopt($this->ch, CURLOPT_FILE, $fp))
			throw new Exception('Failed to set the file as output');

		$this->setToPOST($post);

		$this->sendRequest();

		$this->close();

		fclose($fp);
	}

	/** 
	 * @param $params If not null, it will be added to the URL
	 */
	public function postJSON($url, $data, $params = null)
	{
		$data_string = json_encode($data);

		if (!is_null($params))
			$url = $this->urlParamsJoin($url, $params);

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string)
			)
		);

		$result = curl_exec($ch);

		curl_close($ch);

		return $result;
	}
}
