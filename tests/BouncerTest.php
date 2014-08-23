<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Tests for Bouncer module.
 * 
 * @package  Bouncer
 * @category Tests
 * @author   Guillaume Poirier-Morency <guillaumepoiriermorency@gmail.com>
 * @license  BSD 3-clauses
 */
class BouncerTest extends Unittest_TestCase {

	public function getAdmin()
	{
		return ORM::factory('User')
						->values(array(
							'username' => 'admin',
							'email' => 'admin@example.com',
							'password' => 'abcd1234'
						))->create()
						->add('roles', ORM::factory('Role', array('name' => 'admin')))
						->add('roles', ORM::factory('Role', array('name' => 'login')));
	}

	public function getUser()
	{
		return ORM::factory('User')
						->values(array(
							'username' => 'user',
							'password' => 'abcd1234',
							'email' => 'user@example.com',
						))->create()
						->add('roles', ORM::factory('Role', array('name' => 'login')));
	}

	public function getNobody()
	{
		return ORM::factory('User')
						->values(array(
							'username' => 'nobody',
							'password' => 'abcd1234',
							'email' => 'nobody@example.com',
						))->create();
	}

	public function getBouncer()
	{
		return new Bouncer(array(
			'admin' => 'admin',
			'login' => array('not' => 'login'),
			/**
			 * Logic tree.
			 */
			'pay' => array(
				'and' => array(
					'login', 'not' => 'admin' // only non-admin may pay
				)
			),
			'logout' => 'login', // logged in user may logout
			'invalid' => array(// 2 logic per level is invalid as we cannot infer wether to AND or OR operands
				'not' => 'admin',
				'not' => 'login'
			),
			/**
			 * Logic tree with 2 levels.
			 */
			'complex' => array(
				'or' => array(
					'and' => array('user', 'login'),
					'and' => array('admin', 'login')
				),
			)
		));
	}

	public function testSimpleRoleChecking()
	{

		$bouncer = $this->getBouncer();

		// simple role checking
		$this->assertTrue($bouncer->user($this->getAdmin())->may('admin'));
		$this->assertFalse($bouncer->user($this->getUser())->may('admin'));
	}

	public function testNegatedLogic()
	{

		$bouncer = $this->getBouncer();

		// negated logic
		$this->assertFalse($bouncer->user($this->getAdmin())->may('login'));
		$this->assertFalse($bouncer->user($this->getUser())->may('login'));
		$this->assertTrue($bouncer->user($this->getNobody())->may('login'));
	}

	public function testLogicTree()
	{

		$bouncer = $this->getBouncer();

		// and with 2 operands including a negated logic
		$this->assertFalse($bouncer->user($this->getAdmin())->may('pay'));
		$this->assertTrue($bouncer->user($this->getUser())->may('pay'));

		$this->assertFalse($bouncer->user($this->getNobody())->may('logout'));
	}

	public function testComplexLogicTree()
	{

		$bouncer = $this->getBouncer();

		$this->assertTrue($bouncer->user($this->getAdmin())->may('complex'));
		$this->assertFalse($bouncer->user($this->getUser())->may('complex'));
		$this->assertFalse($bouncer->user($this->getNobody())->may('complex'));
	}

	public function tearDown()
	{

		DB::delete('users')->execute();

		parent::tearDown();
	}

}
