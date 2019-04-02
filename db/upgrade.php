<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_datafield_voting_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    return true;
}
