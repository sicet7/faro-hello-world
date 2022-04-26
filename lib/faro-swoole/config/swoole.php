<?php

declare(strict_types=1);

use function Config\concat;
use function Config\env;
use function Config\val;

$userInfo = posix_getpwuid(posix_getuid());
$groupInfo = posix_getgrgid(posix_getgid());

/**
 * Swoole config documentation: https://www.swoole.co.uk/docs/modules/swoole-server/configuration
 */
return [

    // process
    'daemonize' => 0,
    'user' => env('SWOOLE_USER', ($userInfo['name'] ?? 'user')),
    'group' => env('SWOOLE_GROUP', ($groupInfo['name'] ?? 'user')),
    'chroot' => val('dir.root'),
    'pid_file' => concat(val('dir.root'), '/swoole.pid'),

    // server
    'worker_num' => swoole_cpu_num(),
    'dispatch_mode' => 2,

    // worker
    'max_request' => 0,

    // task worker to enable task workers "onTask" event needs to be handled.
    /*'task_ipc_mode' => 1,
    'task_max_request' => 100,
    'task_tmpdir' => concat(val('dir.root'), '/var/tmp/task'),
    'task_worker_num' => 4,
    'task_enable_coroutine' => true,
    'task_use_object' => false,*/

    // logging
    'log_level' => 1,
    'log_file' => concat(val('dir.log'), '/swoole.log'),
    'log_rotation' => SWOOLE_LOG_ROTATION_DAILY | SWOOLE_LOG_ROTATION_SINGLE,
    'log_date_format' => '%Y-%m-%dT%H:%M:%S%z',# ISO-8601
    'log_date_with_microseconds' => false,

    // tcp
    'buffer_output_size' => 2 * 1024 * 1024,# 2MB
    'tcp_fastopen' => false,
    'max_conn' => 1000,
    'tcp_defer_accept' => true,
    'open_tcp_keepalive' => true,
    //'open_tcp_nodelay' => false,
    'socket_buffer_size' => 32 * 1024 * 1024,# 32MB

    // coroutine
    'enable_coroutine' => true,
    'max_coroutine' => 3000,
    'send_yield' => false,

    // tcp server
    'heartbeat_idle_time' => 600,
    'heartbeat_check_interval' => 60,
    'enable_delay_receive' => false,
    'enable_reuse_port' => true,
    'enable_unsafe_event' => true,

    // protocol
    'open_http_protocol' => true,
    'open_http2_protocol' => true,

    // Requires "onMessage" callback.
    'open_websocket_protocol' => false,

    // to enable & configure SSL see Swoole configuration.

    // static files
    'enable_static_handler' => true,
    'document_root' => concat(val('dir.root'), '/public'),
    'static_handler_locations' => [],

    'http_parse_post' => true,
    'http_parse_cookie' => true,
    'upload_tmp_dir' => concat(val('dir.var'), '/tmp/upload'),

    // compression
    'http_compression' => true,
    'http_compression_level' => 3,# 1 - 9
    'http_gzip_level' => 1,
    'compression_min_length' => 20,
];
