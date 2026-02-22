<?php
// breadcrumb.php

$current = basename($_SERVER['PHP_SELF']);

function breadcrumb($current){
    $map = [
        'index.php'   => 'Home',
        'rooms.php'   => 'Rooms',
        'about.php'   => 'About Us',
        'contact.php' => 'Contact'
    ];

    if ($current == 'index.php') return;

    echo '<nav class="breadcrumb">';
    echo '<a href="index.php">Home</a>';
    
    if (isset($map[$current])) {
        echo ' <span class="sep">›</span> ';
        echo '<span class="active">'.$map[$current].'</span>';
    }

    echo '</nav>';
}

breadcrumb($current);
?>
