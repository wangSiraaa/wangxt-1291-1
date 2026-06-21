import request from '../utils/request'

export interface Schedule {
  id: number
  activity_id: number
  inheritor_id: number
  start_time: string
  end_time: string
  max_students: number
  registered_count: number
  notice: string
  inheritor_confirmed: number
  inheritor_remark: string
  status: number
  status_text?: string
  confirmed_text?: string
  activity?: {
    id: number
    title: string
    fee: number
  }
  inheritor?: {
    id: number
    name: string
    phone: string
  }
  created_at: string
  updated_at: string
}

export interface ScheduleForm {
  activity_id: number
  inheritor_id: number
  start_time: string
  end_time: string
  max_students: number
  notice: string
}

export function getScheduleList(params?: {
  keyword?: string
  activity_id?: number
  inheritor_id?: number
  status?: number
  inheritor_confirmed?: number
  start_date?: string
  end_date?: string
  page_size?: number
  page?: number
}) {
  return request<{
    data: Schedule[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/schedules',
    method: 'get',
    params
  })
}

export function getScheduleDetail(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}`,
    method: 'get'
  })
}

export function createSchedule(data: ScheduleForm) {
  return request<Schedule>({
    url: '/schedules',
    method: 'post',
    data
  })
}

export function updateSchedule(id: number, data: Partial<ScheduleForm>) {
  return request<Schedule>({
    url: `/schedules/${id}`,
    method: 'put',
    data
  })
}

export function deleteSchedule(id: number) {
  return request({
    url: `/schedules/${id}`,
    method: 'delete'
  })
}

export function confirmSchedule(id: number, data: {
  confirmed: number
  remark?: string
}) {
  return request<Schedule>({
    url: `/schedules/${id}/confirm`,
    method: 'post',
    data
  })
}

export function openRegistration(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}/open-registration`,
    method: 'post'
  })
}

export function closeRegistration(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}/close-registration`,
    method: 'post'
  })
}

export function startSchedule(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}/start`,
    method: 'post'
  })
}

export function endSchedule(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}/end`,
    method: 'post'
  })
}

export function cancelSchedule(id: number) {
  return request<Schedule>({
    url: `/schedules/${id}/cancel`,
    method: 'post'
  })
}
