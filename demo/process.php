<?php

use avadim\Process\Process;

include_once '../src/autoload.php';

$result = [];
if (isset($_GET['cmd'])) {
    $pid = isset($_GET['pid']) ? $_GET['pid'] : null;
    switch ($_GET['cmd']) {
        case 'start';
            $shell = Process::initPhp(__DIR__ . '/script-endless.php');
            $pid = $shell->start();
            if ($pid) {
                $result = [
                    'error'     => 0,
                    'pid'       => $pid,
                    'status'    => 'start',
                    'command'   => $shell->getCommand()
                ];
            } else {
                $result = [
                    'error'     => 1,
                ];
            }
            break;
        case 'check';
            if (Process::isRunning($pid)) {
                $result = [
                    'error'     => 0,
                    'pid'       => $pid,
                    'status'    => 'running',
                ];
            } else {
                $result = [
                    'error'     => 0,
                    'pid'       => $pid,
                    'status'    => 'unknown',
                ];
            }
            break;
        case 'stop';
            if (Process::isRunning($pid)) {
                if (Process::kill($pid)) {
                    $result = [
                        'error'     => 0,
                        'pid'       => $pid,
                        'status'    => 'killed',
                    ];
                } else {
                    $result = [
                        'error'     => 3,
                        'pid'       => $pid,
                        'status'    => 'not killed',
                    ];
                }
            } else {
                $result = [
                    'error'     => 2,
                    'pid'       => $pid,
                    'status'    => 'unknown',
                ];
            }
            break;
    }
    if (!isset($result['error'])) {
        $result['error'] = -1;
    }
    header('Content-Type: application/json');
    echo json_encode($result);
}