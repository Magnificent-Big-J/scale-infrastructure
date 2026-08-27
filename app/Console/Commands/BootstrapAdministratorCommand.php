<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BootstrapAdministratorCommand extends Command
{
    protected $signature = 'app:bootstrap-admin {--email=} {--name=}';

    protected $description = 'Create the first administrator account with a generated password. Refuses to run if an administrator already exists.';

    public function handle(): int
    {
        if (User::role('administrator')->exists()) {
            $this->error('An administrator account already exists. This command is one-time only - manage users from the app itself.');

            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask('Administrator email');
        $name = $this->option('name') ?: $this->ask('Administrator name', 'Administrator');

        $validator = validator(
            ['email' => $email, 'name' => $name],
            ['email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'string', 'max:255']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = Str::password(32);

        // forceCreate: email_verified_at isn't in the model's #[Fillable] list
        // (a bootstrapped admin should start pre-verified, unlike a normal
        // self-registration), and this command is itself a privileged,
        // one-time-only operation.
        $user = User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('administrator');

        activity()
            ->performedOn($user)
            ->event('admin_bootstrapped')
            ->withProperties(['email' => $email])
            ->log('Bootstrapped the first administrator account');

        $this->newLine();
        $this->components->info('Administrator account created.');
        $this->line("  Email:    {$email}");
        $this->line("  Password: {$password}");
        $this->newLine();
        $this->warn('This password is shown once and is not recoverable. Store it securely, log in, and change it (and enable 2FA) immediately.');

        return self::SUCCESS;
    }
}
