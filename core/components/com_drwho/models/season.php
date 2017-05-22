<?php
namespace Components\Drwho\Models;

use Hubzero\Database\Relational;
use Session;
use Date;

// Include the models we'll be using
require_once(__DIR__ . '/doctor.php');

/**
 * Drwho model class for a season
 */
class Season extends Relational
{
	/**
	 * The table namespace
	 *
	 * @var  string
	 **/
	protected $namespace = 'drwho';

	/**
	 * Default order by for model
	 *
	 * @var  string
	 */
	public $orderBy = 'ordering';

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array
	 */
	protected $rules = array(
		'title' => 'notempty'
	);

	/**
	 * Automatically fillable fields
	 *
	 * @var  array
	 **/
	public $always = array(
		'alias'
	);

	/**
	 * Generates automatic owned by field value
	 *
	 * @param   array   $data  the data being saved
	 * @return  string
	 */
	public function automaticAlias($data)
	{
		$alias = str_replace(' ', '-', $data['title']);
		return preg_replace("/[^a-zA-Z0-9\-]/", '', strtolower($alias));
	}

	/**
	 * Defines a belongs to one relationship between task and assignee
	 *
	 * @return  object
	 */
	public function creator()
	{
		return $this->belongsToOne('Hubzero\User\User', 'created_by');
	}

	/**
	 * Defines a belongs to one relationship between task and assignee
	 *
	 * @return  object
	 */
	public function doctor()
	{
		return $this->belongsToOne('Doctor', 'doctor_id');
	}

	/**
	 * Defines a one to many through relationship with records by way of tasks
	 *
	 * @return  $this
	 */
	public function characters()
	{
		return $this->manyToMany('Character', '#__drwho_character_seasons');
	}

	/**
	 * Return a formatted timestamp for when
	 * the season started (premiere).
	 *
	 * @param   string  $as  What format to return
	 * @return  string
	 */
	public function started($as='')
	{
		return $this->_datetime('premiere_date', $as);
	}

	/**
	 * Return a formatted timestamp for when
	 * the season ended (finale).
	 *
	 * @param   string  $as  What format to return
	 * @return  string
	 */
	public function ended($as='')
	{
		return $this->_datetime('finale_date', $as);
	}

	/**
	 * Return a formatted timestamp for the 
	 * created date
	 *
	 * @param   string  $as  What format to return
	 * @return  string
	 */
	public function created($as='')
	{
		return $this->_datetime('created', $as);
	}

	/**
	 * Return a formatted timestamp
	 *
	 * @param   string  $field  Datetime field to use [premiere_date, finale_date, created]
	 * @param   string  $as     What format to return
	 * @return  string
	 */
	protected function _datetime($field, $as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return Date::of($this->get($field))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get($field))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			case 'relative':
				return Date::of($this->get($field))->relative();
			break;

			default:
				if ($as)
				{
					return Date::of($this->get($field))->toLocal($as);
				}
				return $this->get($field);
			break;
		}
	}

	/**
	 * Generate and return various links to the entry
	 * Link will vary depending upon action desired, such as edit, delete, etc.
	 *
	 * @param      string $type   The type of link to return
	 * @param      mixed  $params String or array of extra params to append
	 * @return     string
	 */
	public function link($type='')
	{
		static $base;

		if (!isset($base))
		{
			$base = 'index.php?option=com_drwho';
		}

		$link = $base;

		// If it doesn't exist or isn't published
		switch (strtolower($type))
		{
			case 'edit':
				$link .= '&controller=seasons&task=edit&id=' . $this->get('id');
			break;

			case 'delete':
				$link .= '&controller=seasons&task=delete&id=' . $this->get('id') . '&' . Session::getFormToken() . '=1';
			break;

			case 'view':
			case 'permalink':
			default:
				$link .= '&controller=characters&season=' . $this->get('id');
			break;
		}

		return $link;
	}

	/**
	 * Deletes the existing/current model
	 *
	 * @return  bool
	 */
	public function destroy()
	{
		if (!$this->characters()->sync(array()))
		{
			return false;
		}

		return parent::destroy();
	}
}

