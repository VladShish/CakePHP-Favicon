<?php

App::uses('HttpSocket', 'Network/Http');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * @author Vlad Shish <vladshish89@gmail.com>
 */
class Favicon {

	/**
	 * @var array
	 */
	private static $__services = array(
		'http://g.etfv.co/',
		'http://www.google.com/s2/favicons?domain=',
	);

	/**
	 * Default favicons image type
	 * 
	 * @var string
	 */
	protected static $_defaultType = 'ico';

	/**
	 * Get favicon url by link
	 * 
	 * @param  string $link
	 * @param  boolean $force
	 * @return mix
	 */
	public static function getServiceLink($link, $force = false) {
		$host = self::_getHost($link, $force);
		if (empty($host)) {
			return;
		}

		foreach (self::$__services as $service) {
			if (self::_isContentNormal($service, $host, $force)) {
				return $service . $host;
			}
		}

		return;
	}

	/**
	 * Get favicon url by link
	 *
	 * @deprecated
	 * @param  string $link
	 * @return mix
	 */
	public static function getByLink($link) {
		return self::getServiceLink($link);
	}

	/**
	 * Save img in your images cache
	 * 
	 * @deprecated
	 * @param  string $link
	 * @return string
	 */
	public static function cacheImg($link, $force = false) {
		return self::cacheByLink($link, $force);
	}

	/**
	 * Save img in your images cache
	 * 
	 * @param  string $link
	 * @return string
	 */
	public static function cacheByLink($link, $force = false) {
		$host = self::_getShortHost($link, $force);
		if (empty($host)) {
			return false;
		}

		return self::cache($host, $force);
	}

	/**
	 * Save img in your images cache
	 * 
	 * @param  string $link
	 * @return string
	 */
	public static function cache($host, $force = false) {
		$dirPath = Configure::read('Favicon.dir_path') . self::_getSubFolderName($host);
		$fullFileName = $dirPath . DS . $host;

		if ($force) {
			self::delete($host);
		}

		if ((self::_isFaviconExists($dirPath, $host)) && !$force) {
			return false;
		}

		$favicUrl = self::getServiceLink($host, $force);
		if (empty($favicUrl)) {
			return false;
		}

		$result = self::_getByUrl($favicUrl, $force);
		if (empty($result['body'])) {
			return false;
		}

		self::_createSubFolder($dirPath);
		$extension = self::_getExtension($result);

		$file = new File($fullFileName . '.' . $extension, true, 0644);
		$file->write($result['body'], 'w');

		return true;
	}

	/**
	 * Delete all files belong to this host
	 * 
	 * @param  sting $host
	 */
	public static function delete($host) {
		$dirPath = Configure::read('Favicon.dir_path') . self::_getSubFolderName($host);
		$Dir = new Folder($dirPath);
		$files = $Dir->find($host . '.*');

		foreach ($files as $index => $fileName) {
			$File = new File($dirPath . DS . $fileName);
			$File->delete();
			unset($files[$index]);
		}

		$filesResidue = $Dir->find();
		if (empty($filesResidue)) {
			$Dir->delete();
		}
	}

	/**
	 * Return local favicon url by link
	 * 
	 * @param  string $link
	 * @return string
	 */
	public static function urlByLink($link) {
		if (strpos($link, 'http://') === false) {
			$link = 'http://' . $link;
		}

		$parsedUrl = parse_url($link);
		$host = str_replace('www.', '', $parsedUrl['host']);
		$subFolders = DS . 'favicons' . DS . $host[0] . DS;
		$dirPath =  APP . WEBROOT_DIR . $subFolders;

		App::uses('Folder', 'Utility');
		$dir = new Folder($dirPath);
		$files = $dir->find($host . '.*');

		if (!empty($files[0])) {
			return $subFolders . $files[0];
		}
	}

	/**
	 * Get class by link
	 * 
	 * @param  string $link
	 * @return string
	 */
	public static function getIdByLink($link) {
		$host = self::_getShortHost($link);
		return self::getIdByDomin($host);
	}

	/**
	 * Get class by domin
	 * 
	 * @param  string $domin
	 * @return string
	 */
	public static function getIdByDomin($domin) {
		return str_replace('.', '-', $domin);
	}

	/**
	 * Is favicon exists
	 * 
	 * @param  string  $dirPath
	 * @param  string  $host
	 * @return boolean
	 */
	protected static function _isFaviconExists($dirPath, $host) {
		$dir = new Folder($dirPath);
		$files = $dir->find($host . '.*');
		return !empty($files);
	}

