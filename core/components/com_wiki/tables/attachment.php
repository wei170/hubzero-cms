<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Components\Wiki\Tables;

/**
 * Wiki table class for file attachments
 */
class Attachment extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  Database
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__wiki_attachments', 'id', $db);
	}

	/**
	 * Load a record and bind to $this
	 *
	 * @param   mixed    $keys    Alias or ID
	 * @param   inteher  $pageid  Parent page ID
	 * @return  boolean  True on success
	 */
	public function load($oid=NULL, $pageid=NULL)
	{
		if ($oid === NULL)
		{
			return false;
		}

		if (is_string($oid))
		{
			return parent::load(array(
				'filename' => $oid,
				'pageid'   => $pageid
			));
		}

		return parent::load($oid);
	}

	/**
	 * Get a record ID based on filename and page ID
	 *
	 * @param   string  $filename  File name
	 * @param   string  $pageid    Parent page ID
	 * @return  array
	 */
	public function getID($filename, $pageid)
	{
		$this->_db->setQuery("SELECT id, description FROM $this->_tbl WHERE filename=" . $this->_db->quote($filename) . " AND pageid=" . $this->_db->quote($pageid));
		return $this->_db->loadRow();
	}

	/**
	 * Delete a record based on parent page and filename
	 *
	 * @param   string   $filename  File name
	 * @param   string   $pageid    Parent page ID
	 * @return  boolean  False if errors, true on success
	 */
	public function deleteFile($filename, $pageid)
	{
		if (!$filename || !$pageid)
		{
			return false;
		}

		$this->_db->setQuery("DELETE FROM $this->_tbl WHERE filename=" . $this->_db->quote($filename) . " AND pageid=" . $this->_db->quote($pageid));
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Turn attachment syntax into links
	 *
	 * @param   string  $text  Text to look for attachments in
	 * @return  string
	 */
	public function parse($text)
	{
		$f = '/\[\[file#[0-9]*\]\]/sU';
		return preg_replace_callback($f, array(&$this, 'getAttachment'), $text);
	}

	/**
	 * Processor for parse()
	 *
	 * @param   array   $matches  Attachment syntax string
	 * @return  string
	 */
	public function getAttachment($matches)
	{
		$match  = $matches[0];
		$tokens = preg_split('/#/', $match);
		$id = intval(end($tokens));

		$this->_db->setQuery("SELECT filename, description FROM $this->_tbl WHERE id=" . $this->_db->quote($id));
		$a = $this->_db->loadRow();

		if (is_file(PATH_APP . $this->path . DS . $this->pageid . DS . $a[0]))
		{
			if (preg_match("#bmp|gif|jpg|jpe|jpeg|tif|tiff|png#i", $a[0]))
			{
				return '<img src="' . $this->path . DS . $this->pageid . DS . $a[0] . '" alt="' . $a[1] . '" />';
			}
			else
			{
				$html  = '<a href="' . $this->path . DS . $this->pageid . DS . $a[0] . '" title="' . $a[1] . '">';
				$html .= ($a[1]) ? $a[1] : $a[0];
				$html .= '</a>';
				return $html;
			}
		}

		return '[file #' . $id . ' not found]';
	}

	/**
	 * Set the page ID for a record
	 *
	 * @param   integer  $oldid  Old page ID
	 * @param   integer  $newid  New page ID
	 * @return  boolean  False if errors, true on success
	 */
	public function setPageID($oldid=null, $newid=null)
	{
		if (!$oldid || !$newid)
		{
			return false;
		}

		$this->_db->setQuery("UPDATE $this->_tbl SET pageid=" . $this->_db->quote($newid) . " WHERE pageid=" . $this->_db->quote($oldid));
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Get filespace
	 *
	 * @return  string
	 */
	public function filespace()
	{
		static $path;

		if (!$path)
		{
			$path = PATH_APP . DS . trim(\Component::params('com_wiki')->get('filepath', '/site/wiki'), DS);
		}

		return $path;
	}
}
