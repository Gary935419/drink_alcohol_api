<?php

namespace App\Exceptions;

use Exception;

class OneException extends Exception
{
    /**
     * The business error code.
     */
    public $errorCode;

    /**
     * String array to replace %s in error message.
     */
    public $args;

    /**
     * The business error message.
     */
    public $message;

    /**
     * Create a new exception instance.
     *
     * @param  int  $errorCode
     * @param  string  $additional
     * @param  array  $logs
     * @return void
     */
    public function __construct($code=0,$str='')
    {
		$formated_code = sprintf('%05d', $code);
        $error_key = sprintf("errors.ERROR%04d", $code);
        $lang_msg = trans($error_key,[],'ja');
        $this->errorCode = $error_key;
        $this->message = $lang_msg;
        parent::__construct($lang_msg.$str." (CODE:{$formated_code})");
    }

    /**
     * Get validation error code.
     *
     * @return string
     */
    public function code()
    {
    	return $this->errorCode;
    }

    /**
     * Get validation error message.
     *
     * @return string
     */
    public function message()
    {
    	return $this->message;
    }

    public function __toString() {
		return __CLASS__ . ": [{$this->errorCode}]: {$this->message}\n";
	}
}