	/**
	 * Get short host some urls
	 * 
	 * @param  string $link
	 * @return string
	 */
	protected static function _getShortHost($link, $force = false) {
		$fullHost = self::_getHost($link, $force);
		$hostParsed = parse_url($fullHost);
		$host = str_replace('www.', '', $hostParsed['host']);
		return $host;
	}

	/**
	 * Create sub folder
	 * 
	 * @param  string $subFolderPath
	 * @return void
	 */
	protected static function _createSubFolder($subFolderPath) {
		if (!is_dir($subFolderPath)) {
			$dir = new Folder();
			$dir->create($subFolderPath);
			$dir->chmod($subFolderPath, '777');
		}
	}

	/**
	 * Get extension from headers
	 * 
	 * @param  array $result
	 * @return string
	 */
	protected function _getExtension($result) {
		if (empty($result['headers'])) {
			return self::$_defaultType;
		}

		switch ($result['headers']['Content-Type']) {
			case 'image/x-icon':
				return 'ico';
			case 'image/png':
				return 'png';
			case 'image/gif':
				return 'gif';
			case 'image/jpeg':
				return 'jpg';
			default:
				return self::$_defaultType;
		}
	}

	/**
	 * Get sub folder name
	 * 
	 * @param  string $host
	 * @return string
	 */
	protected static function _getSubFolderName($host) {
		$hostRepleced = str_replace('www.', '', $host);
		return $hostRepleced[0];
	}

	/**
	 * Get host url
	 * 
	 * @param  string $link
	 * @return string
	 */
	protected static function _getHost($link, $force = false) {
		if (strpos($link, 'http://') === false) {
			$link = 'http://' . $link;
		}

		$host = 'http://' . parse_url($link, PHP_URL_HOST);
		$host = self::__fixHost($host);
		if (Cache::read($host, 'Favicon') && !$force) {
			return Cache::read($host, 'Favicon');
		}

		try {
			$HttpSocket = new HttpSocket();
			$results = $HttpSocket->get($host);
		} catch (Exception $Exception) {
			return;
		}

		$hostNew = !empty($results->headers['Location']) ? $results->headers['Location'] : $host;
		Cache::write($host, $hostNew, 'Favicon');

		return $hostNew;
	}

	/**
	 * Is content normall or default
	 * 
	 * @param  string  $service
	 * @param  string  $host
	 * @return boolean
	 */
	protected static function _isContentNormal($service, $host, $force = false) {
		$faviconSite = self::_getBodyByUrl($service . $host, $force);
		$faviconDefault = self::_getBodyByUrl($service . 1, $force);

		return (boolean)($faviconSite != $faviconDefault);
	}

	/**
	 * Get favicon body by service url
	 * 
	 * @param  string $faviconUrl
	 * @return string
	 */
	protected function _getBodyByUrl($faviconUrl, $force = false) {
		if (Cache::read($faviconUrl, 'Favicon') && !$force) {
			return Cache::read($faviconUrl, 'Favicon');
		}

		try {
			$HttpSocket = new HttpSocket();
			$results = $HttpSocket->get($faviconUrl);
			$body = $results->body;
		} catch (Exception $Exception) {
			$body = '';
		}

		Cache::write($faviconUrl, $body, 'Favicon');

		return $body;
	}

	/**
	 * Get favicon body by service url
	 * 
	 * @param  string $faviconUrl
	 * @return string
	 */
	protected function _getByUrl($faviconUrl, $force = false) {
		if (Cache::read($faviconUrl, 'Favicon') && Cache::read($faviconUrl . '_headers', 'Favicon') && !$force) {
			return array(
				'body' => Cache::read($faviconUrl, 'Favicon'),
				'headers' => Cache::read($faviconUrl . '_headers', 'Favicon'),
			);
		}

		try {
			$HttpSocket = new HttpSocket();
			$results = $HttpSocket->get($faviconUrl);
			$body = $results->body;
			$headers = $results->headers;
		} catch (Exception $Exception) {
			$body = '';
			$headers = '';
		}

		Cache::write($faviconUrl, $body, 'Favicon');
		Cache::write($faviconUrl . '_headers', $headers, 'Favicon');

		return array('body' => $body, 'headers' => $headers);
	}

	/**
	 * Return fixed host
	 * @param  string $host
	 * @return string
	 */
	private static function __fixHost($host) {
		$hosts = array(
			'http://asahi.com' => true
		);
		if (isset($hosts[$host])) {
			$host = str_replace('http://', 'http://www.', $host);
		}
		return $host;
	}

}