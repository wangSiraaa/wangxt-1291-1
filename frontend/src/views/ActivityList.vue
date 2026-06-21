<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getActivityList,
  createActivity,
  updateActivity,
  deleteActivity,
  publishActivity,
  cancelActivity,
  type Activity,
  type ActivityForm
} from '../api/activity'
import {
  getAllMaterialPackages,
  type MaterialPackage
} from '../api/materialPackage'

const loading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('新增活动')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)

const searchForm = reactive({
  keyword: '',
  status: null as number | null,
  material_package_id: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const activityList = ref<Activity[]>([])
const materialPackages = ref<MaterialPackage[]>([])

const formData = reactive<ActivityForm>({
  title: '',
  description: '',
  material_package_id: null,
  max_students: 20,
  fee: 0,
  duration_minutes: 120,
  location: '',
  cover_image: '',
  requirements: ''
})

const statusOptions = [
  { label: '草稿', value: 0, type: 'info' },
  { label: '已发布', value: 1, type: 'success' },
  { label: '已结束', value: 2, type: 'warning' },
  { label: '已取消', value: 3, type: 'danger' }
]

function getStatusTag(status: number) {
  const option = statusOptions.find(o => o.value === status)
  return option || { label: '未知', type: 'info' }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getActivityList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    activityList.value = res.data
    pagination.total = res.total
  } finally {
    loading.value = false
  }
}

async function fetchMaterialPackages() {
  materialPackages.value = await getAllMaterialPackages()
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function handleReset() {
  searchForm.keyword = ''
  searchForm.status = null
  searchForm.material_package_id = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增活动'
  currentId.value = null
  Object.assign(formData, {
    title: '',
    description: '',
    material_package_id: null,
    max_students: 20,
    fee: 0,
    duration_minutes: 120,
    location: '',
    cover_image: '',
    requirements: ''
  })
  dialogVisible.value = true
}

async function handleEdit(row: Activity) {
  dialogTitle.value = '编辑活动'
  currentId.value = row.id
  Object.assign(formData, {
    title: row.title,
    description: row.description,
    material_package_id: row.material_package_id,
    max_students: row.max_students,
    fee: row.fee,
    duration_minutes: row.duration_minutes,
    location: row.location,
    cover_image: row.cover_image,
    requirements: row.requirements
  })
  dialogVisible.value = true
}

async function handleDelete(row: Activity) {
  try {
    await ElMessageBox.confirm('确定要删除该活动吗？', '提示', {
      type: 'warning'
    })
    await deleteActivity(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handlePublish(row: Activity) {
  try {
    await ElMessageBox.confirm(
      '确定要发布该活动吗？发布前请确保材料包库存充足。',
      '提示',
      { type: 'warning' }
    )
    await publishActivity(row.id)
    ElMessage.success('发布成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

async function handleCancel(row: Activity) {
  try {
    await ElMessageBox.confirm('确定要取消该活动吗？', '提示', {
      type: 'warning'
    })
    await cancelActivity(row.id)
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
        await updateActivity(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createActivity(formData)
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
  fetchMaterialPackages()
})
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">活动管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增活动
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="请输入活动名称"
            clearable
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="全部"
            clearable
            style="width: 150px"
          >
            <el-option
              v-for="item in statusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="材料包">
          <el-select
            v-model="searchForm.material_package_id"
            placeholder="全部"
            clearable
            style="width: 200px"
          >
            <el-option
              v-for="item in materialPackages"
              :key="item.id"
              :label="item.name"
              :value="item.id"
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
        :data="activityList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="活动名称" min-width="180" />
        <el-table-column label="材料包" min-width="150">
          <template #default="{ row }">
            <span v-if="row.material_package">
              {{ row.material_package.name }}
              <span
                v-if="row.material_package.stock_quantity <= 0"
                class="text-danger"
              >
                (库存不足)
              </span>
            </span>
            <span v-else class="text-info">未关联</span>
          </template>
        </el-table-column>
        <el-table-column prop="max_students" label="最大人数" width="100" />
        <el-table-column prop="fee" label="费用(元)" width="100" />
        <el-table-column prop="duration_minutes" label="时长(分钟)" width="120" />
        <el-table-column prop="location" label="地点" width="150" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button
              v-if="row.status === 0"
              type="success"
              link
              @click="handlePublish(row)"
            >
              发布
            </el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              link
              @click="handleCancel(row)"
            >
              取消
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
          title: [{ required: true, message: '请输入活动名称', trigger: 'blur' }],
          max_students: [{ required: true, message: '请输入最大人数', trigger: 'blur' }],
          fee: [{ required: true, message: '请输入费用', trigger: 'blur' }],
          duration_minutes: [{ required: true, message: '请输入时长', trigger: 'blur' }],
          location: [{ required: true, message: '请输入地点', trigger: 'blur' }]
        }"
      >
        <el-form-item label="活动名称" prop="title">
          <el-input v-model="formData.title" placeholder="请输入活动名称" />
        </el-form-item>
        <el-form-item label="材料包" prop="material_package_id">
          <el-select
            v-model="formData.material_package_id"
            placeholder="请选择材料包"
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="item in materialPackages"
              :key="item.id"
              :label="`${item.name} (库存: ${item.stock_quantity})`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="最大人数" prop="max_students">
          <el-input-number
            v-model="formData.max_students"
            :min="1"
            :max="100"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="费用(元)" prop="fee">
          <el-input-number
            v-model="formData.fee"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="时长(分钟)" prop="duration_minutes">
          <el-input-number
            v-model="formData.duration_minutes"
            :min="30"
            :step="30"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="地点" prop="location">
          <el-input v-model="formData.location" placeholder="请输入活动地点" />
        </el-form-item>
        <el-form-item label="封面图" prop="cover_image">
          <el-input v-model="formData.cover_image" placeholder="请输入封面图URL" />
        </el-form-item>
        <el-form-item label="活动描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="3"
            placeholder="请输入活动描述"
          />
        </el-form-item>
        <el-form-item label="参与要求" prop="requirements">
          <el-input
            v-model="formData.requirements"
            type="textarea"
            :rows="2"
            placeholder="请输入参与要求"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
