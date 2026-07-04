<?php
// includes/functions.php

function formatTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return "منذ $diff ثانية";
    if ($diff < 3600) return "منذ " . round($diff / 60) . " دقيقة";
    if ($diff < 86400) return "منذ " . round($diff / 3600) . " ساعة";
    return "منذ " . round($diff / 86400) . " يوم";
}

function getStatusColor($status) {
    return match($status) {
        'online' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'offline' => 'text-red-400',
        default => 'text-gray-400'
    };
}

function getStatusIndicator($status) {
    return match($status) {
        'online' => 'bg-green-500',
        'warning' => 'bg-yellow-500',
        'offline' => 'bg-red-500',
        default => 'bg-gray-500'
    };
}

function getAlertTypeColor($type) {
    return match($type) {
        'critical' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
        default => 'bg-gray-500'
    };
}

function getSeverityColor($severity) {
    return match($severity) {
        'high', 'critical' => 'bg-red-500',
        'medium' => 'bg-yellow-500',
        'low' => 'bg-blue-500',
        default => 'bg-gray-500'
    };
}

function getLogLevelColor($level) {
    return match($level) {
        'error' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
        'debug' => 'bg-gray-500',
        default => 'bg-gray-500'
    };
}
?>