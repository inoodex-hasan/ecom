@extends('errors.layout')

@section('title', '500 - Internal Server Error')

@section('content')
<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-red-500/10 text-red-400 border border-red-500/20 mb-6">
    <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
    Error 500 • Internal System Exception
</div>

<h1 class="text-7xl sm:text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-b from-white via-slate-200 to-slate-500 tracking-tight mb-4 drop-shadow-sm">
    500
</h1>

<h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 tracking-tight">
    Internal Server Error
</h2>

<p class="text-slate-400 text-base sm:text-lg max-w-lg mb-8 leading-relaxed">
    Something went wrong on our end while processing your request. Our technical team has been notified and is looking into the situation.
</p>

<div class="flex flex-wrap items-center justify-center gap-4">
    @auth
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            Return to Dashboard
        </a>
    @else
        <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            Return to Home
        </a>
    @endauth

    <button onclick="window.location.reload()" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-sm border border-slate-700/80 transition-all hover:border-slate-600 active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Retry Request
    </button>
</div>
@endsection
