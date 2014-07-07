<?php
/**
 * @package     hubzero-cms
 * @author      Steven Snyder <snyder13@purdue.edu>
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
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
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Resources Plugin class for classroom cluster visualization
 */
class plgResourcesClassrooms extends \Hubzero\Plugin\Plugin
{
	/**
	 * Return the alias and name for this category of content
	 *
	 * @param      object $resource Current resource
	 * @return     array
	 */
	public function onResourcesAreas($model)
	{
		$area = array();

		if ($model->isTool() && self::any($model->resource->alias))
		{
			$area['classrooms'] = JText::_('PLG_RESOURCES_CLASSROOMS');
		}

		return $area;
	}

	/**
	 * Return the alias and name for this category of content
	 *
	 * @param      string $alias
	 * @return     integer
	 */
	private static function any($alias)
	{
		static $any = array();

		if (!$alias)
		{
			return FALSE;
		}

		if (!isset($any[$alias]))
		{
			$jThrow = JError::$legacy;

			try
			{
				JError::$legacy = FALSE; // just throw an exception like a normal person, please

				$dbh = JFactory::getDBO();
				$dbh->setQuery('SELECT 1 FROM `#__resource_stats_clusters` WHERE toolname = ' . $dbh->quote($alias) . ' LIMIT 1');
				list($any[$alias]) = $dbh->loadColumn(0);
			}
			catch (\Exception $_ex)
			{
				$any[$alias] = FALSE;
			}

			JError::$legacy = $jThrow;
		}
		return $any[$alias];
	}

	/**
	 * Return data on a resource view (this will be some form of HTML)
	 *
	 * @param      object  $resource Current resource
	 * @param      string  $option    Name of the component
	 * @param      array   $areas     Active area(s)
	 * @param      string  $rtrn      Data to be returned
	 * @return     array
	 */
	public function onResources($model, $option, $areas, $rtrn='all')
	{
		$arr = array(
			'area'     => $this->_name,
			'html'     => '',
			'metadata' => ''
		);

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas))
		{
			if (!array_intersect($areas, $this->onResourcesAreas($model))
			 && !array_intersect($areas, array_keys($this->onResourcesAreas($model))))
			{
				$rtrn = 'metadata';
			}
		}
		if (false && !$model->type->params->get('plg_classrooms'))
		{
			return $arr;
		}

		// Are we returning HTML?
		if ($rtrn == 'all' || $rtrn == 'html')
		{
			$arr['html'] = array('<div id="no-usage"><p class="warning">' . JText::_('PLG_RESOURCES_CLASSROOMS_NO_DATA_FOUND') . '</p></div>');

			if (self::any($model->resource->alias))
			{
				\Hubzero\Document\Assets::addPluginStyleSheet($this->_type, $this->_name);
				\Hubzero\Document\Assets::addPluginScript($this->_type, $this->_name);
				\Hubzero\Document\Assets::addSystemScript('d3.v2.js');

				$dbh = JFactory::getDBO();
				// could have sworn I started with a subquery but it was too slow so I moved to a join. now it appears it must be the other way around. retaining this for a few revisions in case it inverts again
				/*
				$dbh->setQuery(
					"SELECT DISTINCT
						sc2.toolname AS tool,
						sc2.clustersize AS size,
						YEAR(sc2.cluster_start) AS year,
						sc2.cluster_start,
						sc2.cluster_end,
						sc2.first_use,
						SUBSTRING_INDEX(sc2.cluster, '|', 1) AS semester,
						CONCAT(SUBSTRING_INDEX(sc2.cluster, '|', 1), '|', SUBSTRING_INDEX(sc2.cluster, '|', -2)) AS cluster,
						SHA1(CONCAT(sc2.uidNumber, " . $dbh->quote(uniqid()) . ")) AS uid
					FROM #__resource_stats_clusters sc1
					LEFT JOIN #__resource_stats_clusters sc2 ON sc2.cluster = sc1.cluster
					WHERE sc1.toolname = " . $dbh->quote($model->resource->alias) . "
					ORDER BY cluster_start, first_use"
				);
				*/
				$dbh->setQuery(
					"SELECT
						sc.toolname AS tool,
						sc.clustersize AS size,
						YEAR(sc.cluster_start) AS year,
						sc.cluster_start,
						sc.cluster_end,
						sc.first_use,
						SUBSTRING_INDEX(sc.cluster, '|', 1) AS semester,
						CONCAT(SUBSTRING_INDEX(sc.cluster, '|', 1), '|', SUBSTRING_INDEX(sc.cluster, '|', -2)) AS cluster,
						SHA1(CONCAT(sc.uidNumber, ".$dbh->quote(uniqid()).")) AS uid
					FROM #__resource_stats_clusters sc
					WHERE sc.toolname = ".$dbh->quote($model->resource->alias)." AND sc.cluster IN
					(SELECT DISTINCT
						sc2.cluster
					FROM jos_resource_stats_clusters sc2
					WHERE sc2.toolname = ".$dbh->quote($model->resource->alias).")
					ORDER BY cluster_start, first_use"
				);

				$nodes = array();
				foreach ($dbh->loadAssocList() as $row)
				{
					if (!isset($nodes[$row['semester']]))
					{
						$nodes[$row['semester']] = array();
					}
					foreach (array('cluster_start', 'cluster_end', 'first_use') as $dateCol)
					{
						$row[$dateCol] = date('r', strtotime($row[$dateCol]));
					}
					$nodes[$row['semester']][] = $row;
				}
				$arr['html'][] = '<span id="cluster-data" data-tool="' . str_replace('"', '&quot;', $model->resource->alias) . '" data-seed="' . str_replace('"', '&quot;', json_encode(array_values($nodes))) . '"></span>';
			} else { die('none?'); }
			$arr['html'] = implode("\n", $arr['html']);
		}

		return $arr;
	}
}

