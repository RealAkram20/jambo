@props([
    'heads' => [],   // simple column labels; or use the `head` slot for full control
])

{{-- Mirrors the admin data table (movies/index.blade.php:69):
     .table-responsive > table.custom-table with an uppercase 11px thead.
     Pass rows as the default slot (<tr>...</tr>). --}}

<div class="table-responsive">
    <table {{ $attributes->class(['table custom-table align-middle mb-0']) }}>
        <thead>
            @isset($head)
                {{ $head }}
            @else
                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                    @foreach($heads as $h)
                        <th>{{ $h }}</th>
                    @endforeach
                </tr>
            @endisset
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
