<?php

return [
    'run_times' => 10, //amount
    'retry_in_seconds' => 3, //in seconds
    'clear_log_after_seconds' => 60 * 60 * 24 * 7, //in seconds - default after 1 week
    'clear_log_via_job' => false,
    'clear_log_via_job_queue' => 'default',
];
