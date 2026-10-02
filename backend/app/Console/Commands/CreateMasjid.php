<?php

namespace App\Console\Commands;

use App\Actions\ProvisionMasjid;
use App\Enums\CalculationMethod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateMasjid extends Command
{
    protected $signature = 'masjid:create';

    protected $description = 'Create a masjid with its first admin, default funds, categories and iqamah rules';

    public function handle(ProvisionMasjid $provision): int
    {
        $name = text('Masjid name', required: true);
        $attributes = [
            'name' => $name,
            'slug' => text('URL slug', default: Str::slug($name), required: true),
            'city' => text('City'),
            'latitude' => (float) text('Latitude', required: true, validate: fn ($v) => is_numeric($v) && abs($v) <= 90 ? null : 'Enter a latitude between -90 and 90.'),
            'longitude' => (float) text('Longitude', required: true, validate: fn ($v) => is_numeric($v) && abs($v) <= 180 ? null : 'Enter a longitude between -180 and 180.'),
            'timezone' => text('Timezone', default: config('app.timezone'), validate: fn ($v) => in_array($v, \DateTimeZone::listIdentifiers(), true) ? null : 'Unknown timezone.'),
            'calculation_method' => select('Calculation method', collect(CalculationMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all(), default: 'mwl'),
            'asr_method' => select('Asr method', ['standard' => "Standard (Shafi'i, Maliki, Hanbali)", 'hanafi' => 'Hanafi']),
            'currency' => strtoupper(text('Currency (ISO code)', default: 'USD')),
        ];

        $admin = [
            'name' => text('Admin name', required: true),
            'email' => text('Admin email', required: true),
            'password' => password('Admin password', required: true),
        ];

        $validator = Validator::make([...$attributes, 'email' => $admin['email'], 'password' => $admin['password']], [
            'slug' => ['unique:masjids,slug', 'alpha_dash'],
            'email' => ['email', 'unique:users,email'],
            'password' => ['min:8'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $masjid = $provision($attributes, $admin);
        $this->info("Created {$masjid->name}. Public page: ".route('masjid.show', $masjid).' · Admin: '.route('login'));

        return self::SUCCESS;
    }
}
