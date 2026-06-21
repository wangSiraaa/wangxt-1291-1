import request from '../utils/request'

export interface MaterialPackage {
  id: number
  name: string
  sku_code: string
  description: string
  items: any[]
  stock_quantity: number
  warning_quantity: number
  cost: number
  image: string
  status: boolean
  created_at: string
  updated_at: string
}

export interface MaterialPackageForm {
  name: string
  sku_code: string
  description: string
  items: any[]
  stock_quantity: number
  warning_quantity: number
  cost: number
  image: string
  status: boolean
}

export function getMaterialPackageList(params?: {
  keyword?: string
  status?: number
  low_stock?: number
  page_size?: number
  page?: number
}) {
  return request<{
    data: MaterialPackage[]
    total: number
    current_page: number
    last_page: number
  }>({
    url: '/material-packages',
    method: 'get',
    params
  })
}

export function getMaterialPackageDetail(id: number) {
  return request<MaterialPackage>({
    url: `/material-packages/${id}`,
    method: 'get'
  })
}

export function createMaterialPackage(data: MaterialPackageForm) {
  return request<MaterialPackage>({
    url: '/material-packages',
    method: 'post',
    data
  })
}

export function updateMaterialPackage(id: number, data: Partial<MaterialPackageForm>) {
  return request<MaterialPackage>({
    url: `/material-packages/${id}`,
    method: 'put',
    data
  })
}

export function deleteMaterialPackage(id: number) {
  return request({
    url: `/material-packages/${id}`,
    method: 'delete'
  })
}

export function stockIn(id: number, data: {
  quantity: number
  remark?: string
  operator?: string
}) {
  return request<MaterialPackage>({
    url: `/material-packages/${id}/stock-in`,
    method: 'post',
    data
  })
}

export function stockOut(id: number, data: {
  quantity: number
  remark?: string
  operator?: string
}) {
  return request<MaterialPackage>({
    url: `/material-packages/${id}/stock-out`,
    method: 'post',
    data
  })
}

export function getAllMaterialPackages() {
  return request<MaterialPackage[]>({
    url: '/material-packages/all',
    method: 'get'
  })
}
