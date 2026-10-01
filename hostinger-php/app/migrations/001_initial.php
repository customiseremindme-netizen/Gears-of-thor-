<?php
declare(strict_types=1);

use Got\Db;

/** Initial tables for the website, dashboard and enquiries. */
return static function (Db $db): void {
    $db->createTable('users', [
        'id {id}',
        'name VARCHAR(100) NOT NULL',
        'email VARCHAR(190) NOT NULL',
        'password_hash VARCHAR(255) NOT NULL',
        "role VARCHAR(20) NOT NULL DEFAULT 'staff'",
        'is_active TINYINT NOT NULL DEFAULT 1',
        'last_login_at DATETIME NULL',
        'created_at DATETIME NOT NULL',
        'updated_at DATETIME NOT NULL',
    ], [
        'users_email_unique' => ['columns' => ['email'], 'unique' => true],
    ]);

    // Website content: one "draft" row the dashboard edits and one "published"
    // row the public site shows. Both hold the full content as JSON.
    $db->createTable('content', [
        'name VARCHAR(20) NOT NULL PRIMARY KEY',
        'data MEDIUMTEXT NOT NULL',
        'version INT NOT NULL DEFAULT 1',
        'updated_at DATETIME NOT NULL',
        'updated_by INT NULL',
    ]);

    $db->createTable('content_history', [
        'id {id}',
        'data MEDIUMTEXT NOT NULL',
        'note VARCHAR(255) NULL',
        'created_at DATETIME NOT NULL',
        'created_by INT NULL',
    ]);

    $db->createTable('media', [
        'id {id}',
        'kind VARCHAR(10) NOT NULL',
        'path VARCHAR(190) NOT NULL',
        'ext VARCHAR(10) NOT NULL',
        'mime VARCHAR(100) NOT NULL',
        'bytes INT NOT NULL DEFAULT 0',
        'width INT NULL',
        'height INT NULL',
        'variants TEXT NULL',
        'original_name VARCHAR(255) NULL',
        "alt VARCHAR(300) NOT NULL DEFAULT ''",
        'created_at DATETIME NOT NULL',
        'created_by INT NULL',
    ], [
        'media_kind_idx' => ['columns' => ['kind', 'created_at']],
    ]);

    $db->createTable('enquiries', [
        'id {id}',
        'name VARCHAR(100) NOT NULL',
        'phone VARCHAR(20) NOT NULL',
        'phone_display VARCHAR(40) NOT NULL',
        "goal VARCHAR(120) NOT NULL DEFAULT ''",
        "contact_time VARCHAR(120) NOT NULL DEFAULT ''",
        'message TEXT NULL',
        "status VARCHAR(20) NOT NULL DEFAULT 'new'",
        'notes TEXT NULL',
        'is_spam TINYINT NOT NULL DEFAULT 0',
        'spam_reason VARCHAR(100) NULL',
        "consent_text VARCHAR(500) NOT NULL DEFAULT ''",
        'consent_at DATETIME NULL',
        'ip_hash VARCHAR(64) NULL',
        'user_agent VARCHAR(255) NULL',
        'created_at DATETIME NOT NULL',
        'updated_at DATETIME NOT NULL',
        'updated_by INT NULL',
    ], [
        'enquiries_list_idx' => ['columns' => ['is_spam', 'status', 'created_at']],
        'enquiries_phone_idx' => ['columns' => ['phone', 'created_at']],
    ]);

    $db->createTable('enquiry_events', [
        'id {id}',
        'enquiry_id INT NOT NULL',
        'user_id INT NULL',
        'action VARCHAR(30) NOT NULL',
        'detail VARCHAR(255) NULL',
        'created_at DATETIME NOT NULL',
    ], [
        'enquiry_events_enquiry_idx' => ['columns' => ['enquiry_id']],
    ]);

    $db->createTable('settings', [
        'name VARCHAR(100) NOT NULL PRIMARY KEY',
        'value TEXT NULL',
        'updated_at DATETIME NOT NULL',
    ]);

    $db->createTable('throttle', [
        'id {id}',
        'bucket VARCHAR(40) NOT NULL',
        'key_hash VARCHAR(64) NOT NULL',
        'created_at DATETIME NOT NULL',
    ], [
        'throttle_lookup_idx' => ['columns' => ['bucket', 'key_hash', 'created_at']],
    ]);
};
