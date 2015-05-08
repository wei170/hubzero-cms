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
 * @author    Ilya Shunko <ishunko@purdue.edu>
 * @copyright Copyright 2005-2012 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Product viewing controller class
 */
class StorefrontControllerProduct extends \Hubzero\Component\SiteController
{
	/**
	 * Execute a task
	 *
	 * @return     void
	 */
	public function execute()
	{
		require_once(dirname(__DIR__) . DS . 'models' . DS . 'Warehouse.php');
		$this->warehouse = new StorefrontModelWarehouse();

		parent::execute();
	}

	/**
	 * Display product
	 *
	 * @param		$pId
	 * @return     	void
	 */
	public function displayTask()
	{
		$pId = $this->warehouse->productExists(Request::getVar('product', ''));
		if (!$pId)
		{
			App::abort(404, Lang::txt('COM_STOREFRONT_PRODUCT_NOT_FOUND'));
		}

		$this->view->pId = $pId;
		$this->view->css();
		$this->view->js('product_display.js');

		// A flag whether the item is available for purchase (for any reason, used by the auditors)
		$productAvailable = true;

		$pageMessages = array();

		// Get the cart
		require_once(PATH_CORE . DS . 'components' . DS . 'com_cart' . DS . 'models' . DS . 'CurrentCart.php');
		$cart = new CartModelCurrentCart();

		// POST add to cart request
		$addToCartRequest = Request::getVar('addToCart', false, 'post');
		$options = Request::getVar('og', false, 'post');
		$qty = Request::getInt('qty', 1, 'post');

		if ($addToCartRequest)
		{
			// Initialize errors array
			$errors = array();

			// Check if passed options/productID map to a SKU
			try
			{
				$sku = $this->warehouse->mapSku($pId, $options);
				$cart->add($sku, $qty);
			}
			catch (Exception $e)
			{
				$errors[] = $e->getMessage();
				$pageMessages[] = array($e->getMessage(), 'error');
			}

			if (!empty($errors))
			{
				$this->view->setError($errors);
			}
			else
			{
				// prevent resubmitting by refresh
				// If not an ajax call, redirect to cart
				$redirect_url  = Route::url('index.php?option=' . 'com_cart');
				App::redirect($redirect_url);
			}
		}

		// Get the product info
		$product = $this->warehouse->getProductInfo($pId);
		$this->view->product = $product;

		// Run the auditor
		require_once(PATH_CORE . DS . 'components' . DS . 'com_cart' . DS . 'helpers' . DS . 'Audit.php');
		$auditor = Audit::getAuditor($product, $cart->getCartInfo()->crtId);
		$auditorResponse = $auditor->audit();
		//print_r($auditor); die;

		if (!empty($auditorResponse) && $auditorResponse->status != 'ok')
		{
			if ($auditorResponse->status == 'error')
			{
				// Product is not available for purchase
				$productAvailable = false;
				foreach ($auditorResponse->notices as $notice)
				{
					$pageMessages[] = array($notice, 'warning');
				}
			}
		}

		// Get option groups with options and SKUs
		$data = $this->warehouse->getProductOptions($pId);
		if ($data)
		{
			//JError::raiseError(404 , Lang::txt('COM_STOREFRONT_PRODUCT_ERROR'));
			$this->view->options = $data->options;
		}
		//print_r($data); die;

		// Find a price range for the product
		$priceRange = array('high' => 0, 'low' => false);

		/*
			Find if there is a need to display a product quantity dropdown on the initial view load. It will be only displayed for single SKU that allows multiple items.
			For multiple SKUs it will be generated by JS (no drop-down for non-JS users, sorry)
		*/
		$qtyDropDownMaxVal = 0;

		$inStock = true;
		if (!$data || !count($data->skus))
		{
			$inStock = false;
		}
		$this->view->inStock = $inStock;

		if ($data && count($data->skus) == 1)
		{
			// Set the max value for the dropdown QTY
			// TODO: add it to the SKU table to set on the per SKU level
			$qtyDropDownMaxValLimit = 20;

			// Get the first and the only value
			$sku = array_shift(array_values($data->skus));

			// If no inventory tracking, there is no limit on how many can be purchased
			$qtyDropDownMaxVal = $qtyDropDownMaxValLimit;
			if ($sku['info']->sTrackInventory)
			{
				$qtyDropDownMaxVal = $sku['info']->sInventory;
			}

			if ($qtyDropDownMaxVal < 1)
			{
				$qtyDropDownMaxVal = 1;
			}
			// Limit to max number
			elseif ($qtyDropDownMaxVal > $qtyDropDownMaxValLimit)
			{
				$qtyDropDownMaxVal = $qtyDropDownMaxValLimit;
			}

			// If the SKU doesn't allow multiple items, set the dropdown to 1
			if (!$sku['info']->sAllowMultiple)
			{
				$qtyDropDownMaxVal = 1;
			}
		}

		$this->view->qtyDropDown = $qtyDropDownMaxVal;

		if ($data)
		{
			foreach ($data->skus as $sId => $info)
			{
				$info = $info['info'];

				if ($info->sPrice > $priceRange['high'])
				{
					$priceRange['high'] = $info->sPrice;
				}
				if (!$priceRange['low'] || $priceRange['low'] > $info->sPrice)
				{
					$priceRange['low'] = $info->sPrice;
				}
			}
		}
		$this->view->price = $priceRange;

		// Add custom page JS
		if ($data && (count($data->options) > 1 || count($data->skus) > 1))
		{
			$js = $this->getDisplayJs($data->options, $data->skus);

			Document::addScriptDeclaration($js);
		}

		// Get images (if any), gets all images from /site/storefront/products/$pId
		$allowedImgExt = array('jpg', 'gif', 'png');
		$productImg = array();
		$imgWebPath = DS . 'site' . DS . 'storefront' . DS . 'products' . DS . $pId;
		$imgPath = PATH_APP . $imgWebPath;

		if (file_exists($imgPath))
		{
			$files = scandir($imgPath);
			foreach ($files as $file)
			{
				if (in_array(pathinfo($file, PATHINFO_EXTENSION), $allowedImgExt))
				{
					if (substr($file, 0, 7) == 'default')
					{
						// Let the default image to be the first one
						array_unshift($productImg, $imgWebPath . DS . $file);
					}
					else
					{
						$productImg[] = $imgWebPath . DS . $file;
					}
				}
			}
		}
		else
		{
			$productImg[] = DS . 'site' . DS . 'storefront' . DS . 'products' . DS . 'noimage.png';
		}
		$this->view->productImg = $productImg;

		$this->view->productAvailable = $productAvailable;

		//build pathway
		$this->_buildPathway($product->pName);

		// Set notifications
		$this->view->notifications = $pageMessages;

		$this->view->display();
	}

