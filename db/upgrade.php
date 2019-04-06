<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_datafield_voting_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019040500) {

        $table = new xmldb_table('data_votings');

        if ($dbman->table_exists($table)) {
            require_once(__DIR__ . '/../lib.php');
            $votes = $DB->get_records('data_votings');
            foreach ($votes as $vote) {
                if (!datafield_voting_addrecord($vote->fieldid, $vote->recordid, $vote->userid)) {
                    return false;
                }
            }
            $dbman->drop_table($table);
        }
        upgrade_plugin_savepoint(true, 2019040500, 'datafield', 'voting');
    }

    return true;
}
