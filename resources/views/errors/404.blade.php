@extends('errors.layout')

@section('title', '404 - Page Not Found')

@section('content')
<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-6">
    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
    Error 404 • Lost in the Stacks
</div>

<h1 class="text-7xl sm:text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-b from-white via-slate-200 to-slate-500 tracking-tight mb-4 drop-shadow-sm">
    404
</h1>

<h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 tracking-tight">
    Page Not Found
</h2>

<p class="text-slate-400 text-base sm:text-lg max-w-lg mb-8 leading-relaxed">
    The administrative URL or resource you requested could not be found. It may have been relocated, archived, or temporarily moved.
</p>

<div class="flex flex-wrap items-center justify-center gap-4">
    @auth
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Return to Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            Sign In to Account
        </a>
    @endauth

    <button onclick="window.history.back()" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-sm border border-slate-700/80 transition-all hover:border-slate-600 active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Go Back
    </button>
</div>
@endsection
