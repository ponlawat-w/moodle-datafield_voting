<?php
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/lib.php');

$fieldid = required_param('field', PARAM_INT);

$field = $DB->get_record('data_fields', ['id' => $fieldid]);
if (!$field) {
    throw new moodle_exception('Field not found');
}

$datacontext = context_module::instance($field->dataid);
$file = datafield_voting_getimagefile($datacontext->id, $field->id);
if (!$file) {
    throw new moodle_exception('File not found');
}

session_write_close();
header('Content-Type: ' . $file->get_mimetype());
$file->readfile();
