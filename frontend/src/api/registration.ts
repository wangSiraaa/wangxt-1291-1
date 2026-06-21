import request from '../utils/request'

export interface Registration {
  id: number
  registration_no: string
  schedule_id: number
  student_id: number
  fee: number
  payment_status: number
  paid_at: string
  payment_method: string
  transaction_id: string
  checkin_status: number
  checked_in_at: string
  remark: string
  status: number
  payment_status_text?: string
  checkin_status_text?: string
  status_text?: string
  schedule?: {
    id: number
    start_time: string
    end_time: string
    activity?: {
      id: number
      title: string
    }
  }
  student?: {
    id: number
    name: string
    phone: string
  }
  created_at: string
  updated_at: string
}

export interface RegistrationForm {
  schedule_id: number
  student_id: number
  fee: number
  payment_method: string
  remark: string
}

export function getRegistrationList(params?: {
  keyword?: string
  schedule_id?: number
  student_id?: number
  payment_status?: number
  checkin_status?: number
  status?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: Registration[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/registrations',
    method: 'get',
    params
  })
}

export function getRegistrationDetail(id: number) {
  return request<Registration>({
    url: `/registrations/${id}`,
    method: 'get'
  })
}

export function createRegistration(data: RegistrationForm) {
  return request<Registration>({
    url: '/registrations',
    method: 'post',
    data
  })
}

export function updateRegistration(id: number, data: Partial<RegistrationForm>) {
  return request<Registration>({
    url: `/registrations/${id}`,
    method: 'put',
    data
  })
}

export function deleteRegistration(id: number) {
  return request({
    url: `/registrations/${id}`,
    method: 'delete'
  })
}

export function payRegistration(id: number, data: {
  payment_method: string
  transaction_id?: string
  amount?: number
}) {
  return request<Registration>({
    url: `/registrations/${id}/pay`,
    method: 'post',
    data
  })
}

export function checkinRegistration(id: number) {
  return request<Registration>({
    url: `/registrations/${id}/checkin`,
    method: 'post'
  })
}

export function refundRegistration(id: number, data: {
  refund_amount?: number
  remark?: string
}) {
  return request<Registration>({
    url: `/registrations/${id}/refund`,
    method: 'post',
    data
  })
}

export function canSubmitWork(id: number) {
  return request<{
    can_submit: boolean
    message: string
  }>({
    url: `/registrations/${id}/can-submit-work`,
    method: 'get'
  })
}
