<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    private function map_table($table)
    {
        // handle aliasing like 'mahasiswa a'
        $parts = explode(' ', $table, 2);
        $base = $parts[0];
        $alias = isset($parts[1]) ? ' ' . $parts[1] : '';

        $map = [
            'jurusan' => 'departments',
            'kelas' => 'classes',
            'dosen' => 'instructors',
            'mahasiswa' => 'students',
            'matkul' => 'courses',
            'tb_soal' => 'questions',
            'm_ujian' => 'exams',
            'h_ujian' => 'exam_results',
            'kelas_dosen' => 'class_instructors',
            'jurusan_matkul' => 'department_courses'
        ];

        return (isset($map[$base]) ? $map[$base] : $base) . $alias;
    }

    private function map_pk($table, $pk)
    {
        // Map legacy primary/key column names per table
        $base = explode(' ', $table)[0];
        $pk_map = [
            'dosen' => ['nip' => 'employee_id', 'id_dosen' => 'id'],
            'mahasiswa' => ['nim' => 'student_number', 'id_mahasiswa' => 'id'],
            'kelas_dosen' => ['dosen_id' => 'instructor_id', 'kelas_id' => 'class_id'],
            'jurusan_matkul' => ['matkul_id' => 'course_id', 'jurusan_id' => 'department_id'],
            'matkul' => ['id_matkul' => 'id'],
            'kelas' => ['id_kelas' => 'id'],
            'jurusan' => ['id_jurusan' => 'id'],
            'm_ujian' => ['id_ujian' => 'id'],
            'h_ujian' => ['mahasiswa_id' => 'student_id', 'ujian_id' => 'exam_id']
        ];

        if (isset($pk_map[$base]) && isset($pk_map[$base][$pk])) {
            return $pk_map[$base][$pk];
        }
        return $pk;
    }

    public function total($table)
    {
        $mapped = $this->map_table($table);
        $query = $this->db->get($mapped)->num_rows();
        return $query;
    }

    private function map_condition($condition)
    {
        $replace = [
            'dosen.' => 'instructors.',
            'matkul.' => 'courses.',
            'kelas_dosen.' => 'class_instructors.',
            'kelas.' => 'classes.',
            'jurusan.' => 'departments.',
            'mahasiswa.' => 'students.',
            'id_jurusan' => 'id',
            'id_kelas' => 'id',
            'id_matkul' => 'id',
            'id_dosen' => 'id',
            'id_mahasiswa' => 'id',
            'matkul_id' => 'course_id',
            'dosen_id' => 'instructor_id',
            'kelas_id' => 'class_id',
            'jurusan_id' => 'department_id',
            'nim' => 'student_number'
        ];
        return str_replace(array_keys($replace), array_values($replace), $condition);
    }

    public function get_where($table, $pk, $id, $join = null, $order = null)
    {
        // map table and pk where necessary
        $mapped_table = $this->map_table($table);
        $mapped_pk = $this->map_pk($table, $pk);

        $this->db->select('*');
        $this->db->from($mapped_table);
        $this->db->where($mapped_pk, $id);

        if($join !== null){
            foreach($join as $jtable => $field){
                $mapped_jtable = $this->map_table($jtable);
                $field = $this->map_condition($field);
                $this->db->join($mapped_jtable, $field);
            }
        }
        
        if($order !== null){
            foreach($order as $field => $sort){
                $this->db->order_by($this->map_condition($field), $sort);
            }
        }

        $query = $this->db->get();
        return $query;
    }
}
