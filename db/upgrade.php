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

    if ($oldversion < 2019040601) {
        $votingfileds = $DB->get_records('data_fields', ['type' => 'voting']);
        $records = $DB->get_records('data_records');
        foreach ($records as $record) {
            foreach ($votingfileds as $votingfield) {
                $votings = $DB->get_records('data_content', [
                    'fieldid' => $votingfield->id,
                    'recordid' => $record->id
                ]);
                $userids = [];
                foreach ($votings as $voting) {
                    $userids[] = $voting->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID};
                }
                $DB->delete_records('data_content', [
                    'fieldid' => $votingfield->id,
                    'recordid' => $record->id
                ]);

                $userids = array_unique(explode(',', implode(',', $userids)));

                $newcontentrecord = new stdClass();
                $newcontentrecord->fieldid = $votingfield->id;
                $newcontentrecord->recordid = $record->id;
                $newcontentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID} = implode(',', $userids);
                $DB->insert_record('data_content', $newcontentrecord);
            }
        }

        upgrade_plugin_savepoint(true, 2019040601, 'datafield', 'voting');
    }

    return true;
}
