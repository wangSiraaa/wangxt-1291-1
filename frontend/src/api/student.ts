import request from '../utils/request'

export interface Student {
  id: number
  name: string
  phone: string
  id_card: string
  birthday: string
  gender: number
  address: string
  emergency_contact: string
  emergency_phone: string
  remark: string
  status: boolean
  created_at: string
  updated_at: string
}

export interface StudentForm {
  name: string
  phone: string
  id_card: string
  birthday: string
  gender: number
  address: string
  emergency_contact: string
  emergency_phone: string
  remark: string
  status: boolean
}

export function getStudentList(params?: {
  keyword?: string
  status?: number
  gender?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: Student[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/students',
    method: 'get',
    params
  })
}

export function getStudentDetail(id: number) {
  return request<Student>({
    url: `/students/${id}`,
    method: 'get'
  })
}

export function createStudent(data: StudentForm) {
  return request<Student>({
    url: '/students',
    method: 'post',
    data
  })
}

export function updateStudent(id: number, data: Partial<StudentForm>) {
  return request<Student>({
    url: `/students/${id}`,
    method: 'put',
    data
  })
}

export function deleteStudent(id: number) {
  return request({
    url: `/students/${id}`,
    method: 'delete'
  })
}

export function getAllStudents() {
  return request<Student[]>({
    url: '/students/all',
    method: 'get'
  })
}
