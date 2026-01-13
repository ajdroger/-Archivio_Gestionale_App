<?php

$results = [
    'extension_loaded' => extension_loaded('xdebug'),
    'version' => extension_loaded('xdebug') ? phpversion('xdebug') : 'Not installed',
    'functions' => [],
    'ini_settings' => []
];

if ($results['extension_loaded']) {
    $functions_to_check = [
        'xdebug_break',
        'xdebug_call_class',
        'xdebug_call_file',
        'xdebug_call_function',
        'xdebug_call_line',
        'xdebug_code_coverage_started',
        'xdebug_connect_to_client',
        'xdebug_dump_superglobals',
        'xdebug_get_code_coverage',
        'xdebug_get_collected_errors',
        'xdebug_get_function_stack',
        'xdebug_get_gc_run_count',
        'xdebug_get_gc_total_collected_roots',
        'xdebug_get_gcstats_filename',
        'xdebug_get_headers',
        'xdebug_get_monitored_functions',
        'xdebug_get_profiler_filename',
        'xdebug_get_stack_depth',
        'xdebug_get_tracefile_name',
        'xdebug_info',
        'xdebug_is_enabled',
        'xdebug_memory_usage',
        'xdebug_notify',
        'xdebug_peak_memory_usage',
        'xdebug_print_function_stack',
        'xdebug_set_filter',
        'xdebug_start_code_coverage',
        'xdebug_start_error_collection',
        'xdebug_start_function_monitor',
        'xdebug_start_gcstats',
        'xdebug_start_trace',
        'xdebug_stop_code_coverage',
        'xdebug_stop_error_collection',
        'xdebug_stop_function_monitor',
        'xdebug_stop_gcstats',
        'xdebug_stop_trace',
        'xdebug_time_index',
        'xdebug_var_dump'
    ];

    foreach ($functions_to_check as $func) {
        $results['functions'][$func] = function_exists($func);
    }

    $settings_to_check = [
        'xdebug.mode',
        'xdebug.client_host',
        'xdebug.client_port',
        'xdebug.start_with_request',
        'xdebug.discover_client_host',
        'xdebug.log',
        'xdebug.output_dir'
    ];

    foreach ($settings_to_check as $setting) {
        $results['ini_settings'][$setting] = ini_get($setting);
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);

