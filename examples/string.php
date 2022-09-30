<?php

use Raryk\Highlight\Highlight;

require __DIR__ . '/../vendor/autoload.php';

$highlight = new Highlight();

$highlight->setSearch('search text');
foreach($highlight->string('testing search text and link https://example.com') as $string) {
    if(isset($string['link'])) {
        echo '<a href="' . $string['link'] . '">';
        foreach($string['string'] as $string) {
            if($string['type'] == 'search') {
                echo '<span style="background:yellow;color:black">' . $string['text'] . '</span>';
            } else {
                echo '<span>' . $string['text'] . '</span>';
            }
        }
        echo '</a>';
    } else {
        if($string['type'] == 'search') {
            echo '<span style="background:yellow;color:black">' . $string['text'] . '</span>';
        } else {
            echo '<span>' . $string['text'] . '</span>';
        }
    }
}

echo '<br><br>';

$highlight->setSearch('and link https://');
foreach($highlight->string('testing search text and link https://example.com') as $string) {
    if(isset($string['link'])) {
        echo '<a href="' . $string['link'] . '">';
        foreach($string['string'] as $string) {
            if($string['type'] == 'search') {
                echo '<span style="background:yellow;color:black">' . $string['text'] . '</span>';
            } else {
                echo '<span>' . $string['text'] . '</span>';
            }
        }
        echo '</a>';
    } else {
        if($string['type'] == 'search') {
            echo '<span style="background:yellow;color:black">' . $string['text'] . '</span>';
        } else {
            echo '<span>' . $string['text'] . '</span>';
        }
    }
}