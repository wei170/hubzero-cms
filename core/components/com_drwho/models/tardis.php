<?php
namespace Components\Drwho\Models;

use Lang;

// Include the models we'll be using
require_once(__DIR__ . '/character.php');

/**
 * Drwho class for a TARDIS model
 */
class Tardis extends Character
{
	/**
	 * The table name
	 *
	 * @var  string
	 */
	protected $table = '#__drwho_characters';

	/**
	 * The color of the TARDIS
	 *
	 * @var  string
	 */
	public $color = 'blue';

	/**
	 * The shape of the TARDIS
	 *
	 * @var  string
	 */
	public $shape = 'box';

	/**
	 * Display the TARDIS
	 *
	 * @return  string
	 */
	public function render()
	{
		return '
             ___          
             | |          
             | |         
     ------------------- 
     ------------------- 
      |  ___  |  ___  | 
      | | | | | | | | | 
      | |-+-| | |-+-| | 
      | |_|_| | |_|_| | 
      |  ___  |  ___  | 
      | |   | | |   | | 
      | |   | | |   | | 
      | |___| | |___| | 
      |  ___  |  ___  | 
      | |   | | |   | | 
      | |   | | |   | | 
      | |___| | |___| | 
      |       |       | 
     =================== ';
	}

	/**
	 * Travel through space and time
	 *
	 * @param   string  $when   When to travel to
	 * @param   string  $where  Where to travel to
	 * @return  string
	 */
	public function travel($when='', $where='')
	{
		return '((VWORP))<br />((VWORP))<br />((VWORP))';
	}

	/**
	 * Get the color
	 *
	 * @return  string
	 */
	public function color()
	{
		return '((VWORP))<br />((VWORP))<br />((VWORP))';
	}

	/**
	 * Disguise the TARDIS
	 *
	 * @param   string   $as  What to disguise itself as?
	 * @return  boolean  True if 'policebox', False if anything else
	 */
	public function disguise($as='policebox')
	{
		if ($as == 'policebox')
		{
			return true;
		}

		$this->setError(Lang::txt('COM_DRWHO_BROKEN_CHAMELEON_CIRCUIT'));
		return false;
	}
}

