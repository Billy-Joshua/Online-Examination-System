<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->db->query("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));"
        );
    }

    private $table_map = [
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

    private $column_map = [
        'jurusan' => ['id_jurusan' => 'id', 'nama_jurusan' => 'name'],
        'kelas' => ['id_kelas' => 'id', 'nama_kelas' => 'name', 'jurusan_id' => 'department_id'],
        'dosen' => ['id_dosen' => 'id', 'nip' => 'employee_id', 'nama_dosen' => 'full_name', 'matkul_id' => 'course_id'],
        'mahasiswa' => ['id_mahasiswa' => 'id', 'nim' => 'student_number', 'nama' => 'full_name', 'jenis_kelamin' => 'gender', 'kelas_id' => 'class_id'],
        'matkul' => ['id_matkul' => 'id', 'nama_matkul' => 'name'],
        'tb_soal' => ['id_soal' => 'id', 'soal' => 'question_text', 'jawaban' => 'correct_answer', 'bobot' => 'weight', 'dosen_id' => 'instructor_id', 'matkul_id' => 'course_id', 'file_soal' => 'file', 'tipe_file' => 'file_type', 'opsi_a' => 'option_a', 'opsi_b' => 'option_b', 'opsi_c' => 'option_c', 'opsi_d' => 'option_d', 'opsi_e' => 'option_e', 'file_a' => 'file_a', 'file_b' => 'file_b', 'file_c' => 'file_c', 'file_d' => 'file_d', 'file_e' => 'file_e'],
        'm_ujian' => ['id_ujian' => 'id', 'dosen_id' => 'instructor_id', 'matkul_id' => 'course_id', 'nama_ujian' => 'title', 'jumlah_soal' => 'question_count', 'waktu' => 'duration_minutes', 'jenis' => 'question_order', 'tgl_mulai' => 'start_time', 'terlambat' => 'late_deadline', 'token' => 'access_token'],
        'h_ujian' => ['id' => 'id', 'ujian_id' => 'exam_id', 'mahasiswa_id' => 'student_id', 'question_list' => 'question_list', 'answer_list' => 'answer_list', 'jml_benar' => 'correct_count', 'nilai' => 'score', 'nilai_bobot' => 'weighted_score', 'started_at' => 'started_at', 'submitted_at' => 'submitted_at', 'status' => 'status'],
        'kelas_dosen' => ['dosen_id' => 'instructor_id', 'kelas_id' => 'class_id'],
        'jurusan_matkul' => ['matkul_id' => 'course_id', 'jurusan_id' => 'department_id']
    ];

    private function map_table($table)
    {
        $base = explode(' ', $table, 2)[0];
        return isset($this->table_map[$base]) ? str_replace($base, $this->table_map[$base], $table) : $table;
    }

    private function map_column_names($table, $data)
    {
        $base = explode(' ', $table, 2)[0];
        if (!isset($this->column_map[$base]) || !is_array($data)) {
            return $data;
        }
        $map = $this->column_map[$base];
        $mapped = [];
        foreach ($data as $key => $value) {
            $mapped_key = isset($map[$key]) ? $map[$key] : $key;
            $mapped[$mapped_key] = $value;
        }
        return $mapped;
    }

    private function map_primary_key($table, $pk)
    {
        $base = explode(' ', $table, 2)[0];
        if (isset($this->column_map[$base]) && isset($this->column_map[$base][$pk])) {
            return $this->column_map[$base][$pk];
        }
        return $pk;
    }

    public function create($table, $data, $batch = false)
    {
        $mapped_table = $this->map_table($table);
        if ($batch === false) {
            $insert_data = $this->map_column_names($table, $data);
            return $this->db->insert($mapped_table, $insert_data);
        }

        $batch_data = [];
        foreach ($data as $row) {
            $batch_data[] = $this->map_column_names($table, $row);
        }
        return $this->db->insert_batch($mapped_table, $batch_data);
    }

    public function update($table, $data, $pk, $id = null, $batch = false)
    {
        $mapped_table = $this->map_table($table);
        $mapped_pk = $this->map_primary_key($table, $pk);

        if ($batch === false) {
            $update_data = $this->map_column_names($table, $data);
            return $this->db->update($mapped_table, $update_data, array($mapped_pk => $id));
        }

        $batch_data = [];
        foreach ($data as $row) {
            $batch_data[] = $this->map_column_names($table, $row);
        }
        return $this->db->update_batch($mapped_table, $batch_data, $mapped_pk);
    }

    public function delete($table, $data, $pk)
    {
        $mapped_table = $this->map_table($table);
        $mapped_pk = $this->map_primary_key($table, $pk);
        $this->db->where_in($mapped_pk, $data);
        return $this->db->delete($mapped_table);
    }

    /**
     * Data Kelas
     */

    public function getDataKelas()
    {
        $this->datatables->select('c.id AS id_kelas, c.name AS nama_kelas, d.id AS id_jurusan, d.name AS nama_jurusan');
        $this->datatables->from('classes c');
        $this->datatables->join('departments d', 'c.department_id = d.id');
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_kelas, nama_kelas, id_jurusan, nama_jurusan');
        return $this->datatables->generate();
    }

    public function getKelasById($id)
    {
        $this->db->select('id AS id_kelas, name AS nama_kelas, department_id AS jurusan_id');
        $this->db->where_in('id', $id);
        $this->db->order_by('name');
        $query = $this->db->get('classes')->result();
        return $query;
    }

    /**
     * Data Jurusan
     */

    public function getDataJurusan()
    {
        $this->datatables->select('id AS id_jurusan, name AS nama_jurusan');
        $this->datatables->from('departments');
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_jurusan, nama_jurusan');
        return $this->datatables->generate();
    }

    public function getJurusanById($id)
    {
        $this->db->select('id AS id_jurusan, name AS nama_jurusan');
        $this->db->where_in('id', $id);
        $this->db->order_by('name');
        $query = $this->db->get('departments')->result();
        return $query;
    }

    /**
     * Data Mahasiswa
     */

    public function getDataMahasiswa()
    {
        $this->datatables->select('a.id AS id_mahasiswa, a.student_number AS nim, a.full_name AS nama, a.email, b.name AS nama_kelas, c.name AS nama_jurusan');
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE username = a.student_number) AS ada');
        $this->datatables->from('students a');
        $this->datatables->join('classes b', 'a.class_id = b.id');
        $this->datatables->join('departments c', 'b.department_id = c.id');
        return $this->datatables->generate();
    }

    public function getMahasiswaById($id)
    {
        $this->db->select('a.id AS id_mahasiswa, a.student_number AS nim, a.full_name AS nama, a.email, a.gender AS jenis_kelamin, a.class_id AS kelas_id, b.department_id AS jurusan_id');
        $this->db->from('students a');
        $this->db->join('classes b', 'a.class_id = b.id');
        $this->db->where(['a.id' => $id]);
        return $this->db->get()->row();
    }

    public function getJurusan()
    {
        $this->db->select('id AS id_jurusan, name AS nama_jurusan');
        $this->db->from('departments');
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }

    public function getAllJurusan($id = null)
    {
        if ($id === null) {
            $this->db->select('id AS id_jurusan, name AS nama_jurusan');
            $this->db->from('departments');
            $this->db->order_by('name', 'ASC');
            return $this->db->get()->result();
        } else {
            $this->db->select('department_id');
            $this->db->from('department_courses');
            $this->db->where('course_id', $id);
            $jurusan = $this->db->get()->result();
            $id_jurusan = [];
            foreach ($jurusan as $j) {
                $id_jurusan[] = $j->department_id;
            }
            if ($id_jurusan === []) {
                $id_jurusan = null;
            }

            $this->db->select('id AS id_jurusan, name AS nama_jurusan');
            $this->db->from('departments');
            if ($id_jurusan !== null) {
                $this->db->where_not_in('id', $id_jurusan);
            }
            $matkul = $this->db->get()->result();
            return $matkul;
        }
    }

    public function getKelasByJurusan($id)
    {
        $this->db->select('id AS id_kelas, name AS nama_kelas, department_id AS jurusan_id');
        $this->db->where('department_id', $id);
        $query = $this->db->get('classes');
        return $query->result();
    }

    /**
     * Data Dosen
     */

    public function getDataDosen()
    {
        // Map English DB schema (instructors, courses) but keep response keys used by views
        $this->datatables->select("a.id AS id_dosen, a.employee_id AS nip, a.full_name AS nama_dosen, a.email, a.course_id AS matkul_id, b.name AS nama_matkul, (SELECT COUNT(id) FROM users WHERE username = a.employee_id OR email = a.email) AS ada");
        $this->datatables->from('instructors a');
        $this->datatables->join('courses b', 'a.course_id=b.id');
        return $this->datatables->generate();
    }

    public function getDosenById($id)
    {
        // Return result with old property names so controllers/views don't break
        $this->db->select('id AS id_dosen, employee_id AS nip, full_name AS nama_dosen, email, course_id AS matkul_id');
        $this->db->from('instructors');
        $this->db->where('id', $id);
        return $this->db->get()->row();
    }

    /**
     * Data Matkul
     */

    public function getDataMatkul()
    {
        // Map courses -> expose id_matkul / nama_matkul for compatibility
        $this->datatables->select('id AS id_matkul, name AS nama_matkul');
        $this->datatables->from('courses');
        return $this->datatables->generate();
    }

    public function getAllMatkul()
    {
        // Return course list but keep original keys used by views
        $this->db->select('id AS id_matkul, name AS nama_matkul');
        return $this->db->get('courses')->result();
    }

    public function getMatkulById($id, $single = false)
    {
        if ($single === false) {
            $this->db->where_in('id', $id);
            $this->db->order_by('name');
            $this->db->select('id AS id_matkul, name AS nama_matkul');
            $query = $this->db->get('courses')->result();
        } else {
            $this->db->select('id AS id_matkul, name AS nama_matkul');
            $query = $this->db->get_where('courses', array('id'=>$id))->row();
        }
        return $query;
    }

    /**
     * Data Kelas Dosen
     */

    public function getKelasDosen()
    {
        // Map class_instructors + instructors + classes to expected keys
        $this->datatables->select('class_instructors.id, instructors.id AS id_dosen, instructors.employee_id AS nip, instructors.full_name AS nama_dosen, GROUP_CONCAT(classes.name) as kelas');
        $this->datatables->from('class_instructors');
        $this->datatables->join('classes', 'class_instructors.class_id=classes.id');
        $this->datatables->join('instructors', 'class_instructors.instructor_id=instructors.id');
        $this->datatables->group_by('instructors.full_name');
        return $this->datatables->generate();
    }

    public function getAllDosen($id = null)
    {
        $this->db->select('instructor_id');
        $this->db->from('class_instructors');
        if ($id !== null) {
            $this->db->where_not_in('instructor_id', [$id]);
        }
        $dosen = $this->db->get()->result();
        $id_dosen = [];
        foreach ($dosen as $d) {
            $id_dosen[] = $d->instructor_id;
        }
        if ($id_dosen === []) {
            $id_dosen = null;
        }

        $this->db->select('id AS id_dosen, employee_id AS nip, full_name AS nama_dosen');
        $this->db->from('instructors');
        if ($id_dosen !== null) {
            $this->db->where_not_in('id', $id_dosen);
        }
        return $this->db->get()->result();
    }

    
    public function getAllKelas()
    {
        $this->db->select('c.id AS id_kelas, c.name AS nama_kelas, d.name AS nama_jurusan');
        $this->db->from('classes c');
        $this->db->join('departments d', 'c.department_id = d.id');
        $this->db->order_by('c.name');
        return $this->db->get()->result();
    }
    
    public function getKelasByDosen($id)
    {
        $this->db->select('classes.id');
        $this->db->from('class_instructors');
        $this->db->join('classes', 'class_instructors.class_id=classes.id');
        $this->db->where('instructor_id', $id);
        $query = $this->db->get()->result();
        return $query;
    }
    /**
     * Data Jurusan Matkul
     */

    public function getJurusanMatkul()
    {
        // department_courses joining courses and departments; keep column aliases
        $this->datatables->select('department_courses.id, courses.id AS id_matkul, courses.name AS nama_matkul, departments.id AS id_jurusan, GROUP_CONCAT(departments.name) as nama_jurusan');
        $this->datatables->from('department_courses');
        $this->datatables->join('courses', 'department_courses.course_id=courses.id');
        $this->datatables->join('departments', 'department_courses.department_id=departments.id');
        $this->datatables->group_by('courses.name');
        return $this->datatables->generate();
    }

    public function getMatkul($id = null)
    {
        $this->db->select('course_id AS matkul_id');
        $this->db->from('department_courses');
        if ($id !== null) {
            $this->db->where_not_in('course_id', [$id]);
        }
        $matkul = $this->db->get()->result();
        $id_matkul = [];
        foreach ($matkul as $d) {
            $id_matkul[] = $d->matkul_id;
        }
        if ($id_matkul === []) {
            $id_matkul = null;
        }

        $this->db->select('id AS id_matkul, name AS nama_matkul');
        $this->db->from('courses');
        $this->db->where_not_in('id', $id_matkul);
        return $this->db->get()->result();
    }

    public function getJurusanByIdMatkul($id)
    {
        $this->db->select('department_courses.department_id AS jurusan_id');
        $this->db->from('department_courses');
        $this->db->join('departments', 'department_courses.department_id = departments.id');
        $this->db->where('department_courses.course_id', $id);
        $query = $this->db->get()->result();
        return $query;
    }
}
