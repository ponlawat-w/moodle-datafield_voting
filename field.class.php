<?php

require_once(__DIR__ . '/lib.php');

class data_field_voting extends data_field_base {

    public $type = 'voting';

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

    function display_edit_field() {
        global $DB;
        if (empty($this->field->id)
            && $DB->record_exists('data_fields', ['dataid' => $this->data->id, 'type' => $this->type])) {
            throw new moodle_exception('Voting already exists', 'datafield_voting');
        }
        parent::display_edit_field();
    }

    function display_browse_field($recordid, $template) {
        return datafield_voting_getvotingarea($this->data->id, $this->field->id, $recordid);
    }

    function update_content($recordid, $value, $name = '') {
        global $DB;
        $DB->delete_records('data_content', [
           'fieldid' => $this->field->id,
           'recordid' => $recordid
        ]);
    }

}