	/**
	 * Generate JS needed for displaying a product page
	 *
	 * @param		void
	 * @return     	void
	 */
	private function getDisplayJs($ops, $skus)
	{
		$js = "\tSF.OPTIONS = {\n";

			// generate skus
			$js .= "\t\tskus: [\n";

			// generate pricing
			$skuPrices = "\t\tskuPrices: [";

			// generate inventory level for each SKU (for the number of products drop-down)
			$inventory = "\t\tskuInventory: [";

			$i = 0;
			foreach ($skus as $sId => $data)
			{
				$options = $data['options'];
				$info = $data['info'];
				$js .=  "\t\t\t[";

				if ($i)
				{
					$skuPrices .= ',';
				}
				// convert price to integer for precision
				$skuPrices .= '"' . $info->sPrice * 100 . '"';

				// inventory
				if ($i)
				{
					$inventory .= ',';
				}
				if (!$info->sAllowMultiple)
				{
					$inventory .= '1';
				}
				elseif (!$info->sTrackInventory)
				{
					$inventory .= '20';
				}
				elseif (empty($info->sInventory))
				{
					$inventory .= '1';
				}
				else {
					$inventory .= $info->sInventory > 20 ? 20 : $info->sInventory;
				}

				$sku = '';
				foreach ($options as $option)
				{
					if ($sku)
					{
						$sku .= ', ';
					}
					$sku .= '"' . $option . '"';
				}
				$js .= $sku;
				$js .= "]";
				$i++;

				if (count($skus) > $i)
				{
					$js .= ',';
				}
				$js .= "\n";
			}

			$js .= "\t\t],\n";

			$js .= "\n";

			/*
				skus: [
					["1", "4", "6"],
					["2", "4", "7"],
					["3", "5", "6"],
					["2", "5", "8"],
					["2", "5", "7"]
				],
			*/

			$skuPrices .= "],\n";
			$js .= $skuPrices;

			$js .= "\n";

			$inventory .= "],\n";
			$js .= $inventory;

			$js .= "\n";

			/*
				skuPrices: ["10", "5", "7"],
			*/

			// Generate ops
			$js .= "\t\tops: [\n";

			$i = 0;
			foreach ($ops as $oId => $data)
			{
				$options = $data['options'];
				$js .=  "\t\t\t[";

				$optionIds = '';
				foreach ($options as $option)
				{
					if ($optionIds)
					{
						$optionIds .= ', ';
					}
					$optionIds .= '"' . $option->oId . '"';
				}
				$js .= $optionIds;
				$js .= "]";
				$i++;

				if (count($ops) > $i)
				{
					$js .= ',';
				}
				$js .= "\n";
			}

			$js .= "\t\t],\n";

			/*
			ops: [
				["1", "2", "3"],
				["4", "5"],
				["6", "7", "8"]
			]
			*/

		$js .= "\t}";
		return $js;
	}

	/**
	 * Method to set the document path
	 *
	 * @param      array $course_pages List of roup pages
	 * @return     void
	 */
	public function _buildPathway($product)
	{
		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if ($this->_task)
		{
			Pathway::append(
				Lang::txt($product)
			);
		}
	}
}

