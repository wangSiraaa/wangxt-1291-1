<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getInheritorList,
  createInheritor,
  updateInheritor,
  deleteInheritor,
  type Inheritor,
  type InheritorForm
} from '../api/inheritor'

const loading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('新增传承人')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)

const searchForm = reactive({
  keyword: '',
  craft_type: '',
  status: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const inheritorList = ref<Inheritor[]>([])

const formData = reactive<InheritorForm>({
  name: '',
  phone: '',
  id_card: '',
  craft_type: '',
  bio: '',
  avatar: '',
  status: true
})

const craftTypeOptions = [
  '剪纸', '皮影', '泥塑', '刺绣', '编织', '陶艺', '木雕', '年画', '风筝', '其他'
]

function getStatusTag(status: boolean) {
  return status
    ? { label: '启用', type: 'success' }
    : { label: '停用', type: 'danger' }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getInheritorList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    inheritorList.value = res.data
    pagination.total = res.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchList()
}

function handleReset() {
  searchForm.keyword = ''
  searchForm.craft_type = ''
  searchForm.status = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增传承人'
  currentId.value = null
  Object.assign(formData, {
    name: '',
    phone: '',
    id_card: '',
    craft_type: '',
    bio: '',
    avatar: '',
    status: true
  })
  dialogVisible.value = true
}

async function handleEdit(row: Inheritor) {
  dialogTitle.value = '编辑传承人'
  currentId.value = row.id
  Object.assign(formData, {
    name: row.name,
    phone: row.phone,
    id_card: row.id_card,
    craft_type: row.craft_type,
    bio: row.bio,
    avatar: row.avatar,
    status: row.status
  })
  dialogVisible.value = true
}

async function handleDelete(row: Inheritor) {
  try {
    await ElMessageBox.confirm('确定要删除该传承人吗？', '提示', {
      type: 'warning'
    })
    await deleteInheritor(row.id)
    ElMessage.success('删除成功')
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
        await updateInheritor(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createInheritor(formData)
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

onMounted(fetchList)
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">传承人管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增传承人
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="姓名/手机号"
            clearable
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="技艺类型">
          <el-select
            v-model="searchForm.craft_type"
            placeholder="全部"
            clearable
            style="width: 150px"
          >
            <el-option
              v-for="item in craftTypeOptions"
              :key="item"
              :label="item"
              :value="item"
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
            <el-option label="启用" :value="1" />
            <el-option label="停用" :value="0" />
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
        :data="inheritorList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="头像" width="80">
          <template #default="{ row }">
            <el-avatar :src="row.avatar" :size="40">
              {{ row.name.charAt(0) }}
            </el-avatar>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column prop="phone" label="手机号" width="140" />
        <el-table-column prop="id_card" label="身份证号" width="200" />
        <el-table-column prop="craft_type" label="技艺类型" width="120" />
        <el-table-column prop="bio" label="简介" min-width="200" show-overflow-tooltip />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
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
          name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
          phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
          id_card: [{ required: true, message: '请输入身份证号', trigger: 'blur' }],
          craft_type: [{ required: true, message: '请选择技艺类型', trigger: 'change' }]
        }"
      >
        <el-form-item label="姓名" prop="name">
          <el-input v-model="formData.name" placeholder="请输入姓名" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="formData.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="身份证号" prop="id_card">
          <el-input v-model="formData.id_card" placeholder="请输入身份证号" />
        </el-form-item>
        <el-form-item label="技艺类型" prop="craft_type">
          <el-select
            v-model="formData.craft_type"
            placeholder="请选择技艺类型"
            style="width: 100%"
          >
            <el-option
              v-for="item in craftTypeOptions"
              :key="item"
              :label="item"
              :value="item"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="头像" prop="avatar">
          <el-input v-model="formData.avatar" placeholder="请输入头像URL" />
        </el-form-item>
        <el-form-item label="简介" prop="bio">
          <el-input
            v-model="formData.bio"
            type="textarea"
            :rows="3"
            placeholder="请输入简介"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="formData.status" active-text="启用" inactive-text="停用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
