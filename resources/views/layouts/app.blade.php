<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Digitech College Portal' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>

<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen lg:flex">
        <aside class="bg-emerald-800 text-white p-6 lg:w-64"><a
                href="{{ route('portal.dashboard', auth()->user()->role) }}" class="flex gap-3 items-center"><img
                    src="{{ asset('assets/images/16432.png') }}" class="w-11 h-11 object-contain" alt="Digitech"><span
                    class="font-bold">DIGITECH<br><small class="tracking-widest">COLLEGE</small></span></a>
            <p class="mt-8 text-xs uppercase tracking-widest text-emerald-200">{{ ucfirst(auth()->user()->role) }}
                Portal</p>
            <nav class="mt-3 space-y-1">
                @php($links = ['enrollment' => 'Enrollment', 'requirements' => 'Requirements', 'documents' => 'Documents', 'grades' => 'Grades', 'competencies' => 'Competencies', 'attendance' => 'Attendance', 'announcements' => 'Announcements', 'parent-links' => 'Parent Links', 'users' => 'Users', 'profile' => 'Profile'])
                @foreach($links as $key => $label) @if(!($key === 'users' && auth()->user()->role !== 'admin'))<a
                    class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-700 {{ request()->segment(4) === $key ? 'bg-emerald-700' : '' }}"
                href="{{ route('portal.module', [auth()->user()->role, $key]) }}">{{ $label }}</a>@endif @endforeach
            </nav>
            <form class="mt-8" method="POST" action="{{ route('logout') }}">@csrf<button
                    class="text-sm text-emerald-100 hover:text-white">Sign out</button></form>
        </aside>
        <main class="flex-1">
            <header class="border-b bg-white px-6 py-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-slate-400">Integrated Web Portal</p>
                    <h1 class="font-bold">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                <div class="text-right text-sm"><b>{{ auth()->user()->name }}</b>
                    <p class="text-xs text-slate-500">{{ auth()->user()->portal_id }}</p>
                </div>
            </header>
            <div class="p-5 sm:p-8">@if(session('success'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            {{ session('success') }}</div>@endif{{ $slot }}
            </div>
        </main>
    </div>
    <script>lucide.createIcons()</script>
    <script src="{{ asset('js/portal.js') }}"></script>
</body>

</html>