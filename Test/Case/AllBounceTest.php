<?php

/**
 * View Group Test for Bounce
 *
 * PHP versions 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2012, Wan Qi Chen <kami@kamisama.me>
 * @link          http://cakeresque.kamisama.me
 * @package       CakeResque
 * @subpackage	  CakeResque.Lib
 * @since         1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/

/**
 * AllTest class
 *
 * @package 		CakeResque
 * @subpackage 	CakeResque.Test.Case
 */
class AllBounceTest extends CakeTestSuite
{

	public static function suite() {
		$suite = new CakeTestSuite('All tests');
		$suite->addTestDirectory(__DIR__ . DS . 'Config');
		$suite->addTestDirectory(__DIR__ . DS . 'Model' . DS . 'Behavior');
		return $suite;
	}
}
