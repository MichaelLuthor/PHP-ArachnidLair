<?php
function include_loader() {
    $baseDir = dirname(__FILE__);
    foreach ( scandir($baseDir) as $file ) {
        if ( '.' === $file[0] ) {
            continue;
        }
        require_once "{$baseDir}/{$file}";
    }
}
include_loader();