<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        $standaloneConfig = $this->loadStandaloneConfig();

        $doesStandaloneSystemExist = DB::table('Console')->where('ID', $standaloneConfig['id'])->exists();
        if (!$doesStandaloneSystemExist) {
            DB::table('Console')->insert([
                'ID' => $standaloneConfig['id'],
                'Name' => $standaloneConfig['name'],
            ]);
        }
    }

    public function down(): void
    {
        $standaloneConfig = $this->loadStandaloneConfig();

        $doesStandaloneSystemExist = DB::table('Console')->where('ID', $standaloneConfig['id'])->exists();
        if ($doesStandaloneSystemExist) {
            DB::table('Console')->where('ID', $standaloneConfig['id'])->delete();
        }
    }

    private function loadStandaloneConfig(): ?array
    {
        $standaloneConfig = null;

        $systemsConfig = config("systems");
        foreach ($systemsConfig as $id => $config) {
            if (isset($config['name']) && $config['name'] === 'Standalone') {
                $standaloneConfig = $config;
                $standaloneConfig['id'] = $id;

                break;
            }
        }

        return $standaloneConfig;
    }
};
