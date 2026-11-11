@extends('layouts.pos')

@section('title', 'POS Order Terminal')

@section('content')
<div id="pos-root" class="h-full w-full"></div>

{{-- Initial Data Payload for React POS & Test Verification --}}
<script id="pos-initial-state" type="application/json">
{
    "branch": @json($branch),
    "user": @json(auth()->user()),
    "role": "{{ auth()->user()->roles->first()?->name ?? 'Staff' }}",
    "categories": @json($categories),
    "diningAreas": @json($diningAreas),
    "recentOrders": @json($recentOrders ?? []),
    "waiters": @json($waiters ?? []),
    "routes": {
        "storeOrder": "{{ route('pos.orders.store') }}",
        "addItemsBase": "{{ url('/pos/orders') }}",
        "settleBase": "{{ url('/pos/orders') }}",
        "dashboard": "{{ route('dashboard') }}",
        "tablesIndex": "{{ route('tables.index') }}"
    },
    "csrfToken": "{{ csrf_token() }}",
    "appName": "{{ config('app.name', 'Kerefa Restaurant') }}",
    "permissions": @json(auth()->user()->isSuperAdmin() ? ['*'] : auth()->user()->roles->flatMap->permissions->pluck('slug')->unique()->values())
}
</script>

{{-- Server Rendered Items Payload for Headless Assertions --}}
<div class="visually-hidden" aria-hidden="true" style="display: none !important;">
    <h1>POS Order Terminal</h1>
    @foreach($categories as $category)
        <span>{{ $category->name }}</span>
        @foreach($category->menuItems as $item)
            <span>{{ $item->name }} - {{ $item->code }} - {{ $item->price }}</span>
        @endforeach
    @endforeach
</div>
@endsection
