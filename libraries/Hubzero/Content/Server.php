<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Nicholas J. Kisseberth <nkissebe@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Short description for 'Hubzero_Content_Server'
 * 
 * Long description (if any) ...
 */
class Hubzero_Content_Server
{

	/**
	 * Description for '_filename'
	 * 
	 * @var unknown
	 */
	var $_filename;

	/**
	 * Description for '_acceptranges'
	 * 
	 * @var unknown
	 */
	var $_acceptranges;

	/**
	 * Description for '_disposition'
	 * 
	 * @var unknown
	 */
	var $_disposition;

	/**
	 * Description for '_saveas'
	 * 
	 * @var unknown
	 */
	var $_saveas;

	/**
	 * Short description for '__construct'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     void
	 */
	public function __construct()
	{
	}

	/**
	 * Short description for 'saveas'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $saveas Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	public function saveas($saveas = null)
	{
		if (!is_null($saveas))
			$this->_saveas = basename($saveas);

		return($this->_saveas);
	}

	/**
	 * Short description for 'filename'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $filename Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	public function filename($filename = null)
	{
		if (!is_null($filename))
			$this->_filename = $filename;

		return($this->_filename);
	}

	/**
	 * Short description for 'acceptranges'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $acceptranges Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	public function acceptranges($acceptranges = null)
	{
		if (!is_null($acceptranges))
			$this->_acceptranges = ($acceptranges) ? true : false;

		return($this->_acceptranges);
	}

	/**
	 * Short description for 'disposition'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $disposition Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	public function disposition($disposition = null)
	{
		if (!is_null($disposition)) {
			if ( strcasecmp($disposition,"inline") == 0)
				$disposition = "inline";
			else if ( strcasecmp($disposition,"attachment") == 0)
				$disposition = "attachment";
			else
				$disposition = "inline";

			$this->_disposition = $disposition;
		}

		return($this->_disposition);
	}

	/**
	 * Short description for 'serve'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     unknown Return description (if any) ...
	 */
	public function serve()
	{
		return Hubzero_Content_Server::serve_file($this->_filename, $this->_saveas, $this->_disposition, $this->_acceptranges);
	}

