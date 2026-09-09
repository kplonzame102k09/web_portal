<x-layouts.app :title="$config[0]">
    @if(!in_array($module, ['profile', 'settings'], true))
        <div class="flex justify-end mb-4">
            <a class="rounded-lg border bg-white px-3 py-2 text-sm text-emerald-700" href="{{ route('portal.export', [$role, $module]) }}">Export CSV</a>
        </div>
    @endif

    <div class="grid gap-6 {{ in_array($module, ['students', 'children', 'notifications', 'audit-logs']) ? '' : 'xl:grid-cols-[360px_1fr]' }}">
        @if(!in_array($module, ['students', 'children', 'notifications', 'audit-logs'], true))
            <section class="rounded-2xl bg-white p-6 shadow-sm border">
                <h2 class="text-xl font-bold">{{ $edit ? 'Edit' : 'Add' }} {{ rtrim($config[0], 's') }}</h2>
                <form class="mt-5 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('portal.store', [$role, $module]) }}">
                    @csrf
                    @if($edit)
                        <input type="hidden" name="record_id" value="{{ $edit->id }}">
                    @endif

                    @if(in_array($module, ['enrollment', 'requirements', 'documents', 'grades', 'competencies', 'attendance']) && in_array($role, ['admin', 'teacher']))
                        <label class="block text-sm">Student
                            <select name="student_id" required class="mt-1 w-full rounded-lg border p-2">
                                <option value="">Select student</option>
                                @foreach($users->where('role', 'student') as $student)
                                    <option value="{{ $student->id }}" @selected(($edit?->student_id) === $student->id)>{{ $student->name }} ({{ $student->portal_id }})</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    @foreach($config[1] as $field => $label)
                        @php($value = $edit?->data[$field] ?? ($module === 'profile' ? auth()->user()->{$field} : ''))
                        <label class="block text-sm font-medium">{{ $label }}
                            @if(in_array($field, ['message', 'remarks', 'announcement_notice']))
                                <textarea name="{{ $field }}" class="mt-1 w-full rounded-lg border p-2">{{ $value }}</textarea>
                            @elseif($field === 'role')
                                <select name="role" class="mt-1 w-full rounded-lg border p-2">
                                    @foreach(['student', 'parent', 'teacher', 'admin'] as $roleOption)
                                        <option value="{{ $roleOption }}" @selected($value === $roleOption)>{{ $roleOption }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input name="{{ $field }}" value="{{ $value }}" class="mt-1 w-full rounded-lg border p-2">
                            @endif
                        </label>
                    @endforeach

                    @if(in_array($module, ['requirements', 'documents']))
                        <label class="block text-sm">Attachment<input type="file" name="attachment" class="mt-1 block w-full text-sm"></label>
                    @endif

                    @if(!in_array($module, ['profile', 'users', 'settings']))
                        <label class="block text-sm">Status<input name="status" value="{{ $edit?->status ?? ($module === 'documents' ? 'Pending' : 'Submitted') }}" class="mt-1 w-full rounded-lg border p-2"></label>
                        <label class="block text-sm">Record date<input type="date" name="record_date" value="{{ optional($edit?->record_date)->format('Y-m-d') ?? now()->toDateString() }}" class="mt-1 w-full rounded-lg border p-2"></label>
                    @endif
                    <button class="w-full rounded-lg bg-emerald-600 p-3 font-semibold text-white">Save</button>
                </form>
            </section>
        @endif

        <section class="rounded-2xl bg-white p-6 shadow-sm border">
            <h2 class="text-xl font-bold">{{ $config[0] }}</h2>

            @if(in_array($module, ['users', 'students', 'children']))
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-sm"><thead class="text-left text-slate-500"><tr><th class="p-2">Name</th><th class="p-2">Portal ID</th><th class="p-2">Role</th><th class="p-2">Email</th></tr></thead>
                        <tbody>
                            @forelse($records as $person)
                                <tr class="border-t"><td class="p-2">{{ $person->name }}</td><td class="p-2">{{ $person->portal_id }}</td><td class="p-2">{{ ucfirst($person->role) }}</td><td class="p-2">{{ $person->email }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-7 text-center text-slate-500">No linked users yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif($module === 'notifications')
                <div class="mt-4 space-y-3">
                    @forelse($records as $record)
                        <div class="rounded-xl border p-4 {{ ($record->data['read'] ?? false) ? 'bg-white' : 'bg-emerald-50' }}">
                            <b>{{ $record->data['title'] ?? 'Portal update' }}</b>
                            <p class="mt-1 text-sm text-slate-600">{{ $record->data['message'] ?? '' }}</p>
                            @if(!($record->data['read'] ?? false))
                                <form class="mt-2" method="POST" action="{{ route('portal.notification.read', [$role, $record]) }}">
                                    @csrf
                                    <button class="text-xs text-emerald-700">Mark as read</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-sm text-slate-500">No notifications.</p>
                    @endforelse
                </div>
            @elseif($module === 'audit-logs')
                <div class="mt-4 space-y-3">
                    @forelse($records as $record)
                        <div class="border-b pb-3 text-sm"><b>{{ $record->data['action'] }}</b><p class="text-slate-500">{{ $record->data['actor'] }} - {{ $record->created_at }}</p></div>
                    @empty
                        <p class="py-6 text-sm text-slate-500">No audit records.</p>
                    @endforelse
                </div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-sm"><thead class="text-left text-slate-500"><tr><th class="p-2">Date</th><th class="p-2">Details</th><th class="p-2">Student</th><th class="p-2">Status</th><th class="p-2">Actions</th></tr></thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr class="border-t">
                                    <td class="p-2">{{ optional($record->record_date)->format('M d, Y') ?? $record->created_at->format('M d, Y') }}</td>
                                    <td class="p-2">{{ collect($record->data)->except('attachment')->filter()->join(' | ') }}
                                        @if(!empty($record->data['attachment']))
                                            <a class="block text-xs text-emerald-700" target="_blank" href="{{ asset('storage/' . $record->data['attachment']) }}">Download attachment</a>
                                        @endif
                                    </td>
                                    <td class="p-2">{{ $record->student?->name ?? '-' }}</td>
                                    <td class="p-2">{{ $record->status ?? 'Saved' }}</td>
                                    <td class="p-2 whitespace-nowrap">
                                        @if(in_array($role, ['admin', 'teacher']) || ($role === 'student' && in_array($module, ['enrollment', 'requirements', 'documents'])))
                                            <a class="text-xs text-emerald-700" href="{{ route('portal.module', [$role, $module, 'edit' => $record->id]) }}">Edit</a>
                                            <form class="inline" method="POST" onsubmit="return confirm('Delete this record?')" action="{{ route('portal.destroy', [$role, $module, $record]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ml-2 text-xs text-red-700">Delete</button>
                                            </form>
                                        @endif
                                        @if(in_array($role, ['admin', 'teacher']) && !in_array($module, ['announcements', 'parent-links', 'settings']))
                                            <form class="mt-1" method="POST" action="{{ route('portal.status', [$role, $module, $record]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input name="status" class="w-20 rounded border p-1 text-xs" value="{{ $record->status }}">
                                                <button class="text-xs text-emerald-700">Update</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-7 text-center text-slate-500">No records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
