<?php
require_once dirname(__FILE__) . '/../../core/install/install.php';

function _plugin_install() {
    googleNews_install();
}

function _plugin_update() {
    googleNews_update();
}

function _plugin_remove() {
    googleNews_remove();
}
?>