	/**
	 * Short description for 'serve_attachment'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $filename Parameter description (if any) ...
	 * @param      unknown $saveas Parameter description (if any) ...
	 * @param      boolean $acceptranges Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	public function serve_attachment($filename, $saveas = null, $acceptranges = true)
	{
		return Hubzero_Content_Server::serve_file($filename, $saveas, 'attachment', $acceptranges);
	}

	/**
	 * Short description for 'serve_inline'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $filename Parameter description (if any) ...
	 * @param      boolean $acceptranges Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	public function serve_inline($filename, $acceptranges = true)
	{
		return Hubzero_Content_Server::serve_file($filename, null, 'inline', $acceptranges);
	}

	/**
	 * Short description for 'serve_file'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $filename Parameter description (if any) ...
	 * @param      string $saveas Parameter description (if any) ...
	 * @param      string $disposition Parameter description (if any) ...
	 * @param      boolean $acceptranges Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function serve_file($filename, $saveas=null, $disposition='inline', $acceptranges=true)
	{
		$fp = fopen($filename,"rb");

		if ($fp == false)
			return false;

		$fileinfo = pathinfo($filename);

		if (empty($saveas))
			$saveas = $fileinfo['basename'];
		else
			$saveas = basename($saveas);

		$saveas   = addslashes($saveas);

		$filesize = filesize($filename);

		$extension =  $fileinfo['extension'];

		include_once(dirname(__FILE__).DS.'Mimetypes.php');

		$mime = new Hubzero_Content_Mimetypes();
		$type = $mime->getMimeType($filename);

		if ($type == '##INVALID_FILE##') {
			$type = 'application/octet-stream';
		}

		if ($acceptranges
		 && $_SERVER['REQUEST_METHOD']=='GET'
		 && isset($_SERVER['HTTP_RANGE'])
		 && $range=stristr(trim($_SERVER['HTTP_RANGE']),'bytes=')) {
    		$range=substr($range,6);
    		$boundary='g45d64df96bmdf4sdgh45hf5';//set a random boundary
    		$ranges=explode(',',$range);
			$partial = true;
  		}
		else {
			$ranges = array("0-" . ($filesize - 1));
			$partial = false;
		}

		$multipart = (count($ranges) > 1);
      		$content_length=0;

		foreach ($ranges as $range) {

			preg_match("/^\s*(\d*)\s*-\s*(\d*)\s*$/", $range, $match);

  			$first=isset($match[1]) ? $match[1] : '';
  			$last=isset($match[2])  ? $match[2] : '';

			if ($first!='') // byte-range-set
			{
				if (($last >= $filesize) || ($last == ''))
					$last = $filesize - 1;
			}
			else if ($last != '') // suffix-byte-range-set
			{
    				$first = $filesize - $last;
    				$last  = $filesize - 1;

    				if ($first < 0)
					$first = 0;
  			}

  			if ( ($first > $last) || ($last == '') ) // unsatisfiable range
			{
				header("Status: 416 Requested range not satisfiable");
    			header("Content-Range: */$filesize");
    			exit;
  			}

			$result[$range]['first'] = $first;
			$result[$range]['last'] = $last;

			if ($multipart) {
				$content_length+=strlen("\r\n--$boundary\r\n");
				$content_length+=strlen("Content-type: $type\r\n");
				$content_length+=strlen("Content-range: bytes $first-$last/$filesize\r\n\r\n");
			}

			$content_length+=$last-$first+1;
		}

		if ($multipart)
			$content_length+=strlen("\r\n--$boundary--\r\n");

		//output headers

		if ($partial)
			header("HTTP/1.1 206 Partial content");

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$msie = preg_match("/MSIE (\d+)\.(\d+)([^;]*);/i", $_SERVER['HTTP_USER_AGENT'], $matches); // MSIE
		} else {
			$msie = false;
		}

		if (!$partial && ($disposition=="attachment")) {
			if ($msie && ($matches[1] < 6) ) // untested IE 5.5 workaround
				header("Content-Disposition: filename=" . $saveas);
			else
				header("Content-Disposition: attachment; filename=\"" . $saveas . "\"");
		}

		if ($multipart)
			header("Content-Type: multipart/x-byteranges; boundary=$boundary");
		else {
			header("Content-Type: $type");
		}

		// IE6 "save as" chokes on pragma no-cache or no-cache being 
		// first on the Cache-Control header

		if (!$msie)
			header("Pragma: no-cache");

		header("Expires: 0");
		header("Cache-Control: no-store, no-cache, must-revalidate");

		if ($acceptranges)
			header("Accept-Ranges: bytes");

		header("Content-Length: $content_length");

		//output the content


		ob_end_clean();

		foreach ($ranges as $range) {
			$first = $result[$range]['first'];
			$last  = $result[$range]['last'];

			if ($multipart)
				echo "\r\n--$boundary\r\n";

			if ($partial) {
				echo "Content-type: $type\r\n";
				echo "Content-range: bytes $first-$last/$filesize\r\n\r\n";
			}

			$buffer_size = 8096;

			fseek($fp, $first);

			if ( ($last + 1) == $filesize )
				fpassthru($fp);
			else {
				$bytes_left=$last-$first+1;

				while($bytes_left>0 && !feof($fp)) {

					if($bytes_left>$buffer_size)
						$bytes_to_read=$buffer_size;
					else
						$bytes_to_read=$bytes_left;

					$bytes_left-=$bytes_to_read;

					echo fread($fp, $bytes_to_read);

					flush();
				}
			}
		}

		if ($multipart)
			echo "\r\n--$boundary--\r\n";

  		fclose($fp);

		ob_start(); // restart buffering so we can throw away any extraneous output after this point

		return true;
	}
}

