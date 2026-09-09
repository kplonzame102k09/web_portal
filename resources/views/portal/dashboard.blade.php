<x-layouts.app :title="ucfirst($role).' Dashboard'">
    @php
        $roleCopy = [
            'student' => ['Student workspace', 'Your academic progress, requests, and college updates are all in one place.', 'Open enrollment', 'enrollment'],
            'parent' => ['Parent workspace', 'Keep track of your linked children and their academic records.', 'View children', 'children'],
            'teacher' => ['Teacher workspace', 'Manage your students, assessments, grades, and class attendance.', 'My students', 'students'],
            'admin' => ['Administration', 'Monitor portal activity, resolve pending work, and manage the college community.', 'Manage users', 'users'],
        ][$role];
        $icons = ['enrollments'=>['clipboard-check','bg-emerald-50 text-emerald-600'],'documents'=>['file-text','bg-amber-50 text-amber-600'],'grades'=>['chart-no-axes-combined','bg-blue-50 text-blue-600'],'attendance'=>['calendar-check-2','bg-violet-50 text-violet-600']];
        $quickActions = ['student'=>['enrollment'=>'Enrollment','documents'=>'Document requests','grades'=>'View grades'],'parent'=>['children'=>'My children','attendance'=>'Attendance','grades'=>'Grades'],'teacher'=>['students'=>'My students','grades'=>'Gradebook','attendance'=>'Attendance'],'admin'=>['users'=>'Manage users','enrollment'=>'Enrollment queue','documents'=>'Document queue']][$role];
    @endphp
    <section class="portal-hero mb-7 p-6 sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-100">{{ $roleCopy[0] }}</p><h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Welcome back, {{ auth()->user()->first_name }}.</h2><p class="mt-3 max-w-xl text-sm leading-6 text-emerald-50">{{ $roleCopy[1] }}</p></div>
            <a href="{{ route('portal.module',[$role,$roleCopy[3]]) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-lg shadow-emerald-950/10">{{ $roleCopy[2] }} <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i></a>
        </div>
    </section>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($stats as $label=>$count)
            <article class="portal-card p-5"><span class="stat-icon {{ $icons[$label][1] }}"><i data-lucide="{{ $icons[$label][0] }}" class="h-5 w-5"></i></span><p class="mt-4 text-sm font-medium text-slate-500">{{ ucfirst($label) }}</p><p class="mt-1 text-3xl font-extrabold">{{ $count }}</p><p class="mt-1 text-xs text-slate-400">{{ $label==='grades'?'Published academic records':'Current portal records' }}</p></article>
        @endforeach
    </section>
    <section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_1fr]">
        <article class="portal-card p-5 sm:p-6"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-600">Activity</p><h3 class="mt-1 text-xl font-extrabold">Recent updates</h3><p class="mt-1 text-sm text-slate-500">Your latest records and actions in the portal.</p></div><a class="action-link text-sm" href="{{ route('portal.module',[$role,'notifications']) }}">Notifications {{ $unread ? '('.$unread.')' : '' }}</a></div><div class="mt-5 divide-y divide-slate-100">@forelse($records->whereNotIn('type',['notification','audit','settings'])->take(5) as $record)<div class="flex items-center gap-3 py-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="bell-ring" class="h-4 w-4"></i></span><div class="min-w-0 flex-1"><b class="block text-sm">{{ ucfirst(str_replace('-',' ',$record->type)) }}</b><p class="truncate text-xs text-slate-500">{{ collect($record->data)->except('attachment')->filter()->take(2)->join(' - ') ?: 'Portal record updated' }}</p></div><span class="status-pill" data-status="{{ $record->status }}">{{ $record->status ?: 'Saved' }}</span></div>@empty<div class="py-8 text-center text-sm text-slate-500">No activity yet. Use the portal menu to get started.</div>@endforelse</div></article>
        <article class="portal-card p-5 sm:p-6"><p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">Quick access</p><h3 class="mt-1 text-xl font-extrabold">Keep moving</h3><p class="mt-1 text-sm text-slate-500">Open the tasks most relevant to you.</p><div class="mt-5 grid gap-3">@foreach($quickActions as $module=>$label)<a href="{{ route('portal.module',[$role,$module]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm font-semibold hover:border-emerald-300 hover:bg-emerald-50"><span>{{ $label }}</span><i data-lucide="arrow-up-right" class="h-4 w-4 text-emerald-600"></i></a>@endforeach</div></article>
    </section>
</x-layouts.app>