import request from '../utils/request'

export interface Work {
  id: number
  title: string
  description: string
  registration_id: number
  student_id: number
  activity_id: number
  inheritor_id: number
  images: string[]
  inheritor_comment: string
  score: number
  is_excellent: boolean
  is_public: boolean
  admin_remark: string
  status: number
  status_text?: string
  registration?: {
    id: number
    student?: {
      id: number
      name: string
      phone: string
    }
    schedule?: {
      id: number
      activity?: {
        id: number
        title: string
      }
    }
  }
  created_at: string
  updated_at: string
}

export interface WorkForm {
  title: string
  description: string
  registration_id: number
  images: string[]
}

export function getWorkList(params?: {
  keyword?: string
  activity_id?: number
  student_id?: number
  inheritor_id?: number
  status?: number
  is_excellent?: number
  is_public?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: Work[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/works',
    method: 'get',
    params
  })
}

export function getWorkDetail(id: number) {
  return request<Work>({
    url: `/works/${id}`,
    method: 'get'
  })
}

export function createWork(data: WorkForm) {
  return request<Work>({
    url: '/works',
    method: 'post',
    data
  })
}

export function updateWork(id: number, data: Partial<WorkForm>) {
  return request<Work>({
    url: `/works/${id}`,
    method: 'put',
    data
  })
}

export function deleteWork(id: number) {
  return request({
    url: `/works/${id}`,
    method: 'delete'
  })
}

export function reviewWork(id: number, data: {
  status: number
  comment?: string
  score?: number
  remark?: string
}) {
  return request<Work>({
    url: `/works/${id}/review`,
    method: 'post',
    data
  })
}

export function setWorkPublic(id: number) {
  return request<Work>({
    url: `/works/${id}/set-public`,
    method: 'post'
  })
}

export function setWorkPrivate(id: number) {
  return request<Work>({
    url: `/works/${id}/set-private`,
    method: 'post'
  })
}

export function setWorkExcellent(id: number) {
  return request<Work>({
    url: `/works/${id}/set-excellent`,
    method: 'post'
  })
}

export function cancelWorkExcellent(id: number) {
  return request<Work>({
    url: `/works/${id}/cancel-excellent`,
    method: 'post'
  })
}
