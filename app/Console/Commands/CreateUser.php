<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CreateUser extends Command
{
    protected $signature = 'user:create
        {first_name : The user\'s first name}
        {last_name : The user\'s last name}
        {email : The user\'s email address}
        {--role=member : guest, ensemble, member, moderator or admin}';

    protected $description = 'Create a user and print their temporary password';

    public function handle(): int
    {
        $roles = array_map(fn (UserRole $role) => strtolower($role->name), UserRole::cases());
        $input = $this->arguments() + ['role' => strtolower($this->option('role'))];

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in($roles)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = Str::password(16);

        $user = retry(3, fn () => User::forceCreate([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'role' => UserRole::cases()[array_search($input['role'], $roles)],
            'password' => $password,
            'must_change_password' => true,
            'username' => User::generateUniqueUsername($input['first_name'], $input['last_name']),
        ]), when: fn (Throwable $exception) => $exception instanceof UniqueConstraintViolationException);

        $this->info("Created {$user->role->name} {$user->name}.");
        $this->line("Username: {$user->username}");
        $this->line("Temporary password: {$password}");

        return self::SUCCESS;
    }
}
