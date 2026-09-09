<?php
namespace Database\Seeders;
use App\Models\PortalRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [];
        foreach ([['ADM-2026-X9B7GV', 'Kim Philip', 'Lonzame', 'kpdplonzame@digitech.edu', 'admin'], ['STU-2026-000001', 'Maria', 'Santos', 'maria.santos@digitech.edu', 'student'], ['PRT-2026-000001', 'Rosa', 'Santos', 'rosa.santos@digitech.edu', 'parent'], ['TCH-2026-000001', 'Alex', 'Reyes', 'alex.reyes@digitech.edu', 'teacher']] as [$id, $first, $last, $email, $role]) {
            $users[$role] = User::updateOrCreate(['email' => $email], ['portal_id' => $id, 'first_name' => $first, 'last_name' => $last, 'role' => $role, 'password' => Hash::make('password')]);
        }
        $s = $users['student'];
        $t = $users['teacher'];
        $p = $users['parent'];
        foreach ([['enrollment', 'Approved', ['program' => 'Information Technology', 'school_year' => '2026-2027']], ['requirements', 'Submitted', ['requirement' => 'Birth Certificate', 'remarks' => 'Verified']], ['documents', 'Processing', ['document_type' => 'Certificate of Enrollment', 'purpose' => 'Scholarship']], ['grades', 'Published', ['subject' => 'Web Development', 'grade' => '1.50', 'term' => 'First Semester']], ['competencies', 'Competent', ['competency' => 'Develop Web Applications', 'result' => 'Competent']], ['attendance', 'Present', ['subject' => 'Web Development', 'remarks' => 'On time']]] as [$type, $status, $data]) {
            PortalRecord::updateOrCreate(['type' => $type, 'student_id' => $s->id], ['user_id' => $t->id, 'created_by' => $t->id, 'status' => $status, 'record_date' => now()->toDateString(), 'data' => $data]);
        }
        PortalRecord::updateOrCreate(['type' => 'parent-links', 'user_id' => $p->id, 'student_id' => $s->id], ['created_by' => $users['admin']->id, 'status' => 'Active', 'record_date' => now()->toDateString(), 'data' => ['student_portal_id' => $s->portal_id, 'relationship' => 'Mother']]);
        PortalRecord::updateOrCreate(['type' => 'announcements', 'user_id' => $users['admin']->id], ['created_by' => $users['admin']->id, 'status' => 'Published', 'record_date' => now()->toDateString(), 'data' => ['title' => 'Welcome to Digitech Portal', 'message' => 'Your database-backed portal is ready.', 'audience' => 'All']]);
    }
}