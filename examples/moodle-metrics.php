<?php

/**
 * Basic PHP Information
 **/
$php_version = phpversion();
$php_sapi = php_sapi_name();

/**
 * PHPFPM Information
 * - create CURL call to status?json page (configured in php-fpm.conf)
 * - json_decode CURL call
 **/
$phpfpm_url = "http://127.0.0.1/fpm-metrics/status?json";
$phpfpm_info = json_decode(file_get_contents($phpfpm_url),true);

/**
 * Moodle Information
 * - pull in config.php from Moodle
 * - create outside MySQLi connection for this page
 * - query for:
 * -> count of users who are not deleted/suspended and are confirmed, not being the guest user
 * -> count of courses that are not the first ID (front page course)
 * -> count of users logged in the last 5 minutes according to the Moodle log
 **/
$moodle_config = "/path/to/your/moodle/config.php";
require_once($moodle_config);
$mysqli_conn = new mysqli($CFG->dbhost,$CFG->dbuser,$CFG->dbpass,$CFG->dbname);
if( $mysqli_conn->connect_error ){
  die("Connection Error: ".$mysqli_conn->connect_errno."--".$mysqli_conn->connect_error);
}

$query = "SELECT value as version,
    (SELECT COUNT(*) FROM {$CFG->prefix}user WHERE deleted=0 and confirmed=1 and suspended=0 and username <> 'guest') as users,
    (SELECT COUNT(*) FROM {$CFG->prefix}course WHERE id != 1) as courses,
    (SELECT COUNT(*) FROM {$CFG->prefix}logstore_standard_log WHERE action='loggedin' and timecreated > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 5 MINUTE)) as logins
    FROM {$CFG->prefix}config
    WHERE name='version'";

$results = mysqli_query($mysqli_conn,$query);
$moodle_stats = mysqli_fetch_assoc($results);
mysqli_close($mysqli_conn);

/**
 * OUTPUT
 * - set header
 * - HEREDOC output
 **/
header("Content-Type: text/plain; version=0.0.4");
$html = <<<END
# HELP promphp_service_info Outputs basic information about environment
# TYPE promphp_service_info gauge
promphp_service_info{handler="{$php_sapi}",phpVersion="{$php_version}"} 1
# HELP promphp_fpm_proc_idle Outputs idle processes from phpfpm status
# TYPE promphp_fpm_proc_idle gauge
promphp_fpm_proc_idle{handler="{$php_sapi}"} {$phpfpm_info["idle processes"]}
# HELP promphp_fpm_proc_active Outputs active processes from phpfpm status
# TYPE promphp_fpm_proc_active gauge
promphp_fpm_proc_active{handler="{$php_sapi}"} {$phpfpm_info["active processes"]}
# HELP promphp_fpm_proc_total Outputs total processes from phpfpm status
# TYPE promphp_fpm_proc_total gauge
promphp_fpm_proc_total{handler="{$php_sapi}"} {$phpfpm_info["total processes"]}
# HELP promphp_fpm_conn_accepted Outputs connections since startup from phpfpm status
# TYPE promphp_fpm_conn_accepted counter
promphp_fpm_conn_accepted{handler="{$php_sapi}"} {$phpfpm_info["accepted conn"]}
# HELP promphp_moodle_count_users Outputs count of users in database
# TYPE promphp_moodle_count_users counter
promphp_moodle_count_users{handler="moodle"} {$moodle_stats["users"]}
# HELP promphp_moodle_count_courses Outputs count of courses in database
# TYPE promphp_moodle_count_courses counter
promphp_moodle_count_courses{handler="moodle"} {$moodle_stats["courses"]}
# HELP promphp_moodle_count_logins Outputs Logins from the last 5 minutes
# TYPE promphp_moodle_count_logins counter
promphp_moodle_count_logins{handler="moodle"} {$moodle_stats["logins"]}]]]

END;

echo $html;

?>
