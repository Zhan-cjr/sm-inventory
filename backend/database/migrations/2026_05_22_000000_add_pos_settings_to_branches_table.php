<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_minus_stock')->default(true)->after('currency_code');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->integer('receipt_footer_layout')->default(4)->after('longitude'); // 2, 4, 6 lines
            $table->boolean('receipt_show_logo')->default(false)->after('receipt_footer_layout');
            $table->boolean('receipt_show_tax')->default(false)->after('receipt_show_logo');
            $table->string('receipt_tax_message')->default('Harga di atas sudah termasuk PPN')->after('receipt_show_tax');
            $table->decimal('receipt_tax_rate', 5, 2)->default(11.00)->after('receipt_tax_message');
            $table->string('receipt_tax_rate_message')->default('Tarif PPn')->after('receipt_tax_rate');
            $table->decimal('receipt_dpp_rate', 5, 2)->default(1.11)->after('receipt_tax_rate_message');
            $table->string('receipt_dpp_message')->default('SblmPPn')->after('receipt_dpp_rate');
            $table->string('receipt_total_tax_message')->default('NilPPn')->after('receipt_dpp_message');
            
            // Header Lines
            $table->string('receipt_header_line1')->default('{org_name}')->after('receipt_total_tax_message');
            $table->boolean('receipt_header_line1_bold')->default(true)->after('receipt_header_line1');
            $table->string('receipt_header_line2')->default('{branch_address}')->after('receipt_header_line1_bold');
            $table->boolean('receipt_header_line2_bold')->default(false)->after('receipt_header_line2');
            $table->string('receipt_header_line3')->default('Telp: {branch_phone}')->after('receipt_header_line2_bold');
            $table->boolean('receipt_header_line3_bold')->default(false)->after('receipt_header_line3');
            $table->string('receipt_header_line4')->default('{branch_name}')->after('receipt_header_line3_bold');
            $table->boolean('receipt_header_line4_bold')->default(false)->after('receipt_header_line4');

            // Footer Lines
            $table->string('receipt_footer_line1')->default('Terimakasih Atas Kunjungan Anda')->after('receipt_header_line4_bold');
            $table->boolean('receipt_footer_line1_bold')->default(false)->after('receipt_footer_line1');
            $table->string('receipt_footer_line2')->default('Kami Tunggu Kunjungan Berikutnya')->after('receipt_footer_line1_bold');
            $table->boolean('receipt_footer_line2_bold')->default(false)->after('receipt_footer_line2');
            $table->string('receipt_footer_line3')->default('Kini Kami Hadir di GRABMART')->after('receipt_footer_line2_bold');
            $table->boolean('receipt_footer_line3_bold')->default(true)->after('receipt_footer_line3');
            $table->string('receipt_footer_line4')->default('Untuk Belanja lebih HEMAT dan CEPAT')->after('receipt_footer_line3_bold');
            $table->boolean('receipt_footer_line4_bold')->default(true)->after('receipt_footer_line4');
            $table->string('receipt_footer_line5')->nullable()->after('receipt_footer_line4_bold');
            $table->boolean('receipt_footer_line5_bold')->default(false)->after('receipt_footer_line5');
            $table->string('receipt_footer_line6')->nullable()->after('receipt_footer_line5_bold');
            $table->boolean('receipt_footer_line6_bold')->default(false)->after('receipt_footer_line6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['allow_minus_stock']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_footer_layout',
                'receipt_show_logo',
                'receipt_show_tax',
                'receipt_tax_message',
                'receipt_tax_rate',
                'receipt_tax_rate_message',
                'receipt_dpp_rate',
                'receipt_dpp_message',
                'receipt_total_tax_message',
                'receipt_header_line1',
                'receipt_header_line1_bold',
                'receipt_header_line2',
                'receipt_header_line2_bold',
                'receipt_header_line3',
                'receipt_header_line3_bold',
                'receipt_header_line4',
                'receipt_header_line4_bold',
                'receipt_footer_line1',
                'receipt_footer_line1_bold',
                'receipt_footer_line2',
                'receipt_footer_line2_bold',
                'receipt_footer_line3',
                'receipt_footer_line3_bold',
                'receipt_footer_line4',
                'receipt_footer_line4_bold',
                'receipt_footer_line5',
                'receipt_footer_line5_bold',
                'receipt_footer_line6',
                'receipt_footer_line6_bold',
            ]);
        });
    }
};
