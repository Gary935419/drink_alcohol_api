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
    public function __construct($code = 0, $additional='', Exception $previous=null, $extensionData=array(), $logs = [])
    {
        // parent::__construct('The given data was invalid.');

        $error_key = sprintf("errors.ERROR%04d", $code);
		$formated_code = sprintf('%05d', $code);
        $lang_msg = \Lang::get($error_key);

        if (is_array($additional) && $additional) {
			$lang_msg = vsprintf($lang_msg, $additional);
		} elseif (!is_array($additional) && $additional != "") {
			$lang_msg = sprintf($lang_msg, $additional);
        }
        
        if ($logs){
			$level = isset($logs['_type']) ? $logs['_type'] : 'debug';
			unset($logs['_type']);
			if(isset($_SERVER['REQUEST_URI'])) $logs['uri'] = $_SERVER['REQUEST_URI'];
			\Log::$level('OneException_'.$formated_code.': '.json_encode($logs));
		}
        // $this->errorCode = $errorCode;
        // $this->message = trans($errorCode, $args, 'ja');
        $this->errorCode = $error_key;
        $this->message = $lang_msg;
        parent::__construct($lang_msg." (コード:{$formated_code})", $code, $previous);
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
