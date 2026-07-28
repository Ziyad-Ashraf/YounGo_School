<?php

$CI = get_instance();
$CI->load->database();
$CI->load->dbforge();


// 2. CREATE TABLE `custom_fields` IF NOT EXISTS
$custom_fields = array(
    'id' => array(
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => TRUE,
        'auto_increment' => TRUE
    ),
    'course_id' => array(
        'type' => 'INT',
        'constraint' => 11,
        'default' => null,
        'null' => TRUE
    ),
    'custom_type' => array(
        'type' => 'VARCHAR',
        'constraint' => '255',
        'default' => null,
        'null' => TRUE
    ),
    'custom_title' => array(
        'type' => 'VARCHAR',
        'constraint' => '255',
        'default' => null,
        'null' => TRUE
    ),
    'custom_field' => array(
        'type' => 'TEXT',
        'default' => null,
        'null' => TRUE
    ),
    'sorting' => array(
        'type' => 'INT',
        'constraint' => 11,
        'default' => null,
        'null' => TRUE
    ),
    'created_at' => array(
        'type' => 'VARCHAR',
        'constraint' => '100',
        'default' => null,
        'null' => TRUE
    ),
    'updated_at' => array(
        'type' => 'VARCHAR',
        'constraint' => '100',
        'default' => null,
        'null' => TRUE
    )
);

if (! $CI->db->table_exists('custom_fields')) {
    $CI->dbforge->add_field($custom_fields);
    $CI->dbforge->add_key('id', TRUE);

    $attributes = array(
        'ENGINE' => 'InnoDB',
        'DEFAULT CHARACTER SET' => 'utf8',
        'COLLATE' => 'utf8_unicode_ci'
    );

    $CI->dbforge->create_table('custom_fields', TRUE, $attributes);
}

// 1. UPDATE VERSION NUMBER INSIDE SETTINGS TABLE
$settings_data = array('value' => '7.1');
$CI->db->where('key', 'version');
$CI->db->update('settings', $settings_data);