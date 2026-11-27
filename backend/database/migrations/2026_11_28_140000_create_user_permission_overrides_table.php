#!/usr/bin/env php
<?php

/**
 * Migration: Create user_permission_overrides table
 * 
 * This table stores per-user permission overrides (GRANT/DENY/INHERIT)
 * on top of the base role permissions provided by Spatie Laravel Permission.
 * 
 * - user_id: references users table
 * - permission_name: the permission being overridden
 * - status: INHERIT|GRANT|DENY
 * - created_at, updated_at
 * 
 * Unique constraint on (user_id, permission_name) to ensure one override per user per permission.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission_name', 100);
            $table->enum('status', ['INHERIT', 'GRANT', 'DENY'])->default('INHERIT');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['user_id', 'permission_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
    }
};