<?php
    require_once(__DIR__."/common.php");

    class Log {
    	public static function auto($msg, $level = null){
    		if (is_null($level)){
    			$level = Common::$loggingLevel;
    		}
    		switch ($level){
	            case LOG_LVL_ERR:
	                Log::error($msg);
	                break;
	            case LOG_LVL_WARN:
	                Log::warning($msg);
	                break;
	            case LOG_LVL_MSG:
	                Log::msg($msg);
	                break;
	            default:
	                Log::debug($msg);
	                break;
	        }
    	}

	    public static function die($msg){
	    	if (Common::$allowLogging){
				Log::write("DIE", $msg);
			}
			exit(1);
	    }

	    public static function error($msg){
	    	if (Common::$allowLogging){
	    		Log::write("ERROR", $msg);
	    	}
	    }

	    public static function warning($msg){
	    	if (Common::$allowLogging && Common::$loggingLevel >= LOG_LVL_WARN){
	    		Log::write("WARN", $msg);
	    	}
	    }

	    public static function msg($msg){
	    	if (Common::$allowLogging && Common::$loggingLevel >=	LOG_LVL_MSG){
	    		Log::write("MSG", $msg);
	    	}
	    }

	    public static function debug($msg){
	    	if (Common::$allowLogging && Common::$loggingLevel == LOG_LVL_DEBUG){
	    		Log::write("DEBUG", $msg);
	    	}
	    }

	    public static function write($strtype, $msg){
	    	// echo Common::$loggingPath;
	    	$output = "<".date("H:i:s")."> [{$strtype}] {$msg}\n";
	    	$fhandle = fopen($_SERVER["DOCUMENT_ROOT"].Common::$loggingPath.date("d-m-Y").".log", "a");
	    	if ($fhandle === false){
	    		die("Невозможно открыть лог. файл \"".Common::$loggingPath.date("d-m-Y").".log"."\". Свяжитесь с администрацией.<br>");
	    	}
	    	flock($fhandle, LOCK_EX);
	    	fwrite($fhandle, $output);
	    	fclose($fhandle);
	    }
    }
?>