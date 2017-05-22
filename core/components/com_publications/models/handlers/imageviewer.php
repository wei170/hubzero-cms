<?php
/**
 * @package		hubzero-cms
 * @author		Shawn Rice <zooley@purdue.edu>
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
 * Image Viewer Handler
 */
class ImageViewer extends Base
{
	/**
	* Handler type name
	*
	* @var		string
	*/
	protected	$_name 		= 'imageviewer';

	/**
	* Configs
	*
	* @var
	*/
	protected	$_config 	= NULL;

	/**
	* Image Helper
	*
	* @var
	*/
	protected	$_imgHelper = NULL;

	/**
	 * Get default params for the handler
	 *
	 * @return  void
	 */
	public function getConfig($savedConfig = array())
	{
		// Defaults
		$configs = array(
			'name' 			=> 'imageviewer',
			'label' 		=> 'Image Gallery',
			'title' 		=> 'Viewer for image files',
			'about'			=> 'Selected images will be viewed together in a slideshow',
			'params'	=> array(
				'allowed_ext' 		=> array('gif', 'jpg', 'png', 'bmp', 'jpeg'),
				'required_ext' 		=> array(),
				'min_allowed' 		=> 1,
				'max_allowed' 		=> 1000,
				'thumbSuffix' 		=> '_tn',
				'thumbFormat' 		=> 'png',
				'thumbWidth' 		=> '100',
				'thumbHeight' 		=> '60',
				'masterWidth' 		=> '600',
				'masterHeight' 		=> '400',
				'defaultThumb'		=> '/core/components/com_publications/site/assets/img/resource_thumb.gif',
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
	public function cleanUp( $path, $configs = NULL, $md5 = NULL )
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		$thumbName = \Components\Publications\Helpers\Html::createThumbName(
			basename($path),
			$this->_config->params->thumbSuffix,
			$this->_config->params->thumbFormat
		);
		$thumbPath = dirname($path) . DS . $thumbName;

		if (is_file($thumbPath))
		{
			$md5 = hash_file('sha256', $thumbPath);
			Filesystem::delete($thumbPath);
		}

		// Get master and default thumb
		if (!empty($configs))
		{
			$masterThumb  = $configs->pubBase . DS . 'thumb.gif';
			$masterCover  = $configs->pubBase . DS . 'master.png';

			// If image was used as default, delete it
			if (is_file($masterThumb) &&  hash_file('sha256', $masterThumb) == $md5)
			{
				// Delete master thumbnail
				Filesystem::delete($masterThumb);

				// Remove master cover
				if (is_file($masterCover))
				{
					Filesystem::delete($masterCover);
				}
			}
		}

		return true;
	}

	/**
	 * Make image default for publication
	 *
	 * @return  void
	 */
	public function makeDefault( $row, $pub, $configs)
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		// TBD - to come from component configs
		$defaultMasterName  = 'master.png';
		$defaultThumbName 	= 'thumb.gif';

		$path = $this->getFilePath($row->path, $row->id, $configs, $row->params);

		// No file found
		if (!is_file($path))
		{
			return false;
		}

		// Check if image
		if (!getimagesize($path))
		{
			return false;
		}

		$copyToThumb  = $configs->pubBase . DS . $defaultThumbName;
		$copyToMaster = $configs->pubBase . DS . $defaultMasterName;

		$thumbName = \Components\Publications\Helpers\Html::createThumbName(
			basename($path),
			$this->_config->params->thumbSuffix,
			$this->_config->params->thumbFormat
		);
		$thumbPath = dirname($path) . DS . $thumbName;

		// Copy to master
		if (is_file($path))
		{
			Filesystem::copy($path, $copyToMaster);

			// Create/update thumb
			Filesystem::copy($path, $copyToThumb);

			$hi = new \Hubzero\Image\Processor($copyToThumb);
			if (count($hi->getErrors()) == 0)
			{
				$hi->resize(100, false, true, true);
				$hi->save($copyToThumb);
			}
		}
		else
		{
			return false;
		}

		// Get current default
		$currentDefault = new \Components\Publications\Tables\Attachment( $this->_parent->_db );
		$currentDefault->getDefault($row->publication_version_id);

		// Unmark as default
		if ($currentDefault->id)
		{
			$currentDefault->saveParam($currentDefault, 'pubThumb', '');
		}

		// Mark this image as default
		$currentDefault->saveParam($row, 'pubThumb', '1');

		return true;
	}

	/**
	 * Show attachments in an image band (gallery)
	 *
	 * @return  void
	 */
	public function showImageBand($pub)
	{
		// Get element manifest to deliver content as intended
		$elements = $pub->_curationModel->getElements(3);

		if (empty($elements))
		{
			return false;
		}

		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		// Show first element
		$element = $elements[0];

		$manifest 		= $element->manifest;
		$params   		= $manifest->params->typeParams;
		$dirHierarchy 	= isset($params->dirHierarchy) ? $params->dirHierarchy : 1;

		// Get files directory
		$directory = isset($params->directory) && $params->directory
							? $params->directory : $pub->secret;
		$pubPath = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, '', $directory, 0);

		$configs 		= new stdClass;
		$configs->dirHierarchy = $dirHierarchy;
		$configs->pubPath = $pubPath;

		// Do we have attachments?
		$attachments = isset($pub->_attachments['elements'][$element->id])
					? $pub->_attachments['elements'][$element->id] : NULL;

		if (!$attachments)
		{
			return false;
		}

		$html = '';
		$i    = 0;

		foreach ($attachments as $attach)
		{
			$fpath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);

			$thumbName = \Components\Publications\Helpers\Html::createThumbName(
				basename($fpath),
				$this->_config->params->thumbSuffix,
				$this->_config->params->thumbFormat
			);
			$thumbPath = dirname($fpath) . DS . $thumbName;

			if (is_file(PATH_APP . DS . $fpath) && is_file(PATH_APP . DS . $thumbPath))
			{
				// Get extentsion
				$ext = Filesystem::extension(PATH_APP . DS . $fpath);

				$title = $attach->title ? $attach->title : basename($attach->path);
				$link  = Route::url($pub->link('versionid')) . '/Image:' . basename($fpath);
				$rel   = ($ext == 'swf' || $ext == 'mov') ? '' : ' rel="lightbox"';
				$class = ($ext == 'swf' || $ext == 'mov') ? ' class="video"' : '';

				$html .= ' <a ' . $class . ' ' . $rel . '  href="' . $link . '" title="' . $title . '">';
				$html .= '<img src="' . Route::url($pub->link('versionid')) . '/Image:' . $thumbName . '" alt="' . $title . '" class="thumbima" /></a>';

				$i++;
			}
		}

