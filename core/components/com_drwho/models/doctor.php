<?php
namespace Components\Drwho\Models;

// Include the models we'll be using
require_once(__DIR__ . '/character.php');

/**
 * Drwho model class for The Doctor
 */
class Doctor extends Character
{
	protected $table = '#__drwho_characters';

	/**
	 * Use the sonic screwdriver
	 *
	 * @return  string
	 */
	public function sonicScrewdriver()
	{
		return 'bzzzzzzzz';
	}

	/**
	 * Regenerate
	 *
	 * @return  boolean
	 */
	public function regenerate()
	{
		return true;
	}
}

