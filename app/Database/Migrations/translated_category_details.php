<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class translated_category_details extends Migration
{
    public function up()
    {
        // Create translated_category_details table for multi-language support
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'category_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Reference to categories.id',
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'Language code (e.g., en, ar, tr)',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Category name in specific language',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Category description in specific language',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
        ]);

        // Add primary key
        $this->forge->addKey('id', true);

        // Add unique constraint for category_id and language_code combination
        $this->forge->addUniqueKey('unique_category_language', ['category_id', 'language_code']);

        // Add indexes for better performance
        $this->forge->addKey('idx_category_id', 'category_id');
        $this->forge->addKey('idx_language_code', 'language_code');

        // Create the table
        $this->forge->createTable('translated_category_details', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
            'COMMENT' => 'Stores translated category details for multi-language support'
        ]);

        // Add foreign key constraint
        $this->forge->addForeignKey(
            'category_id',
            'categories',
            'id',
            'CASCADE',
            'CASCADE',
            'fk_translated_category_details_category_id'
        );
    }

    public function down()
    {
        // Drop the table
        $this->forge->dropTable('translated_category_details', true);
    }
}
