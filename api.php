<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/lib.php');

require_sesskey();
require_login();

$action = required_param('action', PARAM_ALPHA);
$fieldid = required_param('fieldid', PARAM_INT);
$recordid = required_param('recordid', PARAM_INT);

try {


    if (!$DB->record_exists('data_fields', ['id' => $fieldid])) {
        throw new moodle_exception('Field not exists #' . $fieldid, 'datafield_voting');
    }

    $field = $DB->get_record('data_fields', ['id' => $fieldid]);

    $data = $DB->get_record('data', ['id' => $field->dataid]);
    if (!$data) {
        throw new moodle_exception('Cannot get database module', 'datafield_voting');
    }
    $course = $DB->get_record('course', ['id' => $data->course]);
    if (!$course) {
        throw new moodle_exception('Cannot get course data', 'datafield_voting');
    }
    $coursemodule = get_coursemodule_from_instance('data', $data->id, $course->id);
    if (!$coursemodule) {
        throw new moodle_exception('Cannot get course module', 'datafield_voting');
    }

    $context = context_module::instance($coursemodule->id);

    require_capability('mod/data:viewentry', $context);

    if ($field->type != 'voting') {
        throw new moodle_exception('Incorrect field type', 'datafield_voting');
    }

    if (!$DB->record_exists('data_records', ['id' => $recordid])) {
        throw new moodle_exception('Record not exists #' . $recordid, 'datafield_voting');
    }

    if ($action == 'getdata') {
        echo datafield_voting_getapijsonresponse($recordid, $fieldid);
        exit;
    } else if ($action == 'submitvote') {
        $haveivoted = datafield_voting_haveivoted($recordid, $fieldid);
        if ($haveivoted) {
            if (datafield_voting_deleterecord($fieldid, $recordid)) {
                echo datafield_voting_getapijsonresponse($recordid, $fieldid);
                exit;
            }
            throw new moodle_exception('Cannot undo vote', 'datafield_voting');
        } else {
            if (datafield_voting_addrecord($field->dataid, $field->id, $recordid)) {
                echo datafield_voting_getapijsonresponse($recordid, $fieldid);
                exit;
            }
            throw new moodle_exception('Cannot submit vote', 'datafield_voting');
        }
    }

    throw new moodle_exception('Unknown action', 'datafield_voting');
} catch (moodle_exception $ex) {
    http_response_code(500);
    throw new moodle_exception($ex->errorcode, $ex->module);
}
