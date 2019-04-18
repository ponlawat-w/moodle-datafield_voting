<?php

defined('MOODLE_INTERNAL') || die();

const DATAFIELD_VOTING_COLUMN_CONTENT_USERID = 'content';
const DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT = 'content1';

function datafield_voting_getallfields() {
    global $DB;
    return $DB->get_records('data_fields', ['type' => 'voting']);
}

function datafield_voting_getalldataidscontainingvotings($fields) {
    $dataids = [];
    foreach ($fields as $field) {
        if (!in_array($field->dataid, $dataids)) {
            $dataids[] = $field->dataid;
        }
    }

    return $dataids;
}

function datafield_voting_getcontentrecord($recordid, $fieldid) {
    global $DB;
    $contentrecord = $DB->get_record('data_content', [
        'recordid' => $recordid,
        'fieldid' => $fieldid
    ]);
    if ($contentrecord) {
        return $contentrecord;
    }

    $newcontentrecord = new stdClass();
    $newcontentrecord->fieldid = $fieldid;
    $newcontentrecord->recordid = $recordid;
    $newcontentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID} = '';
    $newcontentrecord->id = $DB->insert_record('data_content', $newcontentrecord);
    if ($newcontentrecord->id) {
        return $newcontentrecord;
    }
    return null;
}

function datafield_voting_getuserids($contentrecord) {
    $content = $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID};
    return $content ? explode(',', $content) : [];
}

function datafield_voting_gettotalvotes($userids) {
    return count($userids);
}

function datafield_voting_haveivoted($userids) {
    global $USER;
    return in_array($USER->id, $userids);
}

function datafield_voting_getapijsonresponse($recordid, $fieldid) {
    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    return json_encode([
        'totalvotes' => datafield_voting_gettotalvotes($userids),
        'haveivoted' => datafield_voting_haveivoted($userids)
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
    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    $totalvotes = datafield_voting_gettotalvotes($userids);
    $haveivoted = datafield_voting_haveivoted($userids);

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

    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    if (!in_array($userid, $userids)) {
        $userids[] = $userid;
        $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID} = implode(',', array_unique($userids));
        $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT} = datafield_voting_gettotalvotes($userids);
        return $DB->update_record('data_content', $contentrecord);
    }

    return true;
}

function datafield_voting_deleterecord($fieldid, $recordid, $userid = null) {
    global $DB, $USER;

    $userid = is_null($userid) ? $USER->id : $userid;

    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    $userids = array_unique(array_diff($userids, [$userid]));
    $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_USERID} = implode(',', $userids);
    $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT} = datafield_voting_gettotalvotes($userids);
    return $DB->update_record('data_content', $contentrecord);
}

function datafield_voting_updatevotecount($contentrecord, $userids = null) {
    global $DB;
    $userids = is_null($userids) ? datafield_voting_getuserids($contentrecord) : $userids;
    $contentrecord->{DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT} = datafield_voting_gettotalvotes($userids);
    return $DB->update_record('data_content', $contentrecord);
}
