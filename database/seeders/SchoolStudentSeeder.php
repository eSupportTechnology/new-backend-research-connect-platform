<?php

namespace Database\Seeders;

use App\Models\RegisterUsers\ParentModel;
use App\Models\RegisterUsers\Student;
use App\Models\RegisterUsers\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolStudentSeeder extends Seeder
{
    /**
     * Seeds school-student accounts the same way registerGeneralUser() does:
     * a GENERAL_USER user, a students row, and the linked parent/guardian.
     * Covers all three verification states so the admin verification screens
     * have something to work with.
     */
    public function run(): void
    {
        $students = [
            [
                'first_name'  => 'Nimal',
                'last_name'   => 'Perera',
                'email'       => 'nimal.student@example.com',
                'school_name' => 'Royal College, Colombo',
                'grade_level' => 10,
                'student_id'  => 'RC-2026-0101',
                'status'      => 'approved',
                'notes'       => null,
                'parent'      => ['Sunil', 'Perera', 'sunil.perera@example.com', '+94771234501', 'Father'],
            ],
            [
                'first_name'  => 'Kamala',
                'last_name'   => 'Silva',
                'email'       => 'kamala.student@example.com',
                'school_name' => 'Visakha Vidyalaya, Colombo',
                'grade_level' => 12,
                'student_id'  => 'VV-2026-0244',
                'status'      => 'pending',
                'notes'       => null,
                'parent'      => ['Chandra', 'Silva', 'chandra.silva@example.com', '+94771234502', 'Mother'],
            ],
            [
                'first_name'  => 'Ruwan',
                'last_name'   => 'Fernando',
                'email'       => 'ruwan.student@example.com',
                'school_name' => 'St. Joseph\'s College, Colombo',
                'grade_level' => 9,
                'student_id'  => 'SJC-2026-0377',
                'status'      => 'pending',
                'notes'       => null,
                'parent'      => ['Anusha', 'Fernando', 'anusha.fernando@example.com', '+94771234503', 'Mother'],
            ],
            [
                'first_name'  => 'Dilini',
                'last_name'   => 'Jayawardena',
                'email'       => 'dilini.student@example.com',
                'school_name' => 'Mahamaya Girls\' College, Kandy',
                'grade_level' => 11,
                'student_id'  => 'MGC-2026-0512',
                'status'      => 'rejected',
                'notes'       => 'Birth certificate image was unreadable — asked for a re-upload.',
                'parent'      => ['Priyantha', 'Jayawardena', 'priyantha.j@example.com', '+94771234504', 'Guardian'],
            ],
            [
                'first_name'  => 'Sahan',
                'last_name'   => 'Bandara',
                'email'       => 'sahan.student@example.com',
                'school_name' => 'Trinity College, Kandy',
                'grade_level' => 13,
                'student_id'  => 'TC-2026-0688',
                'status'      => 'approved',
                'notes'       => null,
                'parent'      => ['Nadeeka', 'Bandara', 'nadeeka.bandara@example.com', '+94771234505', 'Mother'],
            ],
        ];

        foreach ($students as $row) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'first_name'      => $row['first_name'],
                    'last_name'       => $row['last_name'],
                    'password'        => Hash::make('password123'),
                    'role'            => 'GENERAL_USER',
                    'user_type'       => 'School Student',
                    'status'          => 'Active',
                    // School students are granted gold on registration.
                    'membership_tier' => 'gold',
                    'deleted_at'      => null,
                ]
            );

            $user->forceFill(['email_verified_at' => now()])->save();

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_name'            => $row['school_name'],
                    'grade_level'            => $row['grade_level'],
                    'student_id'             => $row['student_id'],
                    'birth_certificate_path' => $this->makeDummyCertificate($row),
                    'verification_status'    => $row['status'],
                    'verification_notes'     => $row['notes'],
                ]
            );

            [$pFirst, $pLast, $pEmail, $pPhone, $pRelation] = $row['parent'];

            ParentModel::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'first_name' => $pFirst,
                    'last_name'  => $pLast,
                    'email'      => $pEmail,
                    'phone'      => $pPhone,
                    'relation'   => $pRelation,
                ]
            );
        }

        $this->command->info('Seeded ' . count($students) . ' school students (password: password123).');
    }

    /**
     * Writes a placeholder birth certificate onto the public disk and returns
     * its relative path, matching what the upload in registerGeneralUser()
     * stores. The filename is derived from the student, so re-running the
     * seeder overwrites the same file instead of piling up new ones.
     */
    private function makeDummyCertificate(array $row): string
    {
        $path = 'birth_certificates/seed-' . Str::slug($row['first_name'] . '-' . $row['last_name']) . '.pdf';

        Storage::disk('public')->put($path, $this->buildPdf([
            'DEMOCRATIC SOCIALIST REPUBLIC OF SRI LANKA',
            'CERTIFICATE OF BIRTH  (SEEDED SAMPLE - NOT A REAL DOCUMENT)',
            '',
            'Name:          ' . $row['first_name'] . ' ' . $row['last_name'],
            'School:        ' . $row['school_name'],
            'Grade:         ' . $row['grade_level'],
            'Student ID:    ' . $row['student_id'],
            'Guardian:      ' . $row['parent'][0] . ' ' . $row['parent'][1] . ' (' . $row['parent'][4] . ')',
            '',
            'This file is generated by SchoolStudentSeeder for local testing of',
            'the student verification screens. It has no legal meaning.',
        ]));

        return $path;
    }

    /**
     * Builds a minimal single-page PDF (Helvetica, A4) from plain text lines.
     * Hand-rolled so the seeder needs no PDF package — the byte offsets in the
     * xref table are computed as the objects are appended.
     */
    private function buildPdf(array $lines): string
    {
        $text = "BT\n/F1 11 Tf\n50 780 Td\n16 TL\n";
        foreach ($lines as $line) {
            // Escape the characters that are syntax inside a PDF string literal.
            $text .= '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line) . ") Tj T*\n";
        }
        $text .= "ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                . '/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length ' . strlen($text) . " >>\nstream\n" . $text . "\nendstream",
        ];

        $pdf     = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref' . "\n" . '0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer' . "\n" . '<< /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }
}