<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Boucer!
 * 
 * if (!Bouncer::factory()->user($user)->may('predefined action')) {
 * 
 *     throw new HTTP_Exception_403('You need the :role to perform :action.', $reason);
 * }
 * 
 * @package Bouncer
 * @author  Guillaume Poirier-Morency <guillaumepoiriermorency@gmail.com>
 * @license BSD 3-clauses
 */
class Kohana_Bouncer {

    public static $default = 'default';

    /**
     * 
     * @param  string $group
     * @return Bouncer
     */
    public static function factory($group = NULL) {

        if ($group === NULL) {

            $group = Bouncer::$default;
        }

        $rules = (array) Kohana::$config->load('bouncer.' . $group);

        return new Bouncer($rules);
    }

    /**
     * 
     * @param array $rules
     */
    public function __construct(array $rules) {

        $this->rules = $rules;

        $this->user = Auth::instance()->logged_in() ? Auth::instance()->get_user() : ORM::factory('User');
    }

    /**
     * 
     * 
     * @param  Model_Auth_User $user
     * @return \Bouncer
     */
    public function user(Model_Auth_User $user) {

        $this->user = $user;

        return $this;
    }

    /**
     * Check if a given user may do a given action.
     *
     * Applies rules recursively in the logic tree.
     *
     * @param Model_Auth_User $user
     * @param string          $action
     */
    public function may($action) {

        $rules = Arr::path($this->rules, $action);

        if ($rules === NULL) {

            throw new Kohana_Exception('No rules has been defined for action :action.', array(':action' => $action));
        }

        $roles = $this->user->roles->find_all()->as_array('id', 'name');

        // rules is a role
        if (is_string($rules)) {

            return in_array($rules, $roles, TRUE);
        }

        // associative arrays are logic tree
        if (Arr::is_assoc($rules)) {

            if (count($rules) !== 1) {

                throw new Kohana_Exception('Logic tree can only have one operator per level.');
            }

            $operator = Arr::get(array_keys($rules), 0);
            $operands = Arr::get(array_values($rules), 0);

            switch ($operator) {

                case 'and':

                    // false if any is false
                    foreach ($operands as $index => $operand) {

                        $not = ($index === 'not');

                        // check if operand is a rule
                        if (is_string($operand)) {

                            if (in_array($operand, $roles, TRUE) === $not) {

                                return FALSE;
                            }

                            continue;
                        }

                        // operand is a logic subtree
                        if ($this->may($action . '.and.' . $index) === $not) {

                            return FALSE;
                        }
                    }

                    return TRUE;

                case 'or':

                    // true if any is true
                    foreach ($operands as $index => $operand) {

                        $not = ($index === 'not');

                        // check if operand is a rule
                        if (is_string($operand)) {

                            if (in_array($operand, $roles, TRUE) !== $not) {

                                return TRUE;
                            }

                            continue;
                        }

                        // operand is a logic subtree
                        if ($this->may($action . '.or.' . $index) !== $not) {

                            return TRUE;
                        }
                    }

                    return FALSE;

                case 'not':

                    return !$this->may($action . '.not');

                default:

                    throw new Kohana_Exception('Undefined operand :operand in :action action.', array(':operand' => $operand, ':action' => $action));
            }
        }

        // negate anything as a default behiavior
        return FALSE;
    }

}
