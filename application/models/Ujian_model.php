<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian_model extends CI_Model {
    
    public function getDataUjian($id)
    {
        // Map exams/courses schema, aliasing to legacy keys
        $this->datatables->select('a.id AS id_ujian, a.access_token AS token, a.title AS nama_ujian, b.name AS nama_matkul, a.question_count AS jumlah_soal, CONCAT(a.start_time, " <br/> (", a.duration_minutes, " Minute)") as waktu, a.question_order AS jenis');
        $this->datatables->from('exams a');
        $this->datatables->join('courses b', 'a.course_id = b.id');
        if($id!==null){
            $this->datatables->where('instructor_id', $id);
        }
        return $this->datatables->generate();
    }
    
    public function getListUjian($id, $kelas)
    {
        $this->datatables->select("a.id AS id_ujian, e.full_name AS nama_dosen, d.name AS nama_kelas, a.title AS nama_ujian, b.name AS nama_matkul, a.question_count AS jumlah_soal, CONCAT(a.start_time, ' <br/> (', a.duration_minutes, ' Minute)') as waktu,  (SELECT COUNT(id) FROM exam_results h WHERE h.student_id = {$id} AND h.exam_id = a.id) AS ada");
        $this->datatables->from('exams a');
        $this->datatables->join('courses b', 'a.course_id = b.id');
        $this->datatables->join('class_instructors c', "a.instructor_id = c.instructor_id");
        $this->datatables->join('classes d', 'c.class_id = d.id');
        $this->datatables->join('instructors e', 'e.id = c.instructor_id');
        $this->datatables->where('d.id', $kelas);
        return $this->datatables->generate();
    }

    public function getUjianById($id)
    {
        $this->db->select('a.id AS id_ujian, a.instructor_id AS dosen_id, a.course_id AS matkul_id, a.title AS nama_ujian, a.question_count AS jumlah_soal, a.duration_minutes AS waktu, a.question_order AS jenis, a.start_time AS tgl_mulai, a.late_deadline AS terlambat, a.access_token AS token');
        $this->db->from('exams a');
        $this->db->join('instructors b', 'a.instructor_id=b.id');
        $this->db->join('courses c', 'a.course_id=c.id');
        $this->db->where('a.id', $id);
        return $this->db->get()->row();
    }

    public function getIdDosen($nip)
    {
        $this->db->select('id AS id_dosen, full_name AS nama_dosen')->from('instructors')->where('employee_id', $nip);
        return $this->db->get()->row();
    }

    public function getJumlahSoal($dosen)
    {
        $this->db->select('COUNT(id) as jml_soal');
        $this->db->from('questions');
        $this->db->where('instructor_id', $dosen);
        return $this->db->get()->row();
    }

    public function getIdMahasiswa($nim)
    {
        $this->db->select('a.id AS id_mahasiswa, a.full_name AS nama, a.student_number AS nim, a.email, a.gender AS jenis_kelamin, a.class_id AS kelas_id, b.name AS nama_kelas, c.name AS nama_jurusan');
        $this->db->from('students a');
        $this->db->join('classes b', 'a.class_id=b.id');
        $this->db->join('departments c', 'b.department_id=c.id');
        $this->db->where('a.student_number', $nim);
        return $this->db->get()->row();
    }

    public function HslUjian($id, $mhs)
    {
        $this->db->select('exam_results.*, UNIX_TIMESTAMP(exam_results.submitted_at) as waktu_habis, exam_results.correct_count AS jml_benar, exam_results.score AS nilai, exam_results.weighted_score AS nilai_bobot, exam_results.started_at AS tgl_mulai, exam_results.submitted_at AS tgl_selesai');
        $this->db->from('exam_results');
        $this->db->where('exam_id', $id);
        $this->db->where('student_id', $mhs);
        return $this->db->get();
    }

    public function getSoal($id)
    {
        $ujian = $this->getUjianById($id);
        $order = $ujian->jenis==="Random" ? 'rand()' : 'id';

        $this->db->select('id AS id_soal, question_text AS soal, file AS file, file_type AS tipe_file, option_a AS opsi_a, option_b AS opsi_b, option_c AS opsi_c, option_d AS opsi_d, option_e AS opsi_e, correct_answer AS jawaban');
        $this->db->from('questions');
        $this->db->where('instructor_id', $ujian->dosen_id);
        $this->db->where('course_id', $ujian->matkul_id);
        $this->db->order_by($order);
        $this->db->limit($ujian->jumlah_soal);
        return $this->db->get()->result();
    }

    public function ambilSoal($pc_urut_soal1, $pc_urut_soal_arr)
    {
        $this->db->select("*, {$pc_urut_soal1} AS jawaban");
        $this->db->from('questions');
        $this->db->where('id', $pc_urut_soal_arr);
        return $this->db->get()->row();
    }

    public function getJawaban($id_tes)
    {
        $this->db->select('answer_list');
        $this->db->from('exam_results');
        $this->db->where('id', $id_tes);
        return $this->db->get()->row()->answer_list;
    }

    public function getHasilUjian($nip = null)
    {
        $this->datatables->select('b.id AS id_ujian, b.title AS nama_ujian, b.question_count AS jumlah_soal, CONCAT(b.duration_minutes, " Minute") as waktu, b.start_time AS tgl_mulai');
        $this->datatables->select('c.name AS nama_matkul, d.full_name AS nama_dosen');
        $this->datatables->from('exam_results a');
        $this->datatables->join('exams b', 'a.exam_id = b.id');
        $this->datatables->join('courses c', 'b.course_id = c.id');
        $this->datatables->join('instructors d', 'b.instructor_id = d.id');
        $this->datatables->group_by('b.id');
        if($nip !== null){
            $this->datatables->where('d.employee_id', $nip);
        }
        return $this->datatables->generate();
    }

    public function HslUjianById($id, $dt=false)
    {
        if($dt===false){
            $db = "db";
            $get = "get";
        }else{
            $db = "datatables";
            $get = "generate";
        }
        
        $this->$db->select('d.id, a.nama, b.nama_kelas, c.nama_jurusan, d.jml_benar, d.nilai');
        $this->$db->from('mahasiswa a');
        $this->$db->join('kelas b', 'a.kelas_id=b.id_kelas');
        $this->$db->join('jurusan c', 'b.jurusan_id=c.id_jurusan');
        $this->$db->join('h_ujian d', 'a.id_mahasiswa=d.mahasiswa_id');
        $this->$db->where(['d.ujian_id' => $id]);
        return $this->$db->$get();
    }

    public function bandingNilai($id)
    {
        $this->db->select_min('nilai', 'min_nilai');
        $this->db->select_max('nilai', 'max_nilai');
        $this->db->select_avg('FORMAT(FLOOR(nilai),0)', 'avg_nilai');
        $this->db->where('ujian_id', $id);
        return $this->db->get('h_ujian')->row();
    }

}