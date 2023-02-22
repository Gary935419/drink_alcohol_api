<?php
/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade fuel without losing your custom config.
 */

return array(
	'driver' => 'auth',
	'verify_multiple_logins' => true,
	'salt' => 'v1JquxVG1D4MAOZdo3iFS2CGV',
	'iterations' => 10000,
	'expire_time' => '+1 hour',
	'ttl' => '3600',
);
