<?php
/**
 * Create Moodle users via CLI bootstrap
 */

define('CLI_SCRIPT', true);
require_once('/bitnami/moodle/config.php');
require_once($CFG->dirroot . '/user/lib.php');

$student_username = getenv('MOODLE_STUDENT_USER') ?: 'student';
$student_password = getenv('MOODLE_STUDENT_PASSWORD') ?: 'StudentPass123!';
$student_email    = getenv('MOODLE_STUDENT_EMAIL')    ?: 'student@example.com';

echo "Checking for student user: $student_username\n";

$existing = $DB->get_record('user', ['username' => $student_username]);
if ($existing) {
    echo "Student user already exists (id=$existing->id).\n";
    exit(0);
}

$user = new stdClass();
$user->username   = $student_username;
$user->password   = hash_internal_user_password($student_password);
$user->firstname  = 'Student';
$user->lastname   = 'Learner';
$user->email      = $student_email;
$user->maildisplay = 0;
$user->auth       = 'manual';
$user->confirmed  = 1;
$user->mnethostid = $CFG->mnet_localhost_id;
$user->country    = 'CH';
$user->lang       = 'en';
$user->idnumber   = 'student001';

$id = user_create_user($user);
echo "Created student user with id=$id\n";

// Enrol student as a content creator (teacher role in system context)
$syscontext = context_system::instance();
$teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($teacherrole && $id) {
    role_assign($teacherrole->id, $id, $syscontext->id);
    echo "Assigned editingteacher role to student user.\n";
}

exit(0);
