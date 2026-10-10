@extends('errors.layout')

@section('title', '403 - Access Forbidden')

@section('content')
<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20 mb-6">
    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
    Error 403 • Restricted Zone
</div>

<h1 class="text-7xl sm:text-9xl font-extrabold text-transparent bg-clip-text bg-gradient-to-b from-white via-slate-200 to-slate-500 tracking-tight mb-4 drop-shadow-sm">
    403
</h1>

<h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 tracking-tight">
    Access Forbidden
</h2>

<p class="text-slate-400 text-base sm:text-lg max-w-lg mb-8 leading-relaxed">
    You do not possess the required permissions or administrative privileges to view this section. If you believe this is an error, please contact the store administrator.
</p>

<div class="flex flex-wrap items-center justify-center gap-4">
    @auth
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            Return to Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 hover:from-sky-400 hover:to-indigo-500 transition-all hover:scale-105 active:scale-95">
            Sign In with Privileges
        </a>
    @endauth

    <button onclick="window.history.back()" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-sm border border-slate-700/80 transition-all hover:border-slate-600 active:scale-95">
        Go Back
    </button>
</div>
@endsection
