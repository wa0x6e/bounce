<?php
/**
 * Index Shell file
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
 * @subpackage    Bounce.Console.Command
 * @since         0.0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Index Shell
 */
App::uses('ClassRegistry', 'Utility');
class IndexShell extends AppShell {

	public function mapping() {
		if (!isset($this->args[0]) || empty($this->args[0])) {
			return $this->err('Please input a model');
		}

		$model = ClassRegistry::init($this->args[0]);
		if ($model->Behaviors->loaded('Indexable')) {
			$model->map();
		} else {
			return $this->err('This model is not attached to the indexable behavior');
		}
	}
}
