kohana-bouncer
==============
Bouncer for efficient role management.

This module allow you to predefine your user's authorization in a configuration
file.

Usage
-----
```php
if (!Bouncer::factory()->may('admin')) 
{
    throw new HTTP_Exception_403('You are not allowed to perform login.');
}
```

Instead of writting
```php
if (!Auth::instance()->logged_in('admin')) 
{
    throw new HTTP_Exception_403('You are not allowed to perform login.');
}
```

If you want to test a specific user
```php
Boucer::factory()->user($user)->may('admin');
```

It gets more interesting when complex verifications are involved as the logic
is moved into a configuration file, keeping the Controller clean.

Configuration
-------------
Bouncer allow multiple policy setup
```php
return array(
    'strong'  => array(...),
    'weak'    => array(...),
    'testing' => array(...),
    ...
);
```

An action is defined by a path, here we have auth.login
```php
return array(
    'auth' => array(
        'login' => array('not' => 'login')
    )
);
```

To authorize a role to perform an action
```php
'my-account' => 'login'
```

You can negate an authorization by using 'not' as a key
```php
'login' => array('not' => 'login')
```

When involving multiple roles, you have to use wether 'and' or 'or' keys
```php
'watch-movies' => array(
    'or' => array(
        'user', 'not' => 'login'
    )
);
```

`and`, `or` and `not` are functionnaly sufficient to express any logical
expression.
