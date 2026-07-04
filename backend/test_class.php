<?php
require 'vendor/autoload.php';
echo class_exists('Filament\Tables\Actions\Action') ? "Yes Filament\Tables\Actions\Action\n" : "No Filament\Tables\Actions\Action\n";
echo class_exists('Filament\Tables\Actions\BulkAction') ? "Yes Filament\Tables\Actions\BulkAction\n" : "No Filament\Tables\Actions\BulkAction\n";
echo class_exists('Filament\Actions\Action') ? "Yes Filament\Actions\Action\n" : "No Filament\Actions\Action\n";
echo class_exists('Filament\Actions\BulkAction') ? "Yes Filament\Actions\BulkAction\n" : "No Filament\Actions\BulkAction\n";
echo class_exists('Filament\Tables\Actions\DeleteBulkAction') ? "Yes Filament\Tables\Actions\DeleteBulkAction\n" : "No Filament\Tables\Actions\DeleteBulkAction\n";