		if ($i > 0)
		{
			$view = new \Hubzero\Component\View(array(
				'base_path' => PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'site',
				'name'      => 'view',
				'layout'    => '_gallery',
			));

			$view->content  = $html;
			return $view->loadTemplate();
		}

		return;
	}

	/**
	 * Draw list of included files
	 *
	 * @return  void
	 */
	public function drawList($attachments, $attConfigs, $pub, $authorized )
	{
		if (!$attachments)
		{
			return false;
		}

		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		$path = str_replace(PATH_APP, '', $attConfigs->pubPath);

		$html = '';

		foreach ($attachments as $attach)
		{
			$fpath = $this->getFilePath($attach->path, $attach->id, $attConfigs, $attach->params);
			$fpath = str_replace(PATH_APP, '', $fpath);

			$thumbName = \Components\Publications\Helpers\Html::createThumbName(
				basename($fpath),
				$this->_config->params->thumbSuffix,
				$this->_config->params->thumbFormat
			);
			$title 		= $attach->title ? $attach->title : $attConfigs->title;
			$title 		= $title ? $title : basename($attach->path);

			$params = new \Hubzero\Config\Registry( $attach->params );

			$html .= '<li>';
			$html .= ' <a rel="lightbox" href="/publications' . DS . $pub->id . DS . $pub->version_id . '/Image:' . basename($fpath) . '">';
			$html .= '<span class="item-image';
			$html .= $params->get('pubThumb', NULL) && $authorized == 'administrator' ? ' starred' : '';
			$html .= '"><img src="/publications' . DS . $pub->id . DS . $pub->version_id . '/Image:' . $thumbName . '" alt="' . $title . '" class="thumbima" /></span>';
			$html .= '<span class="item-title">' . $title . '<span class="details">' . $attach->path . '</span></span>';
			$html .= '</a>';
			$html .= '<span class="clear"></span>';
			$html .= '</li>';
		}

		return $html;
	}

	/**
	 * Make thumb
	 *
	 * @return  void
	 */
	public function makeThumbnail( $row, $pub, $configs)
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		$fpath = $this->getFilePath($row->path, $row->id, $configs, $row->params);

		$thumbName = \Components\Publications\Helpers\Html::createThumbName(
			basename($fpath),
			$this->_config->params->thumbSuffix,
			$this->_config->params->thumbFormat
		);
		$thumbPath = $configs->pubPath . DS . $thumbName;

		// No file found
		if (!is_file($fpath))
		{
			return;
		}

		// Check if image
		if (!getimagesize($fpath))
		{
			return false;
		}

		$md5 = hash_file('sha256', $fpath);

		// Create/update thumb if doesn't exist or file changed
		if (!is_file($thumbPath) || $md5 != $row->content_hash)
		{
			Filesystem::copy($fpath, $thumbPath);
			$hi = new \Hubzero\Image\Processor($thumbPath);
			if (count($hi->getErrors()) == 0)
			{
				$hi->resize($this->_config->params->thumbWidth, false, true, true);
				$hi->save($thumbPath);
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Draw attachment
	 *
	 * @return  void
	 */
	public function drawAttachment($data, $params)
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		// Metadata file?
		$layout =  ($data->get('ext') == 'csv') ? 'file' : 'image';

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=>'projects',
				'element'	=>'publications',
				'name'		=>'attachments',
				'layout'	=>$layout
			)
		);
		$view->data    		= $data;
		$view->config  		= $this->_config;
		$view->params 		= $params;

		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Build file path depending on configs
	 *
	 * @return  string
	 */
	public function getFilePath( $path, $id, $configs = NULL, $params = NULL, $suffix = NULL )
	{
		// Do we transfer file with subdirectories?
		if ($configs->dirHierarchy == 1)
		{
			$fpath = $configs->pubPath . DS . trim($path, DS);
		}
		elseif ($configs->dirHierarchy == 2)
		{
			if (!$suffix && $params)
			{
				// Get file attachment params
				$fParams = new \Hubzero\Config\Registry( $params );
				$suffix  = $fParams->get('suffix');
			}

			// Do not preserve dir hierarchy, but append number for same-name files
			$name 	= $suffix ? \Components\Projects\Helpers\Html::fixFileName(basename($path), ' (' . $suffix . ')') : basename($path);
			$fpath  = $configs->pubPath . DS . $name;
		}
		else
		{
			// Attach record number to file name
			$name 	= \Components\Projects\Helpers\Html::fixFileName(basename($path), '-' . $id);
			$fpath  = $configs->pubPath . DS . $name;
		}

		return $fpath;
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
		// Incoming
		$active = trim(Request::getVar( 'o', NULL )); // Requested image

		$database = \App::get('db');

		$attachments = $editor->get('attachments');

		// Get attachment model
		$modelAttach = new \Components\Publications\Models\Attachments($database);

		// Get image files
		$images = array();

		// Get metadata
		$meta = array();
		if ($editor->get('configured'))
		{
			// Do we have a metadata file?
			// If file found, load metadata from file
		}

		// Draw images
		$view = new \Hubzero\Component\View(array(
			'base_path' => PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'site',
			'name'      => 'handlers',
			'layout'    => 'imagegallery',
		));
		return $view->loadTemplate();
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