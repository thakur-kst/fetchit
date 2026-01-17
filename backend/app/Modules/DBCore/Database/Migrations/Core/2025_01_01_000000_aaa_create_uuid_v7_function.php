<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create UUID v7 Function Migration
 *
 * Creates a PostgreSQL function that generates UUID v7 (time-ordered UUID).
 * UUID v7 provides better database performance than UUID v4 due to sequential nature.
 *
 * UUID v7 Format:
 * - 48 bits: Unix timestamp in milliseconds
 * - 4 bits: Version (7)
 * - 12 bits: Random data
 * - 2 bits: Variant (10)
 * - 62 bits: Random data
 *
 * @package Tenancy
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create UUID v7 generation function
        // Using DB::statement() similar to PostGIS extension pattern
        DB::statement("
            CREATE OR REPLACE FUNCTION gen_random_uuid_v7()
            RETURNS uuid
            AS $$
            DECLARE
                unix_ts_ms BIGINT;
                uuid_bytes BYTEA;
            BEGIN
                -- Get Unix timestamp in milliseconds
                unix_ts_ms := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

                -- Generate 16 random bytes
                uuid_bytes := gen_random_bytes(16);

                -- Set timestamp in first 48 bits (6 bytes)
                uuid_bytes := set_byte(uuid_bytes, 0, (unix_ts_ms >> 40)::INT);
                uuid_bytes := set_byte(uuid_bytes, 1, (unix_ts_ms >> 32)::INT);
                uuid_bytes := set_byte(uuid_bytes, 2, (unix_ts_ms >> 24)::INT);
                uuid_bytes := set_byte(uuid_bytes, 3, (unix_ts_ms >> 16)::INT);
                uuid_bytes := set_byte(uuid_bytes, 4, (unix_ts_ms >> 8)::INT);
                uuid_bytes := set_byte(uuid_bytes, 5, unix_ts_ms::INT);

                -- Set version (7) in bits 48-51 (byte 6, high nibble)
                uuid_bytes := set_byte(uuid_bytes, 6, (get_byte(uuid_bytes, 6) & 15) | 112);

                -- Set variant (10) in bits 64-65 (byte 8, top 2 bits)
                uuid_bytes := set_byte(uuid_bytes, 8, (get_byte(uuid_bytes, 8) & 63) | 128);

                -- Return as UUID type
                RETURN encode(uuid_bytes, 'hex')::UUID;
            END;
            $$ LANGUAGE plpgsql VOLATILE;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if any tables are using the function before dropping
        $result = DB::select("
            SELECT
                table_name,
                column_name,
                column_default
            FROM information_schema.columns
            WHERE column_default LIKE '%gen_random_uuid_v7%'
        ");

        if (count($result) > 0) {
            $tables = array_map(fn($row) => $row->table_name, $result);
            throw new \RuntimeException(
                'Cannot drop gen_random_uuid_v7() function. The following tables use it: ' .
                implode(', ', array_unique($tables))
            );
        }

        DB::statement('DROP FUNCTION IF EXISTS gen_random_uuid_v7()');
    }
};
