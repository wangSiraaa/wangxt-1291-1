import request from '../utils/request'

export interface Inheritor {
  id: number
  name: string
  phone: string
  id_card: string
  craft_type: string
  bio: string
  avatar: string
  status: boolean
  created_at: string
  updated_at: string
}

export interface InheritorForm {
  name: string
  phone: string
  id_card: string
  craft_type: string
  bio: string
  avatar: string
  status: boolean
}

export function getInheritorList(params?: {
  keyword?: string
  craft_type?: string
  status?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: Inheritor[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/inheritors',
    method: 'get',
    params
  })
}

export function getInheritorDetail(id: number) {
  return request<Inheritor>({
    url: `/inheritors/${id}`,
    method: 'get'
  })
}

export function createInheritor(data: InheritorForm) {
  return request<Inheritor>({
    url: '/inheritors',
    method: 'post',
    data
  })
}

export function updateInheritor(id: number, data: Partial<InheritorForm>) {
  return request<Inheritor>({
    url: `/inheritors/${id}`,
    method: 'put',
    data
  })
}

export function deleteInheritor(id: number) {
  return request({
    url: `/inheritors/${id}`,
    method: 'delete'
  })
}

export function getAllInheritors() {
  return request<Inheritor[]>({
    url: '/inheritors/all',
    method: 'get'
  })
}
