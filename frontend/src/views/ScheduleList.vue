<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getScheduleList,
  createSchedule,
  updateSchedule,
  deleteSchedule,
  confirmSchedule,
  openRegistration,
  closeRegistration,
  startSchedule,
  endSchedule,
  cancelSchedule,
  type Schedule,
  type ScheduleForm
} from '../api/schedule'
import { getAllActivities, type Activity } from '../api/activity'
import { getAllInheritors, type Inheritor } from '../api/inheritor'

const loading = ref(false)
const dialogVisible = ref(false)
const confirmDialogVisible = ref(false)
const dialogTitle = ref('新增排期')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)
const confirmData = reactive({ confirmed: 1, remark: '' })

const searchForm = reactive({
  keyword: '',
  activity_id: null as number | null,
  inheritor_id: null as number | null,
  status: null as number | null,
  inheritor_confirmed: null as number | null,
  start_date: '',
  end_date: ''
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const scheduleList = ref<Schedule[]>([])
const activities = ref<Activity[]>([])
const inheritors = ref<Inheritor[]>([])

const formData = reactive<ScheduleForm>({
  activity_id: 0,
  inheritor_id: 0,
  start_time: '',
  end_time: '',
  max_students: 20,
  notice: ''
})

const statusOptions = [
  { label: '待发布', value: 0, type: 'info' },
  { label: '可报名', value: 1, type: 'primary' },
  { label: '报名结束', value: 2, type: 'warning' },
  { label: '进行中', value: 3, type: 'success' },
  { label: '已结束', value: 4, type: 'info' },
  { label: '已取消', value: 5, type: 'danger' }
]

const confirmOptions = [
  { label: '待确认', value: 0, type: 'warning' },
  { label: '已确认', value: 1, type: 'success' },
  { label: '已拒绝', value: 2, type: 'danger' }
]

function getStatusTag(status: number) {
  const option = statusOptions.find(o => o.value === status)
  return option || { label: '未知', type: 'info' }
}

function getConfirmTag(confirmed: number) {
  const option = confirmOptions.find(o => o.value === confirmed)
  return option || { label: '未知', type: 'info' }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getScheduleList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    scheduleList.value = res.data
    pagination.total = res.total
  } finally {
    loading.value = false
  }
}

async function fetchOptions() {
  const [acts, inherts] = await Promise.all([
    getAllActivities(),
    getAllInheritors()
  ])
  activities.value = acts
  inheritors.value = inherts
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function handleReset() {
  searchForm.keyword = ''
  searchForm.activity_id = null
  searchForm.inheritor_id = null
  searchForm.status = null
  searchForm.inheritor_confirmed = null
  searchForm.start_date = ''
  searchForm.end_date = ''
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增排期'
  currentId.value = null
  Object.assign(formData, {
    activity_id: 0,
    inheritor_id: 0,
    start_time: '',
    end_time: '',
    max_students: 20,
    notice: ''
  })
  dialogVisible.value = true
}

async function handleEdit(row: Schedule) {
  dialogTitle.value = '编辑排期'
  currentId.value = row.id
  Object.assign(formData, {
    activity_id: row.activity_id,
    inheritor_id: row.inheritor_id,
    start_time: row.start_time,
    end_time: row.end_time,
    max_students: row.max_students,
    notice: row.notice
  })
  dialogVisible.value = true
}

async function handleDelete(row: Schedule) {
  try {
    await ElMessageBox.confirm('确定要删除该排期吗？', '提示', {
      type: 'warning'
    })
    await deleteSchedule(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

function handleConfirm(row: Schedule) {
  currentId.value = row.id
  confirmData.confirmed = 1
  confirmData.remark = ''
  confirmDialogVisible.value = true
}

async function handleConfirmSubmit() {
  if (!currentId.value) return
  try {
    await confirmSchedule(currentId.value, confirmData)
    ElMessage.success('操作成功')
    confirmDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleOpenRegistration(row: Schedule) {
  try {
    await ElMessageBox.confirm(
      '确定要开放报名吗？请确保材料包库存充足。',
      '提示',
      { type: 'warning' }
    )
    await openRegistration(row.id)
    ElMessage.success('开放报名成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleCloseRegistration(row: Schedule) {
  try {
    await ElMessageBox.confirm('确定要关闭报名吗？', '提示', {
      type: 'warning'
    })
    await closeRegistration(row.id)
    ElMessage.success('关闭报名成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleStart(row: Schedule) {
  try {
    await ElMessageBox.confirm('确定要开始活动吗？', '提示', {
      type: 'warning'
    })
    await startSchedule(row.id)
    ElMessage.success('活动已开始')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleEnd(row: Schedule) {
  try {
    await ElMessageBox.confirm('确定要结束活动吗？', '提示', {
      type: 'warning'
    })
    await endSchedule(row.id)
    ElMessage.success('活动已结束')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleCancel(row: Schedule) {
  try {
    await ElMessageBox.confirm('确定要取消该排期吗？', '提示', {
      type: 'warning'
    })
    await cancelSchedule(row.id)
    ElMessage.success('取消成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (currentId.value) {
        await updateSchedule(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createSchedule(formData)
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
      <h1 class="page-title">排期管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增排期
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="搜索活动名称"
            clearable
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="活动">
          <el-select
            v-model="searchForm.activity_id"
            placeholder="全部"
            clearable
            style="width: 180px"
          >
            <el-option
              v-for="item in activities"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="传承人">
          <el-select
            v-model="searchForm.inheritor_id"
            placeholder="全部"
            clearable
            style="width: 150px"
          >
            <el-option
              v-for="item in inheritors"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="全部"
            clearable
            style="width: 120px"
          >
            <el-option
              v-for="item in statusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="日期">
          <el-date-picker
            v-model="searchForm.start_date"
            type="date"
            placeholder="开始日期"
            value-format="YYYY-MM-DD"
            style="width: 130px"
          />
          <span style="margin: 0 8px">-</span>
          <el-date-picker
            v-model="searchForm.end_date"
            type="date"
            placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 130px"
          />
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
        :data="scheduleList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="活动" min-width="180">
          <template #default="{ row }">
            <div v-if="row.activity">
              <div class="font-medium">{{ row.activity.title }}</div>
              <div class="text-info text-xs">费用: ¥{{ row.activity.fee }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="传承人" width="150">
          <template #default="{ row }">
            <div v-if="row.inheritor">
              <div>{{ row.inheritor.name }}</div>
              <div class="text-info text-xs">{{ row.inheritor.phone }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="开始时间" width="180">
          <template #default="{ row }">{{ row.start_time }}</template>
        </el-table-column>
        <el-table-column label="结束时间" width="180">
          <template #default="{ row }">{{ row.end_time }}</template>
        </el-table-column>
        <el-table-column label="人数" width="120">
          <template #default="{ row }">
            <span class="text-primary">{{ row.registered_count }}</span>
            /{{ row.max_students }}
          </template>
        </el-table-column>
        <el-table-column label="确认状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getConfirmTag(row.inheritor_confirmed).type">
              {{ getConfirmTag(row.inheritor_confirmed).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="360" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.inheritor_confirmed === 0"
              type="success"
              link
              @click="handleConfirm(row)"
            >
              确认
            </el-button>
            <el-button
              v-if="row.status === 0 && row.inheritor_confirmed === 1"
              type="primary"
              link
              @click="handleOpenRegistration(row)"
            >
              开放报名
            </el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              link
              @click="handleCloseRegistration(row)"
            >
              关闭报名
            </el-button>
            <el-button
              v-if="row.status === 2"
              type="success"
              link
              @click="handleStart(row)"
            >
              开始
            </el-button>
            <el-button
              v-if="row.status === 3"
              type="warning"
              link
              @click="handleEnd(row)"
            >
              结束
            </el-button>
            <el-button
              v-if="row.status < 4"
              type="danger"
              link
              @click="handleCancel(row)"
            >
              取消
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
      width="600px"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="formData"
        label-width="100px"
        :rules="{
          activity_id: [{ required: true, message: '请选择活动', trigger: 'change' }],
          inheritor_id: [{ required: true, message: '请选择传承人', trigger: 'change' }],
          start_time: [{ required: true, message: '请选择开始时间', trigger: 'change' }],
          end_time: [{ required: true, message: '请选择结束时间', trigger: 'change' }],
          max_students: [{ required: true, message: '请输入最大人数', trigger: 'blur' }]
        }"
      >
        <el-form-item label="活动" prop="activity_id">
          <el-select
            v-model="formData.activity_id"
            placeholder="请选择活动"
            style="width: 100%"
          >
            <el-option
              v-for="item in activities"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="传承人" prop="inheritor_id">
          <el-select
            v-model="formData.inheritor_id"
            placeholder="请选择传承人"
            style="width: 100%"
          >
            <el-option
              v-for="item in inheritors"
              :key="item.id"
              :label="item.name"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="开始时间" prop="start_time">
          <el-date-picker
            v-model="formData.start_time"
            type="datetime"
            placeholder="选择开始时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束时间" prop="end_time">
          <el-date-picker
            v-model="formData.end_time"
            type="datetime"
            placeholder="选择结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="最大人数" prop="max_students">
          <el-input-number
            v-model="formData.max_students"
            :min="1"
            :max="100"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="注意事项" prop="notice">
          <el-input
            v-model="formData.notice"
            type="textarea"
            :rows="2"
            placeholder="请输入注意事项"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="confirmDialogVisible"
      title="传承人确认"
      width="400px"
      destroy-on-close
    >
      <el-form label-width="80px">
        <el-form-item label="确认">
          <el-radio-group v-model="confirmData.confirmed">
            <el-radio :value="1">确认</el-radio>
            <el-radio :value="2">拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="confirmData.remark"
            type="textarea"
            :rows="3"
            placeholder="请输入备注"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="confirmDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleConfirmSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
