@extends('print.layout')

@section('title', $title . ' - Periode : ' . $period)

@section('content')
@if(isset($note) && !empty($note))
    <p style="font-style: italic; font-size: 12px; color: #555; margin-bottom: 10px;">{{ $note }}</p>
@endif
<table class="report-table" @if(request('export') === 'xls') border="1" style="border-collapse: collapse; border-color: #94a3b8;" @endif>
    <thead>
        <tr>
            @foreach($columns as $col)
                @if(request('export') === 'xls')
                    <th bgcolor="#1e293b" style="width: 150px;"><font color="#ffffff"><b>{{ $col }}</b></font></th>
                @else
                    <th>{{ $col }}</th>
                @endif
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            @php
                $isTotal = $loop->last && isset($row[0]) && is_string($row[0]) && str_contains($row[0], 'TOTAL');
                $bg = $isTotal ? '#e2e8f0' : ($loop->even ? '#f8fafc' : '#ffffff');
            @endphp
            <tr @if(request('export') === 'xls') bgcolor="{{ $bg }}" @endif>
                @foreach($row as $cell)
                    <td class="{{ is_numeric(str_replace(['.', ','], '', $cell)) && strpos($cell, ' ') === false && !preg_match('/^[A-Za-z]/', $cell) ? 'right' : '' }}" @if(request('export') === 'xls') style="mso-number-format:'\@';" @endif>{!! $cell !!}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

@if(isset($summaryBox) && !empty($summaryBox))
<div style="margin-top: 30px; width: 400px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; background-color: #f9fafb;">
    <h4 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Rangkuman Total</h4>
    <table style="width: 100%; border-collapse: collapse;">
        @foreach($summaryBox as $key => $val)
        <tr>
            <td style="padding: 4px 0; font-weight: bold;">{{ $key }}</td>
            <td style="padding: 4px 0; text-align: right;">{{ $val }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

@if(request('export') === 'xls')
    <table>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr>
            <td colspan="3" align="center">Mengetahui,</td>
            <td colspan="{{ max(1, count($columns) - 6) }}"></td>
            <td colspan="3" align="center">Dibuat Oleh,</td>
        </tr>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr><td colspan="{{ count($columns) }}"></td></tr>
        <tr>
            <td colspan="3" align="center">Staff/SPV</td>
            <td colspan="{{ max(1, count($columns) - 6) }}"></td>
            <td colspan="3" align="center">{{ auth()->check() ? auth()->user()->name : 'Admin' }}</td>
        </tr>
    </table>
@else
    <div style="margin-top: 60px; display: flex; justify-content: flex-end; text-align: center; gap: 80px; margin-right: 50px;">
        <div>
            <p style="margin-bottom: 60px;">Mengetahui,</p>
            <p style="border-bottom: 1px solid #000; width: 150px; margin: 0 auto;"></p>
            <p style="margin-top: 5px;">Staff/SPV</p>
        </div>
        <div>
            <p style="margin-bottom: 60px;">Dibuat Oleh,</p>
            <p style="border-bottom: 1px solid #000; width: 150px; margin: 0 auto;"></p>
            <p style="margin-top: 5px;">{{ auth()->check() ? auth()->user()->name : 'Admin' }}</p>
        </div>
    </div>
@endif
@endsection
