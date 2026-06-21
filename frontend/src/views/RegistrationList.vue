<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getRegistrationList,
  createRegistration,
  updateRegistration,
  deleteRegistration,
  payRegistration,
  checkinRegistration,
  refundRegistration,
  canSubmitWork,
  type Registration,
  type RegistrationForm
} from '../api/registration'
import { getScheduleList, type Schedule } from '../api/schedule'
import { getAllStudents, type Student } from '../api/student'

const loading = ref(false)
const dialogVisible = ref(false)
const payDialogVisible = ref(false)
const refundDialogVisible = ref(false)
const dialogTitle = ref('新增报名')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)

const searchForm = reactive({
  keyword: '',
  schedule_id: null as number | null,
  student_id: null as number | null,
  payment_status: null as number | null,
  checkin_status: null as number | null,
  status: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const registrationList = ref<Registration[]>([])
const schedules = ref<Schedule[]>([])
const students = ref<Student[]>([])

const formData = reactive<RegistrationForm>({
  schedule_id: 0,
  student_id: 0,
  fee: 0,
  payment_method: 'cash',
  remark: ''
})

const payFormData = reactive({
  payment_method: 'cash',
  transaction_id: '',
  amount: 0
})

const refundFormData = reactive({
  refund_amount: 0,
  remark: ''
})

const paymentStatusOptions = [
  { label: '未支付', value: 0, type: 'warning' },
  { label: '已支付', value: 1, type: 'success' },
  { label: '已退款', value: 2, type: 'info' }
]

const checkinStatusOptions = [
  { label: '未签到', value: 0, type: 'warning' },
  { label: '已签到', value: 1, type: 'success' }
]

const paymentMethodOptions = [
  { label: '现金', value: 'cash' },
  { label: '微信支付', value: 'wechat' },
  { label: '支付宝', value: 'alipay' },
  { label: '银行转账', value: 'bank' }
]

function getPaymentStatusTag(status: number) {
  const option = paymentStatusOptions.find(o => o.value === status)
  return option || { label: '未知', type: 'info' }
}

function getCheckinStatusTag(status: number) {
  const option = checkinStatusOptions.find(o => o.value === status)
  return option || { label: '未知', type: 'info' }
}

function formatDateTime(datetime: string) {
  if (!datetime) return '-'
  return datetime
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getRegistrationList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    registrationList.value = res.data
    pagination.total = res.total
  } finally {
    loading.value = false
  }
}

async function fetchOptions() {
  const [scheds, studs] = await Promise.all([
    getScheduleList({ page_size: 100 }),
    getAllStudents()
  ])
  schedules.value = scheds.data
  students.value = studs
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function handleReset() {
  searchForm.keyword = ''
  searchForm.schedule_id = null
  searchForm.student_id = null
  searchForm.payment_status = null
  searchForm.checkin_status = null
  searchForm.status = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增报名'
  currentId.value = null
  Object.assign(formData, {
    schedule_id: 0,
    student_id: 0,
    fee: 0,
    payment_method: 'cash',
    remark: ''
  })
  dialogVisible.value = true
}

async function handleEdit(row: Registration) {
  dialogTitle.value = '编辑报名'
  currentId.value = row.id
  Object.assign(formData, {
    schedule_id: row.schedule_id,
    student_id: row.student_id,
    fee: row.fee,
    payment_method: row.payment_method || 'cash',
    remark: row.remark
  })
  dialogVisible.value = true
}

async function handleDelete(row: Registration) {
  try {
    await ElMessageBox.confirm('确定要删除该报名吗？', '提示', {
      type: 'warning'
    })
    await deleteRegistration(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

function handlePay(row: Registration) {
  currentId.value = row.id
  payFormData.amount = row.fee
  payFormData.payment_method = 'cash'
  payFormData.transaction_id = ''
  payDialogVisible.value = true
}

async function handlePaySubmit() {
  if (!currentId.value) return
  try {
    await payRegistration(currentId.value, payFormData)
    ElMessage.success('支付成功')
    payDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleCheckin(row: Registration) {
  try {
    await ElMessageBox.confirm('确定要签到吗？', '提示', {
      type: 'warning'
    })
    await checkinRegistration(row.id)
    ElMessage.success('签到成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

function handleRefund(row: Registration) {
  currentId.value = row.id
  refundFormData.refund_amount = row.fee
  refundFormData.remark = ''
  refundDialogVisible.value = true
}

async function handleRefundSubmit() {
  if (!currentId.value) return
  try {
    await refundRegistration(currentId.value, refundFormData)
    ElMessage.success('退款成功')
    refundDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleCheckCanSubmitWork(row: Registration) {
  try {
    const res = await canSubmitWork(row.id)
    if (res.can_submit) {
      ElMessage.success(res.message)
    } else {
      ElMessage.warning(res.message)
    }
  } catch (error) {
    console.error(error)
  }
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (currentId.value) {
        await updateRegistration(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createRegistration(formData)
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      fetchList()
    } catch (error) {
      console.error(error)
    }
  })
}

function handlePageChange(page: number) {
  pagination.page = page
  fetchList()
}

function handleSizeChange(size: number) {
  pagination.page_size = size
  pagination.page = 1
  fetchList()
}

onMounted(() => {
  fetchList()
  fetchOptions()
})
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">报名管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增报名
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="报名编号/学员姓名"
            clearable
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="排期">
          <el-select
            v-model="searchForm.schedule_id"
            placeholder="全部"
            clearable
            style="width: 200px"
          >
            <el-option
              v-for="item in schedules"
              :key="item.id"
              :label="`#${item.id} ${item.start_time}`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="支付状态">
          <el-select
            v-model="searchForm.payment_status"
            placeholder="全部"
            clearable
            style="width: 120px"
          >
            <el-option
              v-for="item in paymentStatusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="签到状态">
          <el-select
            v-model="searchForm.checkin_status"
            placeholder="全部"
            clearable
            style="width: 120px"
          >
            <el-option
              v-for="item in checkinStatusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <div class="table-card">
      <el-table
        v-loading="loading"
        :data="registrationList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="registration_no" label="报名编号" width="160" />
        <el-table-column label="活动" min-width="180">
          <template #default="{ row }">
            <div v-if="row.schedule?.activity">
              <div class="font-medium">{{ row.schedule.activity.title }}</div>
              <div class="text-info text-xs">{{ row.schedule.start_time }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="学员" width="150">
          <template #default="{ row }">
            <div v-if="row.student">
              <div>{{ row.student.name }}</div>
              <div class="text-info text-xs">{{ row.student.phone }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="fee" label="费用(元)" width="100" />
        <el-table-column label="支付状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getPaymentStatusTag(row.payment_status).type">
              {{ getPaymentStatusTag(row.payment_status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="签到状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getCheckinStatusTag(row.checkin_status).type">
              {{ getCheckinStatusTag(row.checkin_status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="支付时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.paid_at) }}
          </template>
        </el-table-column>
        <el-table-column label="签到时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.checked_in_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="320" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.payment_status === 0"
              type="success"
              link
              @click="handlePay(row)"
            >
              支付
            </el-button>
            <el-button
              v-if="row.payment_status === 1 && row.checkin_status === 0"
              type="primary"
              link
              @click="handleCheckin(row)"
            >
              签到
            </el-button>
            <el-button
              v-if="row.payment_status === 1"
              type="warning"
              link
              @click="handleRefund(row)"
            >
              退款
            </el-button>
            <el-button
              type="info"
              link
              @click="handleCheckCanSubmitWork(row)"
            >
              作品权限
            </el-button>
            <el-button type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button type="danger" link @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.page_size"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handlePageChange"
        />
      </div>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="formData"
        label-width="100px"
        :rules="{
          schedule_id: [{ required: true, message: '请选择排期', trigger: 'change' }],
          student_id: [{ required: true, message: '请选择学员', trigger: 'change' }],
          fee: [{ required: true, message: '请输入费用', trigger: 'blur' }]
        }"
      >
        <el-form-item label="排期" prop="schedule_id">
          <el-select
            v-model="formData.schedule_id"
            placeholder="请选择排期"
            style="width: 100%"
          >
            <el-option
              v-for="item in schedules"
              :key="item.id"
              :label="`${item.activity?.title || '活动'} - ${item.start_time}`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="学员" prop="student_id">
          <el-select
            v-model="formData.student_id"
            placeholder="请选择学员"
            style="width: 100%"
          >
            <el-option
              v-for="item in students"
              :key="item.id"
              :label="`${item.name} - ${item.phone}`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="费用(元)" prop="fee">
          <el-input-number
            v-model="formData.fee"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="支付方式" prop="payment_method">
          <el-select v-model="formData.payment_method" style="width: 100%">
            <el-option
              v-for="item in paymentMethodOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="formData.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入备注"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="payDialogVisible"
      title="确认支付"
      width="400px"
      destroy-on-close
    >
      <el-form label-width="100px">
        <el-form-item label="支付方式">
          <el-select v-model="payFormData.payment_method" style="width: 100%">
            <el-option
              v-for="item in paymentMethodOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="金额(元)">
          <el-input-number
            v-model="payFormData.amount"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="交易号">
          <el-input v-model="payFormData.transaction_id" placeholder="请输入交易号" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="payDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handlePaySubmit">确认支付</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="refundDialogVisible"
      title="确认退款"
      width="400px"
      destroy-on-close
    >
      <el-form label-width="100px">
        <el-form-item label="退款金额(元)">
          <el-input-number
            v-model="refundFormData.refund_amount"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="refundFormData.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入退款原因"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="refundDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleRefundSubmit">确认退款</el-button>
      </template>
    </el-dialog>
  </div>
</template>
