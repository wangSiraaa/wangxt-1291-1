import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/activities'
  },
  {
    path: '/activities',
    name: 'Activities',
    component: () => import('../views/ActivityList.vue')
  },
  {
    path: '/schedules',
    name: 'Schedules',
    component: () => import('../views/ScheduleList.vue')
  },
  {
    path: '/inheritors',
    name: 'Inheritors',
    component: () => import('../views/InheritorList.vue')
  },
  {
    path: '/students',
    name: 'Students',
    component: () => import('../views/StudentList.vue')
  },
  {
    path: '/registrations',
    name: 'Registrations',
    component: () => import('../views/RegistrationList.vue')
  },
  {
    path: '/material-packages',
    name: 'MaterialPackages',
    component: () => import('../views/MaterialPackageList.vue')
  },
  {
    path: '/works',
    name: 'Works',
    component: () => import('../views/WorkList.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
