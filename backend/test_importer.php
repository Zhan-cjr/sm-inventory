<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class TestImporter extends \Filament\Actions\Imports\Importer
{
    protected static ?string $model = \App\Models\Supplier::class;

    public static function getColumns(): array
    {
        $booleanCaster = function (?string $state): bool {
            if (blank($state)) return false;
            $val = strtolower(trim($state));
            if (in_array($val, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'on'])) return true;
            if (in_array($val, ['0', 'false', 'no', 'n', 'tidak', 'nonaktif', 'off', 'non aktif'])) return false;
            return (bool) $val;
        };

        return [
            \Filament\Actions\Imports\ImportColumn::make('is_consignment')
                ->boolean()
                ->castStateUsing($booleanCaster)
                ->fillRecordUsing(fn ($record, $state) => $record->is_consignment = $state ?? false),
        ];
    }

    public function resolveRecord(): ?\Illuminate\Database\Eloquent\Model
    {
        $r = new \App\Models\Supplier();
        $r->organization_id = \App\Models\Organization::first()->id;
        $r->code = 'TEST-002';
        $r->name = 'TEST SUPPLIER 2';
        $r->is_active = true;
        return $r;
    }

    public static function getCompletedNotificationBody(\Filament\Actions\Imports\Models\Import $import): string
    {
        return 'Import completed';
    }
}

$import = \Filament\Actions\Imports\Models\Import::first(); // Just get any
$importer = new TestImporter($import, ['is_consignment' => 'is_consignment'], []);
try {
    $importer(['is_consignment' => '0']);
    echo "Record is_consignment after invoke: ";
    var_dump($importer->getRecord()->is_consignment);
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
