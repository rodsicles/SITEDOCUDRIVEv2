<?php

namespace App\Support;

/**
 * Official SITE program course lists with year/term metadata.
 * BSEnSE and BSIT 4th-Year 2nd Sem are intentionally omitted until provided.
 */
class ProgramCurricula
{
    /**
     * @return array<string, list<array{code: string, title: string, year_level: int, semester: string}>>
     */
    public static function all(): array
    {
        return [
            'BLIS' => self::blis(),
            'BSCpE' => self::bscpe(),
            'BSIT' => self::bsit(),
        ];
    }

    /** @return list<array{code: string, title: string, year_level: int, semester: string}> */
    public static function forProgram(string $program): array
    {
        return self::all()[$program] ?? [];
    }

    /** @return list<array{code: string, title: string, year_level: int, semester: string}> */
    private static function blis(): array
    {
        return self::rows([
            [1, '1st', 'LIS101', 'Introduction to Library and Information Science'],
            [1, '1st', 'SpT01', 'School/Academic Librarianship'],
            [1, '2nd', 'LIS102', 'Collection Management of Information Resources'],
            [1, '2nd', 'ICT101Bis', 'Information Processing and Handling in Libraries and Information Centers'],
            [1, 'Summer', 'SpT102', 'Special/Public Librarianship'],
            [2, '1st', 'LIS103', 'Information Resources and Services I'],
            [2, '1st', 'LIS105', 'Organization of Information Resources I'],
            [2, '1st', 'ICT102BLIS', 'Web Technologies in Libraries and Information Centers'],
            [2, '1st', 'SpT03', 'Preservation of Information Resources'],
            [2, '1st', 'BLIS301', 'Internationalization Elective Interdisciplinary Courses'],
            [2, '2nd', 'LIS104', 'Information Resources and Services II'],
            [2, '2nd', 'LIS106', 'Organization of Information Resources II'],
            [2, '2nd', 'ICT103', 'Digital Libraries and Resources'],
            [2, '2nd', 'SpT04', 'Philosophies and Principles of Teaching'],
            [2, '2nd', 'LIT101', 'Panitikang Panlipunan'],
            [3, '1st', 'LIT102', 'Pelikulang Panlipunan'],
            [3, '1st', 'LIS107', 'Indexing and Abstracting'],
            [3, '1st', 'LIS110', 'Library Materials for Children and Young Adults'],
            [3, '1st', 'ICT104', 'Programming Fundamentals'],
            [3, '1st', 'SpT05', 'Educational Technology'],
            [3, '2nd', 'LIS108', 'Management of Libraries and Information Centers'],
            [3, '2nd', 'LIS109', 'Information Literacy'],
            [3, '2nd', 'ICT105', 'System Analysis and Design in Libraries and Information Centers'],
            [3, '2nd', 'LIS111', 'Introduction to Records Management and Archives'],
            [3, '2nd', 'SpT06', 'Indigenous Knowledge and Multi-Culturalism'],
            [3, '2nd', 'FLG101', 'Foreign Language'],
            [4, '1st', 'ICT106', 'Database Design for Libraries'],
            [4, '1st', 'SpT07', 'Foreign Language'],
            [4, '1st', 'LPr01', 'Library Practice 1'],
            [4, '1st', 'LIS112', 'Research Methods in Library and Information Science'],
            [4, '1st', 'BLIS401', 'Integrating Course 1'],
            [4, '2nd', 'LPr02', 'Library Practice 2'],
            [4, '2nd', 'LIS113', 'Thesis/Research Writing'],
            [4, '2nd', 'BLIS402', 'Integrating Course 2'],
        ]);
    }

