<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type UploadProps } from 'element-plus'
import {
  getWorkList,
  createWork,
  updateWork,
  deleteWork,
  reviewWork,
  setWorkPublic,
  setWorkPrivate,
  setWorkExcellent,
  cancelWorkExcellent,
  type Work,
  type WorkForm
} from '../api/work'
import { getRegistrationList, type Registration } from '../api/registration'
import { getAllActivities, type Activity } from '../api/activity'
import { getAllInheritors, type Inheritor } from '../api/inheritor'

const loading = ref(false)
const dialogVisible = ref(false)
const reviewDialogVisible = ref(false)
const previewVisible = ref(false)
const previewImage = ref('')
const dialogTitle = ref('新增作品')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)

const searchForm = reactive({
  keyword: '',
  activity_id: null as number | null,
  student_id: null as number | null,
  inheritor_id: null as number | null,
  status: null as number | null,
  is_excellent: null as number | null,
  is_public: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const workList = ref<Work[]>([])
const registrations = ref<Registration[]>([])
const activities = ref<Activity[]>([])
const inheritors = ref<Inheritor[]>([])

const formData = reactive<WorkForm>({
  title: '',
  description: '',
  registration_id: 0,
  images: []
})

const reviewFormData = reactive({
  status: 2,
  comment: '',
  score: 0,
  remark: ''
})

const statusOptions = [
  { label: '待审核', value: 1, type: 'warning' },
  { label: '已审核', value: 2, type: 'success' },
  { label: '已驳回', value: 3, type: 'danger' }
]

function getStatusTag(status: number) {
  const option = statusOptions.find(o => o.value === status)
  return option || { label: '未知', type: 'info' }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getWorkList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    workList.value = res.data
    pagination.total = res.total
  } finally {
    loading.value = false
  }
}

async function fetchOptions() {
  const [regs, acts, inherts] = await Promise.all([
    getRegistrationList({ page_size: 100, checkin_status: 1 }),
    getAllActivities(),
    getAllInheritors()
  ])
  registrations.value = regs.data
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
  searchForm.student_id = null
  searchForm.inheritor_id = null
  searchForm.status = null
  searchForm.is_excellent = null
  searchForm.is_public = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增作品'
  currentId.value = null
  Object.assign(formData, {
    title: '',
    description: '',
    registration_id: 0,
    images: []
  })
  dialogVisible.value = true
}

async function handleEdit(row: Work) {
  dialogTitle.value = '编辑作品'
  currentId.value = row.id
  Object.assign(formData, {
    title: row.title,
    description: row.description,
    registration_id: row.registration_id,
    images: [...row.images]
  })
  dialogVisible.value = true
}

async function handleDelete(row: Work) {
  try {
    await ElMessageBox.confirm('确定要删除该作品吗？', '提示', {
      type: 'warning'
    })
    await deleteWork(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

function handleReview(row: Work) {
  currentId.value = row.id
  reviewFormData.status = 2
  reviewFormData.comment = ''
  reviewFormData.score = row.score || 0
  reviewFormData.remark = ''
  reviewDialogVisible.value = true
}

async function handleReviewSubmit() {
  if (!currentId.value) return
  try {
    await reviewWork(currentId.value, reviewFormData)
    ElMessage.success('审核成功')
    reviewDialogVisible.value = false
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleSetPublic(row: Work) {
  try {
    await setWorkPublic(row.id)
    ElMessage.success('设置成功')
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleSetPrivate(row: Work) {
  try {
    await setWorkPrivate(row.id)
    ElMessage.success('设置成功')
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleSetExcellent(row: Work) {
  try {
    await setWorkExcellent(row.id)
    ElMessage.success('设置成功')
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

async function handleCancelExcellent(row: Work) {
  try {
    await cancelWorkExcellent(row.id)
    ElMessage.success('取消成功')
    fetchList()
  } catch (error) {
    console.error(error)
  }
}

function handlePreview(image: string) {
  previewImage.value = image
  previewVisible.value = true
}

function handleImageUpload(file: File) {
  const reader = new FileReader()
  reader.onload = (e) => {
    const result = e.target?.result as string
    if (result && formData.images.length < 9) {
      formData.images.push(result)
    }
  }
  reader.readAsDataURL(file)
  return false
}

function handleRemoveImage(index: number) {
  formData.images.splice(index, 1)
}

const beforeUpload: UploadProps['beforeUpload'] = (rawFile) => {
  if (!rawFile.type.startsWith('image/')) {
    ElMessage.error('请上传图片文件!')
    return false
  }
  if (rawFile.size / 1024 / 1024 > 5) {
    ElMessage.error('图片大小不能超过 5MB!')
    return false
  }
  return true
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (currentId.value) {
        await updateWork(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createWork(formData)
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
      <h1 class="page-title">作品档案</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增作品
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="作品名称"
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
        <el-form-item label="优秀作品">
          <el-select
            v-model="searchForm.is_excellent"
            placeholder="全部"
            clearable
            style="width: 120px"
          >
            <el-option label="是" :value="1" />
            <el-option label="否" :value="0" />
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
        :data="workList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="作品名称" min-width="150" />
        <el-table-column label="作品图片" width="120">
          <template #default="{ row }">
            <div class="images-preview">
              <div
                v-if="row.images && row.images.length > 0"
                class="image-item cursor-pointer"
                @click="handlePreview(row.images[0])"
              >
                <img :src="row.images[0]" alt="" />
                <div
                  v-if="row.images.length > 1"
                  class="absolute bottom-0 right-0 bg-black bg-opacity-60 text-white text-xs px-1 rounded"
                >
                  +{{ row.images.length - 1 }}
                </div>
              </div>
              <span v-else class="text-info">无图片</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="活动" width="180">
          <template #default="{ row }">
            <div v-if="row.registration?.schedule?.activity">
              {{ row.registration.schedule.activity.title }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="学员" width="120">
          <template #default="{ row }">
            <div v-if="row.registration?.student">
              {{ row.registration.student.name }}
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="score" label="评分" width="80" />
        <el-table-column label="优秀" width="80">
          <template #default="{ row }">
            <el-tag v-if="row.is_excellent" type="warning">优秀</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="公开" width="80">
          <template #default="{ row }">
            <el-tag v-if="row.is_public" type="success">公开</el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="360" fixed="right">
          <template #default="{ row }">
            <el-button
              v-if="row.status === 1"
              type="primary"
              link
              @click="handleReview(row)"
            >
              审核
            </el-button>
            <el-button
              v-if="row.status === 2 && !row.is_excellent"
              type="warning"
              link
              @click="handleSetExcellent(row)"
            >
              设为优秀
            </el-button>
            <el-button
              v-if="row.is_excellent"
              type="info"
              link
              @click="handleCancelExcellent(row)"
            >
              取消优秀
            </el-button>
            <el-button
              v-if="row.status === 2 && !row.is_public"
              type="success"
              link
              @click="handleSetPublic(row)"
            >
              公开
            </el-button>
            <el-button
              v-if="row.is_public"
              type="info"
              link
              @click="handleSetPrivate(row)"
            >
              取消公开
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
          title: [{ required: true, message: '请输入作品名称', trigger: 'blur' }],
          registration_id: [{ required: true, message: '请选择报名记录', trigger: 'change' }],
          images: [{ required: true, message: '请上传作品图片', trigger: 'change' }]
        }"
      >
        <el-form-item label="报名记录" prop="registration_id">
          <el-select
            v-model="formData.registration_id"
            placeholder="请选择报名记录"
            style="width: 100%"
          >
            <el-option
              v-for="item in registrations"
              :key="item.id"
              :label="`${item.student?.name || '学员'} - ${item.registration_no}`"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="作品名称" prop="title">
          <el-input v-model="formData.title" placeholder="请输入作品名称" />
        </el-form-item>
        <el-form-item label="作品描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="3"
            placeholder="请输入作品描述"
          />
        </el-form-item>
        <el-form-item label="作品图片" prop="images">
          <div class="images-preview mb-12">
            <div
              v-for="(img, index) in formData.images"
              :key="index"
              class="image-item"
            >
              <img :src="img" alt="" />
              <el-button
                class="delete-btn"
                type="danger"
                size="small"
                circle
                @click="handleRemoveImage(index)"
              >
                <el-icon><Close /></el-icon>
              </el-button>
            </div>
          </div>
          <el-upload
            v-if="formData.images.length < 9"
            :http-request="handleImageUpload"
            :before-upload="beforeUpload"
            :show-file-list="false"
            accept="image/*"
            multiple
          >
            <div class="image-uploader w-24 h-24 flex-center">
              <el-icon class="text-2xl text-gray-400"><Plus /></el-icon>
            </div>
          </el-upload>
          <div class="text-xs text-info mt-8">最多上传9张图片，单张不超过5MB</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="reviewDialogVisible"
      title="审核作品"
      width="500px"
      destroy-on-close
    >
      <el-form label-width="100px">
        <el-form-item label="审核结果">
          <el-radio-group v-model="reviewFormData.status">
            <el-radio :value="2">通过</el-radio>
            <el-radio :value="3">驳回</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="评分">
          <el-input-number
            v-model="reviewFormData.score"
            :min="0"
            :max="100"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="评语">
          <el-input
            v-model="reviewFormData.comment"
            type="textarea"
            :rows="3"
            placeholder="请输入评语"
          />
        </el-form-item>
        <el-form-item label="备注">
          <el-input
            v-model="reviewFormData.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入备注"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="reviewDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleReviewSubmit">确认审核</el-button>
      </template>
    </el-dialog>

    <el-image-viewer
      v-if="previewVisible"
      :url-list="[previewImage]"
      :initial-index="0"
      @close="previewVisible = false"
    />
  </div>
</template>
