<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    public const DEPARTMENT_NAME = 'School of Information Technology and Engineering';
    public const OPTIONS = [
        'BLIS' => 'Bachelor of Library and Information Science',
        'BSEnSE' => 'Bachelor of Science in Environmental Science',
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSCpE' => 'Bachelor of Science in Computer Engineering',
    ];
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public static function codes(): array { return array_keys(self::OPTIONS); }
    public static function labels(): array
    {
        return collect(self::OPTIONS)->map(fn ($name, $code) => $code.' — '.$name)->all();
    }
    public function courses() { return $this->hasMany(Course::class, 'program', 'code'); }
    public function employees() { return $this->hasMany(Employee::class, 'program', 'code'); }
}
