<?php

namespace App\Services;

use App\Models\{Course, Employee, Program, SchoolYear, TeacherLoad, User};
use App\Support\SchoolTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class TeacherLoadService
{
    public function facultyQuery(User $actor)
    {
        $query = Employee::whereIn('program', Program::codes())
            ->whereHas('user', fn ($q) => $q->where('status', 'Active')->whereHas('role', fn ($r) => $r->where('role_name', 'Faculty Employee')));
        if (!$actor->isDean()) $query->where('program', $actor->employee?->program ?? '__unassigned__');
        return $query;
    }

    public function assignedCourses(Employee $faculty, string $semester)
    {
        // Existing assignments are not year-specific; never fall back to unassigned catalog courses.
        return $faculty->user->assignedCourses()->where('courses.program', $faculty->program)
            ->where('is_active', true)->where(fn ($q) => $q->where('semester', $semester)->orWhereNull('semester'))
            ->orderBy('code')->get(['courses.id', 'code', 'title']);
    }

    public function validate(User $actor, array $input, ?TeacherLoad $load = null, bool $finalizing = false): array
    {
        abort_unless($actor->isDean() || $actor->isProgramCoordinator(), 403);
        $data = validator($input, [
            'employee_id' => ['required', 'integer'],
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'semester' => ['required', Rule::in(SchoolTerm::options())],
            'employment_status' => ['required', Rule::in(array_values(Employee::FACULTY_TYPES))],
            'lock_version' => [$load ? 'required' : 'nullable', 'integer', 'min:1'],
            'items' => ['present', 'array', 'max:60', $finalizing ? 'min:1' : 'min:0'],
            'items.*.kind' => ['required', Rule::in(['course', 'duty'])],
            'items.*.course_id' => ['nullable', 'integer'],
            'items.*.title' => ['nullable', 'string', 'max:150'],
            'items.*.section' => ['nullable', 'string', 'max:40'],
            'items.*.lecture_units' => ['required', 'numeric', 'min:0', 'max:99', 'decimal:0,2'],
            'items.*.lab_units' => ['required', 'numeric', 'min:0', 'max:99', 'decimal:0,2'],
            'items.*.load_equivalent' => ['required', 'numeric', 'min:0', 'max:999', 'decimal:0,3'],
            'items.*.class_size' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.schedules' => ['present', 'array', 'max:12'],
            'items.*.schedules.*.days' => ['required', 'array', 'min:1', 'max:7'],
            'items.*.schedules.*.days.*' => ['required', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'items.*.schedules.*.start' => ['required', 'date_format:H:i'],
            'items.*.schedules.*.end' => ['required', 'date_format:H:i'],
            'items.*.schedules.*.mode' => ['required', Rule::in(['Lec', 'Lab'])],
            'items.*.schedules.*.room' => ['required', 'string', 'max:40'],
        ])->validate();

        $faculty = $this->facultyQuery($actor)->find($data['employee_id']);
        abort_unless($faculty, 403, 'Choose an active faculty member within your program.');
        $year = SchoolYear::findOrFail($data['school_year_id']);
        if ($year->isArchived()) throw ValidationException::withMessages(['school_year_id' => 'Archived school years are read-only.']);
        if ($load && ((int) $load->employee_id !== (int) $faculty->employee_id || (int) $load->school_year_id !== (int) $year->id || $load->semester !== $data['semester'])) {
            throw ValidationException::withMessages(['employee_id' => 'The faculty and term cannot change on an existing load. Create a separate record for a different term.']);
        }
        $duplicate = TeacherLoad::where('employee_id', $faculty->employee_id)->where('school_year_id', $year->id)->where('semester', $data['semester']);
        if ($load) $duplicate->where('id', '!=', $load->id);
        if ($duplicate->exists()) throw ValidationException::withMessages(['semester' => 'A load already exists for this faculty and term. Open that record to continue.']);

        $courses = $this->assignedCourses($faculty, $data['semester'])->keyBy('id');
        $items = []; $sections = []; $meetings = []; $units = 0; $equivalents = 0;
        foreach ($data['items'] as $index => $row) {
            $row = \Illuminate\Support\Arr::only($row, ['kind', 'course_id', 'title', 'section', 'lecture_units', 'lab_units', 'load_equivalent', 'class_size', 'schedules']);
            $row['course_code'] = null;
            if ($row['kind'] === 'course') {
                $course = $courses->get($row['course_id'] ?? 0);
                if (!$course) throw ValidationException::withMessages(["items.$index.course_id" => 'Choose a subject assigned to this faculty for the selected semester.']);
                if (!trim($row['section'] ?? '') || empty($row['class_size'])) throw ValidationException::withMessages(["items.$index.section" => 'Enter the section and class size.']);
                $key = $course->id.'|'.mb_strtolower(trim($row['section']));
                if (isset($sections[$key])) throw ValidationException::withMessages(["items.$index.section" => 'This subject and section are already listed. Add another schedule to the existing row.']);
                $sections[$key] = true;
                $row['course_id'] = $course->id;
                $row['course_code'] = $course->code;
                $row['title'] = $course->title;
                $row['section'] = trim($row['section']);
                if ($finalizing && empty($row['schedules'])) throw ValidationException::withMessages(["items.$index.schedules" => 'Add at least one schedule before finalizing.']);
            } else {
                if (!trim($row['title'] ?? '')) throw ValidationException::withMessages(["items.$index.title" => 'Enter the additional duty.']);
                $row['title'] = trim($row['title']);
                $row['course_id'] = null;
                $row['lecture_units'] = $row['lab_units'] = 0;
                $row['class_size'] = $row['section'] = null;
                $row['schedules'] = [];
            }
            foreach ($row['schedules'] as $s => &$schedule) {
                $schedule = \Illuminate\Support\Arr::only($schedule, ['days', 'start', 'end', 'mode', 'room']);
                if ($schedule['end'] <= $schedule['start']) throw ValidationException::withMessages(["items.$index.schedules.$s.end" => 'The end time must be later than the start time.']);
                $schedule['days'] = array_values(array_unique($schedule['days']));
                foreach ($schedule['days'] as $day) {
                    foreach ($meetings[$day] ?? [] as [$start, $end]) {
                        if ($schedule['start'] < $end && $schedule['end'] > $start) throw ValidationException::withMessages(["items.$index.schedules.$s.start" => "The faculty already has a meeting at this time on $day."]);
                    }
                    $meetings[$day][] = [$schedule['start'], $schedule['end']];
                }
            }
            unset($schedule);
            $units += (int) round((float) $row['lecture_units'] * 100) + (int) round((float) $row['lab_units'] * 100);
            $equivalents += (int) round((float) $row['load_equivalent'] * 1000);
            $row['position'] = $index;
            $items[] = $row;
        }
        return [
            'header' => [
                'employee_id' => $faculty->employee_id, 'school_year_id' => $year->id, 'semester' => $data['semester'],
                'program' => $faculty->program, 'faculty_name' => $faculty->full_name, 'employee_number' => $faculty->employee_no,
                'department' => Program::DEPARTMENT_NAME, 'academic_year' => $year->start_year.'–'.$year->end_year,
                'employment_status' => $data['employment_status'], 'total_units' => $units / 100, 'total_load' => $equivalents / 1000,
            ],
            'items' => $items, 'lock_version' => $data['lock_version'] ?? null,
        ];
    }

    public function save(User $actor, array $input, ?TeacherLoad $load = null): TeacherLoad
    {
        return DB::transaction(function () use ($actor, $input, $load) {
            if ($load) {
                $load = TeacherLoad::visibleTo($actor)->lockForUpdate()->findOrFail($load->id);
                abort_if($load->status !== 'draft', 409, 'Finalized loads are read-only.');
                abort_if((int) ($input['lock_version'] ?? 0) !== $load->lock_version, 409, 'This load changed in another session. Reload it before saving.');
            }
            $data = $this->validate($actor, $input, $load);
            if ($load) {
                $load->update($data['header'] + ['lock_version' => $load->lock_version + 1]);
                $load->items()->delete();
            } else {
                $load = TeacherLoad::create($data['header'] + ['created_by_employee_id' => $actor->employee?->employee_id]);
            }
            $load->items()->createMany($data['items']);
            return $load->load('items');
        });
    }

    public function finalize(User $actor, TeacherLoad $load, int $version): TeacherLoad
    {
        return DB::transaction(function () use ($actor, $load, $version) {
            $load = TeacherLoad::visibleTo($actor)->lockForUpdate()->findOrFail($load->id);
            abort_if($load->status !== 'draft' || $version !== $load->lock_version, 409, 'This load has changed or is already finalized. Reload it to continue.');
            $input = $load->toArray();
            $input['items'] = $load->items->toArray();
            $data = $this->validate($actor, $input, $load, true);
            $load->update($data['header'] + ['status' => 'finalized', 'finalized_at' => now(), 'lock_version' => $load->lock_version + 1]);
            $load->items()->delete();
            $load->items()->createMany($data['items']);
            return $load->load('items');
        });
    }
}
