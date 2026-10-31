<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $setupGroup = SetupGroup::inRandomOrder()->first();

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'username' => $this->uniqueValue('username', fn () => $this->faker->userName(), $this->reservedUsernames()),
            'email' => $this->uniqueValue('email', fn () => $this->faker->safeEmail()),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone_number' => $this->faker->phoneNumber(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->secondaryAddress(),
            'address_city' => $this->faker->city(),
            'address_post_code' => $this->faker->postcode(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_number' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->word(),
            'emergency_contact_address_line1' => $this->faker->streetAddress(),
            'emergency_contact_address_line2' => $this->faker->secondaryAddress(),
            'emergency_contact_address_city' => $this->faker->city(),
            'emergency_contact_address_post_code' => $this->faker->postcode(),
            'date_of_birth' => $this->faker->date(),
            'has_photo_permission' => $this->faker->boolean(),
            'is_gift_aiding_subs' => $this->faker->boolean(),
            'image' => $this->faker->imageUrl(),
            'setup_group_id' => $setupGroup ? $setupGroup->id : null,
        ];
    }

    /**
     * Usernames the seeders hand out to the fixed role accounts.
     *
     * @return list<string>
     */
    protected function reservedUsernames(): array
    {
        return array_map(fn (UserRole $role) => strtolower($role->name), UserRole::cases());
    }

    /**
     * Generate a value for a unique column that no existing user has, without relying on Faker's bounded unique pool.
     *
     * @param  list<string>  $excluded
     */
    protected function uniqueValue(string $column, callable $generate, array $excluded = []): string
    {
        do {
            $value = $generate();
        } while (in_array(strtolower($value), $excluded, true) || User::withTrashed()->where($column, $value)->exists());

        return $value;
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
