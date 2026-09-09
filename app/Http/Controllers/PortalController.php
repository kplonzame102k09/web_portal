<?php
namespace App\Http\Controllers;
use App\Models\PortalRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    private const MODULES = ['enrollment' => ['Enrollment', ['program' => 'Program / Track', 'school_year' => 'School Year', 'remarks' => 'Remarks']], 'requirements' => ['Requirements', ['requirement' => 'Requirement', 'remarks' => 'Remarks']], 'documents' => ['Document Requests', ['document_type' => 'Document Type', 'purpose' => 'Purpose', 'remarks' => 'Remarks']], 'grades' => ['Grades', ['subject' => 'Subject', 'grade' => 'Grade', 'term' => 'Term', 'remarks' => 'Remarks']], 'competencies' => ['Competencies', ['competency' => 'Competency', 'result' => 'Result', 'remarks' => 'Remarks']], 'attendance' => ['Attendance', ['subject' => 'Subject', 'remarks' => 'Remarks']], 'announcements' => ['Announcements', ['title' => 'Title', 'message' => 'Message', 'audience' => 'Audience']], 'parent-links' => ['Parent Links', ['student_portal_id' => 'Student Portal ID', 'relationship' => 'Relationship']], 'users' => ['User Management', ['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'role' => 'Role']], 'profile' => ['My Profile', ['contact' => 'Contact number', 'department' => 'Department']], 'students' => ['My Students', []], 'children' => ['My Children', []], 'settings' => ['Portal Settings', ['announcement_notice' => 'Announcement notice', 'support_email' => 'Support email']], 'notifications' => ['Notifications', []], 'audit-logs' => ['Audit Logs', []]];
    private const ACCESS = ['student' => ['enrollment', 'requirements', 'documents', 'grades', 'competencies', 'attendance', 'announcements', 'profile', 'notifications'], 'parent' => ['children', 'attendance', 'grades', 'documents', 'announcements', 'profile', 'notifications'], 'teacher' => ['students', 'grades', 'competencies', 'attendance', 'announcements', 'profile', 'notifications'], 'admin' => ['users', 'enrollment', 'documents', 'grades', 'competencies', 'attendance', 'announcements', 'requirements', 'parent-links', 'settings', 'profile', 'notifications', 'audit-logs']];
    public function dashboard(string $role, Request $r)
    {
        $this->role($r, $role);
        $records = $this->visible($r->user());
        $stats = ['enrollments' => $records->where('type', 'enrollment')->count(), 'documents' => $records->where('type', 'documents')->count(), 'grades' => $records->where('type', 'grades')->count(), 'attendance' => $records->where('type', 'attendance')->count()];
        $unread = $this->notifications($r->user())->where('data.read', false)->count();
        return view('portal.dashboard', compact('role', 'records', 'stats', 'unread'));
    }
    public function module(string $role, string $module, Request $r)
    {
        $this->moduleAccess($role, $module);
        $config = self::MODULES[$module];
        $users = User::orderBy('last_name')->get();
        $records = $this->records($r->user(), $module);
        $edit = $r->filled('edit') ? $records->firstWhere('id', (int) $r->edit) : null;
        return view('portal.module', compact('role', 'module', 'config', 'records', 'users', 'edit'));
    }
    public function store(string $role, string $module, Request $r)
    {
        $this->moduleAccess($role, $module);
        $actor = $r->user();
        if (in_array($module, ['students', 'children', 'notifications', 'audit-logs'], true))
            return back();
        if ($module === 'profile') {
            $profile = $r->validate(['contact' => ['nullable', 'string', 'max:50'], 'department' => ['nullable', 'string', 'max:100'], 'photo' => ['nullable', 'image', 'max:2048']]);
            unset($profile['photo']);
            $actor->update($profile);
            if ($r->hasFile('photo'))
                $actor->update(['photo' => $r->file('photo')->store('profiles', 'public')]);
            $this->audit($actor, 'Profile updated', 'profile', $actor->id);
            return back()->with('success', 'Profile updated.');
        }
        if ($module === 'settings') {
            $data = $r->validate(['announcement_notice' => ['nullable', 'string', 'max:500'], 'support_email' => ['nullable', 'email', 'max:255'], 'notify_students' => ['nullable', 'boolean'], 'notify_parents' => ['nullable', 'boolean'], 'notify_teachers' => ['nullable', 'boolean']]);
            PortalRecord::updateOrCreate(['type' => 'settings', 'user_id' => $actor->id], ['created_by' => $actor->id, 'data' => $data]);
            $this->audit($actor, 'Settings saved', 'settings', 0);
            return back()->with('success', 'Settings saved.');
        }
        if ($module === 'users') {
            abort_unless($role === 'admin', 403);
            $d = $r->validate(['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', Rule::unique('users')->ignore($r->integer('user_id'))], 'role' => ['required', Rule::in(['student', 'parent', 'teacher', 'admin'])], 'password' => ['nullable', 'string', 'min:8']]);
            $u = $r->filled('user_id') ? User::findOrFail($r->integer('user_id')) : new User();
            if (!$u->exists)
                $d['portal_id'] = (['student' => 'STU', 'parent' => 'PRT', 'teacher' => 'TCH', 'admin' => 'ADM'][$d['role']]) . '-' . now()->year . '-' . strtoupper(str()->random(6));
            if (!empty($d['password']))
                $u->password = $d['password'];
            unset($d['password']);
            $u->fill($d);
            if (!$u->exists && !$u->password)
                $u->password = 'password';
            $u->save();
            $this->audit($actor, $r->filled('user_id') ? 'User updated' : 'User created', 'user', $u->id);
            return back()->with('success', 'User saved.');
        }
        $fields = array_keys(self::MODULES[$module][1]);
        $rules = array_fill_keys($fields, ['nullable', 'string', 'max:1000']);
        if (in_array($module, ['requirements', 'documents'], true))
            $rules['attachment'] = ['nullable', 'file', 'max:5120'];
        $data = $r->validate($rules);
        $studentId = $this->studentId($r);
        if ($module === 'parent-links') {
            $student = User::where('portal_id', $data['student_portal_id'] ?? '')->where('role', 'student')->first();
            if (!$student)
                return back()->withErrors(['student_portal_id' => 'Student Portal ID was not found.']);
            $studentId = $student->id;
        }
        if (in_array($module, ['grades', 'competencies', 'attendance'], true))
            abort_unless(in_array($role, ['teacher', 'admin'], true), 403);
        if ($module === 'announcements') {
            abort_unless(in_array($role, ['teacher', 'admin'], true), 403);
            $studentId = null;
        }
        if ($r->hasFile('attachment'))
            $data['attachment'] = $r->file('attachment')->store($module, 'public');
        $record = $r->filled('record_id') ? PortalRecord::findOrFail($r->integer('record_id')) : new PortalRecord();
        if ($record->exists)
            abort_unless($this->mayManage($actor, $record), 403);
        $record->fill(['type' => $module, 'student_id' => $studentId, 'user_id' => $record->user_id ?: $actor->id, 'created_by' => $actor->id, 'status' => $r->input('status', $module === 'documents' ? 'Pending' : 'Submitted'), 'record_date' => $r->input('record_date', now()->toDateString()), 'data' => $data]);
        $record->save();
        $this->audit($actor, $r->filled('record_id') ? ucfirst($module) . ' updated' : ucfirst($module) . ' created', $module, $record->id);
        $this->notifyFor($record, $actor, $r->filled('record_id') ? 'updated' : 'created');
        return redirect()->route('portal.module', [$role, $module])->with('success', 'Record saved.');
    }
    public function status(string $role, string $module, PortalRecord $record, Request $r)
    {
        $this->moduleAccess($role, $module);
        abort_unless(in_array($role, ['teacher', 'admin'], true) && $record->type === $module, 403);
        $record->update($r->validate(['status' => ['required', 'string', 'max:50']]));
        $this->audit($r->user(), ucfirst($module) . ' status changed', $module, $record->id);
        $this->notifyFor($record, $r->user(), 'status changed');
        return back()->with('success', 'Status updated.');
    }
    public function destroy(string $role, string $module, PortalRecord $record, Request $r)
    {
        $this->moduleAccess($role, $module);
        abort_unless($record->type === $module && $this->mayManage($r->user(), $record), 403);
        $this->audit($r->user(), ucfirst($module) . ' deleted', $module, $record->id);
        if (!empty($record->data['attachment']))
            Storage::disk('public')->delete($record->data['attachment']);
        $record->delete();
        return back()->with('success', 'Record deleted.');
    }
    public function export(string $role, string $module, Request $r)
    {
        $this->moduleAccess($role, $module);
        $records = $this->records($r->user(), $module);
        $rows = [['ID', 'Date', 'Student', 'Status', 'Details']];
        foreach ($records as $record) {
            if ($record instanceof User) {
                $rows[] = [$record->portal_id, $record->created_at, $record->name, $record->role, $record->email];
            } else {
                $rows[] = [(string) $record->id, (string) $record->record_date, $record->student?->name ?? '', (string) $record->status, collect($record->data)->except('attachment')->filter()->join(' | ')];
            }
        }
        $out = fopen('php://temp', 'r+');
        foreach ($rows as $row)
            fputcsv($out, $row);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $module . '.csv"']);
    }
    public function readNotification(string $role, PortalRecord $record, Request $r)
    {
        $this->role($r, $role);
        abort_unless($record->type === 'notification' && ($record->data['recipient_id'] ?? null) === $r->user()->id, 403);
        $data = $record->data;
        $data['read'] = true;
        $record->update(['data' => $data]);
        return back();
    }
    public function backup(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        return response()->json(['exported_at' => now()->toIso8601String(), 'users' => User::all(), 'records' => PortalRecord::all()])->header('Content-Disposition', 'attachment; filename="digitech-backup.json"');
    }
    private function role(Request $r, string $role): void
    {
        abort_unless($r->user()->role === $role, 403);
    }
    private function moduleAccess(string $role, string $module): void
    {
        abort_unless(isset(self::MODULES[$module]) && in_array($module, self::ACCESS[$role] ?? [], true), 404);
    }
    private function studentId(Request $r): ?int
    {
        if ($r->user()->role === 'student')
            return $r->user()->id;
        return $r->integer('student_id') ?: null;
    }
    private function records(User $u, string $module)
    {
        if ($module === 'users')
            return User::orderBy('last_name')->get();
        if ($module === 'students')
            return $this->teacherStudents($u);
        if ($module === 'children')
            return $this->children($u);
        if ($module === 'notifications')
            return $this->notifications($u);
        if ($module === 'audit-logs')
            return PortalRecord::where('type', 'audit')->latest()->get();
        if ($module === 'settings')
            return PortalRecord::where('type', 'settings')->get();
        return $this->visible($u)->where('type', $module);
    }
    private function visible(User $u)
    {
        $q = PortalRecord::with(['student', 'author'])->latest();
        if ($u->role === 'admin')
            return $q->get();
        if ($u->role === 'student')
            return $q->where(fn($x) => $x->where('student_id', $u->id)->orWhere('user_id', $u->id)->orWhere('type', 'announcements'))->get();
        if ($u->role === 'parent') {
            $ids = $this->children($u)->pluck('id');
            return $q->where(fn($x) => $x->whereIn('student_id', $ids)->orWhere('user_id', $u->id)->orWhere('type', 'announcements'))->get();
        }
        $ids = $this->teacherStudents($u)->pluck('id');
        return $q->where(fn($x) => $x->whereIn('student_id', $ids)->orWhere('user_id', $u->id)->orWhere('type', 'announcements'))->get();
    }
    private function children(User $u)
    {
        return User::whereIn('id', PortalRecord::where('type', 'parent-links')->where('user_id', $u->id)->pluck('student_id'))->get();
    }
    private function teacherStudents(User $u)
    {
        return User::whereIn('id', PortalRecord::whereIn('type', ['grades', 'competencies', 'attendance', 'enrollment'])->where('created_by', $u->id)->pluck('student_id')->filter()->unique())->get();
    }
    private function mayManage(User $u, PortalRecord $r): bool
    {
        return $u->role === 'admin' || $r->created_by === $u->id || ($u->role === 'student' && $r->student_id === $u->id && in_array($r->type, ['enrollment', 'requirements', 'documents'], true));
    }
    private function audit(User $u, string $action, string $entity, int $id): void
    {
        PortalRecord::create(['type' => 'audit', 'user_id' => $u->id, 'created_by' => $u->id, 'status' => 'Logged', 'record_date' => now(), 'data' => ['action' => $action, 'entity' => $entity, 'record_id' => $id, 'actor' => $u->name]]);
    }
    private function notifications(User $u)
    {
        return PortalRecord::where('type', 'notification')->where('data->recipient_id', $u->id)->latest()->get();
    }
    private function notifyFor(PortalRecord $r, User $actor, string $verb): void
    {
        $ids = array_filter([$r->student_id]);
        if ($r->student_id)
            $ids = array_merge($ids, PortalRecord::where('type', 'parent-links')->where('student_id', $r->student_id)->pluck('user_id')->all());
        foreach (array_unique($ids) as $id)
            if ($id !== $actor->id)
                PortalRecord::create(['type' => 'notification', 'user_id' => $actor->id, 'created_by' => $actor->id, 'status' => 'Unread', 'record_date' => now(), 'data' => ['recipient_id' => $id, 'read' => false, 'title' => ucfirst($r->type) . ' ' . $verb, 'message' => 'A ' . $r->type . ' record was ' . $verb . '.', 'record_id' => $r->id]]);
    }
}