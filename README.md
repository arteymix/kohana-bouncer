kohana-bouncer
==============

Bouncer for efficient role management.

This module allow you to predefine your user's authorization in a configuration
file.

## Usage

    if (!Bouncer::factory()->may('admin')) {
    
        throw new HTTP_Exception_403('You are not allowed to perform login.');
    }

Instead of writting

    if (!Auth::instance()->logged_in('admin')) {
    
        throw new HTTP_Exception_403('You are not allowed to perform login.');
    }

If you want to test a specific user

    Boucer::factory()->user($user)->may('admin');

It gets more interesting when complex verifications are involved as the logic
is moved into a configuration file, keeping the Controller clean.

## Configuration

Bouncer allow multiple policy setup

    return array(
        'strong' => array(...),
        'weak'   => array(...),
        'testing' => array(...),
        ...
    );

An action is defined by a path, here we have auth.login

    return array(
        'auth' => array(
            'login' => array('not' => 'login')
        )
    );

To authorize a role to perform an action

    'my-account' => 'login'

You can negate an authorization by using 'not' as a key

    'login' => array('not' => 'login')

When involving multiple roles, you have to use wether 'and' or 'or' keys

    'watch-movies' => array(
        'or' => array(
            'user', 'not' => 'login'
        )
    );

Theorically, 'and', 'or' and 'not' are functionnaly sufficient for expressing
any boolean expression.
