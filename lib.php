<?php

defined('MOODLE_INTERNAL') || die();

const DATAFIELD_VOTING_FIELD_TYPE = 'param1';
const DATAFIELD_VOTING_FIELD_CONTENT = 'param2';

const DATAFIELD_VOTING_FIELDTYPE_TEXT = 1;
const DATAFIELD_VOTING_FIELDTYPE_ICON = 2;
const DATAFIELD_VOTING_FIELDTYPE_IMAGE = 3;

const DATAFIELD_VOTING_COLUMN_CONTENT_USERID = 'content';
const DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT = 'content1';

function datafield_voting_getallfields() {
    global $DB;
    return $DB->get_records('data_fields', ['type' => 'voting']);
}

function datafield_voting_getallicons() {
    return ['thumbsup', 'thumbsdown'];
}

function datafield_voting_getdefaulticon() {
    return 'thumbsup';
}

function datafield_voting_geticonhtml($iconname, $component = 'datafield_voting') {
    global $OUTPUT;
    return $OUTPUT->pix_icon($iconname, $iconname, $component);
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
    $field = datafield_voting_getfielddata($fieldid);
    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    $totalvotes = datafield_voting_gettotalvotes($userids);
    $haveivoted = datafield_voting_haveivoted($userids);
    return json_encode([
        'totalvotes' => $totalvotes,
        'haveivoted' => $haveivoted,
        'buttonhtml' => datafield_voting_getbuttonhtml($field, $haveivoted)
    ]);
}

function datafield_voting_getvotebuttontext($field, $haveivoted) {
    return html_writer::link('javascript:void(0);', $field->{DATAFIELD_VOTING_FIELD_CONTENT}, [
        'class' => 'btn btn-sm ' . ($haveivoted ? 'btn-primary' : 'btn-default')
    ]) . ' ';
}

function datafield_voting_getvotebuttonicon($field, $haveivoted) {
    global $OUTPUT;
    $icon = $field->{DATAFIELD_VOTING_FIELD_CONTENT} . ($haveivoted ? '' : '-no');
    return html_writer::link('javascript:void(0);', $OUTPUT->pix_icon($icon,
        get_string('vote', 'datafield_voting'),
        'datafield_voting'));
}

function datafield_voting_getvotebuttonimage($field, $haveivoted) {
    $url = datafield_voting_getimageurl($field->id);
    $style = 'height: 2em; width: auto; padding: 3px;';
    $style .= $haveivoted ? 'border: 2px solid #1177d1;' : 'border: 2px solid #A4A4A4;';
    return html_writer::link('javascript:void(0);', html_writer::img($url, get_string('vote', 'datafield_voting'), ['style' => $style])) . ' ';
}

function datafield_voting_getfielddata($fieldid) {
    global $DB;
    return $DB->get_record('data_fields', ['id' => $fieldid]);
}

function datafield_voting_getbuttonhtml($field, $haveivoted) {
    switch ($field->{DATAFIELD_VOTING_FIELD_TYPE}) {
        case DATAFIELD_VOTING_FIELDTYPE_TEXT:
            return datafield_voting_getvotebuttontext($field, $haveivoted);
        case DATAFIELD_VOTING_FIELDTYPE_ICON:
            return datafield_voting_getvotebuttonicon($field, $haveivoted);
        case DATAFIELD_VOTING_FIELDTYPE_IMAGE:
            return datafield_voting_getvotebuttonimage($field, $haveivoted);
    }
    return '';
}

function datafield_voting_getvotingcontenthtml($totalvotes, $haveivoted, $field) {
    $button = datafield_voting_getbuttonhtml($field, $haveivoted);

    return html_writer::span($button, 'datafield_voting-buttonspan') . html_writer::span($totalvotes, 'totalvotes');
}

function datafield_voting_getvotingarea($dataid, $fieldid, $recordid) {
    $field = datafield_voting_getfielddata($fieldid);
    $contentrecord = datafield_voting_getcontentrecord($recordid, $fieldid);
    $userids = datafield_voting_getuserids($contentrecord);
    $totalvotes = datafield_voting_gettotalvotes($userids);
    $haveivoted = datafield_voting_haveivoted($userids);

    return html_writer::span(datafield_voting_getvotingcontenthtml($totalvotes, $haveivoted, $field),
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

function datafield_voting_getimagefile($datacontextid, $fieldid) {
    $fs = get_file_storage();
    $files = $fs->get_directory_files($datacontextid, 'mod_data', 'field_voting', $fieldid, '/');
    foreach ($files as $file) {
        if ($file->get_filename() == '.') {
            continue;
        }
        return $file;
    }
    return null;
}

function datafield_voting_getimageurl($fieldid) {
    return new moodle_url('/mod/data/field/voting/image.php', ['field' => $fieldid]);
}
