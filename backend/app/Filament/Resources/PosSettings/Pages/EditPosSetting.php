<?php

namespace App\Filament\Resources\PosSettings\Pages;

use App\Filament\Resources\PosSettings\PosSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosSetting extends EditRecord
{
    protected static string $resource = PosSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action to prevent deleting organization settings
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        if (!empty($data['selected_branch_id'])) {
            $branch = \App\Models\Branch::find($data['selected_branch_id']);
            if ($branch) {
                $branch->update([
                    'receipt_footer_layout' => $data['receipt_footer_layout'] ?? 4,
                    'receipt_show_logo' => $data['receipt_show_logo'] ?? false,
                    'receipt_show_tax' => $data['receipt_show_tax'] ?? false,
                    'receipt_tax_message' => $data['receipt_tax_message'] ?? '',
                    'receipt_tax_rate' => $data['receipt_tax_rate'] ?? 11.00,
                    'receipt_tax_rate_message' => $data['receipt_tax_rate_message'] ?? '',
                    'receipt_dpp_rate' => $data['receipt_dpp_rate'] ?? 1.11,
                    'receipt_dpp_message' => $data['receipt_dpp_message'] ?? '',
                    'receipt_total_tax_message' => $data['receipt_total_tax_message'] ?? '',
                    
                    'receipt_header_line1' => $data['receipt_header_line1'] ?? '',
                    'receipt_header_line1_bold' => $data['receipt_header_line1_bold'] ?? false,
                    'receipt_header_line2' => $data['receipt_header_line2'] ?? '',
                    'receipt_header_line2_bold' => $data['receipt_header_line2_bold'] ?? false,
                    'receipt_header_line3' => $data['receipt_header_line3'] ?? '',
                    'receipt_header_line3_bold' => $data['receipt_header_line3_bold'] ?? false,
                    'receipt_header_line4' => $data['receipt_header_line4'] ?? '',
                    'receipt_header_line4_bold' => $data['receipt_header_line4_bold'] ?? false,
                    
                    'receipt_footer_line1' => $data['receipt_footer_line1'] ?? '',
                    'receipt_footer_line1_bold' => $data['receipt_footer_line1_bold'] ?? false,
                    'receipt_footer_line2' => $data['receipt_footer_line2'] ?? '',
                    'receipt_footer_line2_bold' => $data['receipt_footer_line2_bold'] ?? false,
                    'receipt_footer_line3' => $data['receipt_footer_line3'] ?? '',
                    'receipt_footer_line3_bold' => $data['receipt_footer_line3_bold'] ?? false,
                    'receipt_footer_line4' => $data['receipt_footer_line4'] ?? '',
                    'receipt_footer_line4_bold' => $data['receipt_footer_line4_bold'] ?? false,
                    'receipt_footer_line5' => $data['receipt_footer_line5'] ?? null,
                    'receipt_footer_line5_bold' => $data['receipt_footer_line5_bold'] ?? false,
                    'receipt_footer_line6' => $data['receipt_footer_line6'] ?? null,
                    'receipt_footer_line6_bold' => $data['receipt_footer_line6_bold'] ?? false,
                ]);
            }
        }
    }
}
