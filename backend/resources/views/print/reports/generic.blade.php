@extends('print.layout')

@section('title', $title . ' - Periode : ' . $period)

@section('content')
@if(isset($note) && !empty($note))
    <p style="font-style: italic; font-size: 12px; color: #555; margin-bottom: 10px;">{{ $note }}</p>
@endif
<table class="report-table">
    <thead>
        <tr>
            @foreach($columns as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td class="{{ is_numeric(str_replace(['.', ','], '', $cell)) && strpos($cell, ' ') === false && !preg_match('/^[A-Za-z]/', $cell) ? 'right' : '' }}">{!! $cell !!}</td>
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
@endsection
