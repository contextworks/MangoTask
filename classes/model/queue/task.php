<?php
class Model_Queue_Task extends Mango {

	protected $_collection = 'tasks';
	protected $_type;

	protected $_fields = array(
		'type'      => array('type' => 'string', 'required' => TRUE),
		'status'   => array('type' => 'enum', 'values' => array('queued', 'active', 'failed', 'completed')),
		'created'  => array('type' => 'int'),
		'updated'  => array('type' => 'int'),
	);

	/**
	 * Finds and activates the next task to be executed
	 *
	 * @return   Model_Task   task (if not loaded, there is no next task)
	 */
	public function get_next()
	{
		$values = $this->db()->command( array(
			'findAndModify' => $this->_collection,
			'new'           => TRUE,
			'sort'          => array('created' => 1),
			'query'         => array('status' => array_search('queued', $this->_fields['status']['values'])),
			'update'        => array(
				'$set'    => array(
					'updated' => time(),
					'status'  => array_search('active', $this->_fields['status']['values'])
				)
			)
		));

		return Mango::factory('task', Arr::get($values,'value', array()), TRUE);
	}

	public function create($safe = TRUE)
	{
		// make sure some values are set
		$this->values( array(
			'status'  => 'queued',
			'type'    => isset($this->type) ? $this->type : $this->_type,
			'created' => time()
		));

		return parent::create($safe);
	}

	public function update( $criteria = array(), $safe = TRUE)
	{
		$this->updated = time();

		return parent::update($criteria, $safe);
	}

	/**
	 * Return error message of failed task for logging purposes
	 *
	 * @param   boolean   Return all possible error information
	 * @return   string   Error message
	 */
	public function error_message($all = FALSE)
	{
		return $this->message;
	}

	/**
	 * Check if task is valid (/can be executed)
	 *
	 * @return   boolean   Task can be executed
	 */
	public function valid()
	{
		return TRUE;
	}

	/**
	 * Execute task (overload this method)
	 *
	 * @param   int   Maximum number of tries before task is considered failed
	 * @return  boolean   Task was executed succesfully
	 */
	public function execute($max_tries = 1)
	{
		return TRUE;
	}

	public function as_array( $clean = TRUE )
	{
		$array = parent::as_array($clean);

		if ( ! $clean)
		{
			$array['valid'] = $this->valid();
		}

		return $array;
	}
}