    /** @return list<array{code: string, title: string, year_level: int, semester: string}> */
    private static function bscpe(): array
    {
        return self::rows([
            [1, '1st', 'ENGGMAT1', 'Calculus 1 (Differential)'],
            [1, '1st', 'CHEM1', 'Chemistry for Engineers'],
            [1, '1st', 'CPE101', 'Computer Engineering as a Discipline'],
            [1, '1st', 'CPE102', 'Programming Logic and Design'],
            [1, '2nd', 'ENGGMAT2', 'Calculus 2 (Integral)'],
            [1, '2nd', 'PHY1', 'Physics for Engineers'],
            [1, '2nd', 'CPE104', 'Object Oriented Programming'],
            [1, 'Summer', 'CPE103', 'Discrete Mathematics'],
            [2, '1st', 'ENGGMAT3', 'Differential Equations'],
            [2, '1st', 'CPE105', 'Data Structures and Algorithms'],
            [2, '1st', 'EE101', 'Fundamentals of Electrical Circuits'],
            [2, '1st', 'CPE107', 'Computer System Servicing'],
            [2, '2nd', 'ENGGMAT4', 'Engineering Data Analysis'],
            [2, '2nd', 'CPE108', 'Software Design'],
            [2, '2nd', 'ENGG102', 'Engineering Economy'],
            [2, '2nd', 'ENGG106', 'Computer-Aided Drafting'],
            [2, '2nd', 'ECE101', 'Fundamentals of Electronics Circuits'],
            [2, 'Summer', 'CPE106', 'Numerical Methods'],
            [3, '1st', 'CPE109', 'Logic Circuits and Design'],
            [3, '1st', 'CPE111', 'Data and Digital Communications'],
            [3, '1st', 'CPE112', 'Introduction to HDL'],
            [3, '1st', 'CPE113', 'Feedback and Control Systems'],
            [3, '1st', 'CPE110', 'Operating Systems'],
            [3, '1st', 'CPE115', 'Computer Engineering Drafting and Design'],
            [3, '1st', 'ELEC101CPE', 'Elective 1 (Embedded Systems 1)'],
            [3, '2nd', 'CPE116', 'Computer Networks and Security'],
            [3, '2nd', 'CPE117', 'Microprocessors'],
            [3, '2nd', 'CPE118', 'Methods of Research'],
            [3, '2nd', 'CPE114', 'Fundamentals of Mixed Signals and Sensors'],
            [3, '2nd', 'CPE119', 'Computer Engineering Laws and Professional Practice'],
            [3, '2nd', 'ENGG121', 'Basic Occupational Health and Safety'],
            [3, '2nd', 'ELEC102CPE', 'Elective 2 (Embedded Systems 2)'],
            [4, '1st', 'CPE120', 'Embedded Systems'],
            [4, '1st', 'CPE121', 'Computer Architecture and Organization'],
            [4, '1st', 'CPE124', 'CpE Practice and Design 1'],
            [4, '1st', 'CPE123', 'Digital Signal Processing'],
            [4, '1st', 'ENGG107', 'Technopreneurship 101'],
            [4, '1st', 'CPE122', 'Emerging Technologies in CpE'],
            [4, '1st', 'ELEC103CPE', 'Elective 3 (Embedded Systems 3)'],
            [4, '2nd', 'CPE125', 'CpE Practice and Design 2'],
            [4, '2nd', 'CPE126', 'Seminars and Field Trips'],
            [4, '2nd', 'CPE127', 'On the Job Training'],
        ]);
    }

    /** @return list<array{code: string, title: string, year_level: int, semester: string}> */
    private static function bsit(): array
    {
        return self::rows([
            [1, '1st', 'ITE101', 'Introduction to Computing'],
            [1, '1st', 'ITE102', 'Programming 1'],
            [1, '2nd', 'ITE103', 'Programming 2'],
            [1, '2nd', 'ITE104', 'Information Management'],
            [1, 'Summer', 'ITE105', 'Discrete Mathematics'],
            [2, '1st', 'ITE106', 'Data Structures and Algorithm'],
            [2, '1st', 'ITE114', 'Free Elective 1 (Accounting Process)'],
            [2, '1st', 'ITE107', 'Object Oriented Programming'],
            [2, '1st', 'ITE108', 'Web Systems and Technologies'],
            [2, '1st', 'ITE109', 'Advanced Database System'],
            [2, '2nd', 'ITE110', 'Rich Media Development'],
            [2, '2nd', 'ITE111', 'Application Development and Emerging Technologies'],
            [2, '2nd', 'ITE112', 'Quantitative Methods'],
            [2, '2nd', 'ITE113', 'Human Computer Interaction'],
            [3, '1st', 'ITE118', 'Elective 1 (Platform Technologies)'],
            [3, '1st', 'ITE119', 'System Integration and Architecture'],
            [3, '1st', 'ITE115', 'Information Assurance and Security'],
            [3, '1st', 'ITE116', 'Integrative and Programming Technologies'],
            [3, '1st', 'ITE117', 'Social and Professional Issues'],
            [3, '1st', 'ITE120', 'Capstone Project 1 (Project Research)'],
            [3, '2nd', 'ITE121', 'Computer Network Systems'],
            [3, '2nd', 'ITE122', 'Elective 2 (Game Development)'],
            [3, '2nd', 'ITE123', 'Capstone Project and Research 2 (Project Development)'],
            [3, '2nd', 'ITE124', 'Elective 3 (Hybrid Mobile Application)'],
            [3, '2nd', 'ITE125', 'Free Elective 2 (Strategic Planning)'],
            [3, '2nd', 'ITE126', 'Artificial Intelligence and Robotics'],
            [4, '1st', 'ITE127', 'Capstone Project and Research 2 - Project Implementation'],
            [4, '1st', 'ITE128', 'Systems Administration and Maintenance'],
            [4, '1st', 'ITE129', 'Free Elective 3 (Project Management)'],
            [4, '1st', 'ITE130', 'Elective 4 (Data Mining)'],
            [4, '1st', 'ITE131', 'Certification Exam'],
            // 4th Year — 2nd Semester: pending curriculum data
        ]);
    }

    /**
     * @param  list<array{0: int, 1: string, 2: string, 3: string}>  $rows
     * @return list<array{code: string, title: string, year_level: int, semester: string}>
     */
    private static function rows(array $rows): array
    {
        return array_map(static fn (array $row) => [
            'year_level' => $row[0],
            'semester' => $row[1],
            'code' => $row[2],
            'title' => $row[3],
        ], $rows);
    }
}
