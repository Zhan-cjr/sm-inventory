import os
import re

directory = 'app/Filament/Exports/'
replacement = """    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export selesai. ' . \\Illuminate\\Support\\Number::format($export->successful_rows) . ' baris berhasil diexport.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . \\Illuminate\\Support\\Number::format($failedRowsCount) . ' baris gagal diexport.';
        }

        return $body;
    }"""

pattern = re.compile(r'    public static function getCompletedNotificationBody\(Export \$export\): string\s*\{.*?\n    \}', re.DOTALL)

for filename in os.listdir(directory):
    if filename.endswith(".php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r') as file:
            content = file.read()
        
        # Use a lambda to avoid backslash escaping issues in the replacement string
        new_content = pattern.sub(lambda match: replacement, content)
        
        with open(filepath, 'w') as file:
            file.write(new_content)
        
        print(f"Updated {filename}")
