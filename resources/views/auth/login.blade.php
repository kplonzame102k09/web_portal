<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login | Digitech College</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 grid lg:grid-cols-2">
    <section class="hidden lg:flex bg-emerald-800 p-12 text-white flex-col justify-between"><a
            href="{{ route('home') }}" class="flex items-center gap-3"><img src="{{ asset('assets/images/16432.png') }}"
                class="w-14 h-14 object-contain" alt="Digitech"><b>DIGITECH<br><small>COLLEGE</small></b></a>
        <div>
            <p class="font-bold uppercase tracking-widest text-emerald-200">Integrated Web Portal</p>
            <h1 class="mt-3 text-5xl font-extrabold">One portal for your college journey.</h1>
            <p class="mt-5 text-emerald-100">Your account is now protected by Laravel sessions and hashed passwords.</p>
        </div><small class="text-emerald-200">Database-backed access</small>
    </section>
    <main class="flex items-center justify-center p-6">
        <form method="POST" action="{{ route('login.store') }}"
            class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">@csrf<h1 class="text-2xl font-bold">Sign in to
                your portal</h1>
            <p class="mt-2 text-sm text-slate-500">Use your portal ID or registered email.</p>@error('identity')
            <p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p>@enderror<label
                class="block mt-6 text-sm font-medium">Role<select name="role"
                    class="mt-1 w-full rounded-lg border p-3">@foreach(['student' => 'Student', 'parent' => 'Parent', 'teacher' => 'Teacher', 'admin' => 'Admin'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></label><label class="block mt-4 text-sm font-medium">Portal ID or email<input name="identity"
                    value="{{ old('identity') }}" required class="mt-1 w-full rounded-lg border p-3"
                    placeholder="ADM-2026-X9B7GV"></label><label class="block mt-4 text-sm font-medium">Password<input
                    name="password" type="password" required class="mt-1 w-full rounded-lg border p-3"></label><label
                class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Remember
                me</label><button class="mt-6 w-full rounded-lg bg-emerald-600 p-3 font-semibold text-white">Sign
                in</button>
            <p class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600"><b>Seeded admin:</b> ADM-2026-X9B7GV /
                password</p>
        </form>
    </main>
</body>

</html>