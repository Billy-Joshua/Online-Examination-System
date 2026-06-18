<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal_model extends CI_Model {
    
    public function getDataSoal($id, $dosen)
    {
        // Map questions -> preserve response keys used by views
        $this->datatables->select('a.id AS id_soal, a.question_text AS soal, FROM_UNIXTIME(a.created_on) as created_on, FROM_UNIXTIME(a.updated_on) as updated_on, b.name AS nama_matkul, c.full_name AS nama_dosen');
        $this->datatables->from('questions a');
        $this->datatables->join('courses b', 'b.id=a.course_id');
        $this->datatables->join('instructors c', 'c.id=a.instructor_id');
        if ($id!==null && $dosen===null) {
            $this->datatables->where('a.course_id', $id);            
        }else if($id!==null && $dosen!==null){
            $this->datatables->where('a.instructor_id', $dosen);
        }
        return $this->datatables->generate();
    }

    public function getSoalById($id)
    {
        $this->db->select('id AS id_soal, question_text AS soal, file AS file, file_type AS tipe_file, option_a AS opsi_a, option_b AS opsi_b, option_c AS opsi_c, option_d AS opsi_d, option_e AS opsi_e, correct_answer AS jawaban, created_on, updated_on');
        $this->db->from('questions');
        $this->db->where('id', $id);
        return $this->db->get()->row();
    }

    public function getMatkulDosen($nip)
    {
        $this->db->select('instructors.course_id AS matkul_id, courses.name AS nama_matkul, instructors.id AS id_dosen, instructors.full_name AS nama_dosen');
        $this->db->from('instructors');
        $this->db->join('courses', 'instructors.course_id=courses.id');
        $this->db->where('instructors.employee_id', $nip);
        return $this->db->get()->row();
    }

    public function getAllDosen()
    {
        $this->db->select('instructors.id AS id_dosen, instructors.employee_id AS nip, instructors.full_name AS nama_dosen, courses.id AS id_matkul, courses.name AS nama_matkul');
        $this->db->from('instructors a');
        $this->db->join('courses b', 'a.course_id=b.id');
        return $this->db->get()->result();
    }
}