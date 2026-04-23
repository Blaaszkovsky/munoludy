<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class MakeAdmin extends Command
{
    protected $signature = 'munoludy:make-admin
        {--name= : Imię administratora}
        {--email= : E-mail administratora}
        {--password= : Hasło (minimum 8 znaków)}
        {--role=super_admin : Rola (super_admin lub editor)}';

    protected $description = 'Tworzy konto administratora panelu Filament z wybraną rolą.';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $name = $this->option('name') ?: $this->ask('Imię i nazwisko');
        $email = $this->option('email') ?: $this->ask('E-mail');
        $password = $this->option('password') ?: $this->secret('Hasło (min. 8 znaków)');
        $role = $this->option('role');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'role'),
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
                'role' => 'required|in:super_admin,editor',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();
        if ($existing) {
            if (!$this->confirm(sprintf('Użytkownik %s już istnieje. Nadpisać hasło i przypisać rolę "%s"?', $email, $role), false)) {
                $this->info('Anulowano.');
                return self::SUCCESS;
            }
            $existing->update(['name' => $name, 'password' => Hash::make($password)]);
            $existing->syncRoles([$role]);
            $this->info("Zaktualizowano konto {$email} z rolą {$role}.");
            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->assignRole($role);

        $path = config('munoludy.admin_path', 'admin');
        $this->info("Utworzono konto {$email} z rolą {$role}.");
        $this->line("Panel: " . url("/{$path}/login"));

        return self::SUCCESS;
    }
}
