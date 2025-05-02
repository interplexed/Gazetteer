<?php

// Produce different datetime formats
// Adapted from https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago

function TimeElapsedString($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - $weeks * 7;

    $units = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
);

// Control code
foreach ($units as $k => $value) {
    if ($value) {
        $string[$k] = $value . ' ' . $string[$k] . ($value > 1 ? 's' : '');
    } else {
        unset($string[$k]);
    }
}

if (!$full) { 
    $string = array_slice($string, 0, 1);
}

  return $string ? implode(', ', $string) . ' ago' : 'just now';
}

