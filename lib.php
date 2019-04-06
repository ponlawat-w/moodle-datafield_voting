<?php

defined('MOODLE_INTERNAL') || die();

const DATAFIELD_VOTING_COLUMN_CONTENT_USERID = 'content';

function datafield_voting_gettotalvotes($recordid, $fieldid) {
    global $DB;
    return $DB->count_records('data_content', [
        'recordid' => $recordid,
        'fieldid' => $fieldid
    ]);
}

function datafield_voting_haveivoted($recordid, $fieldid) {
    global $DB, $USER;
    $useridcolumn = $DB->sql_compare_text(DATAFIELD_VOTING_COLUMN_CONTENT_USERID);
    $useridvalue = $DB->sql_compare_text(':userid');
    return $DB->record_exists_sql(
        "SELECT * FROM {data_content} WHERE recordid = :recordid AND fieldid = :fieldid AND {$useridcolumn} = {$useridvalue}"
    , [
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

function datafield_voting_addrecord($fieldid, $recordid, $userid = null) {
    global $DB, $USER;

    $userid = is_null($userid) ? $USER->id : $userid;

    $newrecord = new stdClass();
    $newrecord->fieldid = $fieldid;
    $newrecord->recordid = $recordid;
    $newrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID} = $userid;

    if (is_null($newrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID})) {
        return false;
    }

    return $DB->insert_record('data_content', $newrecord);
}

function datafield_voting_deleterecord($fieldid, $recordid, $userid = null) {
    global $DB, $USER;
    $userid = is_null($userid) ? $USER->id : $userid;
    $useridcolumn = $DB->sql_compare_text(DATAFIELD_VOTING_COLUMN_CONTENT_USERID);
    $useridvalue = $DB->sql_compare_text(':userid');
    return $DB->execute(
        "DELETE FROM {data_content} WHERE recordid = :recordid AND fieldid = :fieldid AND {$useridcolumn} = {$useridvalue}"
    , [
        'recordid' => $recordid,
        'fieldid' => $fieldid,
        'userid' => $userid
    ]);
}
