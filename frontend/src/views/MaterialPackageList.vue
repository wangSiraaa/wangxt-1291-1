<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import {
  getMaterialPackageList,
  createMaterialPackage,
  updateMaterialPackage,
  deleteMaterialPackage,
  stockIn,
  stockOut,
  type MaterialPackage,
  type MaterialPackageForm
} from '../api/materialPackage'

const loading = ref(false)
const dialogVisible = ref(false)
const stockDialogVisible = ref(false)
const dialogTitle = ref('新增材料包')
const formRef = ref<FormInstance>()
const stockFormRef = ref<FormInstance>()
const currentId = ref<number | null>(null)
const stockType = ref<'in' | 'out'>('in')

const searchForm = reactive({
  keyword: '',
  status: null as number | null,
  low_stock: null as number | null
})

const pagination = reactive({
  page: 1,
  page_size: 10,
  total: 0
})

const materialPackageList = ref<MaterialPackage[]>([])

const formData = reactive<MaterialPackageForm>({
  name: '',
  sku_code: '',
  description: '',
  items: [],
  stock_quantity: 0,
  warning_quantity: 10,
  cost: 0,
  image: '',
  status: true
})

const stockFormData = reactive({
  quantity: 1,
  remark: '',
  operator: ''
})

function getStatusTag(status: boolean) {
  return status
    ? { label: '启用', type: 'success' }
    : { label: '停用', type: 'danger' }
}

function getStockClass(row: MaterialPackage) {
  if (row.stock_quantity <= 0) return 'text-danger'
  if (row.stock_quantity <= row.warning_quantity) return 'text-warning'
  return 'text-success'
}

async function fetchList() {
  loading.value = true
  try {
    const res = await getMaterialPackageList({
      ...searchForm,
      page: pagination.page,
      page_size: pagination.page_size
    })
    materialPackageList.value = res.data
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
  searchForm.low_stock = null
  handleSearch()
}

function handleAdd() {
  dialogTitle.value = '新增材料包'
  currentId.value = null
  Object.assign(formData, {
    name: '',
    sku_code: '',
    description: '',
    items: [],
    stock_quantity: 0,
    warning_quantity: 10,
    cost: 0,
    image: '',
    status: true
  })
  dialogVisible.value = true
}

async function handleEdit(row: MaterialPackage) {
  dialogTitle.value = '编辑材料包'
  currentId.value = row.id
  Object.assign(formData, {
    name: row.name,
    sku_code: row.sku_code,
    description: row.description,
    items: row.items || [],
    stock_quantity: row.stock_quantity,
    warning_quantity: row.warning_quantity,
    cost: row.cost,
    image: row.image,
    status: row.status
  })
  dialogVisible.value = true
}

async function handleDelete(row: MaterialPackage) {
  try {
    await ElMessageBox.confirm('确定要删除该材料包吗？', '提示', {
      type: 'warning'
    })
    await deleteMaterialPackage(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch {
    // 用户取消
  }
}

function handleStockIn(row: MaterialPackage) {
  currentId.value = row.id
  stockType.value = 'in'
  stockFormData.quantity = 1
  stockFormData.remark = ''
  stockFormData.operator = ''
  stockDialogVisible.value = true
}

function handleStockOut(row: MaterialPackage) {
  currentId.value = row.id
  stockType.value = 'out'
  stockFormData.quantity = 1
  stockFormData.remark = ''
  stockFormData.operator = ''
  stockDialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (currentId.value) {
        await updateMaterialPackage(currentId.value, formData)
        ElMessage.success('更新成功')
      } else {
        await createMaterialPackage(formData)
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      fetchList()
    } catch (error) {
      console.error(error)
    }
  })
}

async function handleStockSubmit() {
  if (!stockFormRef.value || !currentId.value) return
  await stockFormRef.value.validate(async (valid) => {
    if (!valid) return
    try {
      if (stockType.value === 'in') {
        await stockIn(currentId.value, stockFormData)
        ElMessage.success('入库成功')
      } else {
        await stockOut(currentId.value, stockFormData)
        ElMessage.success('出库成功')
      }
      stockDialogVisible.value = false
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
      <h1 class="page-title">材料包库存</h1>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增材料包
      </el-button>
    </div>

    <div class="search-bar">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="名称/SKU编码"
            clearable
            style="width: 200px"
          />
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
        <el-form-item label="库存预警">
          <el-select
            v-model="searchForm.low_stock"
            placeholder="全部"
            clearable
            style="width: 150px"
          >
            <el-option label="仅看预警" :value="1" />
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
        :data="materialPackageList"
        border
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="图片" width="80">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="row.image"
              :preview-src-list="[row.image]"
              :width="40"
              :height="40"
              fit="cover"
            />
            <el-avatar v-else :size="40" icon="Box" />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="材料包名称" min-width="180" />
        <el-table-column prop="sku_code" label="SKU编码" width="140" />
        <el-table-column label="库存数量" width="120">
          <template #default="{ row }">
            <span :class="getStockClass(row)">
              {{ row.stock_quantity }}
            </span>
            <span v-if="row.stock_quantity <= row.warning_quantity && row.stock_quantity > 0" class="text-warning">
              (预警)
            </span>
            <span v-if="row.stock_quantity <= 0" class="text-danger">
              (缺货)
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="warning_quantity" label="预警数量" width="100" />
        <el-table-column prop="cost" label="成本(元)" width="100" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusTag(row.status).type">
              {{ getStatusTag(row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button type="success" link @click="handleStockIn(row)">
              入库
            </el-button>
            <el-button
              type="warning"
              link
              :disabled="row.stock_quantity <= 0"
              @click="handleStockOut(row)"
            >
              出库
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
          name: [{ required: true, message: '请输入材料包名称', trigger: 'blur' }],
          sku_code: [{ required: true, message: '请输入SKU编码', trigger: 'blur' }],
          warning_quantity: [{ required: true, message: '请输入预警数量', trigger: 'blur' }],
          cost: [{ required: true, message: '请输入成本', trigger: 'blur' }]
        }"
      >
        <el-form-item label="材料包名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入材料包名称" />
        </el-form-item>
        <el-form-item label="SKU编码" prop="sku_code">
          <el-input v-model="formData.sku_code" placeholder="请输入SKU编码" />
        </el-form-item>
        <el-form-item label="当前库存" prop="stock_quantity">
          <el-input-number
            v-model="formData.stock_quantity"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="预警数量" prop="warning_quantity">
          <el-input-number
            v-model="formData.warning_quantity"
            :min="0"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="成本(元)" prop="cost">
          <el-input-number
            v-model="formData.cost"
            :min="0"
            :precision="2"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="图片" prop="image">
          <el-input v-model="formData.image" placeholder="请输入图片URL" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="2"
            placeholder="请输入描述"
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

    <el-dialog
      v-model="stockDialogVisible"
      :title="stockType === 'in' ? '材料包入库' : '材料包出库'"
      width="400px"
      destroy-on-close
    >
      <el-form
        ref="stockFormRef"
        :model="stockFormData"
        label-width="80px"
        :rules="{
          quantity: [{ required: true, message: '请输入数量', trigger: 'blur' }]
        }"
      >
        <el-form-item label="数量" prop="quantity">
          <el-input-number
            v-model="stockFormData.quantity"
            :min="1"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="操作人" prop="operator">
          <el-input v-model="stockFormData.operator" placeholder="请输入操作人" />
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="stockFormData.remark"
            type="textarea"
            :rows="2"
            placeholder="请输入备注"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="stockDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleStockSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>
