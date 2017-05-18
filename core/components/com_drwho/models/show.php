<?php
namespace Components\Drwho\Models;

use Hubzero\Base\Object;
use Component;
use User;

// Include the models we'll be using
require_once(__DIR__ . '/doctor.php');
require_once(__DIR__ . '/season.php');
require_once(__DIR__ . '/tardis.php');

/**
 * Drwho model class for the show
 */
class Show extends Object
{
	/**
	 * Registry
	 *
	 * @var  array
	 */
	private $config = null;

	/**
	 * DrwhoModelTardis
	 *
	 * @var  object
	 */
	private $tardis = null;

	/**
	 * Returns a reference to this model
	 *
	 * @return  object  Show
	 */
	static function &getInstance()
	{
		static $instance;

		if (!isset($instance))
		{
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Get a list or count of characters
	 *
	 * @param   string   $rtrn     What type of data to return
	 * @param   array    $filters  Filters to apply to the query
	 * @param   boolean  $clear    Clear cached data?
	 * @return  mixed
	 */
	public function characters()
	{
		return Character::all();
	}

	/**
	 * Get a list or count of seasons
	 *
	 * @param   string   $rtrn     What type of data to return
	 * @param   array    $filters  Filters to apply to the query
	 * @param   boolean  $clear    Clear cached data?
	 * @return  mixed
	 */
	public function seasons()
	{
		return Season::all();
	}

	/**
	 * Get a TARDIS model
	 *
	 * @return  object  Tardis
	 */
	public function tardis()
	{
		if (!$this->tardis)
		{
			$this->tardis = Tardis::all()->whereEquals('name', 'TARDIS');
		}

		return $this->tardis;
	}

	/**
	 * Get a parameter from the component config
	 *
	 * @param   string  $property  Param to return
	 * @param   mixed   $default   Value to return if property not found
	 * @return  mixed
	 */
	public function config($property=null, $default=null)
	{
		if (!isset($this->config))
		{
			$this->config = Component::params('com_drwho');
		}
		if ($property)
		{
			return $this->config->get($property, $default);
		}
		return $this->config;
	}

	/**
	 * Check a user's authorization
	 *
	 * @param   string   $action  Action to check
	 * @return  boolean  True if authorized, false if not
	 */
	public function access($action='view')
	{
		if (!$this->config()->get('access-check-done', false))
		{
			if (User::isGuest())
			{
				$this->config()->set('access-check-done', true);
			}
			else
			{
				$this->config()->set('access-admin-entry', User::authorise('core.admin', $this->get('id')));
				$this->config()->set('access-manage-entry', User::authorise('core.manage', $this->get('id')));
				$this->config()->set('access-delete-entry', User::authorise('core.manage', $this->get('id')));
				$this->config()->set('access-edit-entry', User::authorise('core.manage', $this->get('id')));
				$this->config()->set('access-edit-state-entry', User::authorise('core.manage', $this->get('id')));
				$this->config()->set('access-edit-own-entry', User::authorise('core.manage', $this->get('id')));

				$this->config()->set('access-check-done', true);
			}
		}
		return $this->config()->get('access-' . strtolower($action) . '-entry');
	}
}
