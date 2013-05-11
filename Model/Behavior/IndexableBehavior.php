<?php
/**
 * IndexableBehavior file
 *
 * Indexable Behavior
 * Index a model onSave, onEdit and onUpdate
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2013, Wan Qi Chen <kami@kamisama.me>
 * @link          http://cakeresque.kamisama.me
 * @package       Bounce
 * @subpackage    Bounce.Model.Behavior
 * @since         0.0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Indexable Behavior
 */
App::uses('HttpSocket', 'Network/Http');
class IndexableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'mapping' => false // False, index all fields
	);

	public $settings = array();

	public function setup(Model $Model, $settings = array()) {
		$this->_defaults['type'] = Inflector::underscore($Model->alias);

		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);
		$this->HttpSocket = new HttpSocket();
		$this->settings[$Model->alias]['request'] = array(
			'uri' => array(
				'host' => Configure::read('Bounce.host'),
				'port' => Configure::read('Bounce.port'),
				'path' => $this->settings[$Model->alias]['index'] . '/' . $this->settings[$Model->alias]['type'] . '/'
			)
		);
	}

	public function afterSave(Model $Model, $created) {
		if (!$this->beforeIndex($Model) || empty($Model->data)) {
			return true;
		}

		$data = $this->filterData($Model, $Model->data);
		if (empty($data)) {
			return true;
		}

		if ($created) {
			$this->_addIndex($Model, $Model->id, $data);
		} else {
			$this->_updateIndex($Model, $Model->id, $data);
		}

		return true;
	}

	public function afterDelete(Model $Model) {
		if (!$this->beforeIndex($Model)) {
			return true;
		}

		if (empty($Model->id)) {
			return false;
		}

		return $this->_deleteIndex($Model, $Model->id);
	}

/**
 * To execute before indexing an entry
 *
 * @param  Model  	$Model Model of the entry to index
 * @return Boolean 	true to continue with indexation, false to abort
 */
	public function beforeIndex(Model $Model) {
		return true;
	}

/**
 * Filter out all irrelevant datas we don't want to index
 * from an array
 *
 * @precondition 	$data is not empty
 *
 * @param  Model 	$Model  	Model to index
 * @param  array 	$data   	$data passed to save()
 * @return array 				array of data to send to elastic search for indexing
 */
	public function filterData($Model, $data) {
		if ($this->settings[$Model->alias]['mapping'] === false) {
			return $data[$Model->alias];
		}

		$whitelist = Hash::flatten($this->settings[$Model->alias]['mapping']);
		$fields = Hash::flatten($data[$Model->alias]);

		if (empty($whitelist)) {
			return array();
		}

		$fields = array_intersect_key($fields, $whitelist);
		return Hash::expand($fields);
	}

	protected function _addIndex($Model, $id, $data) {
		return $this->_sendRequest('PUT', $this->settings[$Model->alias]['request'], $id, $data);
	}

	protected function _updateIndex($Model, $id, $data) {
		return $this->_sendRequest('POST', $this->settings[$Model->alias]['request'], $id . '/_update', $data);
	}

	protected function _deleteIndex($Model, $id) {
		return $this->_sendRequest('DELETE', $this->settings[$Model->alias]['request'], $id);
	}

	protected function _sendRequest($method, $settings, $query, $body = null) {
		$settings['uri']['path'] .= $query;
		$settings['method'] = $method;

		if ($body !== null) {

			if ($method === 'POST') {
				$body = array('doc' => $body);
			}

			$settings['body'] = json_encode($body);
		}

		$this->HttpSocket->request($settings);
	}

	public function map($Model) {
		$mapping = Hash::flatten($this->settings[$Model->alias]['mapping']);
		foreach ($mapping as $key => $value) {
			$mapping[$key] = array('type' => $value);
		}

		$data = array(
			$this->settings[$Model->alias]['index'] => array(
				'properties' => $mapping
			)
		);

		return $this->_sendRequest('PUT', $this->settings[$Model->alias]['request'], '_mapping', $data);
	}

}

function compArray ($val1, $val2) {
	if (is_array($val1) && is_array($val2)) {
		return array_intersect_ukey($val1, $val2, 'compArray');
	} else {
		return strcasecmp($val1, $val2);
	}
};
