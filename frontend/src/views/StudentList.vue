<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getStudentList,
  createStudent,
  updateStudent,
  deleteStudent,
  type Student,
  type StudentForm
} from '../api/student'

const loading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('新增学员')
const formRef = ref<FormInstance>()
const currentId = ref<number | null>(null)

const searchForm = reactive({
  keyword: '',
  status: null as number | null,
  gender: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const studentList = ref<Student[]>([])

const formData = reactive<StudentForm>({
  name: '',
  phone: '',
  id_card: '',
  birthday: '',
  gender: 1,
  address: '',
  emergency_contact: '',
  emergency_phone: '',
  remark: '',
  status: true
})

const genderOptions = [
  { label: '男', value: 1 },
  { label: '女', value: 2 }
]

function getGenderText(gender: number) {
  return gender === 1 ? '男' : '女'
}

function getStatusTag(status: boolean) {
  return status
    ? { label: '正常', type: 'success' }
    : { label: '停用', type: 'danger' }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getStudentList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    studentList.value = res.data
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
  searchForm.status = null
  searchForm.gender = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增学员'
  currentId.value = null
  Object.assign(formData, {
    name: '',
    phone: '',
    id_card: '',
    birthday: '',
    gender: 1,
    address: '',
    emergency_contact: '',
    emergency_phone: '',
    remark: '',
    status: true
  })
  dialogVisible.value = true
}

async function handleEdit(row: Student) {
  dialogTitle.value = '编辑学员'
  currentId.value = row.id
  Object.assign(formData, {
    name: row.name,
    phone: row.phone,
    id_card: row.id_card,
    birthday: row.birthday,
    gender: row.gender,
    address: row.address,
    emergency_contact: row.emergency_contact,
    emergency_phone: row.emergency_phone,
    remark: row.remark,
    status: row.status
  })
  dialogVisible.value = true
}

async function handleDelete(row: Student) {
  try {
    await ElMessageBox.confirm('确定要删除该学员吗？', '提示', {
      type: 'warning'
    })
    await deleteStudent(row.id)
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
        await updateStudent(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createStudent(formData)
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
      <h1 class="page-title">学员管理</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增学员
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
        <el-form-item label="性别">
          <el-select
            v-model="searchForm.gender"
            placeholder="全部"
            clearable
            style="width: 120px"
          >
            <el-option
              v-for="item in genderOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
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
            <el-option label="正常" :value="1" />
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
        :data="studentList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column label="性别" width="80">
          <template #default="{ row }">{{ getGenderText(row.gender) }}</template>
        </el-table-column>
        <el-table-column prop="phone" label="手机号" width="140" />
        <el-table-column prop="birthday" label="生日" width="120" />
        <el-table-column prop="id_card" label="身份证号" width="200" />
        <el-table-column prop="address" label="地址" min-width="180" show-overflow-tooltip />
        <el-table-column prop="emergency_contact" label="紧急联系人" width="120" />
        <el-table-column prop="emergency_phone" label="紧急联系电话" width="140" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
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
      width="600px"
      destroy-on-close
    >
      <el-form
        ref="formRef"
        :model="formData"
        label-width="120px"
        :rules="{
          name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
          phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
          id_card: [{ required: true, message: '请输入身份证号', trigger: 'blur' }],
          gender: [{ required: true, message: '请选择性别', trigger: 'change' }]
        }"
      >
        <el-form-item label="姓名" prop="name">
          <el-input v-model="formData.name" placeholder="请输入姓名" />
        </el-form-item>
        <el-form-item label="性别" prop="gender">
          <el-radio-group v-model="formData.gender">
            <el-radio :value="1">男</el-radio>
            <el-radio :value="2">女</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="formData.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="身份证号" prop="id_card">
          <el-input v-model="formData.id_card" placeholder="请输入身份证号" />
        </el-form-item>
        <el-form-item label="生日" prop="birthday">
          <el-date-picker
            v-model="formData.birthday"
            type="date"
            placeholder="选择生日"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="地址" prop="address">
          <el-input v-model="formData.address" placeholder="请输入地址" />
        </el-form-item>
        <el-form-item label="紧急联系人" prop="emergency_contact">
          <el-input v-model="formData.emergency_contact" placeholder="请输入紧急联系人" />
        </el-form-item>
        <el-form-item label="紧急联系电话" prop="emergency_phone">
          <el-input v-model="formData.emergency_phone" placeholder="请输入紧急联系电话" />
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="formData.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入备注"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="formData.status" active-text="正常" inactive-text="停用" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
