<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class translated_service_details extends Migration
{
    public function up()
    {
        // Create translated_service_details table for multi-language support
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'service_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Reference to services.id',
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'Language code (e.g., en, ar, tr)',
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Service title in specific language',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Short description in specific language',
            ],
            'long_description' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'Detailed description in specific language',
            ],
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Service tags in specific language',
            ],
            'faqs' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'FAQs in specific language (JSON format)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        // Add primary key
        $this->forge->addKey('id', true);

        // Add unique constraint for service_id and language_code combination
        $this->forge->addUniqueKey(['service_id', 'language_code'], 'unique_service_language');

        // Add indexes for better performance
        $this->forge->addKey('service_id', false, 'idx_service_id');
        $this->forge->addKey('language_code', false, 'idx_language_code');

        // Create the table
        $this->forge->createTable('translated_service_details', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
            'COMMENT' => 'Stores translated service details for multi-language support'
        ]);

        // Add foreign key constraint
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE', 'fk_translated_service_details_service');
    }

    public function down()
    {
        // Drop the table
        $this->forge->dropTable('translated_service_details', true);
    }
}
