{{-- Signature block for printed reports. A table, because DomPDF has no flexbox. --}}
@php
    $preparer = auth()->user();
    $ceo = \App\Models\User::query()->where('role', 'admin')->where('is_system_admin', false)->where('is_active', true)->orderBy('id')->first();
    $finance = \App\Models\User::query()->where('role', 'finance_accounting')->where('is_active', true)->orderBy('id')->first();
    $signatories = [
        ['Prepared by', $preparer?->name, $preparer?->roleLabel()],
        ['Noted by', $finance?->name, 'Finance & Accounting'],
        ['Approved by', $ceo?->name, 'Chief Executive Officer'],
    ];
@endphp

<table style="width: 100%; margin-top: 48px; border: none; border-collapse: collapse;">
    <tr>
        @foreach ($signatories as [$label, $name, $title])
            <td style="width: 33%; border: none; padding: 0 12px 0 0; vertical-align: top; font-size: 12px;">
                <p style="margin: 0 0 36px;">{{ $label }}:</p>
                <p style="margin: 0; border-top: 1px solid #1e293b; padding-top: 4px; font-weight: bold;">{{ $name ?? ' ' }}</p>
                <p style="margin: 2px 0 0; color: #475569;">{{ $title }}</p>
                <p style="margin: 8px 0 0; color: #475569;">Date: ____________________</p>
            </td>
        @endforeach
    </tr>
</table>
