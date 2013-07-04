<?php

namespace phpsec;

class HttpRequestException extends \Exception {}
class HttpRequestInsecureParameterException extends HttpRequestException {}


require_once (__DIR__ . '/tainted.php');

/**
 * HttpRequestArray class
 * Wraps $_SERVER in an ArrayAccess interface
 */
abstract class HttpRequestArray implements \ArrayAccess
{
	protected $data;

	public function __construct($data = null)
	{
		$this->data=$data;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
			$this->data[] = $value;
		else
			$this->data[$offset] = $value;
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		if (isset($this->data[$offset]))
		{
			if (substr($offset,0,4) === 'HTTP')
				return new TaintedString($this->data[$offset]);
			else
				return $this->data[$offset];
		}
		else
			return NULL;
	}
}

/**
 * HttpRequest class
 * Wrapper class to securely process HTTP request parameters
 */
class HttpRequest extends HttpRequestArray
{
	/**
	 * Checks if script is being called from command line
	 * @return boolean
	 */
	protected static function isCLI()
	{
		if (php_sapi_name() === "cli" || !isset($_SERVER['REMOTE_ADDR']))
			return true;
		else
			return false;
	}

	/**
	 * Returns IP address of client
	 * @return  string IP
	 */
	static function IP()
	{
		if (self::isCLI())
			return '127.0.0.1';
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Returns the current URL
	 * @return  string URL
	 */
	static function URL($QueryString=true)
	{
		if (self::isCLI())
			return NULL;
		if ($QueryString && self::QueryString() )
			return (self::Protocol()."://".self::ServerName().self::PortReadable().self::RequestURI()."?".self::QueryString());
		else
			return (self::Protocol()."://".self::ServerName().self::PortReadable().self::RequestURI());
	}

	/**
	 * HTTP Host, aka Domain name
	 *
	 * @return string Host
	 */
	static function Host()
	{
		if (self::IsCLI())
			return "localhost";
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	}

	static function ServerName()
	{
		return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		
	}

	/**
	 * Returns protocol of the client connection, HTTP/HTTPS
	 * @return string Protocol
	 */
	static function Protocol()
	{
		if (self::isCLI())
			return 'cli';
		$x = (isset($_SERVER['HTTPS'])) ? $_SERVER['HTTPS'] : '';
		if ($x == "off" or $x == "")
			return "http";
		else
			return "https";
	}

	/**
	 * Checks if protocol is HTTPS
	 * @return boolean
	 */
	static function isHTTPS()
	{
		return (self::Protocol() === 'https');
	}

	/**
	 * Checks if protocol is HTTP
	 * @return boolean
	 */
	static function isHTTP()
	{
		return (self::Protocol() === 'http');
	}

	/**
	 * Returns port of client connection
	 * @return string Port
	 */
	static function Port()
	{
		if (self::isCLI())
			return NULL;
		return isset ($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : "";
	}

	static function PortReadable()
	{
		$port = self::Port();
		if ($port=="80" && strtolower(self::Protocol())=="http")
			$port="";
		else if ($port=="443" && strtolower(self::Protocol())=="https")
			$port="";
		else
			$port=":".$port;
	}

	/**
	 * Returns the URI for current script
	 * @return  string RequestURI
	 */
	static function RequestURI()
	{
		if (self::isCLI())
			return NULL;
		return isset ($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	}

	/**
	 * Query String, the last part in url after ?
	 *
	 * @return String QueryString
	 */
	static function QueryString ()
	{
		if (self::IsCLI())
			return http_build_query($_GET);
		if (isset($_SERVER['REDIRECT_QUERY_STRING']))
		{
			$a = explode("&", $_SERVER['REDIRECT_QUERY_STRING']);
			$x = array_shift($a);
			return substr($_SERVER['REDIRECT_QUERY_STRING'], strlen($x) + 1);
		}
		else
			return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : "";
	}

	/**
	 * Request method, either GET/POST
	 *
	 * @return string RequestMethod
	 */
	static function Method()
	{
		if (self::IsCLI())
			return "GET";
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Request Path, e.g http://somesite.com/this/is/the/request/path/index.php
	 *
	 * @return string Path
	 */
	static function Path()
	{
		if (self::IsCLI())
			return NULL;
		$RequestURI = $_SERVER['REQUEST_URI'];
		if (strpos($RequestURI,"?") !== false)
			$Path = substr($RequestURI,0,strpos($RequestURI,"?"));
		else
			$Path = $RequestURI;
		return $Path;
	}

	/**
	 * Root of website without trailing slash
	 *
	 * @return string Root
	 */
	static function Root()
	{
		if (self::IsCLI())
			return NULL;
		$root = self::Protocol()."://".self::Host().self::PortReadable().self::Path();
		return $root;
	}

	/**
	 * Returns the IP address of the server under which the current script is executing.
	 */
	static function ServerIP()
	{
		if (self::isCLI())
			return '127.0.0.1';
		return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : NULL;
	}

}

$_SERVER = new HttpRequest($_SERVER);