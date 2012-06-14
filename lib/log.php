<?php
/**
 * @package mobi.universo.util
 */
include_once 'log/Log.php';

/**
 * Create a new log file or use an existing one and delete the logs with more than 60 days
 * @param String $logName The name of the log file. This name will be concatenated with the current date ("logName-YY-M-D)
 * @param Array $logConfig Optional configurations
 * @param Array $logLevel ptional log level
 */
function setupLogFile($logName, $logConfig = null, $logLevel = null){
	date_default_timezone_set('America/Sao_paulo');
	if ($logConfig == null){
		$logConfig = array(
			"timeFormat" => "%H:%M:%S",
			"lineFormat" => "[%{priority}] %{timestamp} | %{message}" //| %{file}-%{line} : %{function}()"
		);
	}
	if ($logLevel == null){
		/**************************************************************
		CHANGE LOG DEBUG LEVEL TO PEAR_LOG_ERR ON PRODUCTION
		***************************************************************/
		// $logLevel =  PEAR_LOG_ERR;
		// $logLevel =  PEAR_LOG_DEBUG;
		$logLevel =  PEAR_LOG_INFO;
	}
	$today = date("Y-n-j");
	$lastWeek = date("Y-n-j", mktime(0, 0, 0, date("m"), date("d")-7,   date("Y")));

	//Delete the old log files - 7 days duration
	@unlink( dirname(__FILE__) . "/../logs/$logName-$lastWeek.log" );
	
	$logFile = dirname(__FILE__) . "/../logs/$logName-$today.log";
	
	return Log::singleton("file", $logFile, "\t" , $logConfig, $logLevel);
}
$logger = setupLogFile("general");
?>