import request from '../utils/request'

export interface Activity {
  id: number
  title: string
  description: string
  material_package_id: number | null
  max_students: number
  fee: number
  duration_minutes: number
  location: string
  cover_image: string
  requirements: string
  status: number
  status_text?: string
  material_package?: {
    id: number
    name: string
    stock_quantity: number
  }
  created_at: string
  updated_at: string
}

export interface ActivityForm {
  title: string
  description: string
  material_package_id: number | null
  max_students: number
  fee: number
  duration_minutes: number
  location: string
  cover_image: string
  requirements: string
}

export function getActivityList(params?: {
  keyword?: string
  status?: number
  material_package_id?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: Activity[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/activities',
    method: 'get',
    params
  })
}

export function getActivityDetail(id: number) {
  return request<Activity>({
    url: `/activities/${id}`,
    method: 'get'
  })
}

export function createActivity(data: ActivityForm) {
  return request<Activity>({
    url: '/activities',
    method: 'post',
    data
  })
}

export function updateActivity(id: number, data: Partial<ActivityForm>) {
  return request<Activity>({
    url: `/activities/${id}`,
    method: 'put',
    data
  })
}

export function deleteActivity(id: number) {
  return request({
    url: `/activities/${id}`,
    method: 'delete'
  })
}

export function publishActivity(id: number) {
  return request<Activity>({
    url: `/activities/${id}/publish`,
    method: 'post'
  })
}

export function cancelActivity(id: number) {
  return request<Activity>({
    url: `/activities/${id}/cancel`,
    method: 'post'
  })
}

export function getAllActivities() {
  return request<Activity[]>({
    url: '/activities/all',
    method: 'get'
  })
}
