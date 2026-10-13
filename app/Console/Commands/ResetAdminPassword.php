<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Console\Command;

class ResetAdminPassword extends Command
{
    protected $signature = 'lens:reset-admin-password
                            {email=admin@lens.app}
                            {--password= : New password (omit to be prompted)}
                            {--name=Lens Admin : Name used when creating a missing admin account}';

    protected $description = 'Create or restore an admin account and set its password.';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $user->isStaff()) {
            $this->error('That email belongs to a non-staff account and was left unchanged.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: '');
        if ($password === '') {
            $password = (string) $this->secret('New password (minimum 8 characters)');
            $confirmation = (string) $this->secret('Confirm new password');
            if (! hash_equals($password, $confirmation)) {
                $this->error('The password entries must match.');

                return self::FAILURE;
            }
        }

        if (mb_strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        $created = $user === null;
        $user ??= new User([
            'email' => $email,
            'name' => (string) $this->option('name'),
            'locale' => 'en',
        ]);

        $user->email = $email;
        $user->role = $user->role === Roles::SUPERVISOR ? Roles::SUPERVISOR : Roles::ADMIN;
        $user->is_active = true;
        $user->password = $password;
        $user->save();

        $this->info($created
            ? 'Admin account created and password set.'
            : 'Admin password updated.');

        return self::SUCCESS;
    }
}
