<?php

namespace App\Support\Documentation;

use Modules\Admins\Models\Admins;
use Modules\Users\Models\User;

/**
 * Base class for every module's Documentation Data Builder.
 *
 * A builder seeds a deterministic, realistic business scenario into the database
 * BEFORE the module's DocumentationCases execute, so the generated /docs come from
 * real API responses over production-shaped records — never null-heavy minimal
 * rows and never random data.
 *
 * This is the project standard for every Mobile and Dashboard module:
 *   - Each module ships one builder: Modules\<Module>\Tests\Documentation\<Module>DataBuilder
 *   - It extends this class and implements build().
 *   - Its DocumentationCases seed their scenario via <Module>DataBuilder::run([...]).
 *
 * Contract:
 *   - DETERMINISTIC  — same records/values on every run (no faker randomness).
 *   - IDEMPOTENT     — safe to call once per DocumentationCase group during the seed
 *                      (use firstOrCreate + backfill).
 *   - REALISTIC      — values represent a genuine production use case.
 */
abstract class DocumentationDataBuilder
{
    /**
     * Seed the scenario and return the records the DocumentationCases reference.
     *
     * @param  array<string,mixed>  $context  Caller-supplied ids/flags, e.g. ['user_id' => 5].
     * @return array<string,mixed>            e.g. ['folder' => ..., 'primaryDoc' => ...].
     */
    abstract public function build(array $context = []): array;

    /** Convenience entry point: <Module>DataBuilder::run(['user_id' => $id]). */
    public static function run(array $context = []): array
    {
        return (new static())->build($context);
    }

    /** The seeded back-office admin id (default owner for admin-driven scenarios). */
    protected function adminId(): int
    {
        $email = config('admins.base_admins.0.email', 'admin@base.com');

        return (int) (Admins::where('email', $email)->value('id') ?? 1);
    }

    /** A seeded demo mobile-app user id, if one exists. */
    protected function demoUserId(string $email = 'user2@demo.com'): ?int
    {
        return User::where('email', $email)->value('id');
    }

    /**
     * Deterministic, realistic media path — yields a stable, production-shaped
     * response URL (asset('storage/'.$path)) without a physical upload.
     */
    protected function mediaPath(string $bucket, string $file): string
    {
        return "media/{$bucket}/{$file}";
    }
}
