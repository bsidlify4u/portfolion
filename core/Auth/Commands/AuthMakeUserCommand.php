<?php

namespace Portfolion\Auth\Commands;

use Portfolion\Console\Command;
use Portfolion\Auth\Facades\Auth;
use Portfolion\Auth\User;
use Portfolion\Hash\HashManager;

class AuthMakeUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected string $signature = 'auth:make-user {--name= : The name of the user}
                                             {--email= : The email of the user}
                                             {--password= : The password of the user}
                                             {--admin : Whether the user should be an admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $isAdmin = $this->option('admin');

        if (!$name) {
            $name = $this->ask('What is the user\'s name?');
        }

        if (!$email) {
            $email = $this->ask('What is the user\'s email?');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format.');
            return 1;
        }

        // Check if the email is already in use
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error('Email is already in use.');
            return 1;
        }

        if (!$password) {
            $password = $this->secret('What is the user\'s password?');
            $confirmPassword = $this->secret('Confirm the password');

            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match.');
                return 1;
            }
        }

        /** @var HashManager $hasher */
        $hasher = app('hash');

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = $hasher->make($password);
        $user->is_admin = $isAdmin ? 1 : 0;
        $user->save();

        $this->info('User created successfully.');
        $this->table(
            ['Name', 'Email', 'Admin'],
            [[$user->name, $user->email, $user->is_admin ? 'Yes' : 'No']]
        );

        return 0;
    }
} 