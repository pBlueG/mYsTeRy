<?php

/**
 * Timer class
 * - adds/deletes and processes all incoming timers
 *
 * @author BlueG
 * @package mYsTeRy-v2
 * @access public
 * @version 2.0a
 */

Class Timer extends Singleton
{

	const NO_ARGUMENTS = 0;

	/**
	 * This array stores all timers
	 *
	 * @var array
	 * @access private
	 */
	private $m_aTimer = array();

	/**
	 * C'tor
	 */
	public function __construct() {}

	/**
	 * This function will add a new element to our public timer array
	 *
	 * @param object $obj Pointer to the class interface
	 * @param string $function Function to get called
	 * @param array $args Set of arguments for the function
	 * @param int $interval Time in seconds
	 * @param boolean $repeat Repeat the timer
	 * @access public
	 */
	public function _add($obj, $function, $args, $interval, $repeat = false)
	{
		$class_name = get_class($obj);
		if(class_exists($class_name)) {
			if(method_exists($class_name, $function) && @is_callable(array($class_name, $function))) {
				$hittime = time()+$interval;
				$this->m_aTimer[] = array(
					'Class' 	=> $obj,
					'Function' 	=> $function,
					'Arguments' 	=> $args,
					'Interval' 	=> $interval,
					'Repeat' 	=> $repeat,
					'Hit' 		=> $hittime
				);
				//$this->m_aTimer = array_values($this->m_aTimer);
				end($this->m_aTimer);
				$retID = key($this->m_aTimer);
				reset($this->m_aTimer);
				return $retID;
			}
		}
		return false;
		
	}

	/**
	 * This function will delete a element of the timer array
	 *
	 * @param string $function Function to delete
	 * @access public
	 */
	public function _delete($id)
	{
		if(isset($this->m_aTimer[$id])) {
			unset($this->m_aTimer[$id]);
			return true;
		}
		return false;
	}

	/**
	 *
	 * This function keeps track of all timers (executing as well as deleting)
	 *
	 */
	public function _update()
	{
		$current = time();
		foreach($this->m_aTimer as $idx => $Timer) {
			if($Timer['Hit'] <= $current) {
				$pMethod = new ReflectionMethod($Timer['Class'], $Timer['Function']);
				if($pMethod->isPrivate() || $pMethod->isProtected())
					$pMethod->setAccessible(true);
				if(is_array($Timer['Arguments']) && @count($Timer['Arguments']) > 0)
					$pMethod->invokeArgs(
						(is_object($Timer['Class']) ? $Timer['Class'] : NULL),
						$Timer['Arguments']
					);
				else
					$pMethod->invoke(
						(is_object($Timer['Class']) ? $Timer['Class'] : NULL)
					);
				unset($pMethod);
				if($Timer['Repeat'])
					$this->m_aTimer[$idx]['Hit'] = time()+$Timer['Interval'];
				else 
					$this->_delete($idx);

			}
		}
	}
}

?>