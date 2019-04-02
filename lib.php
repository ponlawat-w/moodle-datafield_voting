<?php

defined('MOODLE_INTERNAL') || die();

function datafield_voting_gettotalvotes($recordid, $fieldid) {
    global $DB;
    return $DB->count_records('data_votings', [
        'recordid' => $recordid,
        'fieldid' => $fieldid
    ]);
}

function datafield_voting_haveivoted($recordid, $fieldid) {
    global $DB, $USER;
    return $DB->record_exists('data_votings', [
        'recordid' => $recordid,
        'fieldid' => $fieldid,
        'userid' => $USER->id
    ]);
}

function datafield_voting_getapijsonresponse($recordid, $fieldid) {
    return json_encode([
        'totalvotes' => datafield_voting_gettotalvotes($recordid, $fieldid),
        'haveivoted' => datafield_voting_haveivoted($recordid, $fieldid)
    ]);
}

function datafield_voting_getvotingcontenthtml($totalvotes, $haveivoted) {
    global $OUTPUT;

    $icon = $OUTPUT->pix_icon($haveivoted ? 'thumbsup' : 'thumbsup-no',
        get_string('vote', 'datafield_voting'),
        'datafield_voting');

    return html_writer::link('javascript:void(0);', $icon) .
        html_writer::span($totalvotes, 'totalvotes');
}

function datafield_voting_getvotingarea($dataid, $fieldid, $recordid) {
    $totalvotes = datafield_voting_gettotalvotes($recordid, $fieldid);
    $haveivoted = datafield_voting_haveivoted($recordid, $fieldid);

    return html_writer::span(datafield_voting_getvotingcontenthtml($totalvotes, $haveivoted),
        'datafield_voting-area',
        [
            'data-dataid' => $dataid,
            'data-fieldid' => $fieldid,
            'data-recordid' => $recordid,
            'style' => 'margin-right: 0.5em;'
        ]
    );
}

function datafield_voting_addrecord($dataid, $fieldid, $recordid, $userid = null) {
    global $DB, $USER;
    $newrecord = new stdClass();
    $newrecord->dataid = $dataid;
    $newrecord->fieldid = $fieldid;
    $newrecord->recordid = $recordid;
    $newrecord->userid = is_null($userid) ? $USER->id : $userid;
    $newrecord->timecreated = time();

    return $DB->insert_record('data_votings', $newrecord);
}

function datafield_voting_deleterecord($fieldid, $recordid, $userid = null) {
    global $DB, $USER;
    return $DB->delete_records('data_votings', [
        'recordid' => $recordid,
        'fieldid' => $fieldid,
        'userid' => is_null($userid) ? $USER->id : $userid
    ]);
}
