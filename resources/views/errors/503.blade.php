@extends('errors.layout')

@section('title', '503 - Under Scheduled Maintenance')

@section('content')
<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-purple-500/10 text-purple-400 border border-purple-500/20 mb-6">
    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
    Maintenance Mode • System Upgrade
</div>

<h1 class="text-7xl sm:text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-b from-white via-slate-200 to-slate-500 tracking-tight mb-4 drop-shadow-sm">
    503
</h1>

<h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 tracking-tight">
    Under Scheduled Maintenance
</h2>

<p class="text-slate-400 text-base sm:text-lg max-w-lg mb-8 leading-relaxed">
    Loomora systems are undergoing routine performance upgrades and database maintenance. We will be back online shortly. Thank you for your patience!
</p>

<div class="flex flex-wrap items-center justify-center gap-4">
    <button onclick="window.location.reload()" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Check System Status
    </button>
</div>
@endsection
