<?php

require_once(__DIR__ . '/lib.php');

class data_field_voting extends data_field_base {

    public $type = 'voting';
    public $filepicker = null;
    public $text_content = '';
    public $icon_content = '';
    public $currentimage = null;

    public function __construct($field = 0, $data = 0, $cm = 0) {
        global $PAGE;
        parent::__construct($field, $data, $cm);

        $PAGE->requires->js(new moodle_url('/mod/data/field/voting/script.js'));
    }

    function display_search_field() {
        return '';
    }

    function display_add_field($recordid = 0, $formdata = null) {
        return html_writer::start_tag('input', [
            'type' => 'hidden',
            'name' => 'field_' . $this->field->id,
            'id' => 'field_' . $this->field->id,
            'value' => ''
        ]);
    }

    function get_data_contextid() {
        return context_module::instance($this->field->dataid)->id;
    }

    function display_edit_field() {
        global $PAGE;
        require_once(__DIR__ . '/../../../../lib/form/filepicker.php');
        $PAGE->requires->js(new moodle_url('/mod/data/field/voting/formscript.js'));
        if (empty($this->field->name)) {
            $this->field->name = get_string('fieldname', 'datafield_voting');
            $this->field->description = '';
            $this->field->{DATAFIELD_VOTING_FIELD_TYPE} = DATAFIELD_VOTING_FIELDTYPE_ICON;
            $this->icon_content = datafield_voting_getdefaulticon();
        } else {
            switch ($this->field->{DATAFIELD_VOTING_FIELD_TYPE}) {
                case DATAFIELD_VOTING_FIELDTYPE_TEXT:
                    $this->text_content = $this->field->{DATAFIELD_VOTING_FIELD_CONTENT};
                    break;
                case DATAFIELD_VOTING_FIELDTYPE_ICON:
                    $this->icon_content = $this->field->{DATAFIELD_VOTING_FIELD_CONTENT};
                    break;
                case DATAFIELD_VOTING_FIELDTYPE_IMAGE:
                    $this->currentimage = datafield_voting_getimageurl($this->field->id);
                    break;
            }
        }
        $this->filepicker = new MoodleQuickForm_filepicker('customimage', 'customimage', ['id' => 'moodle-data_voting-icon']);
        parent::display_edit_field();
    }

    function display_browse_field($recordid, $template) {
        return datafield_voting_getvotingarea($this->data->id, $this->field->id, $recordid);
    }

    function delete_files() {
        $fs = get_file_storage();
        $fs->delete_area_files($this->get_data_contextid(), 'mod_data', 'field_voting', $this->field->id);
    }

    function move_file_from_draft($itemid) {
        global $USER;
        $contextuser = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextuser->id, 'user', 'draft', $itemid);
        $image = null;
        foreach ($files as $file) {
            if ($file->get_filename() == '.') {
                continue;
            }
            if (!$image || $file->get_timecreated() > $image->get_timecreated()) {
                $image = $file;
            }
        }
        if (!$image) {
            return false;
        }
        $context = context_module::instance($this->field->dataid);
        $result = $fs->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => 'mod_data',
            'filearea' => 'field_voting',
            'itemid' => $this->field->id,
            'filepath' => '/',
            'userid' => $USER->id
        ], $image);
        $fs->delete_area_files($contextuser->id, 'user', 'draft', $itemid);
        if (!$result) {
            return false;
        }
        return true;
    }

    function insert_field() {
        if (!parent::insert_field()) {
            return false;
        }
        if ($this->field->{DATAFIELD_VOTING_FIELD_TYPE} == DATAFIELD_VOTING_FIELDTYPE_IMAGE
            && !$this->move_file_from_draft($this->field->{DATAFIELD_VOTING_FIELD_CONTENT})) {
            return false;
        }
        return true;
    }

    function update_field() {
        global $DB, $USER;
        $olddata = $DB->get_record('data_fields', ['id' => $this->field->id]);
        if (!parent::update_field()) {
            return false;
        }
        if ($this->field->{DATAFIELD_VOTING_FIELD_TYPE} == DATAFIELD_VOTING_FIELDTYPE_IMAGE) {
            $itemid = $this->field->{DATAFIELD_VOTING_FIELD_CONTENT};
            $fs = get_file_storage();
            $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $itemid);
            if (count($files) < 2 && $olddata->{DATAFIELD_VOTING_FIELD_TYPE} == DATAFIELD_VOTING_FIELDTYPE_IMAGE) {
                return true;
            }
            $this->delete_files();
            if (!$this->move_file_from_draft($this->field->{DATAFIELD_VOTING_FIELD_CONTENT})) {
                return false;
            }
        } else if ($olddata->{DATAFIELD_VOTING_FIELD_TYPE} == DATAFIELD_VOTING_FIELDTYPE_IMAGE) {
            $this->delete_files();
        }
        return true;
    }

    function delete_field() {
        $this->delete_files();
        return parent::delete_field();
    }

    function update_content($recordid, $value, $name = '') {
        global $DB;
        $DB->delete_records('data_content', [
           'fieldid' => $this->field->id,
           'recordid' => $recordid
        ]);
    }

    function export_text_value($record) {
        $userids = datafield_voting_getuserids($record);
        return datafield_voting_gettotalvotes($userids);
    }

    function get_sort_field() {
        return DATAFIELD_VOTING_COLUMN_CONTENT_VOTECOUNT;
    }

    function get_sort_sql($fieldname) {
        return "CAST({$fieldname} AS INT)";
    }

    public function parse_search_field($defaults = null) {
        $param = 'f_' . $this->field->id;
        if (empty($defaults[$param])) {
            $defaults = array($param => '');
        }
        return optional_param($param, $defaults[$param], PARAM_NOTAGS);
    }

}
