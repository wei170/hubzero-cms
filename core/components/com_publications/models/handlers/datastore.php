<?php
/**
 * @package		hubzero-cms
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 HUBzero Foundation, LLC.
 * @license		http://opensource.org/licenses/MIT MIT
 *
 * Copyright 2005-2009 HUBzero Foundation, LLC.
 * All rights reserved.
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
 */

namespace Components\Publications\Models\Handlers;

use Components\Publications\Models\Handler as Base;
use stdClass;

/**
 * DataStore Lite Handler
 */
class DataStore extends Base
{
	/**
	* Handler type name
	*
	* @var		string
	*/
	protected	$_name 		= 'datastore';

	/**
	* Configs
	*
	* @var
	*/
	protected	$_config 	= NULL;

	/**
	 * Get default params for the handler
	 *
	 * @return  void
	 */
	public function getConfig($savedConfig = array())
	{
		// Defaults
		$configs = array(
			'name' 			=> 'datastore',
			'label' 		=> 'Data Viewer',
			'title' 		=> 'Interactive data explorer',
			'about'			=> 'Selected CSV file will be viewed as a database',
			'params'	=> array(
				'allowed_ext' 		=> array('csv'),
				'required_ext' 		=> array('csv'),
				'min_allowed' 		=> 1,
				'max_allowed' 		=> 1,
				'enforced'			=> 0
			)
		);

		$this->_config = json_decode(json_encode($this->_parent->parseConfig($this->_name, $configs, $savedConfig)), FALSE);
		return $this->_config;
	}

	/**
	 * Clean-up related files
	 *
	 * @return  void
	 */
	public function cleanup( $path )
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		return true;
	}

	/**
	 * Draw list of included items
	 *
	 * @return  void
	 */
	public function drawList($attachments, $attConfigs, $pub, $authorized )
	{
		// No special treatment for this handler
		return;
	}

	/**
	 * Draw attachment
	 *
	 * @return  void
	 */
	public function drawAttachment($data, $params)
	{
		// No special treatment for this handler
		return;
	}

	/**
	 * Check for changed selections etc
	 *
	 * @return  object
	 */
	public function getStatus( $attachments )
	{
		// Start status
		$status = new \Components\Publications\Models\Status();
		return $status;
	}

	/**
	 * Draw handler status in editor
	 *
	 * @return  object
	 */
	public function drawStatus($editor)
	{
		return;
	}

	/**
	 * Draw handler editor content
	 *
	 * @return  object
	 */
	public function drawEditor($editor)
	{
		return;
	}

	/**
	 * Check against handler-specific requirements
	 *
	 * @return  object
	 */
	public function checkRequired( $attachments )
	{
		return true;
	}
}