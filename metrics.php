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

END;

echo $html;

?>
