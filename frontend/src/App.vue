<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const activeMenu = computed(() => route.path)

const menuItems = [
  {
    path: '/activities',
    title: '活动管理',
    icon: 'Calendar'
  },
  {
    path: '/schedules',
    title: '排期管理',
    icon: 'Clock'
  },
  {
    path: '/inheritors',
    title: '传承人管理',
    icon: 'User'
  },
  {
    path: '/students',
    title: '学员管理',
    icon: 'Avatar'
  },
  {
    path: '/registrations',
    title: '报名管理',
    icon: 'Tickets'
  },
  {
    path: '/material-packages',
    title: '材料包库存',
    icon: 'Box'
  },
  {
    path: '/works',
    title: '作品档案',
    icon: 'Picture'
  }
]

function handleMenuClick(path: string) {
  router.push(path)
}
</script>

<template>
  <div class="app-container">
    <el-container>
      <el-aside width="220px" class="sidebar">
        <div class="logo">
          <el-icon><Brush /></el-icon>
          <span>非遗工坊</span>
        </div>
        <el-menu
          :default-active="activeMenu"
          class="sidebar-menu"
          background-color="#001529"
          text-color="#fff"
          active-text-color="#ffd04b"
        >
          <el-menu-item
            v-for="item in menuItems"
            :key="item.path"
            :index="item.path"
            @click="handleMenuClick(item.path)"
          >
            <el-icon>
              <component :is="item.icon" />
            </el-icon>
            <span>{{ item.title }}</span>
          </el-menu-item>
        </el-menu>
      </el-aside>
      <el-container>
        <el-header class="header">
          <div class="header-title">非遗工坊活动管理系统</div>
          <div class="header-user">
            <el-avatar :size="32" style="background-color: #409eff">
              <el-icon><User /></el-icon>
            </el-avatar>
            <span class="username">管理员</span>
          </div>
        </el-header>
        <el-main class="main-content">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </el-main>
      </el-container>
    </el-container>
  </div>
</template>

<style scoped>
.app-container {
  height: 100vh;
}

.sidebar {
  background-color: #001529;
  overflow: hidden;
}

.logo {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #fff;
  font-size: 20px;
  font-weight: bold;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo .el-icon {
  font-size: 32px;
  color: #ffd04b;
}

.sidebar-menu {
  border-right: none;
}

.sidebar-menu .el-menu-item {
  height: 56px;
  line-height: 56px;
}

.sidebar-menu .el-menu-item:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

.header {
  background-color: #fff;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
}

.header-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 12px;
}

.username {
  color: #666;
  font-size: 14px;
}

.main-content {
  background-color: #f5f7fa;
  padding: 24px;
  overflow-y: auto;